<?php

use backend\widgets\DateFormat;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\AggregatorReportSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Звіти');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-report-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Завантажити звіт'), ['aggregator/upload-report'], ['class' => 'btn btn-success']) ?>
        <?// Html::a(Yii::t('app', 'Create Aggregator Report'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model)
        {
            switch ($model->report_status_id) {
                case 1:  return ['class' => 'warning'];
                case 2:  return ['class' => 'success'];
                case 3:  return ['class' => 'info'];
                case 4:  return ['class' => 'danger'];
            }
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'aggregator_id',
                'value' => function($data) {
                    return $data->aggregator->name;
                },
            ],
            [
                'attribute' => 'report_status_id',
                'value' => function($data) {
                    return $data->reportStatus->name;
                },
            ],
            'total',
            [
                'attribute' => 'currency_id',
                'filter'=> [1 => 'EURO', 2 =>'UAH'],
                'value' => function($data) {
                    return $data->aggregator->currency->name;
                },
            ],
            [
                'attribute' => 'user_id',
                'value' => function($data) {
                    return $data->user->getFullName();
                },
            ],
            'date_added',
            [
                'attribute' => 'date_added',
                'label' => 'Квартал',
                'value' => function($data) {
                    return $data->quarter . ' кв. ' . $data->year; //DateFormat::getQuarterText($data->date_added);
                }
            ],
            //'last_update',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
