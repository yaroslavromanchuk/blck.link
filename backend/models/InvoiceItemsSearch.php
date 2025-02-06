<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\InvoiceItems;

/**
 * InvoiceItemsSearch represents the model behind the search form of `backend\models\InvoiceItems`.
 */
class InvoiceItemsSearch extends InvoiceItems
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'invoice_id', 'track_id', 'artist_id'], 'integer'],
            [['isrc', 'date_item', 'last_update', 'platform'], 'safe'],
           // [[ 'platform'], 'string'],
            [['amount', 'count'], 'number'],
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
        $query = InvoiceItems::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 1000,
            ],
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
            'invoice_id' => $this->invoice_id,
            'track_id' => $this->track_id,
            'artist_id' => $this->artist_id,
            'isrc' => $this->isrc,
           // 'amount' => $this->amount,
            //'date_item' => $this->date_item,
           // 'last_update' => $this->last_update,
        ]);

       // $query->andFilterWhere(['like', 'isrc', $this->isrc]);

        return $dataProvider;
    }
}
