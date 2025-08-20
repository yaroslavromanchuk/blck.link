<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="row">
    <div class=" col-xs-12 col-sm-12 col-md-12 ">
      <div class="x_panel">
        <div class="x_title">
            <h2><?= Html::encode($this->title) ?></h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <?php $form = ActiveForm::begin([ 'options' => ['enctype' => 'multipart/form-data']]); ?>
            <?= $form->field($model, 'admin_id')->hiddenInput(['value'=>Yii::$app->user->identity->id])->label(false)?>
            <div class="row">
                <div class="panel panel-info col-sm-12">
                    <div class="row panel-body">
                        <div class="col-sm-12 col-md-2">
                            <?= $form->field($model, 'active')->checkbox([ 'value' => 1,  'checked ' => true ]) ?>
                        </div>
                        <div class="col-sm-12 col-md-2">
                            <?= $form->field($model, 'percentage')->textInput(['max' => 100]) ?>
                        </div>
                        <div class="col-sm-12  col-md-4">
                            <?= $form->field($model, 'label_id')->widget(Select2::class, [
                                'model' => $model,
                                'data' => \common\models\SubLabel::find()
                                    ->select(['name', 'id'])
                                    ->where(['active' => 1])
                                    ->indexBy('id')
                                    ->column(),
                                'language' => 'uk',
                                'options' => ['placeholder' =>  Yii::t('app', 'Вкажіть лейбл'),],
                            ]) ?>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <?= $form->field($model, 'artist_type_id')
                                ->widget(Select2::class, [
                                    'model' => $model,
                                    'data' => [
                                        1 => 'ФІЗ',
                                        2 => 'ЮР',
                                    ],
                                    'language' => 'uk',
                                    'options' => [
                                            'placeholder' => Yii::t('app', 'Вкажіть тип'),
                                        ],
                                ]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'file')->fileInput() ?>
                        </div>
                    </div>
                </div>
                <div class="panel panel-info col-sm-12">
                    <div class="row panel-body">
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'facebook')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'twitter')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'youtube')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'instagram')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'telegram')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'viber')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'whatsapp')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <?= $form->field($model, 'ofsite')->textInput(['maxlength' => true]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group text-center">
                        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-lg btn-success']) ?>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
      </div>
    </div>
</div>
