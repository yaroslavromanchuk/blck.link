<?php

namespace frontend\models;

use Yii;
use common\models\Log;
use frontend\models\Artist;

/**
 * This is the model class for table "track".
 *
 * @property int $id
 * @property int $artist_id
 * @property string $artist
 * @property string $date
* @property string|null $name
* @property string|null $img
* @property string|null $url
* @property string|null $youtube
* @property string|null $youtube_link
* @property string $tag
* @property int $sharing
* @property int $views
* @property int $click
* @property int $active
* @property Log[] $logs
* @property array $musicServices
* @property array $oficialLink
* @property string $apple
* @property string $boom
* @property string $spotify
* @property string $youtube
* @property string $googleplaystore
* @property string $vk
* @property string $deezer
* @property string $yandex
*
* @property Log[] $logs
* @property Artist $artist
*/
class Track extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'track';
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        
        return [
           'id' => Yii::t('app', '№'),
            'artist_id' => Yii::t('app', 'Артист'),
            'artist' => Yii::t('app', 'Отображаемое имя артиста'),
            'date' => Yii::t('app', 'Дата релиза'),
            'name' => Yii::t('app', 'Релиз'),
            'img' => Yii::t('app', 'Обложка'),
            'url' => Yii::t('app', 'Ссылка'),
            'youtube_link' => Yii::t('app', 'YouTube канал'),
            'tag' => Yii::t('app', 'Тег'),
            'sharing' => Yii::t('app', 'Расшаривать'),
            'views' => Yii::t('app', 'Просмотры'),
            'click' => Yii::t('app', 'Клики'),
            'active' => Yii::t('app', 'Активность'),
            'apple' => Yii::t('app', 'Apple Music'),
            'boom' => Yii::t('app', 'Boom'),
            'spotify' => Yii::t('app', 'Spotify'),
            'youtube' => Yii::t('app', 'Music Youtube'),
            'googleplaystore' => Yii::t('app', 'Googleplaystore'),
            'vk' => Yii::t('app', 'Vk Music'),
            'deezer' => Yii::t('app', 'Deezer'),
            'yandex' => Yii::t('app', 'Yandex'),
       ];
    }
    /** 
    * Gets query for [[Logs]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getLogs()
   { 
       return $this->hasMany(Log::class, ['track' => 'id']);
   }

    /**
     * Gets query for [[Artists]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtist()
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }
    
    public function getImage()
    {
        return '/images/track/'.$this->img;
    }
}
