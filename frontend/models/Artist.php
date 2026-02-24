<?php

namespace frontend\models;

use Yii;
use backend\widgets\DateFormat;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property string $name
 * @property string|null $logo
 * @property string|null $phone
 * @property string|null $email
 * @property int $active
* @property string $facebook 
* @property string $vk 
* @property string $twitter 
* @property string $youtube 
* @property string $instagram 
* @property string $telegram 
* @property string $viber 
* @property string $whatsapp 
* @property string $ofsite
 * @property double $deposit
 * @property double $deposit_1
 * @property double $deposit_3
 * @property string $telegram_id
 * @property string $telegram_code
*
* @property Track[] $tracks
*/
class Artist extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'artist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['active', 'telegram_id'], 'integer'],
            [['logo', 'facebook', 'vk', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['logo'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
            [['telegram_code'], 'string', 'length' => 10],
            [['deposit', 'deposit_1', 'deposit_3'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'logo' => Yii::t('app', 'Logo'),
            'phone' => Yii::t('app', 'Phone'),
            'email' => Yii::t('app', 'Email'),
            'active' => Yii::t('app', 'Active'),
            'facebook' => Yii::t('app', 'Facebook'),
            'vk' => Yii::t('app', 'Vk'),
            'twitter' => Yii::t('app', 'Twitter'),
            'youtube' => Yii::t('app', 'Youtube'),
            'instagram' => Yii::t('app', 'Instagram'),
            'telegram' => Yii::t('app', 'Telegram'),
            'viber' => Yii::t('app', 'Viber'),
            'whatsapp' => Yii::t('app', 'Whatsapp'),
            'ofsite' => Yii::t('app', 'Оф.Сайт'),
            'telegram_code' => Yii::t('app', 'ТГ код'),
        ];
    }

    /**
     * Gets query for [[Tracks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTracks()
    {
        return $this->hasMany(Track::class, ['artist_id' => 'id']);
    }
    
    public function getTrackReport(): string
    {
        $tracks = $this->getTracks();
        $count = $tracks->count();
        
        if (empty($count)) {
            return 'В тебе покищо відсутні треки треків';
        }
        
        $text = "Кількість треків: {$count}\n\n";
        
        /* @var $track Track */
        foreach ($tracks->all() as $track) {
            // $uah = $track->getTotalAmount(2);
            // $eur = $track->getTotalAmount(1);
           //  $usd = $track->getTotalAmount(3);
             
            $text .= "Трек <b>{$track->name}</b>:\n"
                . " <i>- дата релізу:</i> {$track->date}\n"
                . " <i>- переглядів:</i> {$track->views}\n"
                . " <i>- лінк:</i> https://blck.link/{$track->url}\n";
                //. " <i>- дохід трека:</i> \n"
               // . " <i> • UAH:</i> {$uah}\n"
               // . " <i> • EURO:</i> {$eur}\n"
               // . " <i> • USD:</i> {$usd}\n";
        }
        
        return $text;
    }
    
    public function getBalanceReport(): string
    {
        $q = DateFormat::getQuarterText();
        $text = "Твій баланс станом на {$q}:\n";
        
        $text .= "• <b>EURO:</b> {$this->deposit_1}\n"
            . "• <b>USD:</b> {$this->deposit_3}\n"
            . "• <b>UAH:</b> {$this->deposit}\n";
        
        return $text;
    }
}
