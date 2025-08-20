<?php

namespace backend\models;

use common\models\Log;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "track".
 *
 * @property int $id
 * @property string $isrc
 * @property int $artist_id
 * @property int $admin_id
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
 * @property int $is_album
 * @property int $album_id
 * @property string $servise
 * @property array $percentage
 * @property double $deposit_uah
 * @property double $deposit_euro
 * @property string $date_added
 *
 * @property Log[] $logs
 * @property  $musicServices
 * @property  $oficialLink
 * @property  Artist $artist
 * @property FeedToTrack $feeds
 * @property UserToTrack $userToTrack
 */
class Track extends \yii\db\ActiveRecord
{
    public $file;

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
           [['artist_id', 'artist_name', 'name'], 'required'],
           ['isrc', 'required', 'when' => function ($model) {
               return $model->is_album == 0;
           },'whenClient' => "function (attribute, value) {
                return $('#is_album').val() == 0;
            }"],
           [['isrc', 'name', 'artist_name'], 'trim'],
           [['artist_id', 'release_id', 'admin_id', 'sharing', 'is_album', 'album_id', 'views', 'click', 'active'], 'integer'],
           [['date'], 'safe'],
           [['artist_name', 'tag', 'isrc'], 'string', 'max' => 100],
           [['name', 'img', 'youtube_link'], 'string', 'max' => 255],
           [['url'], 'string', 'max' => 50],
           [['deposit_uah', 'deposit_euro'], 'double'],
           [['artist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Artist::class, 'targetAttribute' => ['artist_id' => 'id']],
           ['url', 'unique', 'targetClass' => self::class, 'message' => Yii::t('app', 'Це посилання вже зайняте!')],
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
            'file' => Yii::t('app', 'Файл Обкладинки'),
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
            'deposit_uah' => Yii::t('app', 'Депозит UAH'),
            'deposit_euro' => Yii::t('app', 'Депозит EURO'),
            'is_album' => Yii::t('app', 'Це альбом'),
            'album_id' => Yii::t('app', 'Альбом'),
            'date_added' => Yii::t('app', 'Додано'),
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

    public function isSubLabel(): bool
    {
        return $this->artist->isSubLabel();
    }

    public function isRecords(): bool
    {
        return $this->artist->records;
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

    public function getAlbum()
    {
        return $this->hasOne(self::class, ['id' => 'album_id']);
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
            #->andWhere(['!=', 'track_to_percentage.artist_id', 0])
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

           // if ($feed == 0) { // блеклінк
             //   continue;
          //  }

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

    /**
     * @param int $per
     * @return void
     * @throws \yii\db\Exception
     */
    public function addArtistPercentage(int $per = 100): void
    {
        foreach (OwnershipType::find()->orderBy('sort')->asArray()->all() as $type) {
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
        foreach (OwnershipType::find()->orderBy('sort')->asArray()->all() as $type) {

            if(null === Percentage::findOne(['track_id' => $this->id, 'artist_id' => $this->artist_id, 'ownership_type' => $type['id']])) {
                $per = 100;

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

    public function getTotalAmount(int $currency_id = 1)
    {
        $data = InvoiceItems::find()
            ->select(['SUM(ii.amount) as amount'])
            ->from(InvoiceItems::tableName() . ' as ii')
            ->innerJoin(Invoice::tableName() . ' as i', 'ii.invoice_id = i.invoice_id')
            ->leftJoin(Currency::tableName() . ' as c', 'c.currency_id = i.currency_id')
            ->leftJoin(InvoiceType::tableName() . ' as it', 'it.invoice_type_id = i.invoice_type')
            ->where(['ii.track_id' => $this->id, 'i.currency_id' => $currency_id, 'i.invoice_status_id' => 2, 'i.invoice_type' => 1])
          //  ->andFilterWhere(['=', 'i.invoice_status_id', 2])
            //->andFilterWhere(['in', 'i.invoice_type', [1]])
            //->groupBy(['i.currency_id'])
           // ->asArray()
           // ->all();
        ->one();

        if (empty($data)) {
            return 0;
        }

        return $data['amount'];

      //  $result = [];

      //  foreach ($data as $datum) {
     //       $result[] = $datum['invoice_type_name'] . ': ' . $datum['amount'] . ' ' .$datum['currency_name'];
     //   }

      //  return implode(PHP_EOL, $result);
    }

    public function getPR()
    {
        foreach (OwnershipType::find()->asArray()->all() as $type) {
            $percentage = Percentage::findOne(['track_id' => $this->id, 'artist_id' => $this->artist_id, 'ownership_type' => $type['id']]);

            if ($percentage->percentage == 100) {
                $per = $this->artist->percentage == 0 ? 0 : 100;

                if ($type['id'] == 5) {
                    $per = $this->artist->percentage;
                }

                $percentage->percentage = $per;
                $percentage->save(false);
            }
        }

    }

    public static function getTrackByIsrc(string $isrc): ?Track
    {
        $track = Track::findOne(['isrc' => $isrc]);

        if (!is_null($track)) {
            return $track;
        }

        $track = Track::find()
            ->andWhere(['like', "REPLACE(track.isrc, '-', '')", str_replace('-', '', $isrc)])
            ->one();

        if ($track instanceof self) {
            return $track;
        }

        return null;
    }

    public function getCalculation(int $aggregator_id, float $total): array
    {
        $result = [];
        if (0 == $total) {
            $result[] = [
                'artist_id' => $this->artist_id,
                'amount' => $total,
                'from_artist_id' => null,
                'percentage' => 0,
            ];
            $result[] = [
                'artist_id' => Artist::LABEL,
                'amount' => $total,
                'from_artist_id' => $this->artist_id,
                'percentage' => 0,
            ];

            return $result;
        }

        if ($this->isRecords()) {
            if ($this->artist->percentage > 0) {
                $sumArtist = round($total * ($this->artist->percentage / 100), 4);

                $result[] = [
                    'artist_id' => $this->artist_id,
                    'amount' => $sumArtist,
                    'from_artist_id' => null,
                    'percentage' => $this->artist->percentage,
                ];

                $total = round($total - $sumArtist, 4);
            } else {
                $result[] = [
                    'artist_id' => $this->artist_id,
                    'amount' => 0,
                    'from_artist_id' => null,
                    'percentage' => $this->artist->percentage,
                ];
            }
            $result[] = [
                'artist_id' => Artist::LABEL,
                'amount' => $total,
                'from_artist_id' => $this->artist_id,
                'percentage' => (100 - $this->artist->percentage),
            ];

            return $result;
        } else if ($this->isSubLabel()) { // якщо трек сублейба, рахуємо відсоток від сублейба ане від треку.
            if (in_array($aggregator_id, [1])) {
                $percentageArtist = $this->artist->label->percentage_distribution;
            } else {
                $percentageArtist = $this->artist->label->percentage;
            }

            if ($percentageArtist > 0) {
                $sumArtist = round($total * ($percentageArtist / 100), 4);

                $result[] = [
                    'artist_id' => $this->artist_id,
                    'amount' => $sumArtist,
                    'from_artist_id' => null,
                    'percentage' => $percentageArtist,
                ];

                $total = round($total - $sumArtist, 4);
            } else {
                $result[] = [
                    'artist_id' => $this->artist_id,
                    'amount' => 0,
                    'from_artist_id' => null,
                    'percentage' => $percentageArtist,
                ];
            }

            $result[] = [
                'artist_id' => Artist::LABEL,
                'amount' => $total,
                'from_artist_id' => $this->artist_id,
                'percentage' => (100 - $percentageArtist),
            ];

            return $result;
        }

      /*  $where = AggregatorToOwnershipType::find()
            ->select(['ownership_type_id'])
            ->where(['aggregator_id' => $aggregator_id])
            ->column();*/

        /*switch ($aggregator_id) {
            case 1:
            case 4:
            $where = [OwnershipType::Phonogram]; // Белив і УЛАСП-Ф - Фонограма
                break;
            case 2:
            case 3:
            case 6:
            case 9:
            case 12:
                $where = [OwnershipType::Text, OwnershipType::Music,]; // - Текст + Музика
                break;
            case 5:
                $where = [OwnershipType::Implementation]; // УЛАСП-ОКУАСП В, Виконання
                break;
            case 7: //ТММ - Текст + Музика + Виконання + Фонограма
            case 14: //UCELL - Текст + Музика + Виконання + Фонограма
            case 8: //СУКА - Текст + Музика + Виконання + Фонограма
                $where = [OwnershipType::Text, OwnershipType::Music, OwnershipType::Implementation, OwnershipType::Phonogram];
                break;
            default:
                $where = [];
        }
        */

      // $d = count($where) * 100;

        $data = Percentage::find()
            ->select([
                'track_to_percentage.artist_id',
                '100 / count(aggregator_to_ownership_type.id) * SUM(track_to_percentage.percentage) / 100 AS percentage',
            ])
            ->from('track_to_percentage')
            ->leftJoin('aggregator_to_ownership_type', 'aggregator_to_ownership_type.ownership_type_id = track_to_percentage.ownership_type')
            ->where([
                'track_to_percentage.track_id' => $this->id,
                'aggregator_to_ownership_type.aggregator_id' => $aggregator_id
            ])
            ->groupBy(['track_to_percentage.artist_id'])
            ->asArray()
            ->all();

        foreach ($data as $datum) {
            if ($datum['percentage'] <= 0) {
                continue;
            }

            $pSum = round($total * ($datum['percentage'] / 100), 4);

            $percentageArtist = Percentage::findOne([
                'track_id' => $this->id,
                'artist_id' => $datum['artist_id'],
                'ownership_type' => 5
            ])->percentage;

            if ($percentageArtist > 0) {
                $sumArtist = round($pSum * ($percentageArtist / 100), 4);

                $result[] = [
                    'artist_id' => $datum['artist_id'],
                    'amount' => $sumArtist,
                    'from_artist_id' => null,
                    'percentage' => $percentageArtist,
                ];

                $pSum = round($pSum - $sumArtist, 4);
            } else {
                $result[] = [
                    'artist_id' => $datum['artist_id'],
                    'amount' => 0,
                    'from_artist_id' => null,
                    'percentage' => $percentageArtist,
                ];
            }

            $result[] = [
                'artist_id' => Artist::LABEL,
                'amount' => $pSum,
                'from_artist_id' => $datum['artist_id'],
                'percentage' => (100 - $percentageArtist),
            ];
        }

        // якщо жодному артисту не виплачуємо за тип прав, весь дохід має іти лейбу
         if (empty($result)) {
             $result[] = [
                 'artist_id' => $this->artist_id,
                 'amount' => 0,
                 'from_artist_id' => null,
                 'percentage' => 0,
             ];
             $result[] = [
                 'artist_id' => Artist::LABEL,
                 'amount' => $total,
                 'from_artist_id' => $this->artist_id,
                 'percentage' => 100,
             ];
         }

        return $result;
    }

    public function getUserBalances()
    {
        return $this->hasMany(UserBalance::class, ['track_id' => 'id']);
    }

    /**
     * Gets query for [[UserToTracks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserToTracks()
    {
        return $this->hasMany(UserToTrack::class, ['track_id' => 'id']);
    }
}
