<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        Виникла помилка при обробці Вашого запиту.
    </p>
    <p>
        Будь-ласка, звяжіться з нами, якщо вважаєте що це помилка системи.
    </p>

</div>
