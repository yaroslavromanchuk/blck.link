<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "releases".
 *
 * @property int $release_id
 * @property int $label_id
 * @property int $admin_id
 * @property string $release_name
 * @property string $date_add
 * @property string $last_update
 */
class Release extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'releases';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['release_name'], 'required'],
            [['label_id', 'admin_id'], 'integer'],
            [['date_add', 'last_update'], 'safe'],
            [['release_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'release_id' => Yii::t('app', 'Release ID'),
            'release_name' => Yii::t('app', 'Release Name'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
            'admin_id' => Yii::t('app', 'Менеджер'),
        ];
    }

    public function getAdmin(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
}
