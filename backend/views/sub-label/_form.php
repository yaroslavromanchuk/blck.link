<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\SubLabel */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sub-label-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php //$form->field($model, 'url')->textInput(['maxlength' => true, 'disabled' => true]) ?>

    <?php //$form->field($model, 'logo')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-6 col-md-3">
            <?= $form->field($model, 'active')->checkbox([0 => 'Inactive', 1 => 'Active']) ?>
        </div>
        <div class="col-sm-6 col-md-3">
            <?= $form->field($model, 'percentage')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-6 col-md-3">
            <?= $form->field($model, 'percentage_distribution')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-sm-6 col-md-3">
            <?= $form->field($model, 'label_type_id')
                ->widget(Select2::class, [
                    'model' => $model,
                    'data' => [
                        1 => 'ФІЗ',
                        2 => 'ЮР',
                    ],
                    'language' => 'uk',
                    'options' => ['placeholder' =>  Yii::t('app', 'Вкажіть тип'),],
                ]) ?>
        </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'tov_name')->textInput(['maxlength' => true, 'placeholder' => 'БЕСТ МЬЮЗІК']) ?>
    </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'contract')->textInput(['maxlength' => true, 'placeholder' => '792-ПФ від 14.06.2023']) ?>
    </div>
    <div class="col-sm-12 col-md-3">
        <?= $form->field($model, 'full_name')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-3 col-md-3">
        <?= $form->field($model, 'ipn')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-3 col-md-3">
        <?= $form->field($model, 'edrpou')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-3 col-md-3">
        <?= $form->field($model, 'bank')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-3 col-md-3">
        <?= $form->field($model, 'mfo')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-6 col-md-3">
        <?= $form->field($model, 'iban')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-12 col-md-3">
        <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
    </div>
</div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
