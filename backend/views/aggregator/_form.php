<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\Aggregator */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'currency_id')->widget(Select2::class, [
        'model' => $model,
        'data' => \backend\models\Currency::find()
            ->select(['currency_name', 'currency_id'])
            ->indexBy('currency_id')
            ->column(),
        'language' => 'uk',
        'options' => [
            'placeholder' =>  Yii::t('app', 'Виберіте валюту'),
        ],
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
