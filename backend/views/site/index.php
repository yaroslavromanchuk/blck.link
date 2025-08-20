<?php

/* @var $this yii\web\View */

use backend\widgets\DateFormat;
use yii\data\SqlDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ListView;
use yii\data\ActiveDataProvider;

/*$dataProvider = new ActiveDataProvider([
    'query' => \backend\models\Artist::find()->orderBy(['deposit_1'=> SORT_DESC]),
    'pagination' => [
        'pageSize' => 10,
    ],
]);*/

$quarter = DateFormat::getQuarterNumber();
$year = date('Y');

if ($quarter == 1) {
    $quarter = 4;
    $year--;
} else {
    $quarter--;
}

if ($quarter == 1) {
    $quarter2 = 4;
    $year2 = $year - 1;
} else {
    $quarter2 = $quarter - 1;
    $year2 = $year;
}

$this->title = Yii::t('app', 'Загальна статистика');
?>
<div class="site-index">
    <div class="page-header">
        <h2><?= Html::encode($this->title) ?></h2>
    </div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li role="presentation" class="nav-item active">
            <a class="nav-link" data-toggle="tab" href="#top">ТОР артистів</a>
        </li>
        <li role="presentation" class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#debit">Доходи</a>
        </li>
        <li role="presentation" class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#credit">Розрахунки</a>
        </li>
    </ul>
    <!--<div class="d-inline-block">
        <img src="/img/label.jpg" alt="" style="border-radius: 50%; width:90%;">
    </div>-->
