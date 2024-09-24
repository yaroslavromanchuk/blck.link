<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */
/* @var $items backend\models\InvoiceItemsSearch */
/* @var $modelItems backend\models\InvoiceItems */

$this->title = $model->invoice_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Invoices'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="invoice-view">

    <h1><? // Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Редагувати'), ['update', 'id' => $model->invoice_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Перерахувати суму'), ['re-calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-success']) ?>
        <?php if ($model->invoice_status_id == 1) {
            echo Html::a(Yii::t('app', 'Розрахувати'), ['calculate', 'id' => $model->invoice_id], ['class' => 'btn btn-info']);
        }
        ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->invoice_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

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

    <?php

    if ($model->invoice_type != 1 && $model->invoice_status_id == 1) {
        echo $this->render('_formItems', [
            'model' => $modelItems,
            'invoice' => $model,
        ]);
    }
    ?>

    <h5><?=Yii::t('app', 'Дані інвойсу')?></h5>
    <?= GridView::widget([
        'dataProvider' => $items['dataProvider'],
        'columns' => [
            [
                'attribute' => 'artist_id',
                'value' => function($data) {
                    return $data->artist_id >= 0 ? $data->artist->name : null;
                },
            ],
           // 'platform',
            [
                'attribute' => 'track_id',
                'value' => function($data) {
                    return $data->track_id > 0 ? $data->track->name : null;
                },
            ],
            //'isrc',
           // 'count',
            'amount',
            //'date_item',
            [
                'class' => 'yii\grid\ActionColumn',
                'template'=>  $model->invoice_status_id == 1 ? '{delete}': '',
                'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [

                                'title' => Yii::t('yii', 'Видалити'),

                                'data-confirm' => Yii::t('yii', 'Ви впевнені що бажаєти видалити цей запис з інвойсу?'),

                                'data-method' => 'post',
                            ]);
                        }
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'delete') {
                        $r = Url::to(['invoice/view/', 'id' => $model->invoice_id]);
                        return Url::to(['invoice-items/'.$action, 'id' => $model->id, 'url' =>  $r]);
                    }
                }
            ],
        ],
    ]); ?>

</div>
