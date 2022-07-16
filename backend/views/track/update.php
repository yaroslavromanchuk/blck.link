<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

$this->title = Yii::t('app', 'Редактирование Релиза: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tracks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Редактирование');
?>
<div class="track-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
