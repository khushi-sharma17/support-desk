<?php

use yii\db\Migration;

class m260831_110217_add_assigned_to_to_ticket_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%ticket}}',
            'assigned_to',
            $this->integer()->null()
        );

        $this->addForeignKey(
            'fk-ticket-assigned_to-user-id',
            '{{%ticket}}',
            'assigned_to',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-ticket-assigned_to-user-id',
            '{{%ticket}}'
        );

        $this->dropColumn(
            '{{%ticket}}',
            'assigned_to'
        );
    }
}