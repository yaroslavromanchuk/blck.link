<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "track".
 *
 * @property int $id
 * @property string $isrc
 * @property int $artist_id
 * @property int $release_id
 * @property string $artist
 * @property string $date
 * @property string|null $name
 * @property string|null $img
 * @property string|null url
 * @property string|null $youtube
 * @property string $tag
 * @property int $sharing
 * @property int $views
 * @property int $click
 * @property int $active
 * @property string servise
 * @property array percentage
 *
 * @property Log[] $logs
 * @property  $musicServices
 * @property  $oficialLink
 */
class Track extends \yii\db\ActiveRecord
{
    public $file;

    public array $percentage = [];
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
     public function rules()
   {
       return [
           [['servise'], 'string'],
           [['file'], 'file', 'extensions' => 'png, jpg, jpeg',],
           [['artist_id', 'artist', 'date',  'name', 'img'], 'required'],
           [['artist_id', 'release_id', 'admin_id', 'sharing', 'views', 'click', 'active'], 'integer'],
           [['date'], 'safe'],
           [['artist', 'tag', 'isrc'], 'string', 'max' => 100],
           [['name', 'img', 'youtube_link'], 'string', 'max' => 255],
           [['url'], 'string', 'max' => 50],
           [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
           ['url', 'unique', 'targetClass' => '\backend\models\Track', 'message' => Yii::t('app', 'Це посилання вже зайняте!')],
       ];
   }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', '№'),
            'artist_id' => Yii::t('app', 'Артист'),
            'release_id' => Yii::t('app', 'Реліз'),
            'artist' => Yii::t('app', 'Ім\'я артиста для відображення'),
            'date' => Yii::t('app', 'Дата реліза'),
            'name' => Yii::t('app', 'Назва теку'),
            'img' => Yii::t('app', 'Обкладинка'),
            'file' => Yii::t('app', 'Обкладинка'),
            'url' => Yii::t('app', 'Посилання'),
            'youtube_link' => Yii::t('app', 'YouTube канал'),
            'tag' => Yii::t('app', 'Тег'),
            'sharing' => Yii::t('app', 'Відображать'),
            'views' => Yii::t('app', 'Перегляди'),
            'click' => Yii::t('app', 'Кліки'),
            'active' => Yii::t('app', 'Активність'),
            'servise' => Yii::t('app', 'Площадка'),
            'feeds' => Yii::t('app', 'Фід'),
            'percentage' => Yii::t('app', 'Відсотки'),
            'admin_id' => Yii::t('app', 'Створив'),
            'isrc' => Yii::t('app', 'ISRC'),
       ];
    }
    /** 
    * Gets query for [[Logs]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getLogs() 
   { 
       return $this->hasMany(\common\models\Log::class, ['track' => 'id']);
   }

   public function getLogsLink() 
   { 
      return \common\models\Log::find()
          ->select('log.*, count(log.id) as ctn')
          ->where(['track' => $this->id, 'type' => 'link'])
          ->groupBy(['name'])
          ->asArray()
          ->all();
   }
   public function getLogsServise() 
   { 
       return \common\models\Log::find()
           ->select('log.*, count(log.id) as ctn')
           ->where(['track' => $this->id, 'type' => 'servise'])
           ->groupBy(['name'])
           ->asArray()
           ->all();
   }
   
   public function getView() 
   { 
       return $this->hasMany(\common\models\Views::class, ['track_id' => 'id']);
   } 

    /**
     * Gets query for [[Artist0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtists(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getPercentage(): array
    {
        return Percentage::find()
            ->select(['artist.name', 'track_to_percentage.percentage'])
            ->innerJoin('track', 'track.id = track_to_percentage.track_id')
            ->innerJoin('artist', 'artist.id = track_to_percentage.artist_id')
            ->where(['track_id' => $this->id])
            ->asArray()
            ->all();
    }
    /**
     * Gets query for [Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
    
    public function getImage(): string
    {
        return Yii::getAlias('@site').'/images/track/' . $this->img;
    }

}
