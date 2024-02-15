<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Артисти'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="artist-view">
    
    <p class="text-right">
        <?= Html::a(Yii::t('app', 'Редагувати'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php if(Yii::$app->user->can('admin')){ echo Html::a(Yii::t('app', 'Видалити'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]); } ?>
    </p>
    <div class="row">
        <div class="col-md-2 col-sm-2 col-xs-12 ">
            <div class="card">
                <img class="card-img-top" src="<?=$model->getLogo()?>" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title"><?=$model->name?></h5>
                </div>
                <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'id',
          //  'name',
            'phone',
            'email:email',
           // 'active',
            [ // name свойство зависимой модели owner
                'attribute' => 'admin_id',
                //'label' => 'Релизов',
                'value' => function($data) {
                        return $data->admin->getFullName();
                    },
            ],
        ],
    ]) ?>
            </div>
        </div>
        <div class="col-md-8 col-sm-8 col-xs-12">
            <ul class="list-group list-group-flush">
                <?php foreach ($model->tracks as $t) { ?>
                <li class="list-group-item"><a href="https://blck.link/<?=$t->url?>" target="_blank">
                        <img class="card-img-top1" style="    width: 30px;margin-right: 15px;border-radius: .25rem;" src="<?=$t->getImage()?>" alt="<?=$t->name?>">
                            <?=$t->name?>
                        </a>
                    <span class="badge badge-primary badge-pill">Кліків: <?=$t->click?></span>
                    <span class="badge badge-primary badge-pill">Переглядів: <?=$t->views?></span>
                </li>
              <?php  } ?>
  </ul>
            <?php /*DetailView::widget([
        'model' => $model->tracks,
        'attributes' => [
            'artist',
            'date',
            'name',
            'img',
            'url:url',
            //'youtube',
            'tag',
            'sharing',
            'views',
            'click',
            'active',
           // [                                                  // name свойство зависимой модели owner
                 //       'attribute' => 'admin_id',
                        //'label' => 'Релизов',
                    //    'value' => function($data){ return $data->admin->getFullName();},
                  //  ],
        ],
    ])*/ ?>
        </div>
    </div>
    

</div>
