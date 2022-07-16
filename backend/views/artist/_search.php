<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\ArtistSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="artist-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'phone') ?>

    <?= $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'active') ?>
   <?php // echo $form->field($model, 'facebook') ?>
   <?php // echo $form->field($model, 'vk') ?>
   <?php // echo $form->field($model, 'twitter') ?>
   <?php // echo $form->field($model, 'youtube') ?>
   <?php // echo $form->field($model, 'instagram') ?>
   <?php // echo $form->field($model, 'telegram') ?>
   <?php // echo $form->field($model, 'viber') ?>
   <?php // echo $form->field($model, 'whatsapp') ?>
   <?php // echo $form->field($model, 'ofsite') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
