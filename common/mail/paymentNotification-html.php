<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $InvoiceItems \backend\models\InvoiceItems */

?>
<div class="verify-email">
    <p>Вітаємо <?= Html::encode($InvoiceItems->artist->name) ?>!</p>
    <p>
        Готовий Ваш звіт за підсумками розподілу винагороди за <?=$InvoiceItems->invoice->quarter?> кв. <?=$InvoiceItems->invoice->year?> р.<br>
        Підтвердіть, будь ласка, отримання відповіддю на цей лист.
<br>
        У разі зміни вашого банківського рахунку, який вказаний в договорі, надішліть нові реквізити, а саме:<br>
        <br>
        - IBAN рахунку<br>
        - Назва банку<br>
        <br>
        Для здійснення виплати, потрібно підписати акт-звіт у сервісі "Вчасно", посилання на яке вам також прийде на пошту.
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