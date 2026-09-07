<?php

use yii\db\Migration;

class m260831_070204_add_ticket_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
{
    $this->addColumn('{{%ticket}}', 'client_id', $this->integer()->notNull());
    $this->addColumn('{{%ticket}}', 'subject', $this->string(255)->notNull());
    $this->addColumn('{{%ticket}}', 'description', $this->text()->notNull());
    $this->addColumn('{{%ticket}}', 'status', $this->string(20)->notNull()->defaultValue('open'));
    $this->addColumn('{{%ticket}}', 'category', $this->string(100));
    $this->addColumn('{{%ticket}}', 'ai_summary', $this->text());
    $this->addColumn('{{%ticket}}', 'attachment_path', $this->string(500));
    $this->addColumn('{{%ticket}}', 'created_at', $this->dateTime()->notNull());
}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
{
    $this->dropColumn('{{%ticket}}', 'created_at');
    $this->dropColumn('{{%ticket}}', 'attachment_path');
    $this->dropColumn('{{%ticket}}', 'ai_summary');
    $this->dropColumn('{{%ticket}}', 'category');
    $this->dropColumn('{{%ticket}}', 'status');
    $this->dropColumn('{{%ticket}}', 'description');
    $this->dropColumn('{{%ticket}}', 'subject');
    $this->dropColumn('{{%ticket}}', 'client_id');
}

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260831_070204_add_ticket_columns cannot be reverted.\n";

        return false;
    }
    */
}
