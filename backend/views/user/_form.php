<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $model frontend\models\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'disabled'=>true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'lastName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'firstName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'middleName')->textInput(['maxlength' => true]) ?>


    <?= $form->field($model, 'sex')->dropdownList(
            [
                'm'=>'Мужчина',
                'w' => 'Женщина'
            ],
    ['prompt'=>'Укажите пол']
            ) ?>

   <!--<?=$form->field($model, 'logo')->textInput(['maxlength' => true]) ?>-->
    <?= $form->field($model, 'file')->fileInput()->label('Иконка') ?>
   <?php if($model->logo){
      echo  Html::img(Yii::getAlias('@site').'/user/'.Yii::$app->user->identity->logo, ['style'=> 'margin-bottom:15px;']);
   } ?>

   <?php if(Yii::$app->user->identity->role->name == 'admin') {echo  $form->field($model, 'pass')->textInput();} ?>
   <!-- <?php // $form->field($model, 'auth_key')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'password_hash')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'password_reset_token')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'status')->textInput() ?>

    <?php // $form->field($model, 'created_at')->textInput() ?>

    <?php // $form->field($model, 'updated_at')->textInput() ?>
   -->

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Сохранить'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
