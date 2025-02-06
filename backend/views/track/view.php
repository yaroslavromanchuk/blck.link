<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="track-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Редагувати'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php Html::a(Yii::t('app', 'Видалити'), ['delete', 'id' => $model->id], [
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
            [
                'attribute' => 'album_id',
                'value' => function ($model) {
                    return !empty($model->album_id) ? $model->album->name : '';
                }
            ],
            'isrc',
            //'name',


            //'artist_id',
            [
                'attribute' => 'artist_id',
                'value' => function ($model) {
                    return $model->artist->name;
                }
            ],
            'artist_name',

            'date',

            //'img',
            //'url:url',
          //  'youtube',   
            //'tag',

            'views',
            'click',
            'active:boolean',
            'sharing:boolean',
            'is_album:boolean',
            'date_added',
            [
                'attribute' => 'admin_id',
                'value' => function ($model) {
                    return $model->admin->getFullName();
                }
            ],
        ],
    ]) ?>

</div>
