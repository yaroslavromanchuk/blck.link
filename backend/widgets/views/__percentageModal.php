<?php

use yii\widgets\Pjax;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\models\Perc */
/* @var $track backend\models\Track */
/* @var $artist array */

Modal::begin([
    'header' => '<h4>Відсотки ' .  $track->name . '</h4>',
    'id' => 'percentage-modal' . $track->id,
]);

$classes = [
        1 => 'panel-success',
        2 => 'panel-warning',
        3 => 'panel-danger',
];

Pjax::begin(['id' => 'p_form_' . $model->track_id, 'enablePushState' => true,]);
$form = ActiveForm::begin([
    'id' => 'form_' . $model->track_id,
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to('track/percentage-update'),
    'options' => [
        'data-pjax' => true,
    ],
    'action' => '/track/percentage-update?trackId=' . $model->track_id
]);

foreach ($model->data as $key => $data) {
    ?>
    <div class="track-update row panel <?=$classes[$key]?? ""?>">
        <div class="panel-heading"><?=current(current($data))['ownership_name'] ?? ''?></div>
        <div class="panel-body">
            <?=$this->render('__percentageModalOne', ['data' => $data])?>
        </div>
    </div>
<?php } ?>
<span class="input-group-btn">
            <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-default']) ?>
    </span>
<?php
ActiveForm::end();
Pjax::end();
Modal::end();
?>