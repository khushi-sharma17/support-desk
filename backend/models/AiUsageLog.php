<?php

namespace app\models;

use yii\db\ActiveRecord;

class AiUsageLog extends ActiveRecord
{
    public static function tableName()
    {
        return 'ai_usage_log';
    }

    public function rules()
    {
        return [
            [['user_id', 'ticket_id', 'prompt_tokens', 'completion_tokens', 'total_tokens'], 'integer'],

            [['operation', 'status'], 'required'],

            [['error_message'], 'string'],

            [['operation'], 'string', 'max' => 50],

            [['model'], 'string', 'max' => 100],

            [['status'], 'string', 'max' => 20],

            [['created_at'], 'safe'],
        ];
    }
}