<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "sub_label".
 *
 * @property int $id
 * @property string $name
 * @property string|null $url
 * @property string|null $description
 * @property string|null $logo
 * @property int $active
 * @property string $date_added
 * @property string $last_update
 *
 * @property User $user
 */
class SubLabel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sub_label';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_added', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 100],
            [['url'], 'string', 'max' => 50],
            [['description', 'logo'], 'string', 'max' => 255],
            [['url'], 'unique'],
            //[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'url' => Yii::t('app', 'Url'),
            'description' => Yii::t('app', 'Description'),
            'logo' => Yii::t('app', 'Logo'),
            'active' => Yii::t('app', 'Active'),
            'date_added' => Yii::t('app', 'Date Added'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
}
