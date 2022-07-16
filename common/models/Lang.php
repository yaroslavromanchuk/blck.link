<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "lang".
 *
 * @property int $id
 * @property string $url
 * @property string $local
 * @property string $name
 * @property int $default
 * @property int $date_update
 * @property int $date_create
 */
class Lang extends \yii\db\ActiveRecord
{
	//Переменная, для хранения текущего объекта языка
	static $current = null;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lang';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url', 'local', 'name', 'date_update', 'date_create'], 'required'],
            [['default', 'date_update', 'date_create', 'active'], 'integer'],
            [['url', 'local'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 100],
        ];
    }

	public function behaviors()
	{
    return [
        'timestamp' => [
            'class' => 'yii\behaviors\TimestampBehavior',
            'attributes' => [
                \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['date_create', 'date_update'],
                \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['date_update'],
            ],
        ],
    ];
	}
	
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url префікс',
            'local' => 'Локальність',
            'name' => 'Назва',
            'default' => 'За замовчуванням',
            'active' => 'Активність',
            'date_update' => 'Дата оновлення',
            'date_create' => 'Дата створення',
        ];
    }
	
	//Получение текущего объекта языка
static function getCurrent()
{
    if( self::$current === null ){
        self::$current = self::getDefaultLang();
    }
    return self::$current;
}

//Установка текущего объекта языка и локаль пользователя
static function setCurrent($url = null)
{
    $language = self::getLangByUrl($url);
    self::$current = ($language === null) ? self::getDefaultLang() : $language;
    Yii::$app->language = self::$current->local;
}

//Получения объекта языка по умолчанию
static function getDefaultLang()
{
    return Lang::find()->where('`default` = :default', [':default' => 1])->one();
}

//Получения объекта языка по буквенному идентификатору
static function getLangByUrl($url = null)
{
    if ($url === null) {
        return null;
    } else {
        $language = Lang::find()->where('url = :url', [':url' => $url])->one();
        if ( $language === null ) {
            return null;
        }else{
            return $language;
        }
    }
}
}
