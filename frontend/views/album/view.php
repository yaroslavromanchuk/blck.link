<?php

/* @var $this yii\web\View */
/* @var frontend\models\Albums $album */

use yii\helpers\Html;
$this->title = $album->artist_name.' '.$album->name;
?>
<div class="wrapper">
    <div class="release">
        <div class="event">
            <div class="card" >
                <input hidden="true" id="fon" value="<?=$album->image?>">
                    <?=($album->youtube_link) ? $this->render('video.php', ['track' => $album]):'<img class="card-img-top" src="'.$album->image.'" alt="'.$album->name.'">'?>
                    <div class="card-header event-info">
                      <h1 class="event-info__artist no-default-styles"><?=$album->artist_name?></h1>
                      <h3 class="event-info__event no-default-styles"><?=$album->name?></h3>
                      <?php
                      if (!empty($album->sharing)) {
                          echo $this->render('sharing.php', ['sharing'=> $album->url, 'id'=>$album->id]);
                      }
                      ?>
                    </div>
            <?php

            if(!empty($album->servise)) {
                echo $this->render('services.php', ['services' => $album]);
            }

            if (!empty($album->artist)) {
                echo $this->render('link.php', ['link' => $album->artist, 'id'=>$album->id]);
            }
            ?>

                <?php //$this->render('subscription.php')?>
            </div>
        </div>
    </div>
</div>

<?php if ($album->tracks and false) { ?>
<div class="container">
    <div class="row">
        <?php foreach ($album->tracks as $l) { ?>
            <div class="col-sm-12 col-md-6 col-lg-3 p-3">
                <div class="card relis totop">
                    <?=Html::a(Html::img($l->getImage(), ['alt' => $l->name, 'class' => 'card-img-top']), [$l->url])?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>
