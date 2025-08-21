<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "currency".
 *
 * @property int $currency_id
 * @property string $currency_name
 * @property string $currency_symbol
 * @property string $date_add
 * @property string $last_update
 */
class Currency extends \yii\db\ActiveRecord
{
	const EUR = 1;
	const UAH = 2;
	const USD = 3;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'currency';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['currency_name'], 'required'],
            [['date_add', 'last_update'], 'safe'],
            [['currency_name'], 'string', 'max' => 50],
            [['currency_symbol'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'currency_id' => Yii::t('app', 'Currency ID'),
            'currency_name' => Yii::t('app', 'Currency Name'),
            'currency_symbol' => Yii::t('app', 'Currency Symbol'),
            'date_add' => Yii::t('app', 'Date Add'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
    public function getName(): string
    {
        return $this->currency_name;
    }
}
