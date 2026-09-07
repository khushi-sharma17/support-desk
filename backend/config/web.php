<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],

    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'components' => [

        // 'cors' => [
        //     'class' => \yii\filters\Cors::class,
        //     'cors' => [
        //         'Origin' => ['http://localhost:5173'],
        //         'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        //         'Access-Control-Request-Headers' => ['*'],
        //         'Access-Control-Allow-Credentials' => false,
        //         'Access-Control-Max-Age' => 86400,
        //     ],
        // ],

        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
        ],

        'request' => [
            'cookieValidationKey' => 'F6if-920CyzB11X7CfgsWwaJu4JtZBRF',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],

        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],

        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'mailer' => \yii\mail\MailerInterface::class,

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],

        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/client', 'v1/ticket'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'POST <id>/assign' => 'assign',
                        'OPTIONS <id>/assign' => 'options',

                        'GET <id>/comments' => 'comments',
                        'OPTIONS <id>/comments' => 'options',

                        'POST <id>/add-comment' => 'add-comment',
                        'OPTIONS <id>/add-comment' => 'options',

                        'GET <id>/activity' => 'activity',
                        'OPTIONS <id>/activity' => 'options',

                        'POST <id>/upload' => 'upload',
                        'OPTIONS <id>/upload' => 'options',

                        'GET <id>/download' => 'download',

                        'POST <id>/analyze' => 'analyze',
                        'OPTIONS <id>/analyze' => 'options',
                        'POST ai-search' => 'ai-search',
                        'OPTIONS ai-search' => 'options',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/auth'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'POST login' => 'login',
                        'GET me' => 'me',
                    ],
                ],

                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/user'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'OPTIONS' => 'options',
                    ],
                ],

                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/dashboard'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET' => 'index',
                    ],
                ],
            ],
        ],
    ],

    'params' => $params,
];

if (YII_ENV_DEV) {

    $config['bootstrap'][] = 'debug';

    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';

    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;