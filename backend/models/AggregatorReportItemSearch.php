<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AggregatorReportSearch represents the model behind the search form of `backend\models\AggregatorReport`.
 */
class AggregatorReportItemSearch extends AggregatorReportItem
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'report_id', 'count'], 'integer'],
            [['isrc', 'date_report', 'platform', 'date_added', 'last_update'], 'safe'],
            [['amount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = AggregatorReportItem::find();
          //  ->leftJoin(Track::tableName(), 'track.isrc = aggregator_report_item.isrc')
           // ->onCondition(['=', 'track.album', 0]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

       // $query->leftJoin(Track::tableName(), 'track.isrc = aggregator_report_item.isrc');

        // grid filtering conditions
        $query->andFilterWhere([
            'aggregator_report_item.id' => $this->id,
            'aggregator_report_item.report_id' => $this->report_id,
            'aggregator_report_item.date_report' => $this->date_report,
            'aggregator_report_item.count' => $this->count,
            'aggregator_report_item.amount' => $this->amount,
            'aggregator_report_item.date_added' => $this->date_added,
           // 'last_update' => $this->last_update,
        ]);

        $query->andFilterWhere(['like', 'aggregator_report_item.isrc', $this->isrc])
            ->andFilterWhere(['like', 'aggregator_report_item.platform', $this->platform]);
           // ->andFilterWhere(['like', 'artist', $this->artist])
           // ->andFilterWhere(['like', 'releas', $this->releas])
           // ->andFilterWhere(['like', 'track', $this->track]);

       // $query->andFilterWhere(['is', 'track.id', new \yii\db\Expression('null')]);

        return $dataProvider;
    }
}
