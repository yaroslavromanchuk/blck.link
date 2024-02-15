<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Aggregator */

$this->title = Yii::t('app', 'Update Aggregator: {name}', [
    'name' => $model->Name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aggregators'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->Name, 'url' => ['view', 'id' => $model->aggregator_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="aggregator-upload">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
