<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "albums".
 *
 * @property int $id
 * @property int $admin_id
 * @property int $type_id
 * @property int $artist_id
 * @property string $artist_name
 * @property string|null $date
 * @property string|null $name
 * @property string|null $img
 * @property string|null $url
 * @property string|null $youtube_link
 * @property int $sharing
 * @property int $views
 * @property int $click
 * @property int $active
 * @property string|null $servise
 * @property string $date_added
 * @property string $last_update
 */
class Albums extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'albums';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['admin_id', 'artist_id', 'type_id'], 'required'],
			[['admin_id', 'artist_id', 'sharing', 'views', 'click', 'active', 'type_id'], 'integer'],
			[['date', 'date_added', 'last_update'], 'safe'],
			[['servise'], 'string'],
			[['artist_name'], 'string', 'max' => 100],
			[['name', 'img', 'youtube_link'], 'string', 'max' => 255],
			[['url'], 'string', 'max' => 50],
			['url', 'unique', 'targetClass' => self::class, 'message' => Yii::t('app', 'Це посилання вже зайняте!')],
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'admin_id' => 'Адмін ID',
			'artist_id' => 'Артіст ID',
			'artist_name' => 'Artist Name',
			'date' => 'Реліз',
			'name' => 'Назва',
			'img' => 'Лого',
			'url' => 'Url',
			'youtube_link' => 'Youtube Link',
			'sharing' => 'Sharing',
			'views' => 'Перегляди',
			'click' => 'Кліки',
			'active' => 'Актинвість',
			'servise' => 'Сервіси',
			'date_added' => 'Створено',
			'last_update' => 'Оновлено',
			'type_id' => 'Тип альбому',
		];
	}
	
	/**
	 * Gets query for [[Tracks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTracks(): \yii\db\ActiveQuery
	{
		return $this->hasMany(Track::class, ['album_id' => 'id']);
	}
	
	/**
	 * Gets query for [[Artist]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getArtist(): \yii\db\ActiveQuery
	{
		return $this->hasOne(Artist::class, ['id' => 'artist_id']);
	}
	
	public function getImage()
	{
		return '/images/track/'.$this->img;
	}
}
