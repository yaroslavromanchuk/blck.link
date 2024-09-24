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
$this->params['breadcrumbs'][] = $this->title;
$a_id = 0;
?>
    <?php
    foreach ($models as $artist_id => $type) { ?>
    <div class="track-update row panel <?php if ($artist_id == 0) { echo "panel-danger"; } else if ($artist_id == $track->artist_id) { echo "panel-success";} else { echo "panel-warning";}?>">
        <div class="panel-heading"><?php if ($artist_id == 0) { echo 'Label: ';} else if ($artist_id == $track->artist_id) { echo 'Artist: ';} else { echo 'Feed: ';} ?><?=current($type)->artist->name?></div>
        <div class="panel-body">
        <?php foreach ($type as $model) {
            if ($a_id != $model->artist_id) {
                $a_id = $model->artist_id;
            }
            ?>
            <div class="col-sm-12 col-lg-3">
               <?php
                Pjax::begin(['id' => $model->id]);
                    $form = ActiveForm::begin(['options' => ['data-pjax' => true, ], 'action' => '/track/percentage-update?trackId=' . $model->track_id . '&id=' . $model->id]);
                        echo $form->field($model, 'track_id')->hiddenInput(['value' => $model->track_id])->label(false);
                        echo $form->field($model, 'artist_id')->hiddenInput(['value'=> $model->artist_id])->label(false);
                        echo $form->field($model, 'ownership_type')->hiddenInput(['value' => $model->ownership_type])->label(false);
                        ?>
                <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1"><?=$model->artist_id == 0 ? 'All' : $model->getFullName()?></span>
                    <?=$form->field($model, 'percentage')
                        ->textInput(['maxlength' => true, 'class' => 'form-control', 'aria-describedby' => 'button-addon2'])
                        ->label(false)?>
                    <span class="input-group-btn">
                        <?= Html::submitButton(Yii::t('app', 'Зберегти'), ['class' => 'btn btn-default']) ?>
                    </span>
                </div>

            </div>
        <?php
            ActiveForm::end();
            Pjax::end();
        }
        ?>
        </div>
    </div>
<?php } ?>
<hr>
<div>
        <?php
       /* Pjax::begin(['id' => 0]);
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
        Pjax::end();*/
        ?>
</div>
