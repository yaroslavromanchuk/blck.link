<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorReport */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-report-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'aggregator_id')->textInput() ?>

    <?= $form->field($model, 'report_status_id')->textInput() ?>
    <?= $form->field($model, 'quarter')->textInput() ?>
    
    <?= $form->field($model, 'year')->textInput() ?>

    <?= $form->field($model, 'total')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
