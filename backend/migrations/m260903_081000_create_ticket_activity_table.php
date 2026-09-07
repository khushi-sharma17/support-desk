<?php

use yii\db\Migration;

/**
 * Creates the ticket_activity table for Phase 2.
 */
class m260903_081000_create_ticket_activity_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ticket_activity}}', [
            'id' => $this->primaryKey(),

            'ticket_id' => $this->integer()->notNull(),

            'user_id' => $this->integer()->notNull(),

            'action' => $this->string(100)->notNull(),

            'old_value' => $this->text()->null(),

            'new_value' => $this->text()->null(),

            'created_at' => $this->dateTime()->notNull(),
        ]);

        /*
         * Activity belongs to a ticket.
         */
        $this->addForeignKey(
            'fk-ticket_activity-ticket_id-ticket-id',
            '{{%ticket_activity}}',
            'ticket_id',
            '{{%ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        /*
         * Activity records the user who caused the action.
         */
        $this->addForeignKey(
            'fk-ticket_activity-user_id-user-id',
            '{{%ticket_activity}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        /*
         * Frequently used when displaying a ticket timeline.
         */
        $this->createIndex(
            'idx-ticket_activity-ticket_id-created_at',
            '{{%ticket_activity}}',
            [
                'ticket_id',
                'created_at',
            ]
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-ticket_activity-user_id-user-id',
            '{{%ticket_activity}}'
        );

        $this->dropForeignKey(
            'fk-ticket_activity-ticket_id-ticket-id',
            '{{%ticket_activity}}'
        );

        $this->dropIndex(
            'idx-ticket_activity-ticket_id-created_at',
            '{{%ticket_activity}}'
        );

        $this->dropTable('{{%ticket_activity}}');
    }
}