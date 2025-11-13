<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;


use backend\widgets\DateFormat;
use yii\data\SqlDataProvider;
use yii\widgets\ListView;
use yii\data\ActiveDataProvider;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\LogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Аналитика');
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = 'Фінанси';
?>
<div class="log-index">
    <p>
        <?php // Html::a(Yii::t('app', 'Create Log'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php //Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php /*/ GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
           // 'track',
            [
                        'attribute' => 'track',
                        'value' => function($data){ return $data->tracks->name;},
            ],
            'type',
            'name',
            'referal',
            'ip',
            'country',
            'data',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); */?>

    <?php //Pjax::end(); ?>

    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="row">
                <div class="col-sm-12">
            <div class="panel panel-success">
                <div class="panel-heading">Баланс лейбів EUR </div>
                <div class="panel-body">
                    <?php
                    $dataProvider = new SqlDataProvider([
                        'sql' => 'SELECT l.name,
                                            SUM(deposit_1) as `sum`,
                                            currency.currency_name
                                        FROM `artist`
                                            LEFT JOIN sub_label l ON l.id = artist.label_id
                                            LEFT JOIN currency ON currency.currency_id = 1
                                        WHERE artist.id !=0
                                          and artist.deposit_1 >= 0
                                        GROUP BY artist.label_id
                                        HAVING `sum` > 0
                                        ',
                        'totalCount' => 1,
                        'pagination' => [
                            'pageSize' => 20,
                        ],
                    ]);
                    echo GridView::widget([
                        'dataProvider' => $dataProvider,
                        'showFooter' => true,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'attribute' => 'name',
                                'label' => 'Лейбл',
                                'footer' => '<b>Всього:</b>'
                            ],
                            [
                                'attribute' => 'sum',
                                'label' => 'Сума',
                                'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum')) . '</b>',
                            ],
                            [
                                'attribute' => 'currency_name',
                                'label' => 'Валюта'
                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>
                </div>
                <div class="col-sm-12">
                    <div class="panel panel-warning">
                        <div class="panel-heading">Баланс лейбів USD </div>
                        <div class="panel-body">
                            <?php
                            $dataProvider = new SqlDataProvider([
                                'sql' => 'SELECT l.name,
                                               SUM(deposit_3) as `sum`,
                                                currency.currency_name
                                        FROM `artist`
                                            LEFT JOIN sub_label l ON l.id = artist.label_id
                                            LEFT JOIN currency ON currency.currency_id = 3
                                        WHERE artist.id !=0
                                          and artist.deposit_3 >= 0
                                        GROUP BY artist.label_id
                                        HAVING `sum` > 0
                                        ',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]);
                            
                            echo GridView::widget([
                                'dataProvider' => $dataProvider,
                                'showFooter' => true,
                                'columns' => [
                                    ['class' => 'yii\grid\SerialColumn'],
                                    [
                                        'attribute' => 'name',
                                        'label' => 'Лейбл',
                                        'footer' => '<b>Всього:</b>'
                                    ],
                                    [
                                        'attribute' => 'sum',
                                        'label' => 'Сума',
                                        'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum')) . '</b>',
                                    ],
                                    [
                                        'attribute' => 'currency_name',
                                        'label' => 'Валюта'
                                    ],
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="panel panel-info">
                        <div class="panel-heading">Баланс лейбів UAH</div>
                        <div class="panel-body">
                            <?php
                            $dataProvider = new SqlDataProvider([
                                'sql' => '
                                        SELECT l.name,
                                               SUM(deposit) as `sum`,
                                               currency.currency_name
                                        FROM `artist`
                                            LEFT JOIN sub_label l ON l.id = artist.label_id
                                            LEFT JOIN currency ON currency.currency_id = 2
                                        WHERE artist.id !=0
                                          and artist.deposit >= 0
                                        GROUP BY artist.label_id
                                        HAVING `sum` > 0
                                        ',
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]);
                            
                            echo GridView::widget([
                                'dataProvider' => $dataProvider,
                                'showFooter' => true,
                                'columns' => [
                                    ['class' => 'yii\grid\SerialColumn'],
                                    [
                                        'attribute' => 'name',
                                        'label' => 'Лейбл',
                                        'footer' => '<b>Всього:</b>'
                                    ],
                                    [
                                        'attribute' => 'sum',
                                        'label' => 'Сума',
                                        'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum')) . '</b>',
                                    ],
                                    [
                                        'attribute' => 'currency_name',
                                        'label' => 'Валюта'
                                    ],
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Баланс Акртистів BlackBeats EUR</div>
                        <div class="panel-body">
                            <?php
                            $dataProvider = new SqlDataProvider([
                                'sql' => 'SELECT GROUP_CONCAT(distinct(ag.name)) as name,
                                            IFNULL(ar.year, i.year) as year,
                                            IFNULL(ar.quarter, i.quarter) as quarter,
                                            sum(ii.amount) as sum_in,
                                            sum(IF(ii.payment_invoice_id is null, 0, ii.amount)) as sum_out,
                                            sum(IF(ii.payment_invoice_id is null, ii.amount, 0)) as `sum_ost`,
                                            c.currency_name
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id = 2
                                                and i.invoice_type in (1, 5, 3, 4)
                                            inner join artist a On a.id = ii.artist_id
                                                                       and a.label_id = 0
                                                                       and a.deposit_1 > 0
                                            left join aggregator_report ar ON ar.id = i.aggregator_report_id
                                            left JOIN currency c ON c.currency_id = i.currency_id
                                            left join aggregator ag ON ag.aggregator_id = i.aggregator_id
                                        where ii.artist_id != 0
                                        and i.currency_id = 1
                                        group by ag.internal_type, ar.year, ar.quarter, i.currency_id
                                        ORDER BY i.year asc, i.quarter asc, i.aggregator_id asc',
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]);
                            
                            echo GridView::widget([
                                'dataProvider' => $dataProvider,
                                'showFooter' => true,
                                'columns' => [
                                    ['class' => 'yii\grid\SerialColumn'],
                                    [
                                        'attribute' => 'name',
                                        'label' => 'Агрегатор'
                                    ],
                                    [
                                        'attribute' => 'year',
                                        'label' => 'Рік'
                                    ],
                                    [
                                        'attribute' => 'quarter',
                                        'label' => 'Квартал',
                                        'footer' => '<b>Всього:</b>'
                                    ],
                                    [
                                        'attribute' => 'sum_in',
                                        'label' => 'Дохід',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_in')),
                                    ],
                                    [
                                        'attribute' => 'sum_out',
                                        'label' => 'Виплачено',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_out')),
                                    ],
                                    [
                                        'attribute' => 'sum_ost',
                                        'label' => 'Залишок',
                                        'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum_ost')) . '</b>',
                                    ],
                                    [
                                        'attribute' => 'currency_name',
                                        'label' => 'Валюта'
                                    ],
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="panel panel-warning">
                        <div class="panel-heading">Баланс Акртистів BlackBeats USD</div>
                        <div class="panel-body">
                            <?php
                            $dataProvider = new SqlDataProvider([
                                'sql' => 'SELECT GROUP_CONCAT(distinct(ag.name)) as name,
                                            IFNULL(ar.year, i.year) as year,
                                            IFNULL(ar.quarter, i.quarter) as quarter,
                                            sum(ii.amount) as sum_in,
                                            sum(IF(ii.payment_invoice_id is null, 0, ii.amount)) as sum_out,
                                            sum(IF(ii.payment_invoice_id is null, ii.amount, 0)) as `sum_ost`,
                                            c.currency_name
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id = 2
                                                and i.invoice_type in (1, 5, 3, 4)
                                            inner join artist a On a.id = ii.artist_id
                                                                       and a.label_id = 0
                                                                       and a.deposit_3 > 0
                                            left join aggregator_report ar ON ar.id = i.aggregator_report_id
                                            left JOIN currency c ON c.currency_id = i.currency_id
                                            left join aggregator ag ON ag.aggregator_id = i.aggregator_id
                                        where ii.artist_id != 0
                                        and i.currency_id = 3
                                        group by ag.internal_type, ar.year, ar.quarter, i.currency_id
                                        ORDER BY i.year asc, i.quarter asc, i.aggregator_id asc',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]);
                            
                            echo GridView::widget([
                                'dataProvider' => $dataProvider,
                                'showFooter' => true,
                                'columns' => [
                                    ['class' => 'yii\grid\SerialColumn'],
                                    [
                                        'attribute' => 'name',
                                        'label' => 'Агрегатор'
                                    ],
                                    [
                                        'attribute' => 'year',
                                        'label' => 'Рік'
                                    ],
                                    [
                                        'attribute' => 'quarter',
                                        'label' => 'Квартал',
                                        'footer' => '<b>Всього:</b>'
                                    ],
                                    [
                                        'attribute' => 'sum_in',
                                        'label' => 'Дохід',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_in')),
                                    ],
                                    [
                                        'attribute' => 'sum_out',
                                        'label' => 'Виплачено',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_out')),
                                    ],
                                    [
                                        'attribute' => 'sum_ost',
                                        'label' => 'Залишок',
                                        'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum_ost')) . '</b>',
                                    ],
                                    [
                                        'attribute' => 'currency_name',
                                        'label' => 'Валюта'
                                    ],
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="panel panel-info">
                        <div class="panel-heading">Баланс Акртистів BlackBeats UAH</div>
                        <div class="panel-body">
                            <?php
                            $dataProvider = new SqlDataProvider([
                                'sql' => 'SELECT GROUP_CONCAT(distinct(ag.name)) as name,
                                            IFNULL(ar.year, i.year) as year,
                                            IFNULL(ar.quarter, i.quarter) as quarter,
                                            sum(ii.amount) as sum_in,
                                            sum(IF(ii.payment_invoice_id is null, 0, ii.amount)) as sum_out,
                                            sum(IF(ii.payment_invoice_id is null, ii.amount, 0)) as `sum_ost`,
                                            c.currency_name
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id = 2
                                                and i.invoice_type in (1, 5, 3, 4)
                                            inner join artist a On a.id = ii.artist_id
                                                                       and a.label_id = 0
                                                                       and a.deposit > 0
                                            left join aggregator_report ar ON ar.id = i.aggregator_report_id
                                            left JOIN currency c ON c.currency_id = i.currency_id
                                            left join aggregator ag ON ag.aggregator_id = i.aggregator_id
                                        where ii.artist_id != 0
                                        and i.currency_id = 2
                                        group by ag.internal_type, ar.year, ar.quarter, i.currency_id
                                        ORDER BY  i.year asc, i.quarter asc,  i.aggregator_id asc',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]);
                            
                            echo GridView::widget([
                                'dataProvider' => $dataProvider,
                                'showFooter' => true,
                                'columns' => [
                                    ['class' => 'yii\grid\SerialColumn'],
                                    [
                                        'attribute' => 'name',
                                        'label' => 'Агрегатор'
                                    ],
                                    [
                                        'attribute' => 'year',
                                        'label' => 'Рік'
                                    ],
                                    [
                                        'attribute' => 'quarter',
                                        'label' => 'Квартал',
                                        'footer' => '<b>Всього:</b>'
                                    ],
                                    [
                                        'attribute' => 'sum_in',
                                        'label' => 'Дохід',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_in')),
                                    ],
                                    [
                                        'attribute' => 'sum_out',
                                        'label' => 'Виплачено',
                                        'footer' => array_sum(array_column($dataProvider->getModels(), 'sum_out')),
                                    ],
                                    [
                                        'attribute' => 'sum_ost',
                                        'label' => 'Залишок',
                                        'footer' => '<b>' . array_sum(array_column($dataProvider->getModels(), 'sum_ost')) . '</b>',
                                    ],
                                    [
                                        'attribute' => 'currency_name',
                                        'label' => 'Валюта'
                                    ],
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>
        </div>
        <!--  <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по типу власності і валюті (без частки лейбу)</div>
                    <div class="panel-body">
                        <?php /* GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT o.name,
                                           GROUP_CONCAT(DISTINCT(a.name)) as aggregator,
                                           c.currency_name,
                                           sum(ii.amount) as amount
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id in (2, 4)
                                                and i.invoice_type = 1
                                            INNER join artist art ON art.id = ii.artist_id and art.label_id = 0
                                            left join aggregator a ON a.aggregator_id = i.aggregator_id
                                            left join currency c ON c.currency_id = i.currency_id
                                            left join ownership o ON o.id = a.ownership_type
                                        WHERE ii.artist_id !=0
                                        group by o.id, c.currency_id',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]),
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'name',
                                    'label' => 'Тип власності'
                                ],
                                [
                                    'attribute' => 'aggregator',
                                    'label' => 'Агрегатор',
                                ],
                                [
                                    'attribute' => 'currency_name',
                                    'label' => 'Валюта',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],
                            ]
                        ]) */?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Доп. дохід (баланси)</div>
                    <div class="panel-body">
                        <?php /*GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT c.currency_name,
                                            sum(ii.amount) as amount
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                               and i.invoice_status_id in (2, 4)
                                               and i.invoice_type = 5
                                            INNER join artist art ON art.id = ii.artist_id and art.label_id = 0
                                            left join currency c ON c.currency_id = i.currency_id
                                        WHERE ii.artist_id !=0
                                        group by c.currency_id',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]),
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'currency_name',
                                    'label' => 'Валюта',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],

                            ]
                        ])*/ ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-6"></div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по типу власності (без частки лейбу)</div>
                    <div class="panel-body">
                        <?php /* GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT o.name,
                                           GROUP_CONCAT(DISTINCT(a.name)) as aggregator,
                                           sum(ii.amount) as amount
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id in (2, 4)
                                                and i.invoice_type = 1
                                            INNER join artist art ON art.id = ii.artist_id and art.label_id = 0
                                            left join aggregator a ON a.aggregator_id = i.aggregator_id
                                            left join ownership o ON o.id = a.ownership_type
                                        WHERE ii.artist_id !=0
                                        group by o.id',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]),
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'name',
                                    'label' => 'Тип власності'
                                ],
                                [
                                    'attribute' => 'aggregator',
                                    'label' => 'Агрегатор',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],
                            ]
                        ])*/ ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по валюті (без частки лейбу)</div>
                    <div class="panel-body">
                        <?php /* GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT c.currency_name,
                                           GROUP_CONCAT(DISTINCT(a.name)) as aggregator,
                                           sum(ii.amount) as amount
                                        FROM `invoice_items` ii
                                            inner join invoice i ON i.invoice_id = ii.invoice_id
                                                and i.invoice_status_id in (2, 4)
                                                and i.invoice_type = 1
                                            INNER join artist art ON art.id = ii.artist_id and art.label_id = 0
                                            left join currency c ON c.currency_id = i.currency_id
                                            left join aggregator a ON a.aggregator_id = i.aggregator_id
                                        WHERE ii.artist_id !=0
                                        group by a.currency_id',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]),
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'currency_name',
                                    'label' => 'Валюта',
                                ],
                                [
                                    'attribute' => 'aggregator',
                                    'label' => 'Агрегатор',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],
                            ]
                        ])*/ ?>
                    </div>
                </div>
            </div>-->
    </div>
</div>