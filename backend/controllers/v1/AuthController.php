<?php

declare(strict_types=1);

namespace app\controllers\v1;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['login'],
        ];

        return $behaviors;
    }

    /**
     * POST /v1/auth/login
     *
     * Login using email and password.
     */
    public function actionLogin()
    {
        $bodyParams = Yii::$app->request->bodyParams;

        $email = $bodyParams['email'] ?? null;
        $password = $bodyParams['password'] ?? null;

        /*
         * Validate required fields.
         */
        if ($email === null || $email === '') {
            Yii::$app->response->statusCode = 422;

            return [
                'email' => [
                    'Email is required.'
                ]
            ];
        }

        if ($password === null || $password === '') {
            Yii::$app->response->statusCode = 422;

            return [
                'password' => [
                    'Password is required.'
                ]
            ];
        }

        /*
         * Find user.
         */
        $user = \app\models\User::findByEmail($email);

        /*
         * Do not reveal whether the email exists.
         */
        if (
            $user === null
            || !$user->validatePassword($password)
        ) {
            throw new UnauthorizedHttpException(
                'Invalid email or password.'
            );
        }

        /*
         * Generate a new secure Bearer token.
         */
        $token = bin2hex(random_bytes(32));

        $user->access_token = $token;

        if (!$user->save(false)) {
            throw new \yii\web\ServerErrorHttpException(
                'Unable to create access token.'
            );
        }

        /*
         * Return token + safe user information.
         */
        return [
            'token' => $token,
            'user' => $user->toSafeArray(),
        ];
    }

    /**
     * GET /v1/auth/me
     *
     * Return the currently authenticated user.
     */
    public function actionMe()
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        return $user->toSafeArray();
    }
}