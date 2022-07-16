<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\ArtistSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Артисты');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="artist-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Создать артиста'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

           // 'id',
           // 'logo:raw',
            [
            'label' => 'Фото',
                'attribute' => 'logo',
            'format' => 'raw',
            'value' => function($data){
        $list = '<div  class="trumb_foto">'
                . ''.Html::img(
                        $data->getLogo(),['alt'=>'yii2 - картинка в gridview','style' => 'border-radius: 50%;width:50px; padding:1px;']).''
                    .'</div>';
                 return $list;
            },
        ],
            'name',
           // 'phone',
               [                                                  // name свойство зависимой модели owner
                        'attribute' => 'reliz',
                        //'label' => 'Релизов',
                        'value' => function($data){ return $data->getTracks()->count();},
                    ],
           // 'email:email',
            //'active',
                    

            ['class' => 'yii\grid\ActionColumn',
             'template' => Yii::$app->user->can('admin')?'{view} {update} {delete}': '{view} {update}'
                
                ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
