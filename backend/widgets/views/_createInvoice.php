<?php

use common\models\SubLabel;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\jui\DatePicker;

/* @var $invoice backend\models\Invoice */

Modal::begin([
    'header'=>'<h4>Дані для створення інвойсу</h4>',
    'id'=>'invoice-add-modal',
]);

$currency = \backend\models\Currency::find()
    ->select(['currency_name', 'currency_id'])
    ->indexBy('currency_id')
    ->column();

?>
    <div class="row">
        <div class=" col-xs-12 col-sm-12 col-md-12">
            <div class="card card-success">
                <div class="card-body">
                    <div class="release-form">
                        <?php
                        $form = ActiveForm::begin([
                            'id' => 'create_invoice',
                            //'enableClientValidation' => true,
                            'enableAjaxValidation' => true,
                            'action' => ['artist/create-invoice'],
                            'validationUrl' => ['artist/create-invoice'],
                            // 'options' => ['data-pjax' => true,]
                        ]);
                        ?>
                        <div class="row">
                            <div class="col-sm-12 col-md-4">
                                <?= $form->field($invoice, 'quarter')
                                    ->dropDownList([1 => 1, 2 => 2, 3 => 3, 4 => 4]) ?>
                            </div>
                            <div class="col-sm-12 col-md-4">
                                <?= $form->field($invoice, 'year')
                                    ->dropDownList([2024 => 2024, 2025 => 2025]) ?>
                            </div>
                            <div class="col-sm-12 col-md-4">
                                <?= $form->field($invoice, 'date_pay')->widget(DatePicker::class, [
                                    'language' => 'uk',
                                    'dateFormat' => 'yyyy-MM-dd',
                                    'options' => [
                                        // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
                                        'class'=> 'form-control',
                                        //'autocomplete'=>'on'
                                    ]
                                ])?>
                            </div>
                                <div class="col-sm-12 col-md-6">
                        <?= $form->field($invoice, 'currency_id')
                            ->widget(Select2::class, [
                                'model' => $invoice,
                                'data' => $currency,
                                'language' => 'uk',
                                'options' => ['placeholder' => Yii::t('app', 'Вкажіть валюту виплати'),],
                                'pluginOptions' => [
                                    //  'allowClear' => true
                                ],
                                'pluginEvents' => [
                                    'select2:select' => 'function(e) {
                                      $("#invoice-artist_ids").val(jQuery(".grid-view").yiiGridView("getSelectedRows"));
                                      
                                      if ($(this).val() == 1) { // EURO
                                        $("#invoice-exchange").val(\'\').prop("disabled", false);
                                        $("#invoice-aggregator_id").val(10);
                                      } else if ($(this).val() == 3) { //USD
                                        $("#invoice-exchange").val(\'\').prop("disabled", false);
                                        $("#invoice-aggregator_id").val(13);
                                      } else { //UAH
                                        $("#invoice-exchange").val(1).prop("disabled", true );
                                        $("#invoice-aggregator_id").val(11);
                                      }
                 
                                      }',
                                    'select2:unselect' => 'function(e) {
                                       $("#invoice-exchange").val(1).prop( "disabled", true );
                                    }'
                                ]
                            ]) ?>
                                </div>

                                <div class="col-sm-12 col-md-6">
                        <?= $form->field($invoice, 'exchange')
                            ->textInput()?>
                        <?= $form->field($invoice, 'artist_ids')
                            ->hiddenInput(['value'=> 0])
                            ->label(false)?>
                            <?= $form->field($invoice, 'user_id')
                                ->hiddenInput(['value'=>Yii::$app->user->identity->id])
                                ->label(false)?>
                            <?= $form->field($invoice, 'invoice_type')
                                ->hiddenInput(['value'=> 2 ])
                                ->label(false)?>
                            <?= $form->field($invoice, 'aggregator_id')
                                ->hiddenInput(['value'=> 10])
                                ->label(false)?>
                                </div>
                            <div class="col-sm-12 col-md-4">
                                <?= $form->field($invoice, 'label_id')
                                    ->dropDownList(ArrayHelper::map(SubLabel::find()->where(['active' => 1])->asArray()->all(), 'id', 'name')) ?>
                            </div>
                                <div class="col-sm-12">
                                    <div class="form-group text-center">
                                        <br>
                                        <?= Html::submitButton(Yii::t('app', 'Створити інвойс'), ['class' => 'btn btn-success']) ?>
                                    </div>
                                </div>

                    </div>
                        <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
<?php
Modal::end();
