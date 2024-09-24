<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "aggregator_report".
 *
 * @property int $id
 * @property int $aggregator_id
 * @property int $report_status_id
 * @property float|null $total
 * @property string|null $description
 * @property int $user_id
 * @property string $date_added
 * @property string $last_update
 *
 * @property User $user
 * @property AggregatorReportStatus $reportStatus
 * @property AggregatorReportItem[] $aggregatorReportItems
 */
class AggregatorReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aggregator_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aggregator_id', 'user_id'], 'required'],
            [['aggregator_id', 'report_status_id', 'user_id'], 'integer'],
            [['total'], 'number'],
            [['date_added', 'last_update', 'description'], 'safe'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['aggregator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Aggregator::class, 'targetAttribute' => ['aggregator_id' => 'aggregator_id']],
            [['report_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => AggregatorReportStatus::class, 'targetAttribute' => ['report_status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'aggregator_id' => Yii::t('app', 'Агрегатор'),
            'aggregator_report_id' => Yii::t('app', 'Агрегатор Репорт'),
            'report_status_id' => Yii::t('app', 'Статус'),
            'total' => Yii::t('app', 'Сума'),
            'description' => Yii::t('app', 'Нотатка'),
            'user_id' => Yii::t('app', 'Завантажив'),
            'date_added' => Yii::t('app', 'Завантажено'),
            'last_update' => Yii::t('app', 'Оновлено'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Aggregator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregator()
    {
        return $this->hasOne(Aggregator::class, ['aggregator_id' => 'aggregator_id']);
    }

    /**
     * Gets query for [[ReportStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReportStatus()
    {
        return $this->hasOne(AggregatorReportStatus::class, ['id' => 'report_status_id']);
    }

    /**
     * Gets query for [[AggregatorReportItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAggregatorReportItems()
    {
        return $this->hasMany(AggregatorReportItem::class, ['report_id' => 'id']);
    }
}
