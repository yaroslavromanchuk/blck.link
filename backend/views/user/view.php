<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\User */

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


