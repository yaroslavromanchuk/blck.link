<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "albums_type".
 *
 * @property int $type_id
 * @property string $name
 * @property string $date_added
 * @property string $last_update
 *
 * @property Albums $type
 */
class AlbumsType extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'albums_type';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['name'], 'required'],
			[['date_added', 'last_update'], 'safe'],
			[['name'], 'string', 'max' => 55],
			[['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Albums::class, 'targetAttribute' => ['type_id' => 'type_id']],
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'type_id' => 'Type ID',
			'name' => 'Name',
			'date_added' => 'Date Added',
			'last_update' => 'Last Update',
		];
	}
	
	/**
	 * Gets query for [[Type]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getType()
	{
		return $this->hasOne(Albums::class, ['type_id' => 'type_id']);
	}
}