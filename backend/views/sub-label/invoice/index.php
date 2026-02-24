<?php

use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\widgets\DateFormat;
use common\models\SubLabel;
use kartik\select2\Select2;
use backend\models\InvoiceLogType;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $invoiceSearchModel backend\models\InvoiceSearch */
/* @var $invoiceDataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Інвойси');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Сублейби'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php
    $selected = [];

    if(isset($_GET['InvoiceSearch']['label_id'])) {
        $selected[$_GET['InvoiceSearch']['label_id']] = ['selected' => true];
    }

    $column = [
        ['class' => 'yii\grid\SerialColumn'],
    ];

    $column =  array_merge($column,[
        'invoice_id',
        [
            'attribute' => 'label_id',
            'format' => 'raw',
            'filter' => ArrayHelper::map(SubLabel::find()
                ->where(['active' => 1])
                ->andFilterCompare('id', '>0')
                //->andWhere(['label_id', '!=', 0])
                ->asArray()
                ->all(), 'id', 'name'),
            'value' => function($data) {
                return $data->label->name;
            },
        ],
        [
            'attribute' => 'invoice_type',
           // 'filter'=> [1 => 'Надходження', 2 =>'Виплата', 3 => 'Витрати', 4 => 'Аванс', 5 => 'Баланс'],
            'value' => function($data) {
                return $data->invoiceType->invoice_type_name;
            },
        ],
        [
            'attribute' => 'invoice_status_id',
            'filter'=> [1 => 'Новий', 2 =>"Проведений", 3=>"Помилка", 4 => "В процесі виплати"],
            'value' => function($data) {
                return $data->invoiceStatus->invoice_status_name;
            },
        ],
        /*[
            'attribute' => 'aggregator_id',
            'filter'=> ArrayHelper::map(\backend\models\Aggregator::find()->asArray()->all(), 'aggregator_id', 'name'),
            'value' => function($data) {
                return $data->aggregator->name;
            },
        ],*/
        [
            'attribute' => 'currency_id',
            'filter'=> [1 => 'EURO', 2 =>'UAH', 3 => 'USD'],
            'value' => function($data) {
                return $data->currency->name;
            },
        ],
        [
            'attribute' => 'total',
            'format' => 'raw',
            'value' => function($data) {
				return round($data->total, 2);
			}
        ],
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
            'filter'=> array_combine(range(2024, (int)date('Y')), range(2024, (int)date('Y'))),
        ],
        'description:text',
        //'last_update',
    ]);

    $column =  array_merge($column,[
        [
            'label' => 'Повідомлено',
            'attribute' => 'note',
            'format' => 'raw',
            'contentOptions' => function ($model, $key, $index, $column) {
                return [
                    'id' => "td-notified-" . $model->invoice_id,    // довільний id, якщо потрібно
                ];
            },
            'filter' => [-1 => 'Ні', 1 => 'Так'],
            'value' => function ($data) {
                if ($data->invoice_type != InvoiceType::$credit) {
                    return '';
                }

                if ($data->invoice_status_id != InvoiceStatus::InProgress) {
                    return $data->notified ? '<span class="glyphicon glyphicon-ok text-success"></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                }
                
                $result = "";
                
                if ($data->notified) {
                    $logs = $data->getInvoiceLogs(InvoiceLogType::EMAIL)->all();
                    $titleLog = "Відправлено на email: {$data->label->email} в такі дати:\n";
                    
                   foreach ($logs as $log) {
                        $titleLog .= date('d.m.Y H:i:s', strtotime($log->date_added)) . "\n";
                   }
                    
                    return '<span class="glyphicon glyphicon-ok text-success" data-toggle="tooltip" data-placement="top" data-title=" ' . $titleLog. '"></span>' . (in_array(yii::$app->user->id, [1, 16])
                            ? ' ' . Html::a('<span class="glyphicon glyphicon-repeat"></span>', Url::to(['invoice-items/mail', 'id' => $data->invoice_id]), [
                                'title' => Yii::t('yii', 'Відправити ще раз'),
                                'class' => 'btn btn-warning btn-xs',
                                'data-toggle'=>'tooltip',
                                'data-placement'=>'right',
                                'data-id' => $data->invoice_id,
                            ])
                            : ''
                        );
                } else {
                    return (in_array(yii::$app->user->id, [1, 16, 4]) && !empty($data->label->email)
                        ? Html::a('<span class="glyphicon glyphicon-envelope"></span>', Url::to(['sub-label/invoice-mail', 'id' => $data->invoice_id]), [
                            'title' => Yii::t('yii', 'Відправити повідомлення'),
                            'class' => 'btn btn-success btn-xs',
                            //'target' => '_blank',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'right',
                            'data-id' => $data->invoice_id,
                        ])
                        : '<span class="glyphicon glyphicon-remove text-danger"></span>');
                }
                
                return $result;
            },
        ],
        [
            'label' => 'Підтверджено',
            'attribute' => 'apr',
            'format' => 'raw',
            'contentOptions' => function ($model, $key, $index, $column) {
                return [
                    'id' => "td-approved-" . $model->invoice_id,    // довільний id, якщо потрібно
                ];
            },
            'filter' => [-1 => 'Ні', 1 => 'Так'],
            'value' => function ($data) {
                if ($data->invoice_type != InvoiceType::$credit) {
                    return '';
                }

                if ($data->invoice_status_id != InvoiceStatus::InProgress) {
                    return $data->approved ? '<span class="glyphicon glyphicon-ok text-success" ></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                }

                return $data->approved ? '<span class="glyphicon glyphicon-ok text-success"></span>' : (in_array(yii::$app->user->id, [1, 14, 16, 4]) ? Html::a('<span class="glyphicon glyphicon-ok"></span>', Url::to(['sub-label/invoice-approve', 'id' => $data->invoice_id]), [
                    'title' => Yii::t('yii', 'Підтвердити виплату'),
                    'class' => 'btn btn-success btn-xs',
                    //'target' => '_blank',
                    'data-toggle'=>'tooltip',
                    'data-placement'=>'right',
                    'data-id' => $data->invoice_id,
                ])  : '<span class="glyphicon glyphicon-remove text-danger"></span>');
            },
            //'contentOptions' => ['class' => $data->approved ? 'success' : ''],
        ],
        [
            'label' => 'Сплачено',
            'attribute' => 'pay',
            'format' => 'raw',
            'contentOptions' => function ($model, $key, $index, $column) {
                return [
                    'id' => "td-payed-" . $model->invoice_id,    // довільний id, якщо потрібно
                ];
            },
            'filter' => [-1 => 'Ні', 1 => 'Так'],
            'value' => function ($data) {
                if ($data->invoice_type != InvoiceType::$credit) {
                    return '';
                }

                if ($data->invoice_status_id != InvoiceStatus::InProgress) {
                    return $data->payed ? '<span class="glyphicon glyphicon-ok text-success"></span>' : '<span class="glyphicon glyphicon-remove text-danger"></span>';
                }

                return $data->payed ? '<span class="glyphicon glyphicon-ok text-success"></span>' : (in_array(yii::$app->user->id, [1, 14, 4, 16]) && $data->approved
                    ? Html::a('<span class="glyphicon glyphicon-ok"></span>', Url::to(['sub-label/invoice-pay', 'id' => $data->invoice_id]), [
                    'title' => Yii::t('yii', 'Підтвердити виплату'),
                    'class' => 'btn btn-warning btn-xs',
                    //'target' => '_blank',
                    'data-toggle'=>'tooltip',
                    'data-placement'=>'right',
                    'data-id' => $data->invoice_id,
                ]) : '<span class="glyphicon glyphicon-remove text-danger"></span>');
            },
        ]
    ]);

    ?>

    <?= GridView::widget([
        'dataProvider' => $invoiceDataProvider,
        'filterModel' => $invoiceSearchModel,
        'rowOptions' => function ($item)
        {
            /* @var $item Invoice */
            if($item->invoice_type == 2) {
                if ($item->label->label_type_id == 1) { // ФІЗ
                    if (empty($item->label->contract)
                        || empty($item->label->full_name)
                        || empty($item->label->ipn)
                    ) {
                        return ['class' => 'danger'];
                    }
                } else { // TOV
                    if (empty($item->label->tov_name)
                        || empty($item->label->contract)
                        || empty($item->label->full_name)
                        || empty($item->label->iban)
                    ) {
                        return ['class' => 'danger'];
                    }
                }
            }
        },
        'columns' => array_merge($column,
            [
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => in_array(yii::$app->user->id, [1, 16])? '{view} {update} {export-to-pdf-act} {export-to-excel-report} {delete}' : '{view} {export-to-pdf-act} {export-to-excel-report}'  ,// {update}  {delete} | {view-report} {export} {pdf}
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                            'title' => Yii::t('yii', 'Переглянути'),
                            'data-pjax' => 0,
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
                        ]);
                    },
                    'export-to-pdf-act' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [
                            'title' => Yii::t('yii', 'Акт для СубЛейба в pdf'),
                            // 'target' => '_blank',
                            'style' => ($model->invoice_type == 2 && in_array($model->invoice_status_id,[2, 4])) ? 'margin-left:5px;' : 'display:none;margin-left:5px;',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
                        ]);
                    },
                    'export-to-excel-report' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-cloud-download"></span>', $url, [
                            'title' => Yii::t('yii', 'Звіт для СубЛейба в xlsx'),
                            // 'target' => '_blank',
                            'style' => ($model->invoice_type == 2 && in_array($model->invoice_status_id,[2, 4])) ? 'margin-left:5px;' : 'display:none;margin-left:5px;',
                            'data-toggle'=>'tooltip',
                            'data-placement'=>'left',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::to(['sub-label/invoice-'.$action, 'label_id' => $model->label_id, 'id' => $model->invoice_id]);
                }
            ],
        ]),
    ]); ?>
