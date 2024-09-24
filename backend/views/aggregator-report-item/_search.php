<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReportSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-report-item-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'report_id') ?>

    <?= $form->field($model, 'isrc') ?>

    <?= $form->field($model, 'date_report') ?>

    <?= $form->field($model, 'platform') ?>

    <?php // echo $form->field($model, 'artist') ?>

    <?php // echo $form->field($model, 'releas') ?>

    <?php // echo $form->field($model, 'track') ?>

    <?php // echo $form->field($model, 'count') ?>

    <?php // echo $form->field($model, 'amount') ?>

    <?php // echo $form->field($model, 'date_added') ?>

    <?php // echo $form->field($model, 'last_update') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
