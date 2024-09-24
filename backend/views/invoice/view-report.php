<?php

use yii\data\SqlDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */

$this->title = $model->invoice_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Інвойси'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<p>
    <?= Html::a(Yii::t('app', 'Export звіту'), ['export', 'id' => $model->invoice_id], ['class' => 'btn btn-success']) ?>
</p>
<div class="invoice-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'invoice_id',
            //'invoice_type',
            //'aggregator_id',
            //'currency_id',
            [
                'attribute' => 'invoice_type',
                'value' => function($data) {
                    return $data->invoiceType->invoice_type_name;
                },
            ],
            [
                'attribute' => 'invoice_status_id',
                'value' => function($data) {
                    return $data->invoiceStatus->invoice_status_name;
                },
            ],
            [
                'attribute' => 'aggregator_id',
                'value' => function($data) {
                    return $data->aggregator->name;
                },
            ],
            [
                'attribute' => 'currency_id',
                'value' => function($data) {
                    return $data->currency->name;
                },
            ],
            'total',
            [
                'attribute' => 'user_id',
                'value' => function($data) {
                    return $data->user->getFullName();
                },
            ],
            'date_added',
            'last_update',
        ],
    ]) ?>

    <h5><?=Yii::t('app', 'Дохід артистів')?></h5>
    <?php
    $dataProvider = new SqlDataProvider([
        'sql' => 'SELECT ss.* FROM (SELECT it.`artist_id`, a.name as artist_name, t.name as track_name, sum(it.amount) as summ, sum(DISTINCT(tt.am)) as all_summ FROM `invoice_items` it LEFT JOIN ( SELECT track_id, artist_id, invoice_id, sum(amount) as am FROM `invoice_items` WHERE invoice_id=:invoice_id GROUP BY track_id) as tt ON tt.track_id = it.track_id LEFT JOIN artist as a ON a.id = it.artist_id LEFT JOIN track as t ON t.id = it.track_id WHERE it.invoice_id =:invoice_id GROUP BY it.artist_id, it.track_id) as ss WHERE ss.artist_id != 0 ORDER BY `ss`.`track_name` ASC',
        'params' => [':invoice_id' => $model->invoice_id],
       // 'totalCount' => $count,
        'sort' => [
            'attributes' => [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Name',
                ],
            ],
        ],
        //'pagination' => [
           // 'pageSize' => 20,
       // ],
    ]);
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'artist_name',
                'label' => 'Артист',
                'value' => function($data) {
                    return $data['artist_name'];
                },
            ],
            [
                'attribute' => 'track_name',
                'label' => 'Трек',
                'value' => function($data) {
                    return $data['track_name'];
                },
            ],
            [
                'attribute' => 'summ',
                'label' => 'Дохід виконавця',
                'value' => function($data) {
                    return $data['summ'];
                },
            ],
            [
                'attribute' => 'all_summ',
                'label' => 'Загальний дохід',
                'value' => function($data) {
                    return $data['all_summ'];
                },
            ],
        ],
    ]); ?>

</div>
</br>
