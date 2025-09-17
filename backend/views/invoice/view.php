<?php

use backend\models\Artist;
use backend\models\Currency;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\SubLabel;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\widgets\Pjax;

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
        <?= Html::a(Yii::t('app', 'Перерахувати суму'), ['fix-total', 'id' => $model->invoice_id], ['class' => 'btn btn-success']) ?>
        <?php if ($model->invoice_type == InvoiceType::$debit
            && $model->invoice_status_id == InvoiceStatus::Generated
            && abs($model->total - $model->aggregatorReport->total) > 1
        ) {
            echo Html::a(Yii::t('app', 'Аналізувати'), ['analise', 'id' => $model->invoice_id], ['class' => 'btn btn-danger']);
        } else if ($model->invoice_type == InvoiceType::$credit
            && $model->invoice_status_id == InvoiceStatus::InProgress
        ) {
            echo Html::a(Yii::t('app', 'Закрити виплату'), ['calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        } else if ($model->invoice_status_id == InvoiceStatus::Generated) {
            echo Html::a(Yii::t('app', 'Розрахувати'), ['calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        } else if ($model->invoice_type == InvoiceType::$debit
            && $model->invoice_status_id == InvoiceStatus::Calculated
        ) {
            echo Html::a(Yii::t('app', 'Перерахувати'), ['re-calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-warning',  'alt' => 'якщо була зміна відсотків']);
        }

        Html::a(Yii::t('app', 'Видалити інфойс'), ['delete', 'id' => $model->invoice_id], [
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
       // 'total',
        [
            'attribute' => 'total',
            'format' => 'raw',
            'value' => function($data) {
                return !empty($data->aggregatorReport->total) && abs($data->total - $data->aggregatorReport->total) > 1
                    ? '<span class="text-danger">' . $data->total . '</span> (звіт - '  .$data->aggregator_report_id . ', сума: ' . $data->aggregatorReport->total . ')'
                    : $data->total;
            }
        ],
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

    if ($model->invoice_type != 1 && $model->invoice_status_id == InvoiceStatus::Generated) {
        echo $this->render('_formItems', [
            'model' => $modelItems,
            'invoice' => $model,
        ]);
    } ?>

    <?php
    if ($total['total'] != $total['total_artist']) { ?>
        <h5><?=Yii::t('app', 'Дані інвойсу')?></h5>
        <p>
            Доля акртистів: <?=$total['total_artist']?><br>
            Доля лейбу: <?=($total['total']-$total['total_artist'])?><br>
        </p>
   <?php }

   $Art = ArrayHelper::map(InvoiceItems::find()->select(['CONCAT(artist.name, " (", sub_label.name, ")") as name', 'artist.id'])
        ->innerJoin(Artist::tableName(), 'artist.id = invoice_items.artist_id')
        ->innerJoin(SubLabel::tableName(), 'sub_label.id = artist.label_id')
        ->where(['invoice_items.invoice_id' => $model->invoice_id])
        ->asArray()
        ->all(), 'id', 'name');?>

    <?php
        $column = [
           // 'id',
            [
                'attribute' => 'artist_id',
                'format' => 'raw',
                'filter' => Select2::widget([
                    'name' => 'InvoiceItemsSearch[artist_id]',
                    'attribute' => 'artist_id',
                    'language' => 'uk',
                    'data' => $Art,
                    'options' => [
                        // 'multiple' => true,
                        'placeholder' => '...',
                        'options' => isset($_GET['InvoiceItemsSearch']['artist_id']) ? [$_GET['InvoiceItemsSearch']['artist_id'] => ['selected' => true]] : [],
                        //'value' => 8,
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function($data) {
                    $artist = $data->artist_id >= 0 ? Html::a($data->artist->name . ' (' . $data->artist->label->name . ')' , ['artist/view', 'id' => $data->artist->id], ['target'=>'_blank', 'class' => 'linksWithTarget']) : null;
                    if ($data->invoice->invoice_type == InvoiceType::$credit) {
                        switch ($data->invoice->currency_id)
                        {
                            case 1: // EUR
                                if ($data->artist->deposit != 0 || $data->artist->deposit_3 != 0) {
                                    $artist .= ' Баланс:';
                                    $inv = InvoiceItems::find()
                                        ->select(['invoice.invoice_id', 'invoice.currency_id'])
                                        ->innerJoin(\backend\models\Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id 
                                            and invoice.invoice_type = 2 
                                            and invoice.invoice_status_id in (1, 4) 
                                            and invoice.currency_id in (2,3)'
                                        )->where(['invoice_items.artist_id' => $data->artist_id])
                                        ->indexBy('currency_id')
                                        ->column();
                                    if ($data->artist->deposit != 0 && !isset($inv[2])) {
                                        $artist .= ' <span class="text-danger">UAH: '.$data->artist->deposit.'</span>';
                                    }

                                    if ($data->artist->deposit_3 != 0 && !isset($inv[3])) {
                                        $artist .= ' <span class="text-danger">USD: '.$data->artist->deposit_3.'</span>';
                                    }
                                }
                                break;
                            case 2: // UAH
                                if ($data->artist->deposit_1 != 0 || $data->artist->deposit_3 != 0) {
                                    $artist .= ' Баланс:';
                                    $inv = InvoiceItems::find()
                                        ->select(['invoice.invoice_id', 'invoice.currency_id'])
                                        ->innerJoin(\backend\models\Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id 
                                            and invoice.invoice_type = 2 
                                            and invoice.invoice_status_id in (1, 4) 
                                            and invoice.currency_id in (1,3)'
                                        )->where(['invoice_items.artist_id' => $data->artist_id])
                                        ->indexBy('currency_id')
                                        ->column();
                                    if ($data->artist->deposit_1 != 0 && !isset($inv[1])) {
                                        $artist .= ' <span class="text-danger">EUR: '.$data->artist->deposit_1.'</span>';
                                    }

                                    if ($data->artist->deposit_3 != 0 && !isset($inv[3])) {
                                        $artist .= ' <span class="text-danger">USD: '.$data->artist->deposit_3.'</span>';
                                    }
                                }
                                break;
                            case 3: // USD
                                if ($data->artist->deposit != 0 || $data->artist->deposit_1 != 0) {
                                    $artist .= ' Баланс:';
                                    $inv = InvoiceItems::find()
                                        ->select(['invoice.invoice_id', 'invoice.currency_id'])
                                        ->innerJoin(\backend\models\Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id 
                                            and invoice.invoice_type = 2 
                                            and invoice.invoice_status_id in (1, 4) 
                                            and invoice.currency_id in (1,2)'
                                        )->where(['invoice_items.artist_id' => $data->artist_id])
                                        ->indexBy('currency_id')
                                        ->column();
                                    if ($data->artist->deposit_1 != 0 && !isset($inv[1])) {
                                        $artist .= ' <span class="text-danger">EUR: '.$data->artist->deposit_1.'</span>';
                                    }

                                    if ($data->artist->deposit != 0 && !isset($inv[2])) {
                                        $artist .= ' <span class="text-danger">UAH: '.$data->artist->deposit.'</span>';
                                    }
                               }
                                break;
                        }
                    }

                    return $artist;
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
                    'data' => ArrayHelper::map(InvoiceItems::find()->select(['track.name', 'track.id'])
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
                'footer' => number_format(round(abs($total['total']), 4), 4, ',', ''),
            ],
        ]);

    if (in_array($model->currency_id, [Currency::EUR, Currency::USD])) {
        $column = array_merge($column, [
            [
                'attribute' => 'amount_uah',
                'label' => 'UAH',
                'value' => function($data) use ($model) {
                    return number_format(round(round(abs($data->amount), 2) * $model->exchange, 2), 2,',', '');
                },
                'footer' => number_format(round(round(abs($total['total']), 2) * $model->exchange, 3), 3, ',', '')
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

    if ($model->invoice_type == InvoiceType::$credit
        && in_array($model->invoice_status_id, [InvoiceStatus::InProgress, InvoiceStatus::Calculated])
    ) {
        $column =  array_merge($column,[
            [
                'label' => 'Повідомлено',
                'attribute' => 'note',
                'format' => 'raw',
                'filter' => [1 => 'Так'],
                'value' => function ($data) {
                    if ($data->invoice->invoice_status_id == InvoiceStatus::Calculated) {
                        return $data->notified ? '<span class="glyphicon glyphicon-ok text-success"></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                    }

                    return $data->notified
                        ? '<span class="glyphicon glyphicon-ok text-success"></span>'
                        :  (in_array(yii::$app->user->id, [1, 16, 4]) && !empty($data->artist->email)
                            ? Html::a('<span class="glyphicon glyphicon-envelope"></span>', Url::to(['invoice-items/mail', 'id' => $data->id]), [
                                'title' => Yii::t('yii', 'Відправити повідомлення'),
                                'class' => 'btn btn-success btn-xs',
                                //'target' => '_blank',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'right',
                            ])
                            : '<span class="glyphicon glyphicon-remove text-danger"></span>');
                },
            ],
            [
                'label' => 'Підтверджено',
                'attribute' => 'apr',
                'format' => 'raw',
                'filter' => [1 => 'Так'],
                'value' => function ($data) {
                    if ($data->invoice->invoice_status_id == InvoiceStatus::Calculated) {
                        return $data->approved ? '<span class="glyphicon glyphicon-ok text-success"></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                    }

                    return $data->approved ? '<span class="glyphicon glyphicon-ok text-success"></span>' : (in_array(yii::$app->user->id, [1, 14, 16, 4]) ? Html::a('<span class="glyphicon glyphicon-ok"></span>', Url::to(['invoice-items/approve', 'id' => $data->id]), [
                        'title' => Yii::t('yii', 'Підтвердити виплату'),
                        'class' => 'btn btn-success btn-xs',
                        //'target' => '_blank',
                        'data-toggle'=>'tooltip',
                        'data-placement'=>'right',
                    ])  : '<span class="glyphicon glyphicon-remove text-danger"></span>');
                },
                //'contentOptions' => ['class' => $data->approved ? 'success' : ''],
            ],
            [
                'label' => 'Сплачено',
                'attribute' => 'pay',
                'format' => 'raw',
                'filter' => [1 => 'Так'],
                'value' => function ($data) {
                    if ($data->invoice->invoice_status_id == InvoiceStatus::Calculated) {
                        return $data->payed ? '<span class="glyphicon glyphicon-ok text-success"></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                    }

                    return $data->payed ? '<span class="glyphicon glyphicon-ok text-success"></span>' : (in_array(yii::$app->user->id, [1, 14, 4, 16]) && $data->approved ? Html::a('<span class="glyphicon glyphicon-ok"></span>', Url::to(['invoice-items/pay', 'id' => $data->id]), [
                        'title' => Yii::t('yii', 'Підтвердити виплату'),
                        'class' => 'btn btn-warning btn-xs',
                        //'target' => '_blank',
                        'data-toggle'=>'tooltip',
                        'data-placement'=>'right',
                    ]) : '<span class="glyphicon glyphicon-remove text-danger"></span>');
                },
            ]
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

    <?php //Pjax::begin(['id' => 'invoice_items']) ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
        'rowOptions' => function ($item)
        {
            /* @var $item InvoiceItems */
            if($item->invoice->invoice_type == InvoiceType::$credit) {
                if ($item->artist->artist_type_id == 1) { // ФІЗ
                    if (empty($item->artist->contract)
                        || empty($item->artist->full_name)
                        || empty($item->artist->ipn)
                    ) {
                        return ['class' => 'danger'];
                    }
                } else { // TOV
                    if (empty($item->artist->tov_name)
                        || empty($item->artist->contract)
                        || empty($item->artist->full_name)
                        || empty($item->artist->iban)
                    ) {
                        return ['class' => 'danger'];
                    }
                }
            }
        },
        'columns' => array_merge($column,
            [
                [
                    'class' => 'yii\grid\ActionColumn', //'{pdf-act} {export-act} {pdf-balance} {export-balance}', /*
                    'template'=> $model->invoice_type == 2
                    && in_array($model->invoice_status_id, [InvoiceStatus::InProgress, InvoiceStatus::Calculated])
                        ? '{pdf-act} {export-act} {delete}'
                        : (in_array($model->invoice_type, [2,3,4,5]) && $model->invoice_status_id == InvoiceStatus::Generated
                            ? '{delete}' : ''
                        ),
                    'buttons' => [
                            'delete' => function ($url, $item, $key) {
                                return Html::a('<span class="glyphicon glyphicon-trash" style=""></span>', $url, [
                                    'title' => Yii::t('yii', 'Видалити'),
                                    'class' => (in_array(yii::$app->user->id, [1, 16]) && !$item->payed) ? "pjax-delete-link" : "hidden",
                                    'data-confirm' => Yii::t('yii', 'Ви впевнені що бажаєти видалити цей запис з інвойсу?'),
                                    'data-method' => 'post',
                                    'data-toggle'=>'tooltip',
                                    'data-placement'=>'right',
                                    'pjax-container' => 'invoice_items',
                                ]);
                            },
                        'pdf-act' => function ($url, $item, $key) {
                            return Html::a('<span class="glyphicon glyphicon-floppy-save" style="font-size: x-large;"></span>', $url, [
                                'title' => Yii::t('yii', 'Акт-pdf'),
                                'target' => '_blank',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'left',
                            ]);
                        },
                        'export-act' => function ($url, $item, $key) {
                            return Html::a('<span class="glyphicon glyphicon-paste" style="font-size: x-large;margin-left: 20px"></span>', $url, [
                                'title' => Yii::t('yii', 'Звіт-xlsx'),
                                'target' => '_blank',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'top',
                            ]);
                        },
                        'pdf-balance' => function ($url, $item, $key) {
                            return Html::a('<span class="glyphicon glyphicon-save-file" style="font-size: x-large;margin-left: 20px"></span>', $url, [
                                'title' => Yii::t('yii', 'Баланс-pdf'),
                                'target' => '_blank',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'top',
                            ]);
                        },
                        'export-balance' => function ($url, $item, $key) {
                            return Html::a('<span class="glyphicon glyphicon-paste" style="font-size: x-large;margin-left: 20px"></span>', $url, [
                                'title' => Yii::t('yii', 'Баланс-xlsx'),
                                'target' => '_blank',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'right',
                            ]);
                        },
                    ],
                    'urlCreator' => function ($action, $item, $key, $index) {
                        if ($action === 'delete') {
                            return Url::to(['invoice-items/'.$action, 'id' => $item->id, 'url' =>  Url::to(['invoice/view/', 'id' => $item->invoice_id])]);
                        } else if ($action === 'pdf-act'
                            || $action === 'pdf-balance'
                            || $action === 'export'
                            || $action === 'export-balance'
                            || $action === 'export-act'
                        ) {
                            return Url::to(['invoice-items/'.$action, 'id' => $item->id]);
                        }
                    }
                ]
            ]),
    ]); ?>
    <?php //Pjax::end() ?>

</div>
