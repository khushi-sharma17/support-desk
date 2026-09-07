<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Ticket;
use app\models\TicketActivity;
use yii\console\Controller;

class SlaEscalationController extends Controller
{
    public function actionIndex(): int
    {
        $tickets = Ticket::find()
            ->where(['!=', 'status', 'resolved'])
            ->andWhere(['not', ['due_at' => null]])
            ->orderBy([
                'due_at' => SORT_ASC,
            ])
            ->all();

        $overdue = 0;
        $atRisk = 0;

        foreach ($tickets as $ticket) {
            $status = $ticket->getEscalationStatus();

            if ($status === 'escalated') {
                $overdue++;

                $alreadyEscalated = TicketActivity::find()
                    ->where([
                        'ticket_id' => $ticket->id,
                        'action' => 'sla_escalated',
                    ])
                    ->exists();

                if (!$alreadyEscalated) {
                    $activity = new TicketActivity();
                    $activity->ticket_id = $ticket->id;
                    $activity->user_id = $ticket->assigned_to ?? 1;
                    $activity->action = 'sla_escalated';
                    $activity->old_value = 'within_sla';
                    $activity->new_value = 'escalated';
                    $activity->save(false);
                }
            } elseif ($status === 'at_risk') {
                $atRisk++;
            }
        }

        $this->stdout(
            "SLA escalation check completed.\n"
            . "Overdue: {$overdue}\n"
            . "At risk: {$atRisk}\n"
        );

        return 0;
    }
}