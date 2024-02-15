<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UploadReport */

$this->title = 'Завантаження звітів';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Агрегатори'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Завантаження звіту');
?>
<div class="aggregator-update row">
<div class="row">
    <?php
    $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>
    <div class="col-md-3">
    <?= $form->field($model, 'aggregatorId')->dropDownList(\backend\models\Aggregator::find()
        ->select(['name', 'aggregator_id'])
        ->indexBy('aggregator_id')
        ->column()) ?>
    </div>
    <div class="col-md-3">
    <?= $form->field($model, 'file')->fileInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
    </br>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Завантажити'), ['class' => 'btn btn-success']) ?>
        </div>
    </div>
</div>

    <?php ActiveForm::end() ?>
</div>