<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'sourceLanguage' => 'uk',
    'language'=>'uk',
    'modules' => [
    'languages' => [
        'class' => 'common\modules\languages\LModule',
        //Языки используемые в приложении
        'languages' => [
            'EN' => 'en',
            'UA' => 'uk',
            'RU' => 'ru',
        ],
        'default_language' => 'uk', //основной язык (по-умолчанию)
        'show_default' => false, //true - показывать в URL основной язык, false - нет
    ], 
    ],
    'components' => [
        'request' => [
            'baseUrl' => '',
            'class' => 'common\components\LangRequest',
            'csrfParam' => '_frontend',
            'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_f', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'blck',
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
       
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
			'class' => 'common\components\LangUrlManager',
            'rules' => [
                 'languages' => 'languages/lang/index/', //для модуля мультиязычности
                 '/' => 'site/index',
                [
                    'pattern'=>'sitemap',
                    'route' => 'site/sitemap',
                    'suffix' => '.xml',
                    //карта сайта 
                ],
                'about' => 'site/about',
                'telegram' => 'site/telegram',
                'label' => 'label/index',
                'label/<url:([\w\-_\d]+)>' => 'label/list',
                'label/<url:([\w\-_\d]+)>/<track:([\w\-_\d]+)>' => 'label/view',
                '<link:([\w\-_\d]+)>'=> 'site/view',
                '<action:\w+>' => 'site/<action>', 
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                
            ],
        ],
       
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
