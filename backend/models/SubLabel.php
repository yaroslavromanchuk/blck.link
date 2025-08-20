<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "sub_label".
 *
 * @property int $id
 * @property string $name
 * @property string|null $url
 * @property string|null $logo
 * @property int $active
 * @property string $ipn
 * @property int $edrpou
 * @property int $mfo
 * @property string $bank
 * @property string|null $description
 * @property string $iban
 * @property string $address
 * @property string $date_added
 * @property string $last_update
 * @property int $percentage
 * @property int $percentage_distribution
 * @property int $telegram_id
 * @property string|null $phone
 * @property string|null $email
 * @property string $full_name
 * @property string $contract
 * @property string $tov_name
 * @property int $label_type_id
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
            [['edrpou', 'mfo',  'percentage', 'percentage_distribution', 'telegram_id', 'label_type_id', 'active'], 'integer'],
            [['ipn'], 'string', 'length' => 10],
            [['bank'], 'string', 'max' => 150],
            [['iban'], 'string', 'length' => 29],
            [['address'], 'string', 'max' => 250],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 50],
            [['full_name', 'contract', 'tov_name'], 'string', 'max' => 100],
            [['percentage'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true,  'message' => Yii::t('app', 'Max 100%')],
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
            'name' => Yii::t('app', 'Назва'),
            'phone' => Yii::t('app', 'Телефон'),
            'email' => Yii::t('app', 'Email'),
            'url' => Yii::t('app', 'Url'),
            'logo' => Yii::t('app', 'Логотип'),
            'active' => Yii::t('app', 'Активність'),
            'ipn' => Yii::t('app', 'РНОКПП'),
            'edrpou' => Yii::t('app', 'Код ЄДРПОУ'),
            'address' => Yii::t('app', 'Місцезнаходження'),
            'iban' => Yii::t('app', 'IBAN'),
            'bank' => Yii::t('app', 'Банк'),
            'mfo' => Yii::t('app', 'МФО'),
            'description' => Yii::t('app', 'Додатково (коментар)'),
            'date_added' => Yii::t('app', 'Створено'),
            'last_update' => Yii::t('app', 'Останнє оновлення'),
            'full_name' => Yii::t('app', 'ПІБ директора'),
            'contract' => Yii::t('app', 'Договір'),
            'tov_name' => Yii::t('app', 'Назва ТОВ'),
            'label_type_id' => Yii::t('app', 'Тип'),
            'percentage' => Yii::t('app', 'Паблішинг %'),
            'percentage_distribution' => Yii::t('app', 'Дистрибуція %'),
            'telegram_id' => Yii::t('app', 'ТелеграмID'),
        ];
    }

    public function getType(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ArtistType::class, ['type_id' => 'label_type_id']);
    }

    public function getArtists(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Artist::class, ['label_id' => 'id']);
    }
}
