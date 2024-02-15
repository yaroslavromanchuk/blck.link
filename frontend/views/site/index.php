<?php

/* @var $this yii\web\View */

/* @var $list */
use yii\helpers\Html;
//$this->title = $track->artists->name.' '.$track->name;
?>
<div class="container">
    <?php if($list) { ?>
    <div class="row">
       <?php foreach ($list as $r) { ?>
        <div class="col-sm-12 col-md-6 col-lg-3 p-3">
            <div class="card relis totop">
               <?=Html::a(Html::img($r->image, ['alt'=>$r->name, 'class'=>'card-img-top']), ['/'.$r->url])?>
            </div>
        </div>
           <?php } ?>
    </div>
    <?php } ?>
    
</div>
