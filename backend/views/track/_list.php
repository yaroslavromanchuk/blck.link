<?php

use backend\widgets\PercentageModal;
use yii\helpers\Url;
use yii\helpers\Html;

/* @var $model backend\models\Track */
?>
<div  class="panel">
    <div class="panel-body row">
        <div style="" class="col-sm-8 col-md-2">
            <a href="<?=Url::to('https://blck.link/' . $model->url)?>"  target="_blank" >
            <img class="card-img-top1" style="width: 160px;margin-left: 15px;border-radius: .25rem;" src="<?=$model->getImage()?>" alt="<?=$model->name?>">
            </a>
         </div>
        <div class="col-sm-4 col-md-2">
            <div style="">
            <h5 class="card-title"><?=$model->name?></h5>
            <p class="card-sub-title"><?= Html::a($model->artist_name . ' (' . $model->artist->label->name . ')', ['artist/view', 'id' => $model->artist_id], ['target' => '_blank'])?><br>
                <span class="data"><?=$model->artist->full_name?></span><br>
                <span class="data">Дата релізу: <?=$model->date?></span><br>
                <span class="data">Дата створення: <?=date('Y-m-d', strtotime($model->date_added))?></span><br>
                <span class="data">Створено: <?=$model->admin->firstName?></span>

            </p>
                <?php if ($model->is_album) {
                    echo '(album)';
                } ?>
            </div>
        </div>
        <div class="col-sm-12 col-md-2">
            <p>
                <?=Yii::t('app', 'Перегляди')?>
                <span class="badge"><?=$model->views?></span>
            </p>
            <p>
                <?=Yii::t('app', 'Кліки')?>
                <span class="badge"><?=$model->click?></span>
            </p>
            <p>
                <?=Yii::t('app', 'Дохід')?>
                <span class="badge"><?=$model->getTotalAmount(2) ?? 0?> UAH</span>
                <span class="badge"><?=$model->getTotalAmount(1) ?? 0?> EURO</span>
                <span class="badge"><?=$model->getTotalAmount(3) ?? 0?> USD</span>
            </p>
            <p>
                <?=Yii::t('app', 'Борг')?>
                <span class="badge"><?php echo !empty($model->deposit_uah) ? $model->deposit_uah + $model->getTotalAmount(2) : 0; ?> UAH</span>
                <span class="badge"><?php echo !empty($model->deposit_euro) ? $model->deposit_euro + $model->getTotalAmount(1) : 0; ?> EURO</span>
                <span class="badge"><?php echo !empty($model->deposit_euro) ? $model->deposit_euro + $model->getTotalAmount(3) : 0; ?> USD</span>
            </p>
            <?php if (!$model->is_album) { ?>
                <p>
                    <?=Yii::t('app', 'ISRC')?>
                    <span class=""><?=$model->isrc?></span>
                </p>
            <?php }?>
        </div>
        <div class="col-md-3">
           <?php
           //echo PercentageModal::widget(['trackId' => $model->id]);

            $items = [
                //'view2' =>
               // 'view' => '<a  href="https://blck.link/'. $model->url . '"  target="_blank" title="Мультилінк" aria-label="Мультилінк" ><span style="font-size: 2em" class="glyphicon glyphicon-eye-open"></span></a>',
               // 'analytics' => Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-stats"></span>', ['analytics', 'id' => $model->id], [ 'title' => 'Аналітика', 'aria-label' => 'Аналітика', 'target'=>'_blank']),
            ];
            $items['view'] = Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-eye-open"></span>', ['view', 'id' => $model->id], [ 'title' => 'Переглянути', 'aria-label' => 'Переглянути', 'target'=>'_blank']);

            if (Yii::$app->user->can('moder')) {
                $items['update'] = Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id], [ 'title' => 'Редагувати', 'aria-label' => 'Редагувати', 'target'=>'_blank']);
                $items['copy'] = Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-copy"></span>', ['copy', 'id' => $model->id], [ 'title' => 'Копіювати', 'aria-label' => 'Копіювати', 'target'=>'_blank']);
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
            if (!$model->is_album && !$model->isSubLabel() && !$model->artist->records) {
                $items['per'] = Html::a('<span style="font-size: 2em" class="glyphicon glyphicon-usd"></span>', ['load-modal', 'trackId' => $model->id], ['title' => 'Відсотки', 'aria-label' => 'Відсотки', 'class' => 'showModalButton',
                        'data-toggle' => 'modal',
                        'data-target' => '#percentage-modal',
                        'data-id' => $model->id]
                );
            }

            if ($model->active) {
                $items['view2'] = '<a  href="https://blck.link/'. $model->url . '"  target="_blank" title="Мультилінк" aria-label="Мультилінк" ><span style="font-size: 2em" class="glyphicon glyphicon-music"></span></a>';
            }

          /* $items['per2'] = Html::Button('%',  [
                   'class' => 'showModalButton',
               'data-toggle' => 'modal',
               'data-target' => '#percentage-modal',
               'data-id' => $model->id
           ]);*/

            echo '<nav>' . Html::ul($items, ['item' => function($item) {
                return Html::tag(
                    'li',
                    $item,
                    ['style' => ' margin: 0 1rem; list-style: none;']
                );
            }, 'class' => 'myclass', 'style' => 'list-style-type: none;margin: 0;padding: 0; display: flex;align-items: center;justify-content: center;' ])
           . '</nav>'
            ?>
        </div>
    </div>
</div>