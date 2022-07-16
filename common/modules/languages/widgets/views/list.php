<?php
use yii\helpers\Html;
?>
    <span id="current-lang" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?=$current->name?> <span class="show-more-lang">▼</span>
    </span>
    <ul id="langs" class="dropdown-menu" aria-labelledby="current-lang" >
        <?php foreach ($langs as $lang):?>
            <li class="item-lang">
                <?= Html::a($lang->name, '/'.$lang->url.Yii::$app->getRequest()->getLangUrl()) ?>
            </li>
        <?php endforeach;?>
    </ul>
