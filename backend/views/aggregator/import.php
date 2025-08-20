<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model backend\models\ImportFile */
/* @var $result array */

$this->title = 'Пошук ISRC';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Агрегатори'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Пошук ISRC');
?>
<div class="aggregator-import row">
    <div class="row">
        <div class="col-sm-12">
            Приклад структули файлу
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th>Виконавець</th>
                    <th>Назва твору</th>
                    <th>Країна</th>
                    <th>Платформа</th>
                    <th>Дата звіту</th>
                    <th>Кількість виконань</th>
                    <th>Сума</th>
                    <th>ISRC</th>
                    <th>Номер (для броми)</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        //Pjax::begin();
        $form = ActiveForm::begin([
                'id' => 'import_file',
                'options' => [
                    'enctype' => 'multipart/form-data',
                ]
            ]) ?>
            <div class="col-md-3">
                <?= $form->field($model, 'file')->fileInput() ?>
            </div>
        <div class="col-md-3">
            <?= $form->field($model, 'isBroma')->checkbox() ?>
        </div>
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Завантажити'), [ 'class' => 'btn btn-success']) ?>
                </div>
            </div>
        <?php ActiveForm::end() ?>
        <?php //Pjax::end() ?>
    </div>
    <br>
    <div class="row">
        <div class="col-sm-12">
            <table class="table table-bordered table-hover">
              <?php
              $i = 1;
              foreach ($result as $row) { ?>
                  <tr>
                      <td><?=$i?></td>
                      <?php foreach ($row as $item) {?>
                          <td><?=$item?></td>
                     <?php }?>
                  </tr>
             <?php $i++; }
              ?>
            </table>
        </div>
    </div>
</div>
