<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\PercentageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $trackId int */

$this->title = Yii::t('app', 'Відсотки');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $trackId]];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="release-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a(Yii::t('app', 'Додати'), ['track/percentage-create', 'id' => $trackId], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Редагувати'), ['track/percentage-update', 'trackId' => $trackId], ['class' => 'btn btn-info']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        //'filterModel' => $searchModel,
        'columns' => [
           // ['class' => 'yii\grid\SerialColumn'],

            //'id',
            [ // name свойство зависимой модели owner
                'attribute' => 'artist_id',
                //'label' => Yii::t('app', 'Кількість треків'),
                'value' => function($data) {
                    return \backend\models\Artist::findOne(['id'=> $data['artist_id']])->name;
                },
            ],
            //'artist_id',
            'percentage',
           // 'date_add',
            //'last_update',
        ],
    ]); ?>
</div>
