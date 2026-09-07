<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "ticket".
 *
 * @property int $id
 * @property int $client_id
 * @property int|null $assigned_to
 * @property string $subject
 * @property string $description
 * @property string $status
 * @property string $priority
 * @property string|null $category
 * @property string|null $ai_summary
 * @property string|null $attachment_path
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $due_at
 * @property string|null $resolved_at
 * @property string|null $resolution_notes
 */
class Ticket extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ticket';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    public function rules()
    {
        return [
            [['client_id', 'assigned_to'], 'integer'],

            [['description', 'ai_summary', 'resolution_notes'], 'string'],

            [['subject'], 'string', 'max' => 255],

            [['status'], 'string', 'max' => 20],

            [['priority'], 'string', 'max' => 20],

            [['category'], 'string', 'max' => 100],

            [['attachment_path'], 'string', 'max' => 500],

            [['created_at', 'updated_at', 'due_at', 'resolved_at'], 'safe'],

            [['client_id', 'subject', 'description'], 'required'],

            [['category', 'ai_summary', 'attachment_path'], 'default', 'value' => null],

            [['assigned_to'], 'default', 'value' => null],

            [['due_at', 'resolved_at', 'resolution_notes'], 'default', 'value' => null],

            [['status'], 'default', 'value' => 'open'],

            [['priority'], 'default', 'value' => 'medium'],

            [['status'], 'in', 'range' => [
                'open',
                'pending',
                'resolved',
            ]],

            [['category'], 'in', 'range' => [
                'technical',
                'billing',
                'account',
                'general'
            ]],

            [['priority'], 'in', 'range' => [
                'low',
                'medium',
                'high',
                'critical'
            ]],

            [
                ['assigned_to'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['assigned_to' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'client_id' => 'Client ID',
            'assigned_to' => 'Assigned To',
            'subject' => 'Subject',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            'category' => 'Category',
            'ai_summary' => 'AI Summary',
            'attachment_path' => 'Attachment Path',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'due_at' => 'Due At',
            'resolved_at' => 'Resolved At',
            'resolution_notes' => 'Resolution Notes',
        ];
    }

    public function extraFields()
    {
        return [
            'client',
            'assignedUser',
        ];
    }

    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getAssignedUser()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_to']);
    }
}
