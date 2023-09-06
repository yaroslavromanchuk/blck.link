<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use yii\helpers\Url;
//use dosamigos\tinymce\TinyMce;
use yii\jui\DatePicker;
//use backend\widgets\jui\DatePickerLanguageAsset;
//use backend\widgets\jui\DatePicker;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */
/* @var $form yii\widgets\ActiveForm */
use backend\widgets\CreateArtist;
?>
<div class="row">
    <div class="col-sm-12 col-md-6">
        <div class="row">
            
        </div>
    </div>
    <div class="col-sm-12 col-md-6">
        <div class="row">
            
        </div>
    </div>
            <div class="col-md-12 col-sm-12 col-xs-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2><?= Html::encode($this->title) ?></h2>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    
                     <?php
                   //  Pjax::begin([ 'enablePushState' => false]);
                     $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                    <div class="card" style="padding: 20px">
                        <span class="card-title">Артист</span>
                        <?= $form->field($model, 'admin_id')->hiddenInput(['value'=>Yii::$app->user->identity->id])->label(false)?> 
                    <div class="row">
                            <div class="col-sm-12">  
                                   <?= $form->field($model, 'artist_id')->widget(Select2::classname(), [
    'model' => $model,
    'data' => \backend\models\Artist::find()->select(['name', 'id'])->indexBy('id')->column(),
    'language' => 'ru',
    'options' => ['placeholder' =>  Yii::t('app', 'Выберите артиста'),],
    'pluginOptions' => [
      //  'allowClear' => true
    ],
            'pluginEvents' => [
              'select2:select' => ' function(e) {  $("input#track-artist").val(e.params.data.text); }'  
    ]        
]) ?>
                            </div>
                            <div class="col-sm-12">
                                <?= $form->field($model, 'artist')->textInput(['maxlength' => true]) ?>
                            </div>
                        <div class="col-sm-12">
                                  <div class="form-group ">
        <?= Html::Button(Yii::t('app', 'Добавить артиста'),  ['class' => 'btn btn-sm btn-danger','data-toggle' => 'modal', 'data-target' => '#artist-add-modal']) ?>
    </div>
                            </div>
                        </div>
                        </div>
                           <div class="card" style="padding: 20px">
                        <span class="card-title">Релиз</span>
                    <div class="row">
                          <div class="col-sm-12">
                               <?= $form->field($model, 'sharing')->checkbox([ 'value' => 1,  'checked' => (bool) $model->sharing, 'label' => 'Отобразить', ]) ?>
                        </div>
                        <div class="col-sm-12">
                             <?= $form->field($model, 'date')->widget(DatePicker::class, [
    'language' => 'ru',
    'dateFormat' => 'yyyy-MM-dd',
            'options' => [
       // 'placeholder' => Yii::$app->formatter->asDate($model->created_at),
        'class'=> 'form-control',
        'autocomplete'=>'off'
    ]
])?>
</div>
                       
                         <div class="col-sm-12">
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                         </div>
                        <div class="col-sm-12">
    <?php if($model->url){
        echo $form->field($model, 'url',  ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
    } else {
        echo $form->field($model, 'url', ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
    }  ?>
                    </div>
    <div class="col-sm-12">           
    <?php // $form->field($model, 'img')->textInput(['maxlength' => true]) ?>
    <?php if($model->img){ echo Html::img($model->image,['alt'=>'yii2 - картинка в gridview', 'style' => 'width: 200px; margin-top: 15px;']);}?>
    <?= $form->field($model, 'file')->fileInput() ?>
    </div> 
 <div class="col-sm-12">  
    <?= $form->field($model, 'youtube_link')->textInput(['maxlength' => true]) ?>
 </div>
 <div class="col-sm-12 ">  
    <?= $form->field($model, 'tag')->textInput(['maxlength' => true]) ?>
 </div>
                      
                    </div>
                        </div> 
                    
                        </div>
                        <div class="col-sm-12 col-md-6">
                <div class="card" style="padding: 20px">
                        <span class="card-title">Площадки</span>
                    <div class="row">
                        <?php

                        if (!is_null($model->servise)) {
							$services = unserialize($model->servise);
						if (is_array($services)) {
                            foreach ($services as $key => $service) { ?>

                        <div class="col-sm-12">
    <?= $form->field($model, 'servise[]', ['template'=>'<div class="input-group">{input}<span class="input-group-btn">
    <a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Удалить</a></span>{error}</div>'])->textInput(['maxlength' => true, 'value' => $service, 'id'=>'servise-'.$key]) ?>
                          </div>
                        <?php }
                        }
                        } ?>
                        <?= Html::a('+', null, [
        'class' => 'btn btn-success',
        'data' => [
            'toggle' => 'reroute',
            'tag' =>'add'
        ]
    ]) ?>
                    </div>
                      </div>
                    
                        </div>
                        </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group text-center">
        <?= Html::submitButton(Yii::t('app', 'Сохранить'), ['class' => 'btn btn-lg btn-success']) ?>
    </div>
                        </div>
                    </div>
   <?php ActiveForm::end(); ?>
                  <?php //Pjax::end(); ?>
                </div>
              </div>
            </div>

          </div>
<?= CreateArtist::widget([])?>
  <?php

$script = <<< JS
        $(function(){
    $(document).on('click', '[data-toggle=reroute]', function(e) {
        console.log(this);
    if($(this).data().tag == 'add'){
        var el = '<div class="col-sm-12"><div class="form-group field-servise"><div class="input-group"><input type="text"  class="form-control" name="Track[servise][]" value="" aria-invalid="false"><span class="input-group-btn"><a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Удалить</a></span><div class="help-block"></div></div></div></div>';
        
      //  var block = $(this).prev(".col-sm-12");
       // var cln = block.clone();
//cln.find("input:first").val('');
//$(this).before(cln);
        $(this).before(el);
            }else{
         var block = $(this).parents(".col-sm-12:first");
        console.log(block);
     block.detach();
            }

     
    });
});

JS;
$this->registerJs($script); 
?>


   

    

  
    
   



    

