<?php

use yii\widgets\Pjax;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $items backend\models\InvoiceItemsSearch */
/* @var $invoiceId integer */

?>
<div class="invoice-view">
    <h5><?=Yii::t('app', 'Дані інвойсу') . ' №' . $invoiceId ?></h5>
    <?php Pjax::begin([ 'enablePushState' => false]); ?>
    <?= GridView::widget([
        'dataProvider' => $items['dataProvider'],
        'rowOptions' => function ($model)
        {
           if($model->artist_id > 0 && $model->track_id && $model->artist_id != $model->track->artist_id) {
                return ['class' => 'danger'];
           }
        },
        'columns' => [
            //'platform',
            [   'attribute' => 'artist_id',
                'value' => function ($data) {
                    return  $data->artist_id > 0 ? $data->artist->name : null;
                }
            ],
            [
                'attribute' => 'track_id',
                'value' => function($data) {
                    return  $data->track_id > 0 ? $data->track->name : null;
                },
            ],
            //'isrc',
            //'count',
            'amount',
            'description:text',
            //'date_item',
        ],
    ]);
 ?>
    <?php Pjax::end(); ?>

</div>
