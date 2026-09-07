<?php

namespace app\controllers;

use yii\rest\ActiveController;

class ClientController extends ActiveController
{
    public $modelClass = 'app\models\Client';
    public function actionIndex()
    {
        return $this->render('index');
    }

}
