<?php
use yii\helpers\Html;
use backend\assets\AppAsset;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
/* @var $this \yii\web\View */
/* @var $content string */
if (Yii::$app->user->isGuest) { 
    echo $this->render(
        'main-login',
        ['content' => $content]
    );
}else{
AppAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow" />
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="skin-black">
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    yii\bootstrap\Modal::begin([
    'id' => 'activity-modal',
    'header' => '<h4 class="modal-title">Швидкий перегляд</h4>',
    'footer' => '<a href="#" class="btn btn-primary" data-dismiss="modal">Закрити</a>',
]); ?>
    <div class="well"></div>
<?php yii\bootstrap\Modal::end(); ?>
    <?= $this->render('header.php')?>
    <?= $this->render('left.php')?>
    <?= $this->render('content.php',['content' => $content]) ?>
</div>
<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
       <!-- <p class="pull-right"><?= Yii::powered() ?></p>-->
    </div>
</footer>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
<?php } ?>