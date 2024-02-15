<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Percentage */
/* @var $artist array */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="release-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'track_id')->hiddenInput(['value' => $model->track_id])->label(false) ?>
    <?= $form->field($model, 'artist_id')
        ->widget(Select2::class, [
            'model' => $model,
            'data' => $artist,
            'language' => 'uk',
            'options' => ['placeholder' =>  Yii::t('app', 'Виберіть артиста'),],
            'pluginOptions' => [
                  'allowClear' => true
            ],
        ]) ?>
    <?= $form->field($model, 'percentage')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
