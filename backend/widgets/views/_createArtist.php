<?php
 
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
/* @var $model backend\models\Artist */

Modal::begin([
    'header'=>'<h4>Створення актиста</h4>',
    'id'=>'artist-add-modal',
]);

?>
<div class="row">
            <div class=" col-xs-12 col-sm-12 col-md-12 ">
              <div class="x_panel">
                <div class="x_content">
                    
    <?php $form = ActiveForm::begin(['id' => 'artist_add', 'action' => ['artist/modal'],  'options' => ['enctype' => 'multipart/form-data']]); ?>
                    <div class="row">
           <?= $form->field($model, 'admin_id')->hiddenInput(['value'=>Yii::$app->user->identity->id])->label(false)?>  
      <?= $form->field($model, 'active')->hiddenInput(['value' => 1])->label(false)?>
                 
                        <div class="col-sm-12 col-md-6 col-lg-4">
<?= $form->field($model, 'file')->fileInput()->label('Іконка') ?>
                                    </div>
                        <div class="col-sm-12 col-md-12">
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                                </div>  <div class="col-sm-12 col-md-6">
    <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
                                        </div> <div class="col-sm-12 col-md-6">
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                                            </div>
                                <div class="col-sm-12 col-md-12">
    <?= $form->field($model, 'facebook')->textInput(['maxlength' => true]) ?>
                                                </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'vk')->textInput(['maxlength' => true]) ?>
                                                    </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'twitter')->textInput(['maxlength' => true]) ?>
                                                        </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'youtube')->textInput(['maxlength' => true]) ?>
                                                            </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'instagram')->textInput(['maxlength' => true]) ?>
                                                                </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'telegram')->textInput(['maxlength' => true]) ?>
                                                                    </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'viber')->textInput(['maxlength' => true]) ?>
                                                                        </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'whatsapp')->textInput(['maxlength' => true]) ?>
                                                                            </div> <div class="col-sm-12 col-md-12">
   <?= $form->field($model, 'ofsite')->textInput(['maxlength' => true]) ?>
</div>
                        <div class="col-sm-12 col-md-6 col-lg-12">
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
<?php 
Modal::end();