<?php
declare(strict_types=1);

namespace app\controllers\v1;

use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;

class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173'],
                'Access-Control-Request-Method' => [
                    'GET',
                    'POST',
                    'PUT',
                    'PATCH',
                    'DELETE',
                    'OPTIONS',
                ],
                'Access-Control-Request-Headers' => [
                    'Authorization',
                    'Content-Type',
                    'Accept',
                ],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'only' => ['index'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity
                            && Yii::$app->user->identity->role === 'admin';
                    },
                ],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        unset($actions['index']);

        return $actions;
    }


    public function actionOptions()
    {
        Yii::$app->response->statusCode = 204;
        return null;
    }


    public function actionIndex()
    {
        $user = Yii::$app->user->identity;

        if ($user === null || $user->role !== 'admin') {
            throw new ForbiddenHttpException(
                'Only administrators can view staff members.'
            );
        }

        $agents = \app\models\User::find()
            ->select(['id', 'email', 'role'])
            ->where(['role' => 'agent'])
            ->orderBy(['email' => SORT_ASC])
            ->asArray()
            ->all();

        return $agents;
    }
}