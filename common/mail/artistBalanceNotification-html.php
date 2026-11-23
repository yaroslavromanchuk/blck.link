<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $artist \backend\models\Artist */
/* @var $quarter int */
/* @var $year int */

?>
<div class="verify-email">
    <p><?= Html::encode($artist->translateDocument('Вітаємо') . ' ' . $artist->name . '!') ?></p>
    <p>
        <?= Html::encode($artist->translateDocument('Готовий Ваш звіт за підсумками розподілу винагороди станом на')) ?> <?= $quarter ?> <?= Html::encode($artist->translateDocument('кв.')) ?> <?= $year ?> <?= Html::encode($artist->translateDocument('р.')) ?><br>
        <?= Html::encode($artist->translateDocument('Нагадуємо Вам, що роялті накопичуються і будуть виплачені при досягенні порогу 30 EUR.')) ?>
    </p>
    <p>
        <?= Html::img(Yii::getAlias('@site') .'/images/email_label.jpeg', ['alt' => 'Label logo', 'style' => 'width: 100%; max-width: 400px;']) ?>
    </p>
</div>