<div class="tab-content pb-2" id="myTabContent">
    <div id="top" class="tab-pane fade in active" role="tabpanel">
        <div class="row">
            <div class="col-sm-12 col-md-6 ">
                <div class="panel panel-success">
                    <div class="panel-heading">ТОП 10 артистів за <?= $quarter ?> квартал <?= $year ?></div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => "SELECT a.name,
                                            sum(if(ag.currency_id = 1, ari.amount, 0)) as euro,
                                            sum(if(ag.currency_id = 2, ari.amount, 0)) as uah,
                                            sum(if(ag.currency_id = 3, ari.amount, 0)) as usd
                                        FROM `aggregator_report_item` ari 
                                            inner join aggregator_report ar ON ar.id = ari.report_id 
                                                and ar.quarter = {$quarter} 
                                                and ar.year = {$year} 
                                                and ar.report_status_id = 2
                                            INNER JOIN track t ON t.isrc = ari.isrc 
                                            left join artist a ON a.id = t.artist_id 
                                            left join aggregator ag on ag.aggregator_id = ar.aggregator_id
                                        WHERE a.label_id = 0 and a.id != 0
                                        GROUP by t.artist_id 
                                        ORDER by euro desc",
                                //'params' => [':status' => 1],
                                'totalCount' => 10,
                                'pagination' => [
                                    'pageSize' => 10,
                                ],
                            ]),
                            'columns' => [
                                // ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'name',
                                    'label' => 'Артист'
                                ],
                                [
                                    'attribute' => 'euro',
                                    'label' => 'EURO'
                                ],
                                [
                                    'attribute' => 'usd',
                                    'label' => 'USD'
                                ],
                                [
                                    'attribute' => 'uah',
                                    'label' => 'UAH'
                                ],
                            ]
                        ]) ?>
                    </div>

                </div>
            </div>
            <div class="col-sm-12 col-md-6 ">
                <div class="panel panel-primary">
                    <div class="panel-heading">ТОП 10 артистів за <?= $quarter2 ?> квартал <?= $year2 ?></div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => "SELECT a.name, 
                                            sum(if(ag.currency_id = 1, ari.amount, 0)) as euro,
                                            sum(if(ag.currency_id = 2, ari.amount, 0)) as uah,
                                            sum(if(ag.currency_id = 3, ari.amount, 0)) as usd
                                          FROM `aggregator_report_item` ari 
                                            inner join aggregator_report ar ON ar.id = ari.report_id 
                                               and ar.quarter = {$quarter2} 
                                               and ar.year = {$year2} 
                                               and ar.report_status_id = 2
                                            INNER JOIN track t ON t.isrc = ari.isrc 
                                            left join artist a ON a.id = t.artist_id 
                                            left join aggregator ag on ag.aggregator_id = ar.aggregator_id
                                        WHERE 1 
                                        GROUP by t.artist_id 
                                        ORDER by euro desc",
                                //'params' => [':status' => 1],
                                'totalCount' => 10,
                                'pagination' => [
                                    'pageSize' => 10,
                                ],
                            ]),
                            'columns' => [
                                // ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'name',
                                    'label' => 'Артист'
                                ],
                                [
                                    'attribute' => 'euro',
                                    'label' => 'EURO'
                                ],
                                [
                                    'attribute' => 'usd',
                                    'label' => 'USD'
                                ],
                                [
                                    'attribute' => 'uah',
                                    'label' => 'UAH'
                                ],
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="debit" class="tab-pane fade" role="tabpanel">
        <div class="row">
            <div class="col-sm-12 col-md-4 ">
                <div class="panel panel-success">
                    <div class="panel-heading">Баланс акртистів</div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT l.name,
                                            SUM(IF(deposit_1 > 0, deposit_1, 0)) as EURO,
                                            SUM(IF(deposit > 0, deposit, 0)) as UAH,
                                            SUM(IF(deposit_3 > 0, deposit_3, 0)) as USD
    
                                            #SUM(deposit_1) as EURO,
                                            #SUM(deposit) as UAH,
                                            #SUM(deposit_3) as USD 
                                        FROM `artist` 
                                            INNER JOIN sub_label l ON l.id = artist.label_id
                                        WHERE artist.id !=0
                                          and (artist.deposit_1 >= 0 or artist.deposit >= 0 or artist.deposit_3 >= 0)
                                        GROUP BY artist.label_id
                                        ORDER BY `EURO` DESC, `UAH` DESC, `USD` DESC',
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
                                    'label' => 'Лейбл'
                                ],
                                [
                                    'attribute' => 'EURO',
                                    'label' => 'EURO'
                                ],
                                [
                                    'attribute' => 'USD',
                                    'label' => 'USD'
                                ],
                                [
                                    'attribute' => 'UAH',
                                    'label' => 'UAH'
                                ],
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по типу власності і валюті (без частки лейбу)</div>
                    <div class="panel-body">
                        <?= GridView::widget([
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
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Доп. дохід (баланси)</div>
                    <div class="panel-body">
                        <?= GridView::widget([
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
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-6"></div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по типу власності (без частки лейбу)</div>
                    <div class="panel-body">
                        <?= GridView::widget([
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
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-success">
                    <div class="panel-heading">Дохід по валюті (без частки лейбу)</div>
                    <div class="panel-body">
                        <?= GridView::widget([
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
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="credit" class="tab-pane fade" role="tabpanel">
        <div class="row">
            <div class="col-sm-12 col-md-4">
                <div class="panel panel-danger">
                    <div class="panel-heading">Виплачено артистам</div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT c.currency_name, 
                                            sum(abs(ii.amount)) as amount 
                                        FROM `invoice_items` ii 
                                            inner join invoice i ON i.invoice_id = ii.invoice_id 
                                               and i.invoice_status_id in (2, 4)
                                                and i.invoice_type =2
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
                               // ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'currency_name',
                                    'label' => 'Валюта',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4 ">
                <div class="panel panel-danger">
                    <div class="panel-heading">Витрати + Аванси</div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT c.currency_name, 
                                            sum(abs(ii.amount)) as amount 
                                        FROM `invoice_items` ii 
                                            inner join invoice i ON i.invoice_id = ii.invoice_id 
                                               and i.invoice_status_id in (2, 4)
                                               and i.invoice_type in (3,4)
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
                                //['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'currency_name',
                                    'label' => 'Валюта',
                                ],
                                [
                                    'attribute' => 'amount',
                                    'label' => 'Сума'
                                ],
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4 ">
                <div class="panel panel-danger">
                    <div class="panel-heading">Борг акртистів</div>
                    <div class="panel-body">
                        <?= GridView::widget([
                            'dataProvider' => new SqlDataProvider([
                                'sql' => 'SELECT l.name,
                                            SUM(IF(deposit_1 < 0, abs(deposit_1), 0)) as EURO,
                                            SUM(IF(deposit < 0, abs(deposit), 0)) as UAH,
                                            SUM(IF(deposit_3 < 0, abs(deposit_3), 0)) as USD
                                        FROM `artist`
                                            INNER JOIN sub_label l ON l.id = artist.label_id
                                        WHERE artist.label_id = 0 and artist.id !=0 
                                          and (artist.deposit_1 < 0 or artist.deposit < 0 or artist.deposit_3 < 0)
                                        ORDER BY `EURO` DESC, `UAH` DESC, `USD` DESC',
                                //'params' => [':status' => 1],
                                'totalCount' => 1,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                            ]),
                            'columns' => [
                                //['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'EURO',
                                    'label' => 'EURO'
                                ],
                                [
                                    'attribute' => 'USD',
                                    'label' => 'USD'
                                ],
                                [
                                    'attribute' => 'UAH',
                                    'label' => 'UAH'
                                ],
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <?php
    //echo '<pre>';
    //print_r(Yii::$app->authManager->getRoles());
    //print_r(Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId()));
    // print_r(Yii::$app->user->identity->role->name);
    //print_r(geoip_record_by_name(Yii::$app->request->userIP));
    //print_r($_SESSION);
    //echo '</pre>';
    //echo geoip_country_code_by_name(Yii::$app->request->userIP);
    //echo geoip_region_by_name(Yii::$app->request->userIP);
    //geoip_region_name_by_code();
    //print_r(geoip_region_name_by_code(geoip_country_code_by_name(Yii::$app->request->userIP), geoip_region_by_name(Yii::$app->request->userIP)));


    //echo '<br>' . geoip_country_name_by_name(Yii::$app->request->userIP);
    ?>
</div>
