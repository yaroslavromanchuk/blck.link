<?php

use backend\models\Currency;
use backend\models\InvoiceType;
use yii\jui\DatePicker;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\Invoice */
/* @var $form yii\widgets\ActiveForm */

$invoice_type = InvoiceType::find()
    ->select(['invoice_type_name', 'invoice_type_id'])
    #->where('invoice_type_id != 1')
    ->indexBy('invoice_type_id')
    ->column();
$currency = Currency::find()
    ->select(['currency_name', 'currency_id'])
    ->indexBy('currency_id')
    ->column();
?>

<div class="invoice-form row">
    <?php $form = ActiveForm::begin([
        'id' => 'create_invoice',
        //'enableClientValidation' => true,
        'enableAjaxValidation' => true,
        // 'action' => ['artist/create-invoice'],
        'validationUrl' => Url::to('create'),
    ]); ?>
    <?= $form->field($model, 'user_id')
        ->hiddenInput(['value'=>Yii::$app->user->identity->id])
        ->label(false)?>
    <div class="col-sm-12 col-md-6 col-lg-2">
        <?= $form->field($model, 'exchange')
            ->textInput() ?>
    </div>
    <div class="col-sm-12 col-md-6 col-lg-2">
        <?= $form->field($model, 'quarter')->dropDownList([1 => 1, 2 => 2, 3 => 3, 4 => 4]) ?>
    </div>
    <div class="col-sm-12 col-md-6 col-lg-2">
        <?= $form->field($model, 'year')->dropDownList([2024 => 2024, 2025 => 2025, 2026 => 2026]) ?>
    </div>
    <div class="col-sm-12 col-md-6 col-lg-2">
        <?= $form->field($model, 'description')
            ->textInput() ?>
    </div>
    <div class="col-sm-12 col-md-6 col-lg-2">
        <?= $form->field($model, 'aggregator_id')
            ->widget(Select2::class, [
                'model' => $model,
                'data' => \backend\models\Aggregator::find()
                    ->select(['name', 'aggregator_id'])
                    ->indexBy('aggregator_id')
                    ->column(),
                'language' => 'uk',
                'options' => ['placeholder' => Yii::t('app', 'Вкажіть агрегатор'),]
            ])?>
    </div>

    <?php if ($model->invoice_type == 2) { ?>
        <div class="col-sm-12 col-md-6 col-lg-2">
           <?= $form->field($model, 'date_pay')->widget(DatePicker::class, [
               'language' => 'uk',
               'dateFormat' => 'yyyy-MM-dd',
               'options' => [
                   // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
                   'class'=> 'form-control',
                  // 'autocomplete'=>'off'
               ]
           ])?>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-2">
            <?= $form->field($model, 'period_from')->widget(DatePicker::class, [
                'language' => 'uk',
                'dateFormat' => 'yyyy-MM-dd',
                'options' => [
                    // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
                    'class'=> 'form-control',
                    // 'autocomplete'=>'off'
                ]
            ])?>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-2">
            <?= $form->field($model, 'period_to')->widget(DatePicker::class, [
                'language' => 'uk',
                'dateFormat' => 'yyyy-MM-dd',
                'options' => [
                    // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
                    'class'=> 'form-control',
                    // 'autocomplete'=>'off'
                ]
            ])?>
        </div>
    <?php } ?>
<br>
    <div class="form-group col-sm-12">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
