<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model backend\models\Percentage */

?>
<div class="col-sm-12">
    <?php
    $form = ActiveForm::begin([
            'id' => 'form_' . $model->id,
            'options' => [
                'data-pjax' => true,
            ],
            'action' => '/track/percentage-update?trackId=' . $model->track_id . '&id=' . $model->id
    ]);
    echo $form->field($model, 'track_id')->hiddenInput(['value' => $model->track_id, 'id' => 'track_id' . $model->id])->label(false);
    echo $form->field($model, 'artist_id')->hiddenInput(['value'=> $model->artist_id])->label(false);
    echo $form->field($model, 'ownership_type')->hiddenInput(['value' => $model->ownership_type])->label(false);
    ?>
    <div class="input-group">
        <span class="input-group-addon" id="basic-addon1"><?=$model->artist_id == 0 ? 'All' : $model->getFullName()?></span>
        <?=$form->field($model, 'percentage')
            ->textInput(['id' => 'percentage' . $model->id,'maxlength' => true, 'class' => 'form-control', 'aria-describedby' => 'button-addon2'])
            ->label(false)?>
        <span class="input-group-btn">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-default']) ?>
    </span>
    </div>
    <?php ActiveForm::end();?>
</div>

<?php

$script = "$('#form_" . $model->id . "').on('afterSubmit', function (e) {
    e.preventDefault();
    return false;
});";

//$this->registerJs($script, yii\web\View::POS_READY);