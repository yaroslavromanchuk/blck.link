<?php

$serviceId = null;
if (!empty($trackId)) {
    $serviceId = $trackId;
} elseif (!empty($id)) {
    $serviceId = $id;
} elseif (is_object($services) && isset($services->id)) {
    $serviceId = $services->id;
}

if (!empty($services)) { ?>
<div class="card-body content p-0">
    <div class="order-block links-wrapper order-1">
    <?php foreach ($services as $s) {
        if (empty($s)) {
            continue;
        }
        if (strpos($s, 'apple') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link apple" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="apple">
                      <span class="service-text"><?=Yii::t('app', 'Apple Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
        <?php } elseif (strpos($s, 'boom') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link boom" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="boom">
                      <span class="service-text"><?=Yii::t('app', 'VK Музыка')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'spotify') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link spotify" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="spotify">
                      <span class="service-text"><?=Yii::t('app', 'Spotify')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'music.youtube') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link music_youtube" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="youtube">
                      <span class="service-text"><?=Yii::t('app', 'YouTube Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'youtu') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link youtube" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="youtu">
                      <span class="service-text"><?=Yii::t('app', 'YouTube')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'google') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link googleplaystore" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="googleplaystore">
                      <span class="service-text"><?=Yii::t('app', 'Google Play')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } else if(false /*strpos($s, 'vk')*/) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link vk" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="vk">
                      <span class="service-text"><?=Yii::t('app', 'VK')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'deezer') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link deezer" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="deezer">
                      <span class="service-text"><?=Yii::t('app', 'Deezer')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'yandex') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link yandex" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="yandex">
                      <span class="service-text"><?=Yii::t('app', 'Yandex Music')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'tiktok') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link tik-tok" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="tik-tok">
                      <span class="service-text"><?=Yii::t('app', 'TikTok')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'soundcloud') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link soundcloud" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="soundcloud">
                      <span class="service-text"><?=Yii::t('app', 'SoundCloud')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'sber-zvuk') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link sber-zvuk" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="sber-zvuk">
                      <span class="service-text"><?=Yii::t('app', 'СберЗвук')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
            </div>
       <?php } elseif (strpos($s, 'bandcamp') !== false) { ?>
            <div rel="nofollow" class="order-block servise">
                <div>
                    <a href="<?=$s?>" target="_blank" class="link bandcamp" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="sber-zvuk">
                        <span class="service-text"><?=Yii::t('app', 'BandCamp')?></span>
                        <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                    </a>
                </div>
            </div>
        <?php } else { ?>
           <div rel="nofollow" class="order-block servise">
              <div>
                  <a href="<?=$s?>" target="_blank" class="link link-icon" data-id="<?=htmlspecialchars((string) $serviceId, ENT_QUOTES, 'UTF-8')?>" data-name="link-icon">
                      <span class="service-text"><?=Yii::t('app', 'Link')?></span>
                      <span class="action"><?=Yii::t('app', 'Слушать')?></span>
                  </a>
              </div>
          </div>
   <?php } }?>
        </div>
    </div>
<?php }
