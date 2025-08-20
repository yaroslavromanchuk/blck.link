<?php

use  yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\SubLabel */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sub Labels'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$query = new \yii\db\Query();
$invoice = new ActiveDataProvider([
    'query' => $query->from('invoice')
        ->select(['invoice.invoice_id, CONCAT(invoice.quarter, " кв. ", invoice.year) as quarter, invoice.date_added,
          currency.currency_name, invoice_items.artist_id,
         invoice.invoice_type, track.name as track_name, invoice_items.platform, CONCAT(invoice_type.invoice_type_name, " (", aggregator.name, ")") as invoice_type_name,
          SUM(invoice_items.count) as count, SUM(invoice_items.amount) as total'])
        ->leftJoin('invoice_items', 'invoice_items.invoice_id = invoice.invoice_id')
        ->leftJoin('invoice_type', 'invoice_type.invoice_type_id = invoice.invoice_type')
        ->leftJoin('track', 'track.id = invoice_items.track_id')
        ->leftJoin('artist', 'artist.id = invoice_items.artist_id')
        ->leftJoin('currency', 'currency.currency_id = invoice.currency_id')
        ->leftJoin('aggregator', 'aggregator.aggregator_id = invoice.aggregator_id')
        ->where(['invoice.label_id' => $model->id, 'invoice.invoice_status_id' => 2])
        ->orWhere(['and',
            ['artist.label_id'=>$model->id],
           // ['enable_social'=>1]
        ])
        ->orderBy('invoice.invoice_id DESC')
        ->groupBy(['invoice.invoice_id']),
    'pagination' => [
        'pageSize' => 10,
    ],
]);

$query = new \yii\db\Query();
$artist = new ActiveDataProvider([
    'query' => $query->from('artist')
        ->select('id, name, deposit as uah, deposit_1 as euro, deposit_3 as usd')
        ->where(['label_id' => $model->id]),
    'pagination' => [
        'pageSize' => 10,
    ],
]);

?>
<div class="sub-label-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>

        <?php
        if (Yii::$app->user->can('admin')) {
            echo Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]);
        }
        ?>
    </p>
    <div class="row">
        <div class="col-xs-12 col-md-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    // 'id',
                    //'user_id',
                    'name',
                    'url',
                    //'logo',
                    'active:boolean',
                    'percentage',
                    'percentage_distribution',
                    'phone',
                    'email:email',
                    'full_name',
                    'contract',
                    'iban',
                    'description:text',
                    //'date_added',
                    //'last_update',
                ],
            ]) ?>
        </div>
        <div class="col-xs-12 col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Акртисти</div>
                <div class="panel-body">
                    <?php
                    echo GridView::widget([
                        'dataProvider' => $artist,
                        'columns' => [
                            [
                                'attribute' => 'name',
                                'label' => 'Артіст',
                                'format' => 'raw',
                                'value' => function($data) {
                                    return Html::a($data['name'], ['artist/view', 'id' => $data['id']], ['target'=>'_blank', 'class' => 'linksWithTarget']);
                                },
                            ],
                            [
                                'attribute' => 'uah',
                                'label' => 'UAH',
                            ],
                            [
                                'attribute' => 'euro',
                                'label' => 'EURO',
                            ],
                            [
                                'attribute' => 'usd',
                                'label' => 'USD',
                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">Інвойси</div>
                <div class="panel-body">
                    <?php
                    echo GridView::widget([
                        'dataProvider' => $invoice,
                        'showFooter' => true,
                        'rowOptions' => function ($model)
                        {
                            if(in_array($model['invoice_type'], [1, 5])) {
                                return ['class' => 'success'];
                            } else {
                                return ['class' => 'danger'];
                            }
                        },
                        'columns' => [
                            [
                                'attribute' => 'invoice_id',
                                'label' => '№ інвойса',
                            ],
                            [
                                'attribute' => 'invoice_type_name',
                                'label' => 'Тип інвойса',
                            ],
                            [
                                'attribute' => 'currency_name',
                                'label' => 'Валюта',
                            ],
                            /* [
                                 'attribute' => 'track_name',
                                 'label' => 'Трек',
                             ],
                             [
                                 'attribute' => 'platform',
                                 'label' => 'Платформа',
                             ],*/
                            [
                                'attribute' => 'total',
                                'label' => 'Дебіт/Кредіт',
                            ],
                            [
                                'attribute' => 'quarter',
                                'label' => 'Квартал',
                            ],
                            [
                                'attribute' => 'date_added',
                                'label' => 'Дата',
                                'format' => 'date'
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} ',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        $t = '/invoice/view-modal?id=' . $model['invoice_id'] . '&artistId=' . $model['artist_id'];
                                        return Html::button('<span class="glyphicon glyphicon-eye-open"></span>', ['value'=> Url::to($t ), 'class' => 'btn btn-default btn-xs custom_button']);
                                    },
                                ],

                                // вы можете настроить дополнительные свойства здесь.
                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>



</div>
