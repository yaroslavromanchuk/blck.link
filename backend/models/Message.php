<?php

namespace backend\models;

use Yii;
use common\models\Lang;
/**
 * This is the model class for table "message".
 *
 * @property int $id
 * @property string $name
 * @property string $translate
 * @property int $lang_id
 *
 * @property Lang $lang
 */
class Message extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'translate', 'lang_id'], 'required'],
            [['name', 'translate'], 'string'],
            [['lang_id'], 'integer'],
            [['lang_id'], 'exist', 'skipOnError' => true, 'targetClass' => Lang::className(), 'targetAttribute' => ['lang_id' => 'id']],
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
            'translate' => Yii::t('app', 'Translate'),
            'lang_id' => Yii::t('app', 'Lang ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne(Lang::className(), ['id' => 'lang_id']);
    }
}
