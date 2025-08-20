<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\models\Artist;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceItems */
/* @var $invoice backend\models\Invoice */
/* @var $form yii\widgets\ActiveForm */

$artistData = Artist::find()
    ->select(['CONCAT(artist.name, " (", sub_label.name, ")")', 'artist.id'])
    ->leftJoin('sub_label', 'sub_label.id = artist.label_id')
   // ->leftJoin('user', 'user.id = artist.admin_id')
    //->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
    ->indexBy('artist.id')
    ->column();

$trackData = \backend\models\Track::find()
    ->select(['track.name', 'track.id'])
   // ->leftJoin('user', 'user.id = track.admin_id')
   // ->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
    ->indexBy('track.id')
    ->column();
?>

<div class="invoice-items-form">
    <div class="panel panel-info">
        <div class="panel-heading">Форма додавання записів в інвойс</div>
        <div class="panel-body">
            <?php $form = ActiveForm::begin([
                'action' => '/invoice-items/create?id=' . $invoice->invoice_id
            ]); ?>
            <div class="row">

            <?= $form->field($model, 'invoice_id')->hiddenInput(['value'=> $invoice->invoice_id])->label(false)?>

                <div class="col-sm-12 col-md-6, col-lg-2">
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
                <div class="col-sm-12 col-md-6, col-lg-1">
                    <?= $form->field($model, 'amount')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-sm-12 col-md-6, col-lg-2">
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
                <div class="col-sm-12 col-md-6, col-lg-2">
                    <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-2">


                <?= $form->field($model, 'date_item')->widget(DatePicker::class, [
                    'language' => 'uk',
                    'dateFormat' => 'yyyy-MM-dd',
                    'options' => [
                        // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
                        'class'=> 'form-control',
                        'autocomplete'=>'off'
                    ]
                ])->label('Дата')?>
                </div>
                <div class="form-group col-sm-12">
                    <?= Html::submitButton(Yii::t('app', 'Додати запис в інвойс'), ['class' => 'btn btn-success']) ?>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
