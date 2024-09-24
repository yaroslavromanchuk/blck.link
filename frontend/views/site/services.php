<?php
if (isset($services)) { ?>
<div class="card-body content p-0">
    <div class="order-block links-wrapper order-1">
   <?php  foreach ($services as $s) {
        if(strpos($s, 'apple')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link apple" data-id="<?=$trackId?>"  data-name="apple">
                      <span class="service-text"><?=Yii::t('app', 'Apple Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
        <?php }elseif(strpos($s, 'boom')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link boom" data-id="<?=$trackId?>"  data-name="boom">
                      <span class="service-text"><?=Yii::t('app', 'VK Музыка')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'spotify')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link spotify" data-id="<?=$trackId?>"  data-name="spotify">
                      <span class="service-text"><?=Yii::t('app', 'Spotify')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'music.youtube')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link music_youtube" data-id="<?=$trackId?>"  data-name="youtube">
                      <span class="service-text"><?=Yii::t('app', 'YouTube Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'youtu')) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link youtube" data-id="<?=$trackId?>"  data-name="youtu">
                      <span class="service-text"><?=Yii::t('app', 'YouTube')?></span>
                      <span class="action"><?=Yii::t('app', 'Дивитись')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'google')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link googleplaystore" data-id="<?=$trackId?>"  data-name="googleplaystore">
                      <span class="service-text"><?=Yii::t('app', 'Google Play')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  } else if (strpos($s, 'deezer')) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link deezer" data-id="<?=$trackId?>"  data-name="deezer">
                      <span class="service-text"><?=Yii::t('app', 'Deezer')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'yandex')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link yandex" data-id="<?=$trackId?>"  data-name="yandex">
                      <span class="service-text"><?=Yii::t('app', 'Yandex Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'tiktok')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link tik-tok" data-id="<?=$trackId?>"  data-name="tik-tok">
                      <span class="service-text"><?=Yii::t('app', 'TikTok')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'soundcloud')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link soundcloud" data-id="<?=$trackId?>"  data-name="soundcloud">
                      <span class="service-text"><?=Yii::t('app', 'SoundCloud')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php  }elseif(strpos($s, 'sber-zvuk')){ ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link sber-zvuk" data-id="<?=$trackId?>"  data-name="sber-zvuk">
                      <span class="service-text"><?=Yii::t('app', 'СберЗвук')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
      <?php } elseif(strpos($s, 'bandcamp')) { ?>
            <div rel="nofollow" class="order-block servise">
                <div>
                    <a href="<?=$s?>" target="_blank" class="link bandcamp" data-id="<?=$trackId?>"  data-name="sber-zvuk">
                        <span class="service-text"><?=Yii::t('app', 'BandCamp')?></span>
                        <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                    </a>
                </div>
            </div>
        <?php  } else { ?>
           <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link link-icon" data-id="<?=$trackId?>"  data-name="link-icon">
                      <span class="service-text"><?=Yii::t('app', 'Link')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
   <?php   }
   }?>
        </div>
    </div>
<?php }



