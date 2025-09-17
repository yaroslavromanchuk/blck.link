<?php

use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */
/* @var $sub_label backend\models\SubLabel */
/* @var $dataProvider backend\models\InvoiceItems */
/* @var $modelItems backend\models\InvoiceItems */
/* @var $searchModel backend\models\InvoiceItemsSearch */
/* @var $total array */

$this->title = $model->invoice_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Сублейби'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Інвойси'), 'url' => ['sub-label/invoice']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="invoice-view">

    <h1><?php // Html::encode($this->title) ?></h1>

    <p>
        <?php Html::a(Yii::t('app', 'Редагувати'), ['invoice-update', 'label_id' => $sub_label->id, 'id' => $model->invoice_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Перерахувати суму'), ['invoice-fix-total', 'label_id' => $sub_label->id, 'id' => $model->invoice_id], ['class' => 'btn btn-success']) ?>

        <?php if ($model->invoice_type == InvoiceType::$credit
            && $model->invoice_status_id == InvoiceStatus::InProgress
        ) {
            echo Html::a(Yii::t('app', 'Закрити виплату'), ['invoice-calculate', 'label_id' => $sub_label->id, 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        } else if ($model->invoice_status_id == InvoiceStatus::Generated) {
            echo Html::a(Yii::t('app', 'Розрахувати'), ['invoice-calculate', 'label_id' => $sub_label->id, 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        }
        ?>
        <?php Html::a(Yii::t('app', 'Видалити інфойс'), ['invoice-delete', 'label_id' => $sub_label->id, 'id' => $model->invoice_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Ви впевнені, що хочете видалити цей інвойс?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?php
    $attributes = [
        //'invoice_id',
        //'invoice_type',
        //'aggregator_id',
        [
            'attribute' => 'quarter',
            'value' => function ($data) {
                return $data->quarter . ' кв. ' . $data->year;
            }
        ],
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
        //'exchange',
        [
            'attribute' => 'exchange',
            'value' => function($data) {
                return  $data->currency_id != 2 ? $data->exchange : '';
            },
        ],
        [
            'attribute' => 'total_2',
            'label' => 'Еквівалентно в UAH',
            'value' => function($data) {
                return $data->currency_id != 2 ? $data->total * $data->exchange : '';
            }
        ],
        [
            'attribute' => 'user_id',
            'value' => function($data) {
                return $data->user->getFullName();
            },
        ],
        'description:text',
        'date_added',
        'last_update',
    ];
    if ($model->invoice_type == 2) {
        $attributes = array_merge($attributes, [
            'date_pay:date',
            'period_from:date',
            'period_to:date',
        ]);
    }

    echo DetailView::widget([
        'model' => $model,
        'attributes' => $attributes,
    ]) ?>

    <?php

    /*if ($model->invoice_type != 1 && $model->invoice_status_id == 1) {
        echo $this->render('_formItems', [
            'model' => $modelItems,
            'invoice' => $model,
        ]);
    } */?>

    <?php
    if ($total['total'] != $total['total_artist']) { ?>
        <h5><?=Yii::t('app', 'Дані інвойсу')?></h5>
        <p>
            Доля акртистів: <?=$total['total_artist']?><br>
            Доля лейбу: <?=($total['total']-$total['total_artist'])?><br>
        </p>
    <?php }

    $Art = ArrayHelper::map(\backend\models\InvoiceItems::find()->select(['artist.name as name', 'artist.id'])
        ->innerJoin(\backend\models\Artist::tableName(), 'artist.id = invoice_items.artist_id')
        ->innerJoin(\backend\models\SubLabel::tableName(), 'sub_label.id = artist.label_id')
        ->innerJoin(\backend\models\Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
        ->where(['invoice_items.invoice_id' => $model->invoice_id, 'invoice.label_id' => $sub_label->id])
        ->asArray()
        ->all(), 'id', 'name');?>

    <?php
    $column = [
        // 'id',
        [
            'attribute' => 'artist_id',
            'format' => 'raw',
            'value' => function($data) {
                return $data->artist_id >= 0 ? Html::a($data->artist->name . ' (' . $data->artist->label->name . ')' , ['artist/view', 'id' => $data->artist->id], ['target'=>'_blank', 'class' => 'linksWithTarget']): null;
            },
        ],
    ];
    if (in_array($model->invoice_type, [1, 3])) {
        $column = array_merge($column, [
            [
                'attribute' => 'track_id',
                'filter' => Select2::widget([
                    'name' => 'InvoiceItemsSearch[track_id]',
                    'attribute' => 'track_id',
                    'language' => 'uk',
                    'data' => ArrayHelper::map(\backend\models\InvoiceItems::find()->select(['track.name', 'track.id'])
                        ->innerJoin(\backend\models\Track::tableName(), 'track.id = invoice_items.track_id')
                        ->where(['invoice_items.invoice_id' => $model->invoice_id])
                        ->asArray()
                        ->all(), 'id', 'name'),
                    'options' => [
                        // 'multiple' => true,
                        'placeholder' => '...',
                        'options' => isset($_GET['InvoiceItemsSearch']['track_id']) ? [$_GET['InvoiceItemsSearch']['track_id'] => ['selected' => true]] : [],
                        //'value' => 8,
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function($data) {
                    return $data->track_id > 0 ? $data->track->name : null;
                },
            ],
            'isrc',
        ]);
    }

    $column = array_merge($column, [
        [
            'attribute' => 'amount',
            'value' => function ($data) use ($model) {
                return number_format(abs($data->amount), 2, ',', '');
            },
            'footer' => number_format(round(abs($total['total']), 4), 2, ',', ''),
        ],
    ]);

    if ($model->currency_id == 1) {
        $column = array_merge($column, [
            [
                'attribute' => 'amount_uah',
                'label' => 'UAH',
                'value' => function($data) use ($model) {
                    return ($model->currency_id == 1) ? number_format(round(abs($data->amount), 2) * $model->exchange, 2, ',', '') : null; /*number_format(abs($data->amount) * $model->exchange, 2, ',', '')*/
                },
                'footer' => number_format(round(abs($total['total']), 2) * $model->exchange, 2, ',', '')
            ],
        ]);
    } else if ($model->currency_id == 3) {
        $column = array_merge($column, [
            [
                'attribute' => 'amount_uah',
                'label' => 'UAH',
                'value' => function($data) use ($model) {
                    return ($model->currency_id == 3) ? number_format(round(abs($data->amount), 2) * $model->exchange, 2, ',', '') : null; /*number_format(abs($data->amount) * $model->exchange, 2, ',', '')*/
                },
                'footer' => number_format(round(abs($total['total']), 3) * $model->exchange, 4, ',', '')
            ],
        ]);
    }
    if ($model->invoice_type == 1) {
        $column = array_merge($column, [
            'percentage',
        ]);
    }
    if ($model->invoice_type != 2) {
        $column = array_merge($column, [
            'date_item:date',
            'description:text',
        ]);
    }
    ?>

    <?php
    $this->registerJs("
     $(document).on('ready pjax:success', function() {
         $('.pjax-delete-link').on('click', function(e) {
             e.preventDefault();
             var deleteUrl = $(this).attr('delete-url');
             var pjaxContainer = $(this).attr('pjax-container');
             var result = confirm('Delete this item, are you sure?');                                
             if(result) {
                 $.ajax({
                     url: deleteUrl,
                     type: 'post',
                     error: function(xhr, status, error) {
                         alert('There was an error with your request.' + xhr.responseText);
                     }
                 }).done(function(data) {
                     $.pjax.reload('#' + $.trim(pjaxContainer), {timeout: 3000});
                 });
             }
         });

     });
 ");
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
        'columns' => $column,
    ]); ?>
</div>
