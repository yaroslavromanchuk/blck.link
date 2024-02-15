<?php
/* @var $this yii\web\View */

/* @var $label */
use yii\helpers\Html;
//$this->title = $track->artists->name.' '.$track->name;
?>
<div class="container">
    <?php if(isset($list)) {
        ?>
        <div class="row">
            <?php foreach ($list as $t) { ?>
                <div class="col-sm-12 col-md-6 col-lg-3 p-3">
                    <div class="card relis totop">
                        <?=Html::a(Html::img($t->image, ['alt' => $t->name, 'class' => 'card-img-top']), ['/label/' . $label->url . '/' . $t->url])?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

</div>