<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    /**
     * Database table name.
     */
    public static function tableName(): string
    {
        return 'user';
    }

    /**
     * Find user by primary key.
     */
    public static function findIdentity($id): ?static
    {
        return static::findOne($id);
    }

    /**
     * Find user by access token.
     *
     * We will use this later for Bearer-token authentication.
     */
    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        return static::find()
            ->where(['access_token' => $token])
            ->one();
    }

    /**
     * Find user by email.
     */
    public static function findByEmail(string $email): ?static
    {
        return static::find()
            ->where(['email' => $email])
            ->one();
    }

    /**
     * Get user ID.
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * Get authentication key.
     *
     * We are not using Yii's session auth key for the API yet.
     */
    public function getAuthKey(): ?string
    {
        return null;
    }

    /**
     * Validate authentication key.
     */
    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    /**
     * Check password against password_hash.
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Get user's role.
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Return safe user information.
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}