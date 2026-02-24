<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Albums $model */

$this->title = 'Оновлення альбому: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Альбоми', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Оновилення';
?>
<div class="albums-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
