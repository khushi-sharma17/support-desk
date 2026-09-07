<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\User;

class TicketComment extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'ticket_comment';
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

            [['body'], 'string'],

            [['type'], 'string', 'max' => 20],

            [['ticket_id', 'user_id', 'body'], 'required'],

            [['created_at'], 'safe'],

            [['type'], 'default', 'value' => 'public'],

            [
                ['type'],
                'in',
                'range' => [
                    'public',
                    'internal',
                ],
            ],

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
            'body' => 'Body',
            'type' => 'Type',
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