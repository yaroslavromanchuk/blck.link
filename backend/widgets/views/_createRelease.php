<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;

/* @var $model backend\models\Release */

Modal::begin([
    'header'=>'<h4>Створення релізу</h4>',
    'id'=>'release-add-modal',
]);
?>
    <div class="row">
        <div class=" col-xs-12 col-sm-12 col-md-12">
            <div class="card card-success">
                <div class="card-body">
                    <div class="release-form">
                        <?php
                            Pjax::begin();
                                $form = ActiveForm::begin(['id' => 'release_add', 'action' => ['release/modal']]);
                       ?>
                                <?= $form->field($model, 'release_name')->textInput(['maxlength' => true]) ?>

                                <div class="form-group">
                                    <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
                                </div>

                        <?php ActiveForm::end();
                        Pjax::end();
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
Modal::end();
