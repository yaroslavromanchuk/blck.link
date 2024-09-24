<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;

/* @var $invoice backend\models\Invoice */

Modal::begin([
    'header'=>'<h4>Вкажіть валюту інвойсу</h4>',
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
                            $form = ActiveForm::begin(['id' => 'create_invoice', 'action' => ['artist/create-invoice']]);
                        ?>
                        <?= $form->field($invoice, 'user_id')
                            ->hiddenInput(['value'=>Yii::$app->user->identity->id])
                            ->label(false)?>
                        <?= $form->field($invoice, 'invoice_type')
                            ->hiddenInput(['value'=> 2 ])
                            ->label(false)?>
                        <?= $form->field($invoice, 'aggregator_id')
                            ->hiddenInput(['value'=> 10])
                            ->label(false)?>

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
                                      $("#invoice-artist_ids").val(jQuery("#w0").yiiGridView("getSelectedRows")); 
                                      }'
                                ]
                            ]) ?>

                        <?= $form->field($invoice, 'artist_ids')
                            ->hiddenInput(['value'=> 0])
                            ->label(false)?>

                                <div class="form-group">
                                    <?= Html::submitButton(Yii::t('app', 'Створити інвойс'), ['class' => 'btn btn-success']) ?>
                                </div>

                        <?php ActiveForm::end();?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
Modal::end();
