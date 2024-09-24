<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "track".
 *
 * @property int $id
 * @property string $isrc
 * @property int $artist_id
 * @property int $release_id
 * @property string $artist_name
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
 * @property FeedToTrack feeds
 */
class Track extends \yii\db\ActiveRecord
{
    public $file;

    //public array $percentage = [];
   // public array $feeds = [];
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
           [['artist_id', 'artist_name', 'date',  'name', 'img'], 'required'],
           [['artist_id', 'release_id', 'admin_id', 'sharing', 'views', 'click', 'active'], 'integer'],
           [['date'], 'safe'],
           [['artist_name', 'tag', 'isrc'], 'string', 'max' => 100],
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
            'artist_name' => Yii::t('app', 'Ім\'я артиста для відображення'),
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
            'feeds' => Yii::t('app', 'Фіт'),
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
    public function getArtist(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Artist::class, ['id' => 'artist_id']);
    }

    public function getPercentage(): array
    {
        return Percentage::find()
            ->select(['artist.name as artist_name', 'ownership.name', 'ownership_type.name as type_name', 'track_to_percentage.percentage', ])
            ->from('track_to_percentage')
            ->innerJoin('track', 'track.id = track_to_percentage.track_id')
            ->innerJoin('artist', 'artist.id = track_to_percentage.artist_id')
            ->leftJoin('ownership_type', 'ownership_type.id = track_to_percentage.ownership_type')
            ->leftJoin('ownership', 'ownership.id = ownership_type.ownership_id ')
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

    public function getFeeds(): array
    {
        $data = Artist::find()
            ->select(['artist.id'])
            ->leftJoin('track_to_percentage', 'track_to_percentage.artist_id = artist.id')
            ->where(['track_to_percentage.track_id' => $this->id])
            ->andWhere(['!=', 'track_to_percentage.artist_id', 0])
            ->andWhere(['!=', 'track_to_percentage.artist_id', $this->artist_id])
            ->groupBy('track_to_percentage.artist_id')
            ->asArray()
            ->all();

        return array_column($data, 'id');
    }

    public function saveFeeds(array $data)
    {
        $feeds = $this->getFeeds();
        $db = ActiveRecord::getDb();

        foreach ($feeds as $key => $feed) {

            if ($feed == 0) { // блеклінк
                continue;
            }

            if (!in_array($feed, $data)) {
                $db->createCommand()->delete('track_to_percentage', ['track_id' => $this->id, 'artist_id' => $feed])->execute();
               unset($feeds[$key]);
            }
        }

        $OwnershipType = OwnershipType::find()->asArray()->all();

       foreach ($data as $item) {
           if (!in_array($item, $feeds)) {
               foreach ($OwnershipType as $type) {

                   $per = 0;

                   if ($type['id'] == 5) {
                       $per = $this->artist->percentage;
                   }

                   $percentage = new Percentage();
                   $percentage->artist_id = (int) $item;
                   $percentage->track_id = $this->id;
                   $percentage->ownership_type = $type['id'];
                   $percentage->percentage = $per;
                   $percentage->save(false);
               }
           }
       }
    }

    public function addLabelPercentage()
    {
            $percentage = new Percentage();
            $percentage->artist_id = 0;
            $percentage->track_id = $this->id;
            $percentage->percentage = 30;
            $percentage->save();
    }
    public function addArtistPercentage()
    {
        foreach (OwnershipType::find()->asArray()->all() as $type) {

            $per = 0;

            if ($type['id'] == 5) {
                $per = $this->artist->percentage;
            }

            $percentage =  new Percentage();
            $percentage->artist_id = $this->artist_id;
            $percentage->track_id = $this->id;
            $percentage->ownership_type = $type['id'];
            $percentage->percentage = $per;
            $percentage->save();
        }
    }

    public function updateArtistPercentage()
    {
        foreach (OwnershipType::find()->asArray()->all() as $type) {

            if(null === Percentage::findOne(['track_id' => $this->id, 'artist_id' => $this->artist_id, 'ownership_type' => $type['id']])) {
                $per = 0;

                if ($type['id'] == 5) {
                    $per = $this->artist->percentage;
                }

                $percentage = new Percentage();
                $percentage->artist_id = $this->artist_id;
                $percentage->track_id = $this->id;
                $percentage->ownership_type = (int) $type['id'];
                $percentage->percentage = $per;
                $percentage->save(false);
            }
        }
    }

    public static function getTrackByIsrc(string $isrc): ?Track
    {
        return Track::findOne(['isrc' => $isrc]);
    }

    public function getCalculation(int $aggregator_id, float $total): array
    {
        $where = [];

        switch ($aggregator_id) {
            case 1:
            case 9: $where = [OwnershipType::Phonogram]; break; // Белив і УЛАСП-ОКУАСП Ф - Фонограма
            case 2:
            case 3:
            case 4:
            case 6:
                $where = [OwnershipType::Text, OwnershipType::Music,]; break; // - Текст + Музика
            case 5:
                $where = [OwnershipType::Implementation]; break; // УЛАСП-ОКУАСП В, Виконання
            case 7: // ТММ - Текст + Музика + Виконання + Фонограма
            case 8: $where = [OwnershipType::Text, OwnershipType::Music, OwnershipType::Implementation, OwnershipType::Phonogram]; break; // СУКА - Текст + Музика + Виконання + Фонограма
            default:
                return [];
        }

        $d = count($where) * 100;

        $data = Percentage::find()
            ->select([
                'track_to_percentage.artist_id',
                '100 / ' . $d . ' * SUM(track_to_percentage.percentage) / 100 AS percentage',
            ])
            ->from('track_to_percentage')
            ->leftJoin('ownership_type', 'ownership_type.id = track_to_percentage.ownership_type')
            ->where([
                'track_to_percentage.track_id' => $this->id,
                'track_to_percentage.ownership_type' => $where
            ])
            ->groupBy(['track_to_percentage.artist_id'])
            ->asArray()
            ->all();

        $result = [];

        foreach ($data as $datum) {

            if ($datum['percentage'] <= 0) {
                continue;
            }

            $pSum = $total * $datum['percentage'];
            $pArtista = $pSum * (Percentage::findOne(['track_id' => $this->id, 'artist_id' => $datum['artist_id'], 'ownership_type' => 5])->percentage / 100);

            $result[] = [
                'artist_id' => $datum['artist_id'],
                'amount' => $pArtista,
            ];

            $result[] = [
                'artist_id' => Artist::label,
                'amount' => $pSum - $pArtista,
            ];
        }

        return $result;
    }
}
