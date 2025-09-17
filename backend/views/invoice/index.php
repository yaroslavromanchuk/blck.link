<?php

use backend\widgets\DateFormat;
use common\models\SubLabel;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
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

    <?= Html::a(Yii::t('app', 'Створити інвойс'), ['create'], ['class' => 'btn btn-success']) ?>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php
    $selected = [];

    if(isset($_GET['InvoiceSearch']['label_id'])) {
        $selected[$_GET['InvoiceSearch']['label_id']] = ['selected' => true];
    }
    ?>

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
            [
                'attribute' => 'aggregator_report_id',
                'label' => 'Звіт №',
				'value' => function($data) {
					return $data->aggregator_report_id;
				}
            ],
            /*[
                'attribute' => 'label_id',
                'format' => 'raw',
                'filter' => ArrayHelper::map(SubLabel::find()->where(['active' => 1])->asArray()->all(), 'id', 'name'),

                'value' => function($data) {
                    return $data->label->name;
                },
            ],*/
            //'invoice_type',
            [
                'attribute' => 'invoice_type',
                'filter'=> [1 => 'Надходження', 2 =>'Виплата', 3 => 'Витрати', 4 => 'Аванс', 5 => 'Баланс'],
                'value' => function($data) {
                    return $data->invoiceType->invoice_type_name;
                },
            ],
            [
                'attribute' => 'invoice_status_id',
                'filter'=> [1 => 'Новий', 2 =>"Проведений", 3=>"Помилка", 4=>'В процесі виплати'],
                'value' => function($data) {
                    return $data->invoiceStatus->invoice_status_name;
                },
            ],
            [
                'attribute' => 'aggregator_id',
                'filter'=> ArrayHelper::map(\backend\models\Aggregator::find()->asArray()->all(), 'aggregator_id', 'name'),
                'value' => function($data) {
                    return $data->aggregator->name;
                },
            ],
            [
                'attribute' => 'currency_id',
                'filter'=> [1 => 'EUR', 2 =>'UAH', 3 => 'USD'],
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
            'date_added:date',
            [
                'attribute' => 'quarter',
               // 'label' => 'Квартал',
                'filter'=> [1 => '1 кв.', 2 =>'2 кв.', 3 => '3 кв.', 4 => '4 кв.'],
                'value' => function($data) {
                    return $data->quarter . ' кв.'; //DateFormat::getQuarterText($data->date_added);
                }
            ],
            [
                'attribute' => 'year',
                'filter'=> [2024 => 2024, 2025 =>2025],
            ],
            'description:text',
            //'last_update',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => in_array(yii::$app->user->id, [1, 16])? '{view} {update} {export-to-excel} {export-to-pdf-act-for-sub-label} {export-to-excel-report-for-sub-label} {delete}' : '{view} {export-to-excel} {export-to-pdf-act-for-sub-label} {export-to-excel-report-for-sub-label}'  ,// {update}  {delete} | {view-report} {export} {pdf}
                'buttons' => [
                    'view-report' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-indent-left"></span>', $url, [
                            'title' => Yii::t('yii', 'Звіт по доходу'),
                            'data-pjax' => 0,
                            'style' => $model->invoice_type != 1 ?'display:none; margin-left:5px;' : 'margin-left:5px;',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
                        ]);
                    },
                   'export' => function ($url, $model) {
                       return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [

                           'title' => Yii::t('yii', 'Export report to xlsx'),
                           'data-pjax' => 0,
                           'style' => !in_array($model->invoice_type, [1,2]) ?'display:none;margin-left:5px;' : 'margin-left:5px;',
                           'data-toggle'=>'tooltip',
                           'data-placement'=>'left',
                       ]);
                   },
                   'export-to-excel' => function ($url, $model) {
                       return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [
                           'title' => Yii::t('yii', 'Звіт по артистам в xlsx'),
                          // 'target' => '_blank',
                           'style' => $model->invoice_type != 1 ? 'display:none;margin-left:5px;' : 'margin-left:5px;',
                           'data-toggle'=>'tooltip',
                           'data-placement'=>'left',
                       ]);
                   },
                    'export-to-pdf-act-for-sub-label' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [
                            'title' => Yii::t('yii', 'Акт для СубЛейба в pdf'),
                            // 'target' => '_blank',
                            'style' => ($model->label_id != 0 && $model->invoice_type == 2 && $model->invoice_status_id == 2) ? 'margin-left:5px;' : 'display:none;margin-left:5px;',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
                        ]);
                    },
                    'export-to-excel-report-for-sub-label' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [
                            'title' => Yii::t('yii', 'Звіт для СубЛейба в xlsx'),
                            // 'target' => '_blank',
                            'style' => ($model->label_id != 0 && $model->invoice_type == 2 && $model->invoice_status_id == 2) ? 'margin-left:5px;' : 'display:none;margin-left:5px;',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
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
