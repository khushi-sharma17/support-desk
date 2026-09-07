<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%client}}`.
 */
class m260831_065117_create_client_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
{
    $this->createTable('{{%client}}', [
        'id' => $this->primaryKey(),
        'name' => $this->string(255)->notNull(),
        'contact_email' => $this->string(255)->notNull(),
    ]);
}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
{
    $this->dropTable('{{%client}}');
}
}
