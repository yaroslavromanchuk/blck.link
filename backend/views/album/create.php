<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Albums $model */

$this->title = 'Create Albums';
$this->params['breadcrumbs'][] = ['label' => 'Albums', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="albums-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
