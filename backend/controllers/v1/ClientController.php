<?php

declare(strict_types=1);

namespace app\controllers\v1;

use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use yii\rest\ActiveController;

class ClientController extends ActiveController
{
    public $modelClass = 'app\models\Client';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        /*
         * Bearer token authentication.
         */
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        /*
         * Role-based authorization.
         */
        $behaviors['access'] = [
            'class' => AccessControl::class,

            'rules' => [

                // GET: admin and agent
                [
                    'allow' => true,
                    'actions' => ['index', 'view'],
                    'matchCallback' => function () {
                        return \Yii::$app->user->identity !== null
                            && in_array(
                                \Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                // POST: admin and agent
                [
                    'allow' => true,
                    'actions' => ['create'],
                    'matchCallback' => function () {
                        return \Yii::$app->user->identity !== null
                            && in_array(
                                \Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                // PATCH/PUT: admin and agent
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'matchCallback' => function () {
                        return \Yii::$app->user->identity !== null
                            && in_array(
                                \Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                // DELETE: admin only
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'matchCallback' => function () {
                        return \Yii::$app->user->identity !== null
                            && \Yii::$app->user->identity->role === 'admin';
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}