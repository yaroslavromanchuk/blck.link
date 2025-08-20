<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReportItem */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-report-item-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php // $form->field($model, 'report_id')->textInput() ?>

    <?= $form->field($model, 'isrc')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'date_report')->textInput() ?>

    <?= $form->field($model, 'platform')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'count')->textInput() ?>

    <?php // $form->field($model, 'amount')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
