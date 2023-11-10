<?php

use yii\helpers\Url;
use yii\helpers\Html;
?>

    <div  class="card1">
       
        <div style="" class="d-inline-table col-7">
            <a href="<?=Url::to('https://blck.link/' . $model->url)?>"  target="_blank" >
            <img class="card-img-top1" style="    width: 160px;margin: 15px;border-radius: .25rem;" src="<?=$model->getImage()?>" alt="<?=$model->name?>">
            </a>
         </div>
        <div class="d-inline-table col-4">
            <div style="    position: relative;top: -40px;">
            <h5 class="card-title"><?=$model->artist?></h5>
            <p class="card-sub-title"><?=$model->name?><br><span class="data"><?=$model->date?></span></p>
            </div>
    </div>
        <div class="d-inline-table col-4">
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
        
        <div class="d-inline-table col-12 text-center">
            <!--href="/admin/track/view?id=<?=$model->id?>"-->
            <a  href="http://blck.link/<?=$model->url?>"  target="_blank" title="Переглянути" aria-label="Переглянути" ><span style="font-size: 2em" class="glyphicon glyphicon-eye-open"></span></a>
            </div>
        <?php if(Yii::$app->user->can('moder')){ ?>
        <div class="d-inline-table col-12 text-center">
            <a href="/admin/track/update?id=<?=$model->id?>" title="Редагувати" aria-label="Редагувати" data-pjax="0"><span  style="font-size: 2em" class="glyphicon glyphicon-pencil"></span></a>
        </div>
        <?php
			 echo '<div class="d-inline-table col-12 text-center">' .
             Html::a('<span  style="font-size: 2em" class="glyphicon glyphicon-remove"></span>', ['delete', 'id' => $model->id], [
				//'class' => 'btn btn-danger',
				'data' => [
					'confirm' => Yii::t('app', 'Ви впевнені що хочете видалити цей трек?'),
					'method' => 'post',
				],
			]) . '</div>';
        } ?>
        <div class="d-inline-table col-12 text-center">
            <a href="/admin/track/analitiks?id=<?=$model->id?>" title="Аналітика" aria-label="Аналітика" data-pjax="0"><span  style="font-size: 2em" class="glyphicon glyphicon-stats"></span></a>
        </div>
    </div>
<hr>