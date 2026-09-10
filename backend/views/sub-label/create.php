<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SubLabel */

$this->title = Yii::t('app', 'Створити партнера');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Партнери'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sub-label-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
