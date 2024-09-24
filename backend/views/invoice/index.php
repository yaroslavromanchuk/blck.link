<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\InvoiceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Інвойси');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Створити інвойс'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $key, $index, $grid)
        {
            switch ($model->invoice_status_id) {
                case 1:  return ['class' => 'warning'];
                case 2:  return ['class' => 'success'];
                case 3:  return ['class' => 'danger'];
            }
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'invoice_id',
            //'invoice_type',
            [
                'attribute' => 'invoice_type',
                'filter'=> [1 => 'Надходження', 2 =>'Виплата', 3 => 'Витрати'],
                'value' => function($data) {
                    return $data->invoiceType->invoice_type_name;
                },
            ],
            [
                'attribute' => 'invoice_status_id',
                'filter'=> [1 => 'Згенерований', 2 =>"Розрахований", 3=>"Помилка"],
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
            //[
            //    'attribute' => 'ownership_type',
            //    'value' => function($data) {
            //        return $data->aggregator->ownershipType->name;
            //    },
            //],
            [
                'attribute' => 'currency_id',
                'filter'=> [1 => 'EURO', 2 =>'UAH'],
                'value' => function($data) {
                    return $data->currency->name;
                },
            ],
            //'invoice_status_id',
           //'aggregator_id',
            //'currency_id',
            'total',
            [
                'attribute' => 'user_id',
                'value' => function($data) {
                    return $data->user->getFullName();
                },
            ],
            'date_added',
            //'last_update',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => $searchModel->invoice_status_id != 1 ? '{view}  {update}  {delete}' : '{view}  {view-report}  {export}',
               'buttons' => [
                        'view-report' => function ($url, $model) {
                            return Html::a('<span class="glyphicon glyphicon-indent-left"></span>', $url, [

                                'title' => Yii::t('yii', 'Звіт по доходу'),
                                'data-pjax' => 0,
                                'style' => $model->invoice_type != 1 ?'display:none; margin-left:5px;' : 'margin-left:5px;',
                            ]);
                    },
                   'export' => function ($url, $model) {
                       return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [

                           'title' => Yii::t('yii', 'Export report'),
                           'data-pjax' => 0,
                           'style' => $model->invoice_type != 1 ?'display:none;margin-left:5px;' : 'margin-left:5px;',
                       ]);
                   },

                ],
                //'urlCreator' => function ($action, $model, $key, $index) {
                  //  return Url::to(['invoice/'.$action, 'id' => $model->invoice_id]);
               // }
            ],
        ],
    ]); ?>


</div>
