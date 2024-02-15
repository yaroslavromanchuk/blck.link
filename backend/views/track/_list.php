<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $model backend\models\Track */
?>
<div  class="row">
    <div style="" class="col-sm-8 col-md-2">
        <a href="<?=Url::to('https://blck.link/' . $model->url)?>"  target="_blank" >
        <img class="card-img-top1" style="width: 160px;margin-left: 15px;border-radius: .25rem;" src="<?=$model->getImage()?>" alt="<?=$model->name?>">
        </a>
     </div>
    <div class="col-sm-4 col-md-2">
        <div style="">
        <h5 class="card-title"><?=$model->artist?></h5>
        <p class="card-sub-title"><?=$model->name?><br><span class="data"><?=$model->date?></span></p>
        </div>
    </div>
    <div class="col-sm-6 col-md-2">
        <div style="width: 48%"  class="d-inline-block text-center">
            <p>
                <span class="views"> <?=$model->views?></span>
            </p>

            <?=Yii::t('app', 'Перегляди')?>
        </div>
        <div style="width: 48%" class="d-inline-block text-center">
            <p>
                <span class="click"><?=$model->click?></span>
            </p>
            <?=Yii::t('app', 'Кліки')?>
        </div>
    </div>
    <div class="col-sm-6 col-md-2">
        <?php
        $items = [
            'view' => '<a  href="https://blck.link/'. $model->url . '"  target="_blank" title="Переглянути" aria-label="Переглянути" ><span style="font-size: 2em" class="glyphicon glyphicon-eye-open"></span></a>',
            'analytics' => Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-stats"></span>', ['analytics', 'id' => $model->id], [ 'title' => 'Аналітика', 'aria-label' => 'Аналітика']),
        ];

        if (Yii::$app->user->can('moder')) {
            $items['update'] = Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id], [ 'title' => 'Редагувати', 'aria-label' => 'Редагувати']);
            $items['delete'] = Html::a('<span  style="font-size: 2em" class="glyphicon glyphicon-remove"></span>',
                ['delete', 'id' => $model->id],
                [
                    //'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('app', 'Ви впевнені що хочете видалити цей трек?'),
                        'method' => 'post',
                    ],
                ]
            );
        }
        echo Html::ul($items, ['item' => function($item) {
            return Html::tag(
                'li',
                $item,
                ['style' => 'margin: 10px; list-style: none;']
            );
        }, 'class' => 'myclass', ]);
        ?>
    </div>
    <div class="col-sm-6 col-md-2">
        <p>Долі у відсотках:</p>
        <?php
        $model->percentage = $model->getPercentage();

        if (empty($model->percentage) || count($model->percentage) == 1) {
            echo '<span  style="font-size: 2em" class="glyphicon glyphicon-remove btn-danger"></span>';
            echo Html::a('Додати', ['track/percentage-update', 'trackId' => $model->id]);
        } else {

            echo Html::ol($model->percentage, ['item' => function($item) {
                return Html::tag(
                    'li',
                    $item['name'] . ': ' . $item['percentage'],
                    ['style' => 'margin: 5px;']
                );
            }, 'class' => 'myclass', ]);

            echo Html::a('Змінити', ['track/percentage-update', 'trackId' => $model->id]);

        } ?>
    </div>
</div>
<hr>