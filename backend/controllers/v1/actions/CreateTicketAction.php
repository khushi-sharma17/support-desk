<?php

namespace app\controllers\v1\actions;

use Yii;
use yii\rest\CreateAction;
use app\services\AiService;

class CreateTicketAction extends CreateAction
{
    public function run()
    {
        $model = new $this->modelClass;

        $bodyParams = Yii::$app->request->bodyParams;

        /*
         * Check what the client actually sent BEFORE validation.
         *
         * This is important because Ticket.php gives priority
         * a default value of "medium" during validation.
         */
        $categoryProvided =
            isset($bodyParams['category']) &&
            $bodyParams['category'] !== '';

        $priorityProvided =
            isset($bodyParams['priority']) &&
            $bodyParams['priority'] !== '';

        $model->load($bodyParams, '');

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;

            return $model->getErrors();
        }

        try {
            $aiService = new AiService();

            $aiResult = $aiService->analyzeTicket(
                $model->subject,
                $model->description
            );

            /*
             * Always save the AI-generated summary.
             */
            $model->ai_summary = $aiResult['summary'];

            /*
             * AI chooses category only when the client
             * did not explicitly provide one.
             */
            if (!$categoryProvided) {
                $model->category = $aiResult['category'];
            }

            /*
             * AI chooses priority only when the client
             * did not explicitly provide one.
             */
            if (!$priorityProvided) {
                $model->priority = $aiResult['priority'];
            }

        } catch (\Throwable $e) {

            Yii::error(
                'AI ticket analysis failed: ' . $e->getMessage(),
                'ai'
            );

            /*
             * Ticket creation should still work if AI fails.
             */
            $model->ai_summary = null;
        }

        if (!$model->save(false)) {
            Yii::$app->response->statusCode = 500;

            return [
                'error' => 'Unable to save ticket.'
            ];
        }

        Yii::$app->response->statusCode = 201;

        return $model;
    }
}