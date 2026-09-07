<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class TicketActivity extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'ticket_activity';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['ticket_id', 'user_id'], 'integer'],

            [['action'], 'string', 'max' => 100],

            [['old_value', 'new_value'], 'string'],

            [['ticket_id', 'user_id', 'action'], 'required'],

            [['created_at'], 'safe'],

            [
                ['ticket_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Ticket::class,
                'targetAttribute' => ['ticket_id' => 'id'],
            ],

            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'ticket_id' => 'Ticket ID',
            'user_id' => 'User ID',
            'action' => 'Action',
            'old_value' => 'Old Value',
            'new_value' => 'New Value',
            'created_at' => 'Created At',
        ];
    }

    public function getTicket()
    {
        return $this->hasOne(Ticket::class, ['id' => 'ticket_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function extraFields(): array
    {
        return [
            'user',
        ];
    }
}