<?php

declare(strict_types=1);

namespace app\controllers\v1;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use app\models\Ticket;
use app\models\User;

class DashboardController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173'],
                'Access-Control-Request-Method' => [
                    'GET',
                    'OPTIONS',
                ],
                'Access-Control-Request-Headers' => [
                    'Authorization',
                    'Content-Type',
                    'Accept',
                ],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['index'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],
                [
                    'allow' => true,
                    'actions' => ['options'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex()
    {
        $user = Yii::$app->user->identity;

        $query = Ticket::find();

        /*
         * Agents only see statistics for their own tickets.
         */
        if ($user->role === 'agent') {
            $query->andWhere([
                'assigned_to' => $user->id,
            ]);
        }

        $tickets = $query->all();

        $total = count($tickets);

        $open = 0;
        $pending = 0;
        $resolved = 0;

        $low = 0;
        $medium = 0;
        $high = 0;
        $critical = 0;

        $slaBreached = 0;
        $slaAtRisk = 0;
        $agentWorkload = [];
        $categoryCounts = [];
        $totalResolutionSeconds = 0;
        $resolvedTicketCount = 0;

        $myAssigned = 0;
        $myOpenAssigned = 0;
        $myOverdueAssigned = 0;
        $myCriticalAssigned = 0;
        $myRecentlyUpdated = 0;

        foreach ($tickets as $ticket) {


            // Current agent's personal metrics
            if ($user->role === 'agent' && $ticket->assigned_to === $user->id) {
                $myAssigned++;

                if ($ticket->status === 'open') {
                    $myOpenAssigned++;
                }

                if ($ticket->getIsSlaBreached()) {
                    $myOverdueAssigned++;
                }

                if ($ticket->priority === 'critical') {
                    $myCriticalAssigned++;
                }

                if (
                    !empty($ticket->updated_at)
                    && strtotime($ticket->updated_at) >= strtotime('-24 hours')
                ) {
                    $myRecentlyUpdated++;
                }
            }


            if ($ticket->assigned_to) {
                if (!isset($agentWorkload[$ticket->assigned_to])) {
                    $agentWorkload[$ticket->assigned_to] = [
                        'agent_id' => $ticket->assigned_to,
                        'agent_name' => 'Unknown Agent',
                        'ticket_count' => 0,
                    ];
                }

                $agentWorkload[$ticket->assigned_to]['ticket_count']++;

                $agent = User::findOne($ticket->assigned_to);

                if ($agent) {
                    $agentWorkload[$ticket->assigned_to]['agent_name'] =
                        $agent->email;
                }
            }


            // Category counts
            $category = $ticket->category ?: 'Uncategorized';

            if (!isset($categoryCounts[$category])) {
                $categoryCounts[$category] = 0;
            }

            $categoryCounts[$category]++;


            // Status counts
            if ($ticket->status === 'open') {
                $open++;
            } elseif ($ticket->status === 'pending') {
                $pending++;
            } elseif ($ticket->status === 'resolved') {
                $resolved++;
            }

            // Priority counts
            if ($ticket->priority === 'low') {
                $low++;
            } elseif ($ticket->priority === 'medium') {
                $medium++;
            } elseif ($ticket->priority === 'high') {
                $high++;
            } elseif ($ticket->priority === 'critical') {
                $critical++;
            }

            // Resolution time
            if (
                $ticket->status === 'resolved'
                && !empty($ticket->created_at)
                && !empty($ticket->resolved_at)
            ) {
                $resolutionSeconds =
                    strtotime($ticket->resolved_at)
                    - strtotime($ticket->created_at);

                if ($resolutionSeconds >= 0) {
                    $totalResolutionSeconds += $resolutionSeconds;
                    $resolvedTicketCount++;
                }
            }


            // SLA / escalation counts
            if ($ticket->getIsSlaBreached()) {
                $slaBreached++;
            }

            if ($ticket->getEscalationStatus() === 'at_risk') {
                $slaAtRisk++;
            }
        }

        $averageResolutionSeconds = $resolvedTicketCount > 0
        ? $totalResolutionSeconds / $resolvedTicketCount
        : 0;

        return [
            'success' => true,

            'tickets' => [
                'total' => $total,
                'open' => $open,
                'pending' => $pending,
                'resolved' => $resolved,
            ],

            'priority' => [
                'low' => $low,
                'medium' => $medium,
                'high' => $high,
                'critical' => $critical,
            ],

            'sla' => [
                'breached' => $slaBreached,
                'at_risk' => $slaAtRisk,
            ],

            'category' => $categoryCounts,

            'average_resolution_seconds' => $averageResolutionSeconds,

            'staff_metrics' => [
                'my_assigned' => $myAssigned,
                'my_open_assigned' => $myOpenAssigned,
                'my_overdue_assigned' => $myOverdueAssigned,
                'my_critical_assigned' => $myCriticalAssigned,
                'my_recently_updated' => $myRecentlyUpdated,
            ],

            'agent_workload' => $agentWorkload,
        ];
    }



    public function actionOptions()
    {
        return [];
    }
}