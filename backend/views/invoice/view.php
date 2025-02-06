<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */
/* @var $dataProvider backend\models\InvoiceItems */
/* @var $modelItems backend\models\InvoiceItems */
/* @var $searchModel backend\models\InvoiceItemsSearch */
/* @var $total array */

$this->title = $model->invoice_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Інвойси'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="invoice-view">

    <h1><?php // Html::encode($this->title) ?></h1>

    <p>
        <?php Html::a(Yii::t('app', 'Редагувати'), ['update', 'id' => $model->invoice_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Перерахувати суму'), ['re-calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-success']) ?>
        <?php if ($model->invoice_status_id == 1) {
            echo Html::a(Yii::t('app', 'Розрахувати'), ['calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        }
        ?>
        <?php Html::a(Yii::t('app', 'Видалити інфойс'), ['delete', 'id' => $model->invoice_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Ви впевнені, що хочете видалити цей інвойс?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
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
                    return  ($data->currency_id == 1) ? $data->exchange : '';
                },
            ],
            [
                    'attribute' => 'total_2',
                    'label' => 'Еквівалентно в UAH',
                    'value' => function($data) {
                        return ($data->currency_id == 1) ? $data->total * $data->exchange : '';
                    }
            ],
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

    <?php

    if ($model->invoice_type != 1 && $model->invoice_status_id == 1) {
        echo $this->render('_formItems', [
            'model' => $modelItems,
            'invoice' => $model,
        ]);
    }
    ?>

    <h5><?=Yii::t('app', 'Дані інвойсу')?></h5>
    <p>
        Доля акртиста: <?=$total['total_artist']?><br>
        Доля лейбу: <?=($total['total']-$total['total_artist'])?><br>
    </p>

    <?php
        $column = [
           // 'id',
            [
                'attribute' => 'artist_id',
                'format' => 'raw',
                'value' => function($data) {
                    return $data->artist_id >= 0 ? Html::a($data->artist->name, ['artist/view', 'id' => $data->artist->id], ['target'=>'_blank', 'class' => 'linksWithTarget']): null;
                },
            ],
        ];
    if (in_array($model->invoice_type, [1, 3])) {
        $column = array_merge($column, [
            [
                'attribute' => 'track_id',
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
                        return number_format(abs($data->amount), 3, ',', '');
                    },
                'footer' => number_format(round(abs($total['total']), 3), 4, ',', ''),
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
                'footer' => number_format(round(abs($total['total']), 3) * $model->exchange, 4, ',', '')
            ],
        ]);
    }
        $column = array_merge($column, [
            'date_item:date',
            'description:text',
        ]);
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
        'rowOptions' => function ($model)
        {
            if($model->invoice->invoice_type == 2) {
                if ($model->artist->artist_type_id == 1) {
                    if (empty($model->artist->contract) || empty($model->artist->full_name)) {
                        return ['class' => 'danger'];
                    }
                } else {
                    if (empty($model->artist->tov_name) || empty($model->artist->contract) || empty($model->artist->full_name)) {
                        return ['class' => 'danger'];
                    }
                }
            }
        },
        'columns' => array_merge($column,
            [
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template'=> $model->invoice_type == 2 ? '{pdf-act} {export-act} {pdf-balance} {export-balance} {delete}' : (in_array($model->invoice_type, [3,4,5]) && $model->invoice_status_id == 1 ? '{delete}' : ''),
                    'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::a('<span class="glyphicon glyphicon-trash" style=""></span>', $url, [

                                    'title' => Yii::t('yii', 'Видалити'),

                                    'data-confirm' => Yii::t('yii', 'Ви впевнені що бажаєти видалити цей запис з інвойсу?'),

                                    'data-method' => 'post',
                                ]);
                            },
                        'pdf-act' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-floppy-save" style="font-size: x-large;"></span>', $url, [

                                'title' => Yii::t('yii', 'Акт'),
                                'target' => '_blank'
                            ]);
                        },
                        'export-act' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-paste" style="font-size: x-large;margin-left: 20px"></span>', $url, [

                                'title' => Yii::t('yii', 'Export Act'),
                                'target' => '_blank'
                            ]);
                        },
                        'pdf-balance' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-save-file" style="font-size: x-large;margin-left: 20px"></span>', $url, [

                                'title' => Yii::t('yii', 'Звіт балансу'),
                                'target' => '_blank'
                            ]);
                        },
                        'export-balance' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-paste" style="font-size: x-large;margin-left: 20px"></span>', $url, [

                                'title' => Yii::t('yii', 'Export Balance'),
                                'target' => '_blank'
                            ]);
                        },
                    ],
                    'urlCreator' => function ($action, $model, $key, $index) {
                        if ($action === 'delete') {
                            return Url::to(['invoice-items/'.$action, 'id' => $model->id, 'url' =>  Url::to(['invoice/view/', 'id' => $model->invoice_id])]);
                        } else if ($action === 'pdf-act'
                            || $action === 'pdf-balance'
                            || $action === 'export'
                            || $action === 'export-balance'
                        ) {
                            return Url::to(['invoice-items/'.$action, 'id' => $model->id]);
                        }
                    }
                ]
            ]),
    ]); ?>

</div>
