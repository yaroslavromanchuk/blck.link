<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $balanceProvider backend\models\UserBalance */
/* @var $searchBalance backend\models\UserBalanceSearch */
/* @var $bonusProvider backend\models\UserBonus*/

$this->title = $model->lastName.' '.$model->firstName;
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
                        'value' => function($data){
                            switch ($data->sex)
                            {
                                case 'm': return 'Чоловік';
                                case 'w': return 'Жінка';
                                default : return '';
                            }
                        }
                    ],
                    //'logo',
                    [
                        'attribute' => 'logo',
                        'format' => 'raw',
                        'value' => function($data){
                            return Html::img($data->img, ['style'=>'border-radius: 50%', 'alt'=>$data->lastName] );
                        }
                    ],
                    [
                        'attribute' => 'balance',
                        'label' => Yii::t('app', 'Баланс'),
                        'format' => 'raw',
                        'value' => function($data) {
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
                    [
                        'attribute'=>'label_id',
                        'value'=> function($data) {
                            return $data->label_id ? $data->label->name : null;
                        }
                    ],
                    'artist_id',
                    'track_id',
                    'percentage',
                ],
            ]); ?>
        </div>
        <div class="col-sm-12">
            <p>Нараховані бонуси:</p>
            
            <?= GridView::widget([
                'dataProvider' => $balanceProvider,
                'filterModel' => $searchBalance,
                'columns' => [
                    //  ['class' => 'yii\grid\SerialColumn'],
                    
                    'balance_id',
                    'invoice_id',
                    [
                        'attribute'=>'label_id',
                        'value'=> function($data) {
                            return $data->label_id ? $data->label->name : null;
                        }
                    ],
                    ['attribute'=>'artist_id',
                        'value'=> function($data){
                            return $data->artist_id ? $data->artist->name : null;
                        }
                    ],
                    ['attribute'=>'track_id',
                        'value'=> function($data){
                            return $data->track_id ? $data->track->name : null;
                        }
                    ],
                    'all_sum',
                    'percentage',
                    'amount',
                    [
                        'attribute'=>'currency_id',
                        'value'=> function($data){
                            return $data->currency->name;
                        }
                    ],
                    [
                        'attribute'=>'is_pay',
                        'value'=> function($data){
                            return $data->is_pay ? 'Так' : 'Ні';
                        }
                    ],
                    
                    // 'body:ntext',
                    'date_added:datetime',
                    //'updated_at',
                    
                    // [
                    //'class' => 'yii\grid\ActionColumn',
                    // 'template'=> '{delete}',//
                    // ]
                ],
            ]); ?>
        </div>
    </div>
    
    
    
    

    
    
</div>


