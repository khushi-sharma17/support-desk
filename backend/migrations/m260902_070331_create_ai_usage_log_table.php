<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ai_usage_log}}`.
 */
class m260902_070331_create_ai_usage_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ai_usage_log}}', [
            'id' => $this->primaryKey(),

            'user_id' => $this->integer()->null(),

            'ticket_id' => $this->integer()->null(),

            'operation' => $this->string(50)->notNull(),

            'model' => $this->string(100)->null(),

            'status' => $this->string(20)->notNull(),

            'prompt_tokens' => $this->integer()->null(),

            'completion_tokens' => $this->integer()->null(),

            'total_tokens' => $this->integer()->null(),

            'error_message' => $this->text()->null(),

            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            'idx-ai_usage_log-user_id',
            '{{%ai_usage_log}}',
            'user_id'
        );

        $this->createIndex(
            'idx-ai_usage_log-ticket_id',
            '{{%ai_usage_log}}',
            'ticket_id'
        );

        $this->createIndex(
            'idx-ai_usage_log-created_at',
            '{{%ai_usage_log}}',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ai_usage_log}}');
    }
}