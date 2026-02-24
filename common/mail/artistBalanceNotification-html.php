<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $artist \backend\models\Artist */
/* @var $quarter int */
/* @var $year int */

?>
<div class="verify-email">
    <p>Вітаємо <?= Html::encode($artist->name) ?>!</p>
    <p>
        Готовий Ваш звіт за підсумками розподілу винагороди станом на <?=$quarter?> кв. <?=$year?> р.<br>
        Нагадуємо Вам, що роялті накопичуються і будуть виплачені при досягенні порогу 30 EUR.
    </p>
    <p>
        <?= Html::img(Yii::getAlias('@site') .'/images/email_label.jpeg', ['alt' => 'Label logo', 'style' => 'width: 100%; max-width: 400px;']) ?>
    </p>
</div>