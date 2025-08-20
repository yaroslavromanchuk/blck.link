<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $invoiceModel \backend\models\Invoice */

?>
<div class="verify-email">
    <p>Вітаємо <?= Html::encode($invoiceModel->label->name) ?>!</p>
    <p>
        Готовий Ваш звіт за підсумками розподілу винагороди за <?=$invoiceModel->quarter?> кв. <?=$invoiceModel->year?> р.
        <br>
        Для здійснення виплати, підпишіть, будь ласка, акт-звіт у сервісі "Вчасно", посилання на яке Вам також прийде на пошту.
        <br>
        У випадку зміни реквізитів просимо надати нові, надіславши зворотне повідомлення.
    </p>
    <p>
        Як зареєструватися у сервісі «Вчасно»<br>
        <?= Html::a(Html::encode('https://www.youtube.com/watch?v=4qTOAmGPtDg'), 'https://www.youtube.com/watch?v=4qTOAmGPtDg') ?>
    </p>
    <p>
        Як підписати вхідний документ в сервісі «Вчасно»<br>
        <?= Html::a(Html::encode('https://www.youtube.com/watch?v=7WdRAjprR6Y'), 'https://www.youtube.com/watch?v=7WdRAjprR6Y') ?>
    </p>
    <p>
        Хелп центр, ось тут взагалі вся інформація розписана по роботі у "Вчасно", якщо виникнуть питання<br>
        <br>
        <?= Html::a(Html::encode('https://help.vchasno.com.ua/poperednij-pereglyad-dokumentiv-pidpysujte-bez-reyestracziyi/'), 'https://help.vchasno.com.ua/poperednij-pereglyad-dokumentiv-pidpysujte-bez-reyestracziyi/') ?>
    </p>
    <p>
      <?= Html::img(Yii::getAlias('@site') .'/images/email_label.jpeg', ['alt' => 'Label logo', 'style' => 'width: 100%; max-width: 400px;']) ?>
    </p>
</div>