<?php 
use yii\helpers\Html;
?>
<div class="col-md-3 left_col">
            <div class="left_col scroll-view">

                <div class="navbar nav_title" style="border: 0;">
                    <?= Html::a(Html::img('/img/label.jpg', ['style' => 'border-radius: 50%;width:50px;margin-right: 15px;']) . '<span> ' . Yii::$app->name. '</span>', Yii::$app->homeUrl, ['class' => 'site_title']) ?>
                </div>
                <div class="clearfix"></div>
                <br />

                <!-- sidebar menu -->
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">

                    <div class="menu_section">
                        <h3>Меню</h3>
                        <?php
                         $items[] = ["label" => Yii::t('app', 'Головна'), "url" =>  Yii::$app->homeUrl, "icon" => "home"];

                        if(Yii::$app->user->can('label')) {
                            $items[] = ["label" => Yii::t('app', 'Артисти'), "url" => ['/artist'], "icon" => "files-o"];
                            $items[] = ["label" => Yii::t('app', 'Треки'), "url" => ['/track'], "icon" => "files-o"];
                            $items[] = ['label' => Yii::t('app', 'Релізи'),  'url' => ['/release'], "icon" => "files-o"];
                        }

                        if(Yii::$app->user->can('moder')) {
                            //$items[] = ['label' => Yii::t('app', 'Агрегатори'),  'url' => ['/aggregator'], "icon" => "files-o"];
                            $items[] = [
                                'label' => Yii::t('app', 'Звіти'),
                                'icon' => 'table',
                                'url' => "#",
                                'items'=> [
                                    [
                                        'label' => Yii::t('app', 'Агрегатори'),
                                        'url' => '#',
                                        'items' => [
                                            ['label' => Yii::t('app', 'Список агрегатори'), 'url' => ['/aggregator']],
                                            ['label' => Yii::t('app', 'Звіти агрегаторів'), 'url' => ['/aggregator-report']],
                                            ['label' => Yii::t('app', 'Статуси звітів агрегаторів'), 'url' => ['/aggregator-report-status']],
                                            ['label' => Yii::t('app', 'Власність'), 'url' => ['/ownership']],
                                            ['label' => Yii::t('app', 'Катеорія власності'), 'url' => ['/ownership-type']],
                                        ],
                                    ],
                                    [
                                        'label' => Yii::t('app', 'Інвойси'),
                                        'url' => '#',
                                        'items' => [
                                            ['label' => Yii::t('app', 'Список інвойсів'), 'url' => ['/invoice']],
                                            ['label' => Yii::t('app', 'Типи інвойсів'), 'url' => ['/invoice-type']],
                                        ],
                                    ],
                                ],
                            ];
                        }

                        if(Yii::$app->user->can('manager')) {
                            $items[] = ['label' => Yii::t('app', 'Аналітика'),  'url' => ['/log'], "icon" => "files-o"];
                        }

                        if(Yii::$app->user->can('manager') && isset(Yii::$app->user->identity->label->id)) {
                            $items[] = ['label' => Yii::t('app', 'Налаштування'),  'url' => ['/sub-label/view/', 'id' => Yii::$app->user->identity->label->id], "icon" => "table"];
                        }
                        // $items[] = ["label" => Yii::t('app', 'Официальные ссылки'), "url" => ["/link"], "icon" => "close"];
                        // $items[] = ["label" => Yii::t('app', 'Музыкальные Сервисы'), "url" => ['/services'], "icon" => "files-o"];
                          if(Yii::$app->user->can('admin')) {
                              $items[] = [
                                        'label' => Yii::t('app', 'Конфіги'),
                                        'icon' => 'table',
                                        'url' => "#",
                                        'items'=> [
                                                ['label' => Yii::t('app', 'Користувачі'),  'url' => ['/user']],
                                               // ['label' => Yii::t('app', 'Аналитика'),  'url' => ['/log']],
                                                ['label' => Yii::t('app', 'Переклади'),  'url' => ['/message']],
                                                ['label' => Yii::t('app', 'Суб Лейбли'), 'url' => ['/sub-label']],
                                                //['label' => Yii::t('app', 'Агрегатори'),  'url' => ['/aggregator']],
                                        ],
                                    ];

                              }
                          /*  [
                                "items" => [
                                    ["label" => Yii::t('app', 'Главная'), "url" =>  Yii::$app->homeUrl, "icon" => "home"],
                                    ["label" => Yii::t('app', 'Артисты'), "url" => ['/artist/index/'], "icon" => "files-o"],
                                    ["label" => Yii::t('app', 'Релизы'), "url" => ['/track/index/'], "icon" => "files-o"],
                                    ["label" => Yii::t('app', 'Официальные ссылки'), "url" => ["link/index"], "icon" => "close"],
                                    ["label" => Yii::t('app', 'Музыкальные Сервисы'), "url" => ['services/index/'], "icon" => "files-o"],
                                    [
                                        "label" => "Widgets",
                                        "icon" => "th",
                                        "url" => "#",
                                        "items" => [
                                            ["label" => "Menu", "url" => ["site/menu"]],
                                            ["label" => "Panel", "url" => ["site/panel"]],
                                        ],
                                    ],
                                    [
                                        "label" => "Настройки",
                                        "url" => "#",
                                        "icon" => "table",
                                        "items" => [
                                            [
                                                "label" => "Пользователи",
                                                "url" => ['/ышеу/index/'],,
                                                "badge" => "123",
                                            ],
                                            [
                                                "label" => "Success",
                                                "url" => "#",
                                                "badge" => "new",
                                                "badgeOptions" => ["class" => "label-success"],
                                            ],
                                            [
                                                "label" => "Danger",
                                                "url" => "#",
                                                "badge" => "!",
                                                "badgeOptions" => ["class" => "label-danger"],
                                            ],
                                        ],
                                    ],
                                    [
                                        "label" => "Multilevel",
                                        "url" => "#",
                                        "icon" => "table",
                                        "items" => [
                                            [
                                                "label" => "Second level 1",
                                                "url" => "#",
                                            ],
                                            [
                                                "label" => "Second level 2",
                                                "url" => "#",
                                                "items" => [
                                                    [
                                                        "label" => "Third level 1",
                                                        "url" => "#",
                                                    ],
                                                    [
                                                        "label" => "Third level 2",
                                                        "url" => "#",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ]
                            ]*/
                    echo   \yiister\gentelella\widgets\Menu::widget([
                           'items' => $items
                       ])
                        ?>
                    </div>

                </div>
                  <!-- /sidebar menu -->

                <!-- /menu footer buttons -->
               <!-- <div class="sidebar-footer hidden-small">
                    <a data-toggle="tooltip" data-placement="top" title="Settings">
                        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                    </a>
                    <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
                    </a>
                    <a data-toggle="tooltip" data-placement="top" title="Lock">
                        <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
                    </a>
                    
                    <a data-toggle="tooltip" data-placement="top" title="Logout">
                        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                    </a>
                </div>-->
                <!-- /menu footer buttons -->
            </div>
        </div>