<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Albums $model */

$this->title = 'Створення альбому';
$this->params['breadcrumbs'][] = ['label' => 'Альбоми', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="albums-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
