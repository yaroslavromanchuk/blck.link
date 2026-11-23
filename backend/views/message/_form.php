<?php

use common\models\Lang;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\Message */
/* @var $form yii\widgets\ActiveForm */

$langItems = Lang::find()
    ->select(['name', 'id'])
    ->where(['active' => 1])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();
?>

<div class="message-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textarea(['rows' => 4])->hint(Yii::t('app', 'Оригінальний ключ або україномовний текст')) ?>

    <?= $form->field($model, 'lang_id')->dropDownList($langItems, ['prompt' => Yii::t('app', 'Оберіть мову')]) ?>

    <?= $form->field($model, 'translate')->textarea(['rows' => 6])->hint(Yii::t('app', 'Переклад для обраної мови')) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
