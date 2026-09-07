<?php

use yii\db\Migration;

class m260903_074248_extend_ticket_for_phase2 extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%ticket}}',
            'updated_at',
            $this->dateTime()->null()
        );

        $this->addColumn(
            '{{%ticket}}',
            'due_at',
            $this->dateTime()->null()
        );

        $this->addColumn(
            '{{%ticket}}',
            'resolved_at',
            $this->dateTime()->null()
        );

        $this->addColumn(
            '{{%ticket}}',
            'resolution_notes',
            $this->text()->null()
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%ticket}}', 'resolution_notes');
        $this->dropColumn('{{%ticket}}', 'resolved_at');
        $this->dropColumn('{{%ticket}}', 'due_at');
        $this->dropColumn('{{%ticket}}', 'updated_at');
    }
}