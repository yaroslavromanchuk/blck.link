<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
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
                'm'=>'Чоловік',
                'w' => 'Жінка',
            ],
            [
                    'prompt'=>'Вкажіть стать',
            ]
            ) ?>

   <!--<? //$form->field($model, 'logo')->textInput(['maxlength' => true]) ?>-->
    <?= $form->field($model, 'file')->fileInput()->label('Иконка') ?>
   <?php

   if(Yii::$app->user->can('label')) {
   }

   if($model->logo) {
      echo  Html::img(Yii::getAlias('@site').'/user/'.Yii::$app->user->identity->logo, ['style'=> 'margin-bottom:15px;']);
   }

   if(Yii::$app->user->can('admin')) {
       echo  $form->field($model, 'pass')->textInput();
   } ?>


   <!-- <?php // $form->field($model, 'auth_key')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'password_hash')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'password_reset_token')->textInput(['maxlength' => true]) ?>

    <?php // $form->field($model, 'status')->textInput() ?>
   -->

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
