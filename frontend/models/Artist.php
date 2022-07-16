<?php

namespace frontend\models;

use Yii;

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
            [['active'], 'integer'],
            [['logo', 'facebook', 'vk', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['logo'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
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
        ];
    }

    /**
     * Gets query for [[Tracks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTracks()
    {
        return $this->hasMany(Track::className(), ['artist_id' => 'id']);
    }
}
