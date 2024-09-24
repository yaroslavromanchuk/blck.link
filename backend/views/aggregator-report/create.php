<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReport */

$this->title = Yii::t('app', 'Create Aggregator Report');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aggregator Reports'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-report-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
