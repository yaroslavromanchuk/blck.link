<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\models\Artist;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceItems */
/* @var $invoice backend\models\Invoice */
/* @var $form yii\widgets\ActiveForm */

$artistData = Artist::find()
    ->select(['artist.name', 'artist.id'])
    ->leftJoin('user', 'user.id = artist.admin_id')
    ->andFilterWhere(['user.type' => 1])
    ->indexBy('artist.id')
    ->column();

$trackData = \backend\models\Track::find()
    ->select(['track.name', 'track.id'])
    ->leftJoin('user', 'user.id = track.admin_id')
    ->andFilterWhere(['user.type' => 1])
    ->indexBy('track.id')
    ->column();
?>

<div class="invoice-items-form">
    <div class="panel panel-info">
        <div class="panel-heading">Форма додавання записів в інвойс</div>
        <div class="panel-body row">
            <?php $form = ActiveForm::begin([
                'action' => '/invoice-items/create?id=' . $invoice->invoice_id
            ]); ?>

            <?= $form->field($model, 'invoice_id')->hiddenInput(['value'=> $invoice->invoice_id])->label(false)?>

            <div class="col-sm-12 col-md-6, col-lg-4">
                <?= $form->field($model, 'artist_id')
                    ->widget(Select2::class, [
                        'model' => $model,
                        'data' => $artistData,
                        'language' => 'uk',
                        'options' => ['placeholder' =>  Yii::t('app', 'Виберіте артиста'),],
                        'pluginOptions' => [
                            //  'allowClear' => true
                        ],
                        'pluginEvents' => [
                            //'select2:select' => ' function(e) {  $("input#track-artist").val(e.params.data.text); }'
                        ]
                    ]) ?>
            </div>
            <div class="col-sm-12 col-md-6, col-lg-4">
                <?= $form->field($model, 'amount')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-sm-12 col-md-6, col-lg-4">
            <?= $form->field($model, 'track_id')
                ->widget(Select2::class, [
                    'model' => $model,
                    'data' => $trackData,
                    'language' => 'uk',
                    'options' => ['placeholder' =>  Yii::t('app', 'Можете вказати трек'),],
                    'pluginOptions' => [
                        //  'allowClear' => true
                    ],
                    'pluginEvents' => [
                        //'select2:select' => ' function(e) {  $("input#track-artist").val(e.params.data.text); }'
                    ]
                ]) ?>
            </div>
            <? //$form->field($model, 'isrc')->textInput(['maxlength' => true]) ?>
            <? //$form->field($model, 'date_item')->textInput() ?>
            <? //$form->field($model, 'last_update')->textInput() ?>
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Додати запис в інвойс'), ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
