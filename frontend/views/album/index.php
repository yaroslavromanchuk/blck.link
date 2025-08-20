<?php
/* @var $this yii\web\View */
/* @var $list */

use yii\helpers\Html;
//$this->title = $track->artists->name.' '.$track->name;
?>
<div class="container">
    <?php if ($list) { ?>
        <div class="row">
            <?php foreach ($list as $l) { ?>
                <div class="col-sm-12 col-md-6 col-lg-3 p-3">
                    <div class="card relis totop">
                        <?=Html::a(Html::img($l->getImage(), ['alt' => $l->name, 'class' => 'card-img-top']), [ '/album/' . $l->url])?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>