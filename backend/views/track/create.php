<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

$this->title = Yii::t('app', 'Создание релиза');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Релизы'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="track-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
