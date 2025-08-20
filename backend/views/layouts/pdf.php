<?php

use backend\assets\AppAsset;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */
AppAsset::register($this);
?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow" />
        <?= Html::csrfMetaTags() ?>
        <?php $this->head() ?>
    </head>
    <body class="pdf-page">
    <div class="container" style="font-size: 14px">
        <?= $content ?>
    </div>
    </body>
    </html>
<?php $this->endPage() ?>
