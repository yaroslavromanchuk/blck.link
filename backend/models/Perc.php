<?php
namespace backend\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class Perc extends Model
{
    public int $track_id;
    public $data;
    public int $sum;
    public function rules()
    {
        return [
           // [[ 'ownership_id'], 'required'],
            [[ 'sum'], 'integer'],
            [[ 'ownership_name'], 'string'],
           // [[ 'ownership_id', 'sum'], 'integer'],
            //['sum', 'validatePassword'],
            //[['percentage'], 'max' => 100],
             [['sum'], 'compare', 'compareValue' => 100, 'operator' => '<=',  'skipOnError' => true, 'targetClass' => Perc::class, 'message' => Yii::t('app', 'Сума відсотів не може перевищувати 100%')],

        ];
    }

    public function attributeLabels(): array
    {
        return [
            'track_id' => Yii::t('app', 'Трек'),
            'ownership_id' => Yii::t('app', 'Тип'),
            'ownership_name' => Yii::t('app', 'Тип2'),
            'sum' => Yii::t('app', 'Сума'),
            'data' => Yii::t('app', 'Дані'),
        ];
    }

}