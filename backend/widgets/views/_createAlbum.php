<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;

/* @var $model backend\models\Albums */

Modal::begin([
    'header'=>'<h4>Створення альбому</h4>',
    'id'=>'album-add-modal',
]);

?>
    <div class="row">
        <div class=" col-xs-12 col-sm-12 col-md-12">
            <div class="card card-success">
                <div class="card-body">
                    <div class="album-form">
                        <?php
                           // Pjax::begin();
                            $form = ActiveForm::begin(['id' => 'album_add', 'action' => ['album/modal']]);
                        ?>
                        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                        <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
						<?= $form->field($model, 'artist_id')
							->widget(Select2::class, [
								'model' => $model,
								'data' => \backend\models\Artist::find()
									->select(['artist.name', 'artist.id'])
                                    ->where(['artist.label_id' => 0])
									->indexBy('artist.id')
									->column(),
								'language' => 'uk',
								'options' => ['placeholder' =>  Yii::t('app', 'Виберіть артиста'),],
								'pluginOptions' => [
									//  'allowClear' => true
								],
								//'pluginEvents' => [
								//'select2:select' => ' function(e) {  $("input.release").val(e.params.data.text); }'
								//]
							])?>
                            <div class="form-group">
                                <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
                            </div>
                        <?php ActiveForm::end();
                        //Pjax::end();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
Modal::end();
