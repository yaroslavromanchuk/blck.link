<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\AlbumSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="albums-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'admin_id') ?>

    <?= $form->field($model, 'artist_id') ?>

    <?= $form->field($model, 'artist_name') ?>

    <?= $form->field($model, 'date') ?>

    <?php // echo $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'img') ?>

    <?php // echo $form->field($model, 'url') ?>

    <?php // echo $form->field($model, 'youtube_link') ?>

    <?php // echo $form->field($model, 'sharing') ?>

    <?php // echo $form->field($model, 'views') ?>

    <?php // echo $form->field($model, 'click') ?>

    <?php // echo $form->field($model, 'active') ?>

    <?php // echo $form->field($model, 'servise') ?>

    <?php // echo $form->field($model, 'date_added') ?>

    <?php // echo $form->field($model, 'last_update') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
