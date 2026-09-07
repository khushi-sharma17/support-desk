<?php

declare(strict_types=1);

namespace app\controllers\v1\actions;

use Yii;
use yii\base\Action;
use app\models\Ticket;
use app\models\User;

class AssignTicketAction extends Action
{
    public function run($id)
    {
        // Make sure the current user is authenticated.
        $user = Yii::$app->user->identity;

        if (!$user) {
            Yii::$app->response->statusCode = 401;

            return [
                'error' => 'Authentication required.'
            ];
        }

        // Only admins can assign tickets.
        if ($user->role !== 'admin') {
            Yii::$app->response->statusCode = 403;

            return [
                'error' => 'Only administrators can assign tickets.'
            ];
        }

        // Find the ticket.
        $ticket = Ticket::findOne($id);

        if (!$ticket) {
            Yii::$app->response->statusCode = 404;

            return [
                'error' => 'Ticket not found.'
            ];
        }

        // Get JSON request body.
        $bodyParams = Yii::$app->request->bodyParams;

        // assigned_to is required.
        if (!array_key_exists('assigned_to', $bodyParams)) {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Assigned To is required.'
                ]
            ];
        }

        $assignedTo = $bodyParams['assigned_to'];

        // Allow null to unassign a ticket.
        if ($assignedTo === null || $assignedTo === '') {
            $ticket->assigned_to = null;

            if (!$ticket->save(false)) {
                Yii::$app->response->statusCode = 500;

                return [
                    'error' => 'Unable to update ticket.'
                ];
            }

            return $ticket;
        }

        // Make sure assigned user exists.
        $agent = User::findOne($assignedTo);

        if (!$agent) {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Assigned user does not exist.'
                ]
            ];
        }

        // Only users with the agent role can receive tickets.
        if ($agent->role !== 'agent') {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Tickets can only be assigned to users with the agent role.'
                ]
            ];
        }

        // Assign ticket.
        $ticket->assigned_to = (int) $assignedTo;

        if (!$ticket->save(false)) {
            Yii::$app->response->statusCode = 500;

            return [
                'error' => 'Unable to assign ticket.'
            ];
        }

        return $ticket;
    }
}