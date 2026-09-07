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



    public function getSlaHours()
    {
        return match ($this->priority) {
            'low' => 72,
            'medium' => 48,
            'high' => 24,
            'critical' => 8,
            default => 48,
        };
    }


    public function calculateDueAt()
    {
        if (empty($this->created_at)) {
            return null;
        }

        return date(
            'Y-m-d H:i:s',
            strtotime($this->created_at . ' +' . $this->getSlaHours() . ' hours')
        );
    }


    public function getSlaStatus()
    {
        if ($this->status === 'resolved' || !empty($this->resolved_at)) {
            return 'resolved';
        }

        if (empty($this->due_at)) {
            return 'no_deadline';
        }

        if (strtotime($this->due_at) < time()) {
            return 'breached';
        }

        return 'within_sla';
    }

    public function getIsSlaBreached()
    {
        return $this->getSlaStatus() === 'breached';
    }



    public function getEscalationStatus()
    {
        if ($this->status === 'resolved' || !empty($this->resolved_at)) {
            return 'not_required';
        }

        if ($this->getIsSlaBreached()) {
            return 'escalated';
        }

        if (empty($this->due_at)) {
            return 'not_required';
        }

        $remainingSeconds = strtotime($this->due_at) - time();

        // Escalate when less than 25% of the SLA time remains.
        $escalationThreshold = $this->getSlaHours() * 3600 * 0.25;

        if ($remainingSeconds <= $escalationThreshold) {
            return 'at_risk';
        }

        return 'normal';
    }



    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Set SLA due time when ticket is created
        if ($insert) {
            $this->due_at = $this->calculateDueAt();
        }

        return true;
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


    public function fields()
    {
        $fields = parent::fields();

        $fields['sla_status'] = function ($model) {
            return $model->getSlaStatus();
        };

        $fields['is_sla_breached'] = function ($model) {
            return $model->getIsSlaBreached();
        };

        $fields['sla_hours'] = function ($model) {
            return $model->getSlaHours();
        };

        $fields['escalation_status'] = function ($model) {
            return $model->getEscalationStatus();
        };

        return $fields;
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
