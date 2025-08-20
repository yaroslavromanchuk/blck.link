<?php

use backend\models\Artist;
use backend\models\Currency;
use backend\models\InvoiceType;
use yii\jui\DatePicker;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceReport */
/* @var $invoice array */

?>

<div class="invoice-form row">
    <?php $form = ActiveForm::begin([
        'id' => 'create_invoice_report',
        //'enableClientValidation' => true,
        'enableAjaxValidation' => true,
        // 'action' => ['artist/create-invoice'],
        'validationUrl' => Url::to('report'),
    ]); ?>
    <div class="col-sm-12 col-md-6">
        <?php

        $selected = [];
        if (!empty($model->data )) {
            foreach ($model->data as $feed) {
                $selected[$feed] = ['selected' => true];
            }
        }
        echo $form->field($model, 'data[]')
            ->widget(Select2::class, [
                'model' => $model,
                'data' => [
                    'a.name as artist' => Yii::t('app', 'Назва артиста'),
                    't.name as track' => Yii::t('app', 'Назва треку'),
                    'sum(ari2.amount) as amount' => Yii::t('app', 'Дохід'),
                    'sum(ari2.`count`) as `count`' => Yii::t('app', 'Перегляди')
                ],
                'language' => 'uk',
                'options' => [
                    'multiple' => true,
                    'placeholder' =>  Yii::t('app', 'Виберіть дані для звіту'),
                    'options' => $selected,
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ])
        ?>
    </div>
    <div class="col-sm-12 col-md-6">
        <?php
        $selected = [];

        if (!empty($model->invoiceId)) {
            foreach ($model->invoiceId as $feed) {
                $selected[$feed] = ['selected' => true];
            }
        }

        echo $form->field($model, 'invoiceId[]')
            ->widget(Select2::class, [
                'model' => $model,
                'data' => $invoice,
                'language' => 'uk',
                'options' => [
                    'multiple' => true,
                    //'values' => array_values($model->feeds),
                    'placeholder' =>  Yii::t('app', 'Виберіть інвойси'),
                    'options' => $selected,
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ])
        ?>
    </div>
    <div class="col-sm-12 col-md-4">
        <?php
        echo $form->field($model, 'groupBy')
            ->widget(Select2::class, [
                'model' => $model,
                'data' => [
                    't.artist_id' => Yii::t('app', 'Артист'),
                    't.id' => Yii::t('app', 'Трек'),
                ],
                'language' => 'uk',
                'options' => [
                    'multiple' => false,
                    'placeholder' =>  Yii::t('app', 'Групувати за'),
                ]
            ])
        ?>
    </div>
    <div class="col-sm-12 col-md-4">
        <?php

        echo $form->field($model, 'orderBy')
            ->widget(Select2::class, [
                'model' => $model,
                'data' => [
                    'amount ASC' => Yii::t('app', 'Дохід зростання'),
                    'amount DESC' => Yii::t('app', 'Дохід спадання'),
                    'count ASC' => Yii::t('app', 'Перегляди зростання'),
                    'count DESC' => Yii::t('app', 'Перегляди спадання'),
                ],
                'language' => 'uk',
                'options' => [
                    'multiple' => false,
                    'placeholder' =>  Yii::t('app', 'Сортувати за'),
                ]
            ])
        ?>
    </div>
    <div class="col-sm-12 col-md-4">
        <?php
        echo $form->field($model, 'limit');
        ?>
    </div>
    <div class="form-group col-sm-12">
        <?= Html::submitButton(Yii::t('app', 'Генерувати звіт'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
<?php if (!empty($report)) { ?>
<div class="row">
    <div class="col-sm-12">

                <h4><?= Yii::t('app', 'Звіт по інвойсам') ?></h4>
        <table class="table">
                <thead>
                <tr>
               <?php
                   foreach ($report[0] as $h) {
                    echo '<th>' . $h . '</th>';
                   }
                   unset($report[0]);
                ?>
                </tr>
               </thead>
                <tbody>
                <?php
                foreach ($report as $h) {
                    echo '<tr>';
                    foreach ($h as $v) {
                        echo '<td>' . $v . '</td>';
                    }
                    echo '</tr>';
                }
                ?>
                </tbody>
        </table>


    </div>
</div>
<?php  } ?>
