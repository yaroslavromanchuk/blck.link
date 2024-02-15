<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_type".
 *
 * @property int $user_type_id
 * @property string $type_name
 * @property string $date_add
 * @property string $last_update
 */
class UserType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_name'], 'required'],
            [['type_name'], 'string', 'max' => 55],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_type_id' => Yii::t('app', 'User Type ID'),
            'type_name' => Yii::t('app', 'Type Name'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
}
