<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UploadReport */
/* @var $result array */
/* @var $foundTrack int */
/* @var $addedTrack int */

$this->title = 'Імпорт треків';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Імпорт треків');
?>
<div class="aggregator-update">
    <div class="row">
        <div class="col-sm-12">
            <p>Формат файлу для імпорту</p>
            <table class="table">
                <thead>
                <th>ISRC</th>
                <th>Назва треку</th>
                <th>ПІБ артиста</th>
                <th>Псевдонім артиста</th>
                <th>СублейблІД</th>
                </thead>
            </table>
        </div>
    </div>
    <p>Форма завантаженя файлу</p>
    <div class="row" id="upload_area">
        <div class="col-sm-12">
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
    </div>
</div>
<br>
<div class="row">
    <div class="col-sm-12">
        <p>Результат імпорту</p>
        
        <?php if ($foundTrack > 0) : ?>
            <div class="alert alert-warning">
                <p>Знайдено треків: <?= $foundTrack ?></p>
            </div>
        <?php endif; ?>
        <?php if ($addedTrack > 0) : ?>
            <div class="alert alert-success">
                <p>Додано треків: <?= $addedTrack ?></p>
            </div>
        <?php endif; ?>
        <table class="table table-primary">
            <thead>
                <th>ISRC</th>
                <th>Назва треку</th>
                <th>ПІБ артиста</th>
                <th>Псевдонім артиста</th>
                <th>СублейблІД</th>
                <th>Результат імпорту</th>
            </thead>
            <tbody>
                <?php if (!empty($result)) : ?>
                    <?php foreach ($result as $res) : ?>
                        <tr>
                            <td><?= $res['isrc'] ?></td>
                            <td><?= $res['track_name'] ?></td>
                            <td><?= $res['artist_name'] ?></td>
                            <td><?= $res['artist_alias'] ?></td>
                            <td><?= $res['sub_label'] ?></td>
                            <td><?= implode(', ', $res['import_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6">Немає результатів імпорту</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>