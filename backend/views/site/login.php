<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Авторизація';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login" style="max-width: 320px;margin: 50px auto;text-align: center;">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Введіть дані для входу:</p>

    <div class="row1">
        <div class="col-lg-5111">
            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

                <?= $form->field($model, 'password')->passwordInput() ?>

                <?= $form->field($model, 'rememberMe')->checkbox() ?>
                
                   <!-- <div class="form-group"><? //Html::a('Регистрация', yii\helpers\Url::to(['site/signup']), ['class'=>'btn btn-link']) ?></div>-->
             <div class="form-group">
                 <? echo Html::a('Реесструвати Лейбл', yii\helpers\Url::to(['site/signup-label']), ['class'=>'btn btn-link']) ?></div>

            <div style="color:#999;margin:1em 0">
                    Ви можете <?= Html::a('відновити пароль', ['site/request-password-reset']) ?>.
                </div>
                <div class="form-group">
                    <?= Html::submitButton('Вхід', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
