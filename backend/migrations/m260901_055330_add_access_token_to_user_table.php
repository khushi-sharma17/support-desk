<?php

use yii\db\Migration;

class m260901_055330_add_access_token_to_user_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%user}}',
            'access_token',
            $this->string(64)->unique()
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'access_token');
    }
}