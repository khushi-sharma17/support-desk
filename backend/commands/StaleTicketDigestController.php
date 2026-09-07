<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;

class StaleTicketDigestController extends Controller
{
    /**
     * Find tickets that have been open for more than 48 hours
     * and write them to a CSV digest.
     */
    public function actionIndex(): int
    {
        /*
         * Use the database server's current time.
         *
         * This keeps the Yii query consistent with the
         * MySQL/MariaDB time used by the application database.
         */
        $tickets = \app\models\Ticket::find()
            ->where(['status' => 'open'])
            ->andWhere(
                'created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)'
            )
            ->orderBy([
                'created_at' => SORT_ASC,
            ])
            ->all();

        /*
         * Create digest directory if it does not exist.
         */
        $digestDir = Yii::getAlias('@runtime/digests');

        if (!is_dir($digestDir)) {
            mkdir($digestDir, 0775, true);
        }

        /*
         * Create today's CSV file.
         */
        $filename = $digestDir
            . DIRECTORY_SEPARATOR
            . 'stale-tickets-'
            . date('Y-m-d')
            . '.csv';

        $handle = fopen($filename, 'w');

        if ($handle === false) {
            $this->stderr(
                "Unable to create CSV file.\n"
            );

            return 1;
        }

        /*
         * CSV header.
         */
        fputcsv($handle, [
            'id',
            'client_id',
            'subject',
            'status',
            'priority',
            'category',
            'created_at',
            'assigned_to',
        ]);

        /*
         * Write stale tickets.
         */
        foreach ($tickets as $ticket) {
            fputcsv($handle, [
                $ticket->id,
                $ticket->client_id,
                $ticket->subject,
                $ticket->status,
                $ticket->priority,
                $ticket->category,
                $ticket->created_at,
                $ticket->assigned_to,
            ]);
        }

        fclose($handle);

        /*
         * Report result.
         */
        $this->stdout(
            "Digest created successfully.\n"
            . "Tickets found: "
            . count($tickets)
            . "\n"
            . "File: "
            . $filename
            . "\n"
        );

        return 0;
    }
}