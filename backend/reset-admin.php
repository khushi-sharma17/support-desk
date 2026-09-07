<?php

define('YII_ENV', 'dev');
define('YII_DEBUG', true);
define('YII_ENV_DEV', true);
define('YII_ENV_PROD', false);
define('YII_ENV_TEST', false);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';

new yii\console\Application($config);

$user = \app\models\User::find()
    ->where(['email' => 'admin@supportdesk.com'])
    ->one();

if (!$user) {
    echo "Admin user not found.\n";
    exit(1);
}

$user->password_hash =
    Yii::$app->security->generatePasswordHash('Admin@123');

if ($user->save(false)) {
    echo "Admin password reset successfully.\n";
    echo "Email: admin@supportdesk.com\n";
    echo "Password: Admin@123\n";
} else {
    echo "Failed to reset password.\n";
    print_r($user->errors);
}