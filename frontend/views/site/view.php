<?php

/* @var $this yii\web\View
 *
 * @var $track \frontend\models\Track
 */

$this->title = $track->artist_name.' '.$track->name;
?>
<div class="wrapper">
    <div class="release">
        <div class="event">
            <div class="card" >
                <input hidden="true" id="fon" value="<?=$track->image?>">
                <?=($track->youtube_link) ? $this->render('video.php', ['track' => $track]):'<img class="card-img-top" src="'.$track->image.'" alt="'.$track->name.'">'?>
         <div class="card-header event-info">
              <h1 class="event-info__artist no-default-styles"><?=$track->artist_name?></h1>
              <h3 class="event-info__event no-default-styles"><?=$track->name?></h3>
              <?=$track->sharing ? $this->render('sharing.php', ['sharing'=> $track->url, 'id'=>$track->id]):''?>
          </div>
                <?php
                if (isset($services)) {
                    echo $this->render('services.php', ['services' => $services, 'trackId'=>$track->id]);
                }

                echo $this->render('link.php', ['artist' => $track->artist, 'id' => $track->id]);

                echo $this->render('subscription.php');
                ?>
            </div>
        </div>
    </div>
</div>