</div>

<?php
$this->registerJs("
$(document).on('click', '.btn-notify', function (e) {
e.preventDefault();
let id = $(this).data('id');
let key = 'td' + id;

    $.ajax({
        url: '/sub-label/invoice-mail/?id='+id, // ваш екшн
        type: 'GET',
        success: function (response) {
            // Оновлюємо лише потрібний рядок у GridView
            $('#td-notified-' + id).html(response);
        },
        error: function () {
            alert('Помилка при відправці повідомлення!');
        }
    });
    
    return false;
});

$(document).on('click', '.btn-approved', function (e) {
e.preventDefault();
let id = $(this).data('id');
let key = 'td' + id;

    $.ajax({
        url: '/sub-label/invoice-approve/?id='+id,
        type: 'GET',
        success: function (response) {
            // Оновлюємо лише потрібний рядок у GridView
            $('#td-approved-' + id).html(response);
        },
        error: function () {
            alert('Помилка при підтвердженні!');
        }
    });
    
    return false;
});

$(document).on('click', '.btn-payed', function (e) {
        e.preventDefault();
       
        let link = $(this);
        let url = link.data('url') || link.attr('href');
        let id = link.data('id');
        let key = 'td' + id;

        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                // Оновлюємо лише потрібний рядок у GridView
                $('#td-payed-' + id).html(response);
            },
            error: function () {
                alert('Помилка при підтвердженні!');
            }
        });

        return false;
    });
");
?>
