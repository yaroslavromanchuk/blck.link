<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UploadReport */


$this->title = 'Імпорт треків';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Імпорт треків');
?>
<div class="aggregator-update">
    <div class="row" id="upload_area">
        <?php
        $form = ActiveForm::begin([
            'id' => 'upload_file',
            'options' => [
                'enctype' => 'multipart/form-data',
            ]
        ]) ?>
        <div class="col-md-3">
            <?= $form->field($model, 'file')->fileInput()->label('Файл для імпорту') ?>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Завантажити'), [ 'class' => 'btn btn-success']) ?>
            </div>
        </div>
        <?php ActiveForm::end() ?>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <table class="table">
                <thead>
                <th>ISRC</th>
                <th>Назва треку</th>
                <th>ПІБ артиста<th>
                <th>Псевдонім артиста</th>
                <th>СублейблІД</th>
                </thead>
            </table>
            <tbody>
            </tbody>
        </div>
    </div>
</div>