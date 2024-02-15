<?php

/* @var $this yii\web\View */
/* @var $track */

$this->title = $track->artist.' '.$track->name;
?>
<div class="wrapper">
    <div class="release">
        <div class="event">
            <div class="card" >
                <input hidden="true" id="fon" value="<?=$track->image?>">
                <?=($track->youtube_link) ? $this->render('video.php', ['track' => $track]):'<img class="card-img-top" src="'.$track->image.'" alt="'.$track->name.'">'?>
                    <div class="card-header event-info">
                      <h1 class="event-info__artist no-default-styles"><?=$track->artist?></h1>
                      <h3 class="event-info__event no-default-styles"><?=$track->name?></h3>
                      <?php
                      if (!empty($track->sharing)) {
                          echo $this->render('sharing.php', ['sharing'=> $track->url, 'id'=>$track->id]);
                      }
                      ?>
                    </div>
            <?php

            if(!empty($track->services)) {
                echo $this->render('services.php', ['services' => $track]);
            }

            if (!empty($track->artists)) {
                echo $this->render('link.php', ['link' => $track->artists, 'id'=>$track->id]);
            }
            ?>

                <?php //$this->render('subscription.php')?>
            </div>
        </div>
    </div>
</div>
