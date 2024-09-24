<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\AggregatorSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'aggregator_id') ?>
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

    <?= $form->field($model, 'name') ?>
    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'date_add') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Шукати'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Очистити форму'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
