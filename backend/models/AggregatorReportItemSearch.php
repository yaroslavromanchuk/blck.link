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
            [['isrc', 'date_report', 'platform', 'artist', 'releas', 'track', 'date_added', 'last_update'], 'safe'],
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

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'report_id' => $this->report_id,
            'date_report' => $this->date_report,
            'count' => $this->count,
            'amount' => $this->amount,
            'date_added' => $this->date_added,
            'last_update' => $this->last_update,
        ]);

        $query->andFilterWhere(['like', 'isrc', $this->isrc])
            ->andFilterWhere(['like', 'platform', $this->platform])
            ->andFilterWhere(['like', 'artist', $this->artist])
            ->andFilterWhere(['like', 'releas', $this->releas])
            ->andFilterWhere(['like', 'track', $this->track]);

        return $dataProvider;
    }
}
