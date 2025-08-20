<?php

use yii\widgets\Pjax;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $models backend\models\Percentage */
/* @var $track backend\models\Track */
/* @var $artist array */

Modal::begin([
    'header' => '<h4>Відсотки ' .  $track->name . '</h4>',
    'id' => 'percentage-modal' . $track->id,
]);
$a_id = 0;

foreach ($models as $artist_id => $type) { ?>
    <div class="track-update row panel <?php if ($artist_id == 0) { echo "panel-danger"; } else if ($artist_id == $track->artist_id) { echo "panel-success";} else { echo "panel-warning";}?>">
        <div class="panel-heading"><?php if ($artist_id == 0) { echo 'Label: ';} else if ($artist_id == $track->artist_id) { echo 'Artist: ';} else { echo 'Feed: ';} ?><?=current($type)->artist->name?></div>
        <div class="panel-body">
            <?php foreach ($type as $model) {
                if ($a_id != $model->artist_id) {
                    $a_id = $model->artist_id;
                }

                Pjax::begin(['id' => 'form_' . $model->id, 'timeout' => false, 'enablePushState' => true,]);
                echo $this->render('_percentageModalOne', ['model' => $model]);
                Pjax::end();

                $script = '
                
                var id =  ' . $model->id . '
    $("#p_' . $model->id.'").on("pjax:complete", function () {
      $("#percentage-modal' .$track->id . '").show();
                });
';
               // $this->registerJs($script, yii\web\View::POS_READY);
            } ?>
        </div>
    </div>
<?php }
Modal::end();
?>