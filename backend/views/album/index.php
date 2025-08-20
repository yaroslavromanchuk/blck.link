<?php

use backend\models\Albums;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var backend\models\AlbumSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Albums';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="albums-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Albums', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
           // ['class' => 'yii\grid\SerialColumn'],
            'id',
			[
				'attribute' => 'img',
				'format' => 'raw',
				'value' => function ($model) {
					return Html::img($model->getImage(), ['style' => 'width: 100px; height: auto;']);
				},
			],
			'name',
			[
				//'attribute' => 'artist_id',
				'label' => 'Артіст',
				'value' => function ($model) {
					return $model->artist->name;
				},
			],
			[
				//'attribute' => 'artist_name',
				'label' => 'Кількість треків',
				'value' => function ($model) {
					return $model->tracks ? count($model->tracks) : 0;
				},
			],
            [
                'attribute' => 'type_id',
                'value' => function ($model) {
                    return $model->type ? $model->type->name : 'Unknown';
                },
            ],
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a('<span style="font-size: 1em" class="glyphicon glyphicon-eye-open"></span>', 'https://blck.link/album/' . $model->url, ['target' => '_blank']);
                },
            ],
			//'url:url',
            //'admin_id',
            //'artist_id',
            //'artist_name',
            //'date',
            
            //'img',
            //'url:url',
            //'youtube_link',
            //'sharing',
            //'views',
            //'click',
            //'active',
            //'servise:ntext',
            'date_added',
            //'last_update',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Albums $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
