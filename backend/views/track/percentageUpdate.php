<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $models backend\models\Percentage */
/* @var $track backend\models\Track */
/* @var $artist array */

$this->title = Yii::t('app', 'Редагування відсотків: {name}', [
    'name' => $track->name,
]);

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => $track->name, 'url' => ['percentage', 'id' => $model->track_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Редагування відсотків');
?>

<div class="track-update">
    <?php
    foreach ($models as $model) {
        Pjax::begin(['id' => $model->id]);
            $form = ActiveForm::begin(['options' => ['data-pjax' => true, ], 'action' => '/admin/track/percentage-update?trackId=' . $model->track_id . '&id=' . $model->id]);
            echo $form->field($model, 'track_id')->hiddenInput(['value' => $model->track_id])->label(false);
            echo $form->field($model, 'artist_id')->hiddenInput(['value'=>$model->artist_id])->label(false);
            echo  $form->field($model, 'percentage')->textInput(['maxlength' => true])->label(\backend\models\Artist::findOne(['id'=> $model->artist_id])->name);
       ?>
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
    </div>
    <?php
            ActiveForm::end();
        Pjax::end();
    }
?>
</div>
<hr>
<div>
        <?php
        Pjax::begin(['id' => 0]);
        $form = ActiveForm::begin(['options' => ['data-pjax' => true, ], 'action' => '/admin/track/percentage-create?trackId=' . $create->track_id]);
        echo $form->field($create, 'track_id')->hiddenInput(['value'=> $create->track_id])->label(false);

        echo $form->field($create, 'artist_id')
            ->widget(Select2::class, [
                'model' => $create,
                'data' => $artist,
                'language' => 'uk',
                'options' => ['placeholder' =>  Yii::t('app', 'Виберіте артиста'),]
            ]);

        echo  $form->field($create, 'percentage')->textInput(['maxlength' => true]);
        ?>
        <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-success']) ?>
        </div>
        <?php
        ActiveForm::end();
        Pjax::end();
        ?>
</div>
