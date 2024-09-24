<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReportItem */

$this->title = Yii::t('app', 'Create Aggregator Report Item');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aggregator Report Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-report-item-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
