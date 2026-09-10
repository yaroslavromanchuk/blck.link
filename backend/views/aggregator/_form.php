<?php

use backend\models\AggregatorService;
use backend\models\AggregatorServiceType;
use backend\models\AggregatorTypeUse;
use backend\models\Currency;
use backend\models\Ownership;
use backend\models\OwnershipType;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\Aggregator */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="aggregator-form row">

    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'label_id')->hiddenInput(['value'=>Yii::$app->user->identity->label_id])->label(false)?>
    <div class="col-sm-12 col-md-2">
        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-12 col-md-2">
    <?= $form->field($model, 'currency_id')->dropDownList(
        ArrayHelper::map(Currency::find()->all(), 'currency_id', 'currency_name'),
       // ['prompt' => 'Валюта']
    ) ?>
    </div>
    <div class="col-sm-12 col-md-2">
        <?= $form->field($model, 'service_type_id')->dropDownList(
            ArrayHelper::map(AggregatorServiceType::find()->all(), 'service_type_id', 'name'),
        // ['prompt' => 'Тип діяльності']
        ) ?>
    </div>
    <div class="col-sm-12 col-md-2">
    <?= $form->field($model, 'ownership_type')->dropDownList(
        ArrayHelper::map(Ownership::find()->all(), 'id', 'name'),
      //  ['prompt' => 'Тип Власності']
    ) ?>
    </div>
    <div class="col-sm-12 col-md-2">
    <?= $form->field($model, 'type_use_id')->dropDownList(
        ArrayHelper::map(AggregatorTypeUse::find()->all(), 'type_id', 'name'),
       // ['prompt' => 'Русурс зі звіту']
    ) ?>
    </div>
    <div class="col-sm-12 col-md-2">
    <?= $form->field($model, 'service_id')->dropDownList(
        ArrayHelper::map(AggregatorService::find()->all(), 'service_id', 'name'),
        ['prompt' => 'Брати зі звіту']
    ) ?>
    </div>
    <div class="col-sm-12 col-md-12">
        <?= $form->field($model, 'ownership_types')->checkboxList(
            ArrayHelper::map(OwnershipType::find()->all(), 'id', 'name'),
        // ['prompt' => 'Тип Виконання']
        ) ?>
    </div>
    <div class="col-sm-12 col-md-3">
        <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
    </div>
   
    <div class="form-group col-sm-12">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
