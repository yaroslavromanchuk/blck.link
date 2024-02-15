<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model SignupLabelForm */

use backend\models\SignupLabelForm;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Реестрація Лейбла';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup" style="max-width: 1024px;margin: auto;">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Будь-ласка, заповніть поля щоб зарееструвати Лейбл:</p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

                <?= $form->field($model, 'email') ?>

                <?= $form->field($model, 'url') ?>

                <?= $form->field($model, 'password')->passwordInput() ?>

                <div class="form-group">
                    <?= Html::submitButton('Відправити', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
