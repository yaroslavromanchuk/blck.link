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
        'columns' => [
            //'platform',
            [
                'attribute' => 'track_id',
                'value' => function($data) {
                    return  $data->track_id > 0 ? $data->track->name : null;
                },
            ],
            //'isrc',
            //'count',
            'amount',
            //'date_item',
        ],
    ]);
 ?>
    <?php Pjax::end(); ?>

</div>
