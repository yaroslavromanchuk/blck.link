<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model backend\models\TrackSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="card">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>
    <div class="card-body">
        <span class="card-title">Поиск</span>
        <div class="row">
        <div class="col-sm-12 col-md-6 col-lg-3">
              <?= $form->field($model, 'artist_id')->widget(Select2::classname(), [
    'model' => $model,
    'data' => \backend\models\Artist::find()->select(['name', 'id'])->indexBy('id')->column(),
    'language' => 'ru',
    'options' => ['placeholder' =>  Yii::t('app', 'Выберите артиста'),],
    'pluginOptions' => [
        'allowClear' => true
    ],        
]) ?>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-3">
              <?= $form->field($model, 'name')->widget(Select2::classname(), [
    'model' => $model,
    'data' => \backend\models\Track::find()->select(['name', 'id'])->indexBy('name')->column(),
    'language' => 'ru',
    'options' => ['placeholder' =>  Yii::t('app', 'Выберите релиз'),],
    'pluginOptions' => [
        'allowClear' => true
    ],        
]) ?>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-3">
            <?php  echo $form->field($model, 'url')->widget(Select2::classname(), [
    'model' => $model,
    'data' => \backend\models\Track::find()->select(['url', 'id'])->indexBy('url')->column(),
    'language' => 'ru',
    'options' => ['placeholder' =>  Yii::t('app', 'Выберите ссылку'),],
    'pluginOptions' => [
        'allowClear' => true
    ],        
]) ?>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-3">
             <?= $form->field($model, 'date')->widget(DatePicker::class, [
    'language' => 'ru',
    'dateFormat' => 'yyyy-MM-dd',
    'options' => [
       // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
        'class'=> 'form-control',
        'autocomplete'=>'off'
    ],
    'clientOptions' => [
        'changeMonth' => true,
       // 'changeYear' => true,
       // 'yearRange' => '2020:2025',
        //'showOn' => 'button',
        //'buttonText' => 'Выбрать дату',
       // 'buttonImageOnly' => true,
       //'buttonImage' => 'images/calendar.gif'
    ]
])?>
        </div>
            <div class="col-ms-12 text-center">
                <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Искать'), ['class' => 'btn  btn-primary']) ?>
        <?php // Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>
            </div>
         </div>
        
    </div>
    

    <?php ActiveForm::end(); ?>

</div>
