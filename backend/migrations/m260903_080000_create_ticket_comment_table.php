<?php

use yii\db\Migration;

/**
 * Creates the ticket_comment table for Phase 2.
 */
class m260903_080000_create_ticket_comment_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ticket_comment}}', [
            'id' => $this->primaryKey(),

            'ticket_id' => $this->integer()->notNull(),

            'user_id' => $this->integer()->notNull(),

            'body' => $this->text()->notNull(),

            'type' => $this->string(20)->notNull()->defaultValue('public'),

            'created_at' => $this->dateTime()->notNull(),
        ]);

        /*
         * A comment belongs to a ticket.
         */
        $this->addForeignKey(
            'fk-ticket_comment-ticket_id-ticket-id',
            '{{%ticket_comment}}',
            'ticket_id',
            '{{%ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        /*
         * A comment belongs to the user who created it.
         */
        $this->addForeignKey(
            'fk-ticket_comment-user_id-user-id',
            '{{%ticket_comment}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        /*
         * Only public and internal comments are allowed.
         */
        $this->createIndex(
            'idx-ticket_comment-type',
            '{{%ticket_comment}}',
            'type'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-ticket_comment-user_id-user-id',
            '{{%ticket_comment}}'
        );

        $this->dropForeignKey(
            'fk-ticket_comment-ticket_id-ticket-id',
            '{{%ticket_comment}}'
        );

        $this->dropIndex(
            'idx-ticket_comment-type',
            '{{%ticket_comment}}'
        );

        $this->dropTable('{{%ticket_comment}}');
    }
}