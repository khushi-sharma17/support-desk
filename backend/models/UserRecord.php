<?php

namespace app\models;

use yii\db\ActiveRecord;

class UserRecord extends ActiveRecord
{
    public static function tableName()
    {
        return 'user';
    }

    public function rules()
    {
        return [
            [['email', 'password_hash', 'role'], 'required'],

            [['email'], 'string', 'max' => 255],

            [['password_hash'], 'string', 'max' => 255],

            [['role'], 'string', 'max' => 20],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'email',
            'role',
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'password_hash' => 'Password Hash',
            'role' => 'Role',
        ];
    }
}