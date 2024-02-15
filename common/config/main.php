<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language'=>'uk',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'session' => [
            'class' => 'yii\web\DbSession',
            'writeCallback' => function () {
                return [
                    'user_id' => Yii::$app->user->id
                ];
    
            }
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            //'cache' => 'cache',
            'defaultRoles' => ['user'],
            'itemTable'       => 'auth_item',
            'itemChildTable'  => 'auth_item_child',
            'assignmentTable' => 'auth_assignment',
            'ruleTable'       => 'auth_rule',
            //'class' => 'yii\rbac\PhpManager',
            //зададим куда будут сохраняться наши файлы конфигураций RBAC
           // 'itemFile' => '@common/components/rbac/items.php',
           // 'assignmentFile' => '@common/components/rbac/assignments.php',
            //'ruleFile' => '@common/components/rbac/rules.php'
        ],
        'config' => [ // настройки с БД
            'class' => 'common\components\Config',
        ],

    ],
];
