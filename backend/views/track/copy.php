<?php

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

$this->title = Yii::t('app', 'Копіювання треку: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Копіювання');
?>
<div class="track-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
