<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "client".
 *
 * @property int $id
 * @property string $name
 * @property string $contact_email
 */
class Client extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'contact_email'], 'required'],
            [['name', 'contact_email'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'contact_email' => 'Contact Email',
        ];
    }


    public function fields()
    {
        return [
            'id',
            'name',
            'contact_email',
        ];
    }

    public function extraFields()
    {
        return [
            'tickets',
        ];
    }

    public function getTickets()
    {
        return $this->hasMany(Ticket::class, ['client_id' => 'id']);
    }
}
