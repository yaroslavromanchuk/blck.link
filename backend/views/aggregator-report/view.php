<?php

use backend\models\AggregatorReportStatus;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReport */
/* @var $items backend\models\AggregatorReportItem */
/* @var $searchModel backend\models\AggregatorReportItemSearch */
/* @var $loaded array */
/* @var $perc integer */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Звіти'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="aggregator-report-view">

    <h1>№ <?= Html::encode($this->title) ?></h1>
    <p>
       <?php

        if ($model->report_status_id == AggregatorReportStatus::LOADED) {
            echo Html::a(Yii::t('app', 'Згенерувати інвойс'), ['generate-invoice', 'id' => $model->id], ['class' => 'btn btn-success']);
        } else if ($model->report_status_id == AggregatorReportStatus::CONFLICT) {
            echo Html::a(Yii::t('app', 'Догенерувати інвойс'), ['generate-invoice', 'id' => $model->id], ['class' => 'btn btn-info']);

        }
        if ($model->report_status_id == AggregatorReportStatus::LOADED) {
            echo Html::a(Yii::t('app', 'Видалити'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Ви впевнені що хочете видалити цей звіт?'),
                    'method' => 'post',
                ],
            ]);
        }  ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            //'aggregator_id',
            [
                'attribute' => 'aggregator_id',
                'value' => function($data) {
                    return $data->aggregator->name;
                },
            ],
            [
               // 'attribute' => 'aggregator_id',
                'label' => 'Валюта',
                'value' => function($data) {
                    return $data->aggregator->currency->name;
                },
            ],
            //'report_status_id',
            [
                'attribute' => 'report_status_id',
                'value' => function($data) {
                    return $data->reportStatus->name;
                },
            ],
            'total',
            [
                'attribute' => 'user_id',
                'value' => function($data) {
                    return $data->user->getFullName();
                },
            ],
            'description:ntext',
            //'user_id',
            'date_added',
            'last_update',
            [
                //'attribute' => 'report_status_id',
                'label' => 'Знайдено треків',
                'value' => $perc . ' %',
            ],
        ],
    ]) ?>

    <h5><?=Yii::t('app', 'Дані звіту')?></h5>
    <?= GridView::widget([
        'dataProvider' => $items['dataProvider'],
        'filterModel' => $searchModel,
        'rowOptions' => function ($model) use ($loaded)
        {
            if(in_array(str_replace('-', '', $model->isrc), $loaded)) {
                return ['class' => 'success'];
            } else {
                return ['class' => 'danger'];
            }
        },
        'columns' => [
            'isrc',
            'platform',
            'count',
            'amount',
            'date_report',
        ]
    ]); ?>

</div>
