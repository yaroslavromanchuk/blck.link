<?php

use backend\models\Currency;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\CheckboxColumn;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $balanceProvider backend\models\UserBalance */
/* @var $searchBalance backend\models\UserBalanceSearch */
/* @var $bonusProvider backend\models\UserBonus */

$this->title = $model->lastName . ' ' . $model->firstName;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Користувачі'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="user-view">
    <p>
        <?= Html::a(Yii::t('app', 'Редактировать'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <!-- <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>-->
    </p>
    <div class="row">
        <div class="col-sm-12 col-md-6">
            
            
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    // 'id',
                    'username',
                    'email:email',
                    'lastName',
                    'firstName',
                    'middleName',
                    //'sex',
                    [
                        'attribute' => 'sex',
                        'value' => function ($data) {
                            switch ($data->sex) {
                                case 'm':
                                    return 'Чоловік';
                                case 'w':
                                    return 'Жінка';
                                default :
                                    return '';
                            }
                        }
                    ],
                    //'logo',
                    [
                        'attribute' => 'logo',
                        'format' => 'raw',
                        'value' => function ($data) {
                            return Html::img($data->img, ['style' => 'border-radius: 50%', 'alt' => $data->lastName]);
                        }
                    ],
                    [
                        'attribute' => 'balance',
                        'label' => Yii::t('app', 'Баланс'),
                        'format' => 'raw',
                        'value' => function ($data) {
                            $echo = '';
                            foreach ($data->balance as $balance) {
                                $echo .= Html::tag('span', $balance['name'] . ': ' . $balance['amount'], ['class' => 'badge badge-info']) . PHP_EOL;
                            }
                            
                            return Html::tag('p', $echo, ['class' => 'badge badge-success']);
                        }
                    ]
                    //'auth_key',
                    // 'password_hash',
                    // 'password_reset_token',
                    // 'status',
                    // 'created_at',
                    // 'updated_at',
                ],
            ]) ?>
        </div>
        <div class="col-sm-12 col-md-6">
            <p>Бонуси користувача:</p>
            
            <?= GridView::widget([
                'dataProvider' => $bonusProvider,
                'columns' => [
                    /*[
                        'attribute'=>'label_id',
                        'value'=> function($data) {
                            return $data->label_id ? $data->label->name : null;
                        }
                    ],*/
                   // 'artist_id',
                    [
                        'attribute' => 'artist_id',
                        'format' => 'raw',
                        'value' => function ($data) {
                                return Html::a($data->artist->name, ['artist/view', 'id' => $data->artist_id]);
                        }
                    ],
                    [
                        'attribute' => 'track_id',
                        'format' => 'raw',
                        'value' => function ($data) {
                        return Html::a($data->track->name, ['track/view', 'id' => $data->track_id]);
                        }
                    ],
                    //'track_id',
                    'percentage',
                ],
            ]); ?>
        </div>
        <div class="col-sm-12">
            <p>Нараховані бонуси:</p>
            <?php Pjax::begin(['id' => 'balance-grid']); ?>

            <?= Html::button('Позначити як виплачені', [
                    'class' => 'btn btn-success',
                    'id' => 'bulk-pay-btn',
            ]) ?>

            <?= GridView::widget([
                    'id' => 'grid-balance',
                'dataProvider' => $balanceProvider,
                'filterModel' => $searchBalance,
                'columns' => [
                   ['class' => CheckboxColumn::class],
                    'balance_id',
                    'invoice_id',
                    ['attribute' => 'artist_id',
                        'value' => function ($data) {
                            return $data->artist_id ? $data->artist->name : null;
                        }
                    ],
                    [
                        'attribute' => 'track_id',
                        'value' => function ($data) {
                            return $data->track_id ? $data->track->name : null;
                        }
                    ],
                    'all_sum',
                    'percentage',
                    'amount',
                    [
                        'attribute' => 'currency_id',
                            'filter' => ArrayHelper::map(
                                    Currency::find()->all(),
                                    'currency_id',
                                    'currency_name'
                            ),
                        'value' => function ($data) {
                            return $data->currency->name;
                        }
                    ],
                    [
                        'attribute' => 'is_pay',
                        'filter' => [2 => 'Ні', 1 => 'Так'],
                        'value' => function ($data) {
                            return $data->is_pay ? 'Так' : 'Ні';
                        }
                    ],
                    'date_added:datetime',
                    'date_pay:datetime',
                ],
            ]); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>


</div>
<?php
$script = <<< JS
$('#bulk-pay-btn').on('click', function () {
var keys = $('#grid-balance').yiiGridView('getSelectedRows');

if (keys.length === 0) {
alert('Вибери хоча б один рядок');
return;
}

if (!confirm('Ви впевнені?')) {
return;
}

$.ajax({
url: 'bulk-pay',
type: 'POST',
data: {
ids: keys,
_csrf: yii.getCsrfToken()
},
success: function () {
$.pjax.reload({container:'#balance-grid'});
},
error: function () {
alert('Помилка при оновленні');
}
});
});
JS;

$this->registerJs($script);


