<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log', 'languages'],
    'sourceLanguage' => 'ru-RU',
    'modules' => [
        'languages' => [
        'class' => 'common\modules\languages\LModule',
        //Языки используемые в приложении
        'languages' => [
            'EN' => 'en',
            'RU' => 'ru',
            'UA' => 'uk',
        ], 
        'default_language' => 'ru', //основной язык (по-умолчанию)
        'show_default' => false, //true - показывать в URL основной язык, false - нет
    ],
    ],
    'as access' => [
        'class' => 'yii\filters\AccessControl',
        'except' => ['site/login','site/signup', 'site/request-password-reset', 'site/reset-password', 'site/error'],
        'rules' => [
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ],
        ],
        
    'components' => [
        'request' => [
           'baseUrl' => '/admin',
            'csrfParam' => '_backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'translit' => [
      'class' => 'common\models\Translit',
    ],
         'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
           // 'suffix' => '/',
            'rules' => [
                
            ],
        ],

        'language'=>'ru-RU',
        'i18n' => [
            'translations' => [
            'app*' => [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@common/messages',
            'sourceLanguage' => 'ru',
            'fileMap' => [
                'main' => 'main.php',
                'main/error' => 'error.php',
            ],
            'on missingTranslation' => ['common\components\TranslationEventHandler', 'handleMissingTranslation']
        ],

    ],
],
    ],
    'params' => $params,
];
