<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "track".
 *
 * @property int $id
 * @property int $artist_id
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
 *
* @property Log[] $logs
* @property MusicServices $musicServices
* @property OficialLink $oficialLink
 * @property Artist $artist
 */
class Track extends \yii\db\ActiveRecord
{
    public $file;
   // public $servise;
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
           [['file'], 'file', 'extensions' => 'png, jpg, jpeg'], 
           [['artist_id', 'artist', 'date',  'name', 'img'], 'required'], 
           [['artist_id',  'admin_id', 'sharing', 'views', 'click', 'active'], 'integer'],
           [['date'], 'safe'],
           [['artist', 'tag'], 'string', 'max' => 100],
           [['name', 'img', 'youtube_link'], 'string', 'max' => 255],
           [['url'], 'string', 'max' => 50],
           [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::className(), 'targetAttribute' => ['artist_id' => 'id']],
           ['url', 'unique', 'targetClass' => '\backend\models\Track', 'message' => Yii::t('app', 'Эта ссылка уже занята!')],
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
            'artist' => Yii::t('app', 'Отображаемое имя артиста'),
            'date' => Yii::t('app', 'Дата релиза'),
            'name' => Yii::t('app', 'Релиз'),
            'img' => Yii::t('app', 'Обложка'),
            'file' => Yii::t('app', 'Обложка'),
            'url' => Yii::t('app', 'Ссылка'),
           'youtube_link' => Yii::t('app', 'YouTube канал'),
           'tag' => Yii::t('app', 'Тег'),
            'sharing' => Yii::t('app', 'Расшаривать'),
            'views' => Yii::t('app', 'Просмотры'),
            'click' => Yii::t('app', 'Клики'),
            'active' => Yii::t('app', 'Активность'),
             'servise' => Yii::t('app', 'Площадка'),
            'admin_id' => Yii::t('app', 'Создал'),
       ];
    }
    /** 
    * Gets query for [[Logs]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getLogs() 
   { 
       return $this->hasMany(\common\models\Log::className(), ['track' => 'id']); 
   } 
   public function getLogsLink() 
   { 
      return \common\models\Log::find()->select('log.*, count(log.id) as ctn')->where(['track' => $this->id, 'type' => 'link'])->groupBy(['name'])->asArray()->all();
   }
   public function getLogsServise() 
   { 
       return \common\models\Log::find()->select('log.*, count(log.id) as ctn')->where(['track' => $this->id, 'type' => 'servise'])->groupBy(['name'])->asArray()->all();
   }
   
   public function getView() 
   { 
       return $this->hasMany(\common\models\Views::className(), ['track_id' => 'id']); 
   } 

    /**
     * Gets query for [[Artist0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArtists()
    {
        return $this->hasOne(Artist::className(), ['id' => 'artist_id']);
    }
    /**
     * Gets query for [Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(User::className(), ['id' => 'admin_id']);
    }
    
    public function getImage()
    {
        return Yii::getAlias('@site').'/images/track/'.$this->img;
    }
}
