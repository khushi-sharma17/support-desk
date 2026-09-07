<?php

require __DIR__ . '/vendor/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';

new yii\web\Application($config);

$user = \app\models\User::findByEmail('agent@supportdesk.com');

if ($user === null) {
    echo "USER NOT FOUND\n";
    exit;
}

echo "USER FOUND\n";
echo "ID: " . $user->id . "\n";
echo "EMAIL: " . $user->email . "\n";
echo "ROLE: " . $user->role . "\n\n";

$password = 'Agent@123';

if ($user->validatePassword($password)) {
    echo "PASSWORD VALID\n";
} else {
    echo "PASSWORD INVALID\n";
}