<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;
use kartik\select2\Select2;
use backend\models\Artist;
use backend\models\Release;
use backend\widgets\CreateArtist;
use backend\widgets\CreateRelease;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

if (Yii::$app->user->identity->type == 1) {
    $artistData = Artist::find()
        ->select(['artist.name', 'artist.id'])
        ->leftJoin('user', 'user.id = artist.admin_id')
        ->andFilterWhere(['user.type' => 1])
        ->indexBy('artist.id')
        ->column();

    $releaseData = Release::find()
        ->select(['releases.release_name', 'releases.release_id'])
        ->leftJoin('user', 'user.id = releases.admin_id')
        ->andFilterWhere(['user.type' => 1])
        ->indexBy('releases.release_id')
        ->column();
} else {
    $artistData = Artist::find()
        ->select(['artist.name', 'artist.id'])
        ->andFilterWhere(['artist.admin_id' => Yii::$app->user->identity->id])
        ->indexBy('artist.id')
        ->column();

    $releaseData = Release::find()
        ->select(['releases.release_name', 'releases.release_id'])
        ->andFilterWhere(['releases.admin_id' => Yii::$app->user->identity->id])
        ->indexBy('releases.release_id')
        ->column();
}
?>
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
              <h2><?= Html::encode($this->title) ?></h2>
              <div class="clearfix"></div>
            </div>
            <div class="x_content">
                 <?php
               //  Pjax::begin([ 'enablePushState' => false]);
                 $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
                 ?>
                <div class="row">
                    <div class="col-sm-12 col-md-6"><!--Артист/Площадки-->
                        <div class="card card-success">
                            <h5 class="card-header">Артист</h5>
                            <div class="card-body">
                                <?= $form->field($model, 'admin_id')
                                    ->hiddenInput(['value'=>Yii::$app->user->identity->id])
                                    ->label(false)?>
                                <div class="row">
                                    <div class="col-sm-12">
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
                                                      'select2:select' => ' function(e) {  $("input#track-artist").val(e.params.data.text); }'
                                            ]
                                        ]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'artist')->textInput(['maxlength' => true]) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
                                    <?= Html::Button(Yii::t('app', 'Додати артиста'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#artist-add-modal']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h5 class="card-header">Реліз</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'release_id')
                                            ->widget(Select2::class, [
                                                'model' => $model,
                                                'data' => $releaseData,
                                                'language' => 'uk',
                                                'options' => ['placeholder' =>  Yii::t('app', 'Виберіте Реліз'),],
                                                'pluginOptions' => [
                                                    //  'allowClear' => true
                                                ],
                                                'pluginEvents' => [
                                                    'select2:select' => ' function(e) {  $("input.release").val(e.params.data.text); }'
                                                ]
                                            ]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <span class="release"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
                                    <?= Html::Button(Yii::t('app', 'Створити реліз'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#release-add-modal']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="card"><!--Площадки-->
                            <h5 class="card-header">Площадки</h5>
                            <div class="card-body">
                                <div class="row">
                                    <?php

                                    if (!is_null($model->servise)) {
                                        $services = unserialize($model->servise);

                                        if (is_array($services)) {
                                            foreach ($services as $key => $service) { ?>
                                                <div class="col-sm-12">
                                                    <?= $form->field($model,
                                                        'servise[]',
                                                        ['template'=>'<div class="input-group">{input}
                                                            <span class="input-group-btn">
                                                                <a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Видалити</a>
                                                            </span>{error}
                                                            </div>'
                                                        ])->textInput(['maxlength' => true, 'value' => $service, 'id'=>'servise-'.$key]) ?>
                                                </div>
                                            <?php }
                                        }
                                    } ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
                                    <?= Html::a('Додати', null, [
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
                    <div class="col-sm-12 col-md-6"><!--Трек-->
                        <div class="card">
                            <div class="card-body">
                                <span class="card-title">Трек</span>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'sharing')->checkbox([ 'value' => 1,  'checked' => (bool) $model->sharing, 'label' => 'Відображати', ]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'isrc')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'date')->widget(DatePicker::class, [
                                            'language' => 'uk',
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
                    </div>
                </div>
                <div class="row">
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
<?php //echo CreateArtist::widget(); ?>
<?php //echo CreateRelease::widget(); ?>
  <?php

$script = <<< JS
        $(function() {
    $(document).on('click', '[data-toggle=reroute]', function(e) {
        console.log(this);
    if($(this).data().tag == 'add'){
        var el = '<div class="col-sm-12"><div class="form-group field-servise"><div class="input-group"><input type="text"  class="form-control" name="Track[servise][]" value="" aria-invalid="false"><span class="input-group-btn"><a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Удалить</a></span><div class="help-block"></div></div></div></div>';
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
   

    

  
    
   



    

