<?php

use backend\models\Artist;
use backend\models\Track;
use backend\widgets\CreateArtist;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;

/** @var yii\web\View $this */
/** @var backend\models\Albums $model */
/** @var yii\widgets\ActiveForm $form */
$artistData = Artist::find()
	->select(['artist.name', 'artist.id'])
    ->where(['artist.label_id' => 0, 'artist.active' => 1])
	//->leftJoin('user', 'user.id = artist.admin_id')
	//->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
	->indexBy('artist.id')
	->column();

$tr = Track::find()
    ->select(['track.name', 'track.id'])
    ->innerJoin('artist', 'artist.id = track.artist_id')
    ->andOnCondition(['artist.active' => 1, 'artist.label_id' => 0]);

    if (empty($model->id)) {
        $tr->andFilterWhere(['is','track.album_id', null]);
    }
    
    if (!empty($model->artist_id)) {
        $tr->andFilterWhere(['track.artist_id' => $model->artist_id]);
    }

$trackData = $tr->indexBy('track.id')
    ->column();
//->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id])
    //->andFilterWhere(['track.is_album' => 0])
?>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_content">
				<?php
				$form = ActiveForm::begin([
					'options' => ['enctype' => 'multipart/form-data']
				]);
				?>
                <div class="row">
                    <div class="col-sm-12 col-md-6"><!--Трек-->
                        <div class="card">
                            <h5 class="card-header">Дані альбому</h5>
                            <div class="card-body">
                                <div class="row">
                                    <?= $form->field($model, 'admin_id')
                                        ->hiddenInput(['value'=>Yii::$app->user->identity->id])
                                        ->label(false)?>
                                    <div class="col-sm-12 col-md-6">
                                        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <?= $form->field($model, 'artist_id')
                                            ->widget(Select2::class, [
                                                'model' => $model,
                                                'data' => $artistData,
                                                'language' => 'uk',
                                                'options' => ['placeholder' =>  Yii::t('app', 'Виберіть артиста'),],
                                                'pluginOptions' => [
                                                    //  'allowClear' => true
                                                ],
                                            ]) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?php if($model->url){
                                            echo $form->field($model, 'url',  ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
                                        } else {
                                            echo $form->field($model, 'url', ['enableAjaxValidation' => true])->textInput(['maxlength' => true]);
                                        }  ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?= $form->field($model, 'youtube_link')->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <?= $form->field($model, 'type_id')
                                            ->widget(Select2::class, [
                                                'model' => $model,
                                                'data' => [
                                                    1 => 'Альбом',
                                                    2 => 'EP (міні альбом)',
                                                    //3 => 'Сингл',
                                                ],
                                                'language' => 'uk',
                                                'options' => ['placeholder' =>  Yii::t('app', 'Тип альбому'),],
                                                'pluginOptions' => [
                                                    //  'allowClear' => true
                                                ],
                                            ])
                                        ?>
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
                                    <div class="col-sm-6 col-md-6">
                                        <?= $form->field($model, 'active')
                                            ->checkbox([ 'value' => 1,  'checked' => (bool) $model->active, 'label' => 'Відображати']) ?>
                                    </div>
                                    <div class="col-sm-12">
                                        <?php // $form->field($model, 'img')->textInput(['maxlength' => true]) ?>
                                        <?php if(!empty($model->img)) {
                                            echo Html::img($model->image,['alt'=>'yii2 - картинка в gridview', 'style' => 'width: 200px; margin-top: 15px;']);
                                        }
                                        ?>
                                        <?= $form->field($model, 'img')
                                            ->hiddenInput(['value' => !empty($model->img) ? $model->img : ''])
                                            ->label(false)?>
                                        <?= $form->field($model, 'file')->fileInput() ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6"><!--Артист/Площадки/Фіти-->
                        <!--<div class="row">
                            <div class="col-sm-12">
                                <div class="card card-success">
                                    <h5 class="card-header">Артист</h5>
                                    <div class="card-body">
                                    </div>
                                    <div class="card-footer">
                                        <div class="form-group text-center">
											<?php //Html::Button(Yii::t('app', 'Додати артиста'),  ['class' => 'btn btn-sm btn-success','data-toggle' => 'modal', 'data-target' => '#artist-add-modal']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>-->
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="card">
                                    <h5 class="card-header">Треки</h5>
                                    <div class="card-body">
                                        <div class="col-sm-12 feeds">
                                            <?php
                                            $selected = [];
                                            
                                            foreach ($model->getTracks()->all() as $track) {
                                                $selected[$track->id] = ['selected' => true];
                                            }
                                            
                                            echo $form->field($model, 'tracks[]')
                                                ->widget(Select2::class, [
                                                    'model' => $model,
                                                    'data' => $trackData,
                                                    'language' => 'uk',
                                                    'options' => [
                                                        'multiple' => true,
                                                        //'values' => array_values($model->feeds),
                                                        'placeholder' =>  Yii::t('app', 'Вкажіть треки'),
                                                        'options' => $selected,
                                                    ],
                                                    'pluginOptions' => [
                                                        'allowClear' => true,
                                                    ],
                                                ])->label('Треки')?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="card"><!--Площадки-->
                                    <h5 class="card-header">Площадки</h5>
                                    <div class="card-body">
                                        <div class="row">
											<?php
											
											if (!empty($model->servise)) {
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
                    
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group text-center">
							<?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-lg btn-success']) ?>
                        </div>
                    </div>
                </div>
				<?php ActiveForm::end();
				?>
            </div>
        </div>
    </div>
</div>

<?php echo CreateArtist::widget(); ?>
<?php
$script = <<< JS
        $(function() {
            $(document).on('click', '[data-toggle=reroute]', function(e) {
                console.log(this);
                
            if($(this).data().tag == 'add') {
                var el = '<div class="col-sm-12"><div class="form-group field-servise"><div class="input-group"><input type="text"  class="form-control" name="Albums[servise][]" value="" aria-invalid="false"><span class="input-group-btn"><a class="btn btn-sm btn-danger" data-toggle="reroute" data-tag="dell">Видалити</a></span><div class="help-block"></div></div></div></div>';
        
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

