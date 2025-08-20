<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_report".
 *
 * @property int $id
 * @property int $report_id
 * @property int $track_id
 * @property string $isrc
 * @property string $date_report
 * @property string $platform
 * @property string $artist
 * @property string $releas
 * @property string $track
 * @property string $country
 * @property int $count
 * @property float $amount
 * @property string $date_added
 * @property string $last_update
 *
 * @property Aggregator $aggregator
 * @property AggregatorReportStatus $reportStatus
 */
class AggregatorReportItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_report_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'count', 'report_id', 'track_id'], 'integer'],
            [['report_id', 'isrc', 'date_report', 'amount'], 'required'],
            [['date_report', 'date_added', 'last_update'], 'safe'],
            [['amount'], 'number'],
            [['isrc'], 'string', 'max' => 100],
            [['platform', 'country'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'report_id' => Yii::t('app', '№ репорту'),
            'track_id' => Yii::t('app', 'ІД треку'),
            'isrc' => Yii::t('app', 'Isrc'),
            'date_report' => Yii::t('app', 'Дані від'),
            'platform' => Yii::t('app', 'Платформа'),
            'country' => Yii::t('app', 'Країна'),
           // 'releas' => Yii::t('app', 'Реліз'),
            //'track' => Yii::t('app', 'Трек'),
            'count' => Yii::t('app', 'Преегляди'),
            'amount' => Yii::t('app', 'Винагорода'),
            'date_added' => Yii::t('app', 'Date Added'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }
}
