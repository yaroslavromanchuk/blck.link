<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReportStatus */

$this->title = Yii::t('app', 'Create Aggregator Report Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aggregator Report Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-report-status-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
