<?php

use backend\models\Currency;
use backend\models\InvoiceType;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */
/* @var $form yii\widgets\ActiveForm */

$invoice_type = InvoiceType::find()
    ->select(['invoice_type_name', 'invoice_type_id'])
    ->where('invoice_type_id != 1')
    ->indexBy('invoice_type_id')
    ->column();
$currency = Currency::find()
    ->select(['currency_name', 'currency_id'])
    ->indexBy('currency_id')
    ->column();
?>

<div class="invoice-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'user_id')
        ->hiddenInput(['value'=>Yii::$app->user->identity->id])
        ->label(false)?>

    <?= $form->field($model, 'invoice_status_id')->hiddenInput(['value'=> 1])->label(false) ?>
    <?= $form->field($model, 'invoice_type')
        ->widget(Select2::class, [
            'model' => $model,
            'data' => $invoice_type,
            'language' => 'uk',
            'options' => ['placeholder' =>  Yii::t('app', 'Вкажіть тип інвойсу'),]
        ]) ?>

    <?= $form->field($model, 'currency_id')
        ->widget(Select2::class, [
            'model' => $model,
            'data' => $currency,
            'language' => 'uk',
            'options' => ['placeholder' =>  Yii::t('app', 'Вкажіть валюти інвойсу'),]
        ]) ?>


    <?= $form->field($model, 'total')->hiddenInput(['value'=> 0])->label(false)?>
    <?= $form->field($model, 'aggregator_id')->hiddenInput(['value'=> 10])->label(false)?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Створити'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
