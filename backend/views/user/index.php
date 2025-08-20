<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Пользователи');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">
    <p>
        <?= Html::a(Yii::t('app', 'Создать пользователя'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
            'id',
            //'label',
            [
                'attribute' => 'label_id',
                'value' => function ($data) { return $data->label->name ?? 'BlackBeats'; },
            ],
            'username',
            'email:email',
            'lastName',
            'firstName',
            [// name свойство зависимой модели owner
                // 'attribute' => 'admin_id',
                'label' => Yii::t('app', 'Доступ'),
                'value' => function($data){ return $data->role;},
            ],
            //'middleName',
            //'sex',
            //'logo',
            //'auth_key',
            //'password_hash',
            //'password_reset_token',
            //'status',
            //'created_at',
            //'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
