<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "artist".
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $logo
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
     public $file;
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
	{
        return 'artist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
	{
        return [
            [['name'], 'required'],
            [['active', 'admin_id'], 'integer'],
            [['name'], 'string', 'max' => 150],
            ['name', 'unique', 'targetClass' => '\backend\models\Artist', 'message' => Yii::t('app', 'Артист з цим ім\'ям вже існує!')],
            [['logo', 'facebook', 'vk', 'twitter', 'youtube', 'instagram', 'telegram', 'viber', 'whatsapp', 'ofsite'], 'string', 'max' => 255],
            [['file'], 'image', 'extensions' => 'png, jpg, jpeg'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
	{
        return [
            'id' => Yii::t('app', '№'),
            'name' => Yii::t('app', 'І\'мя'),
            'logo' => Yii::t('app', 'Фото'),
            'phone' => Yii::t('app', 'Телефон'),
            'email' => Yii::t('app', 'Email'),
            'active' => Yii::t('app', 'Активність'),
            'facebook' => Yii::t('app', 'Facebook'),
            'vk' => Yii::t('app', 'Vk'),
            'twitter' => Yii::t('app', 'Twitter'),
            'youtube' => Yii::t('app', 'Youtube'),
            'instagram' => Yii::t('app', 'Instagram'),
            'telegram' => Yii::t('app', 'Telegram'),
            'viber' => Yii::t('app', 'Viber'),
            'whatsapp' => Yii::t('app', 'Whatsapp'),
            'ofsite' => Yii::t('app', 'Оф.Сайт'),
            //'reliz' => Yii::t('app', 'Релизы'),
            'admin_id' => Yii::t('app', 'Створив'),
            'file' => Yii::t('app', 'Лого'),
        ];
    }

    /**
     * Gets query for [[Tracks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTracks(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Track::class, ['artist_id' => 'id']);
    }
    /**
     * Gets query for [Admin]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
    public function getLogo(): string
	{
        return Yii::getAlias('@site').'/images/artist/'.$this->logo;
    }
}
