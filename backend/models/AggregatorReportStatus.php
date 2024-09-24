<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_report_status".
 *
 * @property int $id
 * @property string $name
 * @property string $date_added
 * @property string $last_update
 *
 * @property AggregatorReportItem[] $aggregatorReports
 */
class AggregatorReportStatus extends \yii\db\ActiveRecord
{
    const LOADED = 1;
    const GENERATED_INVOICE = 2;
    const CANCELED = 3;
    const CONFLICT = 4;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_report_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date_added', 'last_update'], 'safe'],
            [['name'], 'string', 'max' => 255],
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
            'date_added' => Yii::t('app', 'Date Added'),
            'last_update' => Yii::t('app', 'Last Update'),
        ];
    }

    /**
     * Gets query for [[AggregatorReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregatorReports()
    {
        return $this->hasMany(AggregatorReportItem::className(), ['report_status_id' => 'id']);
    }
}
