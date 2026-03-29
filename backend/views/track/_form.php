<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;
use kartik\select2\Select2;
use backend\models\Artist;
use backend\models\Release;
use backend\models\Track;
use backend\widgets\CreateArtist;
use backend\widgets\CreateRelease;
use backend\widgets\CreateAlbum;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Track */

$artistData = Artist::getDb()->cache(function ($db) {
    return Artist::find()
    ->select(['CONCAT(artist.name, " (", sub_label.name, ")")', 'artist.id'])
    ->leftJoin('sub_label', 'sub_label.id = artist.label_id')
    ->where(['artist.active' => 1])
    //->leftJoin('user', 'user.id = artist.admin_id')
    //->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
    ->indexBy('artist.id')
    ->column();
}, 30);

$releaseData = Release::getDb()->cache(function ($db) {
    return Release::find()
    ->select(['releases.release_name', 'releases.release_id'])
    //->leftJoin('user', 'user.id = releases.admin_id')
    //->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
    ->indexBy('releases.release_id')
    ->column();
}, 3600);

$albumData = Track::getDb()->cache(function ($db) {
    return Track::find()
        ->select(['track.name', 'track.id'])
        /// ->leftJoin('user', 'user.id = track.admin_id')
        //->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
        ->andFilterWhere(['track.is_album' => 1])
        ->indexBy('track.id')
        ->column();
}, 3600); // Кешування на 1 годину (3600 секунд)
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
                 $form = ActiveForm::begin([
                         'id' => 'track-form',
                     
                     'enableAjaxValidation' => true,
                     'enableClientValidation' => true,
                     
                     'options' => ['enctype' => 'multipart/form-data']
                 ]);
                 ?>
                <div class="row">
                    <div class="col-sm-12 col-md-6"><!--Артист/Площадки/Фіти-->
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
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
                                                'select2:select' => ' function(e) {
                                                    var data = e.params.data;
                                                    console.log("Selected:", data);

                                                 // $("input#track-artist_name").val(e.params.data.text);
                                                }'
                                            ]
                                        ]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'artist_name')->textInput(['maxlength' => true]) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
                                    <?= Html::Button(Yii::t('app', 'Додати артиста'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#artist-add-modal']) ?>
                                </div>
                            </div>
                        </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                        <div class="card">
                            <h5 class="card-header">Фіти</h5>
                            <div class="card-body">
                                <div class="col-sm-12 feeds">
                                    <?php
                                    $selected = [];

                                    foreach ($model->getFeeds() as $feed) {
                                        $selected[$feed] = ['selected' => true];
                                    }

                                    unset($artistData[$model->artist_id]);

                                    echo $form->field($model, 'feeds[]')
                                        ->widget(Select2::class, [
                                            'model' => $model,
                                            'data' => $artistData,
                                            'language' => 'uk',
                                            'options' => [
                                                'multiple' => true,
                                                //'values' => array_values($model->feeds),
                                                'placeholder' =>  Yii::t('app', 'Виберіте артиста на фіті'),
                                                'options' => $selected,
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' => true,
                                            ],
                                        ])->label('Виберіть артистів на фітах')?>
                                </div>
                            </div>
                        </div>
                            </div>
                        </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6">
                        <div class="card">
                            <h5 class="card-header">Альбом</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
										<?= $form->field($model, 'album_id')
											->widget(Select2::class, [
												'model' => $model,
												'data' => \backend\models\Albums::find()
													->select(['albums.name', 'albums.id'])
													//->leftJoin('user', 'user.id = releases.admin_id')
													//->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
													->indexBy('albums.id')
													->column(),
												'language' => 'uk',
												'options' => ['placeholder' =>  Yii::t('app', 'Виберіть альбом'),],
												'pluginOptions' => [
													 'allowClear' => true
												],
												//'pluginEvents' => [
													//'select2:select' => ' function(e) {  $("input.release").val(e.params.data.text); }'
												//]
											]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <span class="release"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
									<?= Html::Button(Yii::t('app', 'Створити альбом'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#album-add-modal']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- <div class="col-sm-12 col-md-6 col-lg-6">
                        <div class="card">
                            <h5 class="card-header">Реліз</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <?php /*$form->field($model, 'release_id')
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
                                            ]) */?>
                                    </div>
                                    <div class="col-sm-12">
                                        <span class="release"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group text-center">
                                    <?php // Html::Button(Yii::t('app', 'Створити реліз'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#release-add-modal']) ?>
                                </div>
                            </div>
                        </div>
                    </div>-->
                    <div class="col-sm-12">
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
                    </div>
                </div>
                    <div class="col-sm-12 col-md-6"><!--Трек-->
                        <div class="card">
                            <div class="card-body">
                                <span class="card-title">Трек</span>
                                <div class="row">
                                   <!-- <div class="col-sm-6 col-md-6">
                                        <?php // $form->field($model, 'sharing')->checkbox([ 'value' => 1,  'checked' => (bool) $model->sharing]) ?>
                                    </div>-->
                                    <div class="col-sm-6 col-md-6">
                                        <?= $form->field($model, 'active')->checkbox([ 'value' => 1,  'checked' => (bool) $model->active, 'label' => 'Відображати']) ?>
                                    </div>
                                    <div class="col-sm-6 col-md-6">

                                        <?php $form->field($model, 'is_album')
                                            ->checkbox([ 'value' => 1, 'checked' => (bool) $model->is_album, 'label' => 'Це альбом', 'onchange' => 'if($(this).prop("checked")) { $("#track-album_id").val(\'\').prop("disabled", true);} else {$("#track-album_id").prop("disabled", false);}']) ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <?= $form->field($model, 'isrc')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
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

                                    <div class="col-sm-12 col-md-6">
                                        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <?php if($model->url){
                                            echo $form->field($model, 'url',  ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
                                        } else {
                                            echo $form->field($model, 'url', ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
                                        }  ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?php // $form->field($model, 'img')->textInput(['maxlength' => true]) ?>
                                        <?php if(!empty($model->img)) {
                                            echo Html::img($model->image, ['alt'=>'yii2 - картинка в gridview', 'style' => 'width: 200px; margin-top: 15px;']);
                                        }
                                        ?>
                                        <?= $form->field($model, 'file')->fileInput() ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <?= $form->field($model, 'youtube_link')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
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

                <div id="upload-loader" style="display:none; text-align:center; margin-top:20px;">
                    <div class="spinner-border text-success" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div style="margin-top:10px;">
                        Збереження даних… будь ласка, зачекайте
                    </div>
                </div>

                <div id="upload-progress" style="display:none; margin-top:15px;">
                    <div class="progress">
                        <div id="upload-progress-bar"
                             class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             style="width:0%">
                            0%
                        </div>
                    </div>
                </div>


                <?php ActiveForm::end();
            ?>
        </div>
      </div>
</div>
<?php echo CreateArtist::widget(); ?>
<?php //CreateRelease::widget(); ?>
<?php echo CreateAlbum::widget(); ?>
  <?php

$script = <<< JS

        $(function() {
        
$(document).on('beforeSubmit', 'form#track-form', function (e) {
    e.preventDefault();
    console.log('qrwq2342');

    const form = $(this);
    const formData = new FormData(form[0]);
    
    
// ✅ показати лоадер
    $('#upload-loader').show();

    // ✅ заблокувати кнопку сабміту
    $(form).find(':submit').prop('disabled', true);


    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        contentType: false, // 🔴 критично
        processData: false, // 🔴 критично
        xhr: function () {
            const xhr = new window.XMLHttpRequest();
    
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
    
                    $('#upload-progress').show();
                    $('#upload-progress-bar')
                        .css('width', percent + '%')
                        .text(percent + '%');
                }
            });
    
            return xhr;
        },
        success: function (res) {
            console.log(res);
            if (res && res.success && res.redirect) {
                // ✅ редірект після успішного upload
                window.location.href = res.redirect;
            }
        },

        error: function (xhr) {
            alert('Помилка при завантаженні');
            console.error(xhr.responseText);
        },
        complete: function () {
        $('#upload-loader').hide();
        $('#upload-progress').hide();
        $('#upload-progress-bar').css('width', '0%').text('0%');
        $(form).find(':submit').prop('disabled', false);
        }
    });

    return false;
});
            
            $(document).on('click', '[data-toggle=reroute]', function(e) {
                console.log(this);
                
            if($(this).data().tag == 'add') {
                var el = '<div class="col-sm-12"><div class="form-group field-servise"><div class="input-group"><input type="text"  class="form-control" name="Track[servise][]" value="" aria-invalid="false"><span class="input-group-btn"><a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Удалить</a></span><div class="help-block"></div></div></div></div>';
        
                $(this).before(el);
            } else {
                var block = $(this).parents(".col-sm-12:first");
                console.log(block);
                block.detach();
            }
    });

    $(document).on('click', '[data-toggle=feeds]', function(e) {
        console.log(this);
        
    if($(this).data().tag == 'add') {
        var el = ''; 
        
       // $('.feedsAll').after(el);
        
        console.log(el);
        var el = '' +
         '<div class="col-sm-12">' +
            '<div class="form-group field-feeds">' +
              '<div class="input-group">' +
               '<input type="text"  class="form-control" name="Track[feeds][]" value="" aria-invalid="false">' +
                    '<span class="input-group-btn">' +
                     '<a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Видалити</a>' +
                     '</span>' +
                   '<div class="help-block"></div>' +
               '</div>' +
            '</div>' +
         '</div>';
        $(this).before(el);
    } else {
        var block = $(this).parents(".col-sm-12:first");
        console.log(block);
        block.detach();
    }

    });
});



JS;
$this->registerJs($script); 
?>
   

    

  
    
   



    

