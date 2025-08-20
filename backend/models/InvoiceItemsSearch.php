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
            [['amount', 'count', 'note', 'apr', 'pay'], 'number'],
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
                'pageSize' => 100,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

/*
        if ($this->note >= 0 || $this->apr >= 0 || $this->pay >= 0) {
            $query->leftJoin(InvoiceLog::tableName(), 'invoice_log.invoice_id = invoice_items.invoice_id and invoice_log.artist_id = invoice_items.artist_id');

            $n = [];
            if ($this->note >= 0) {
                if ($this->note == 1) {
                    $n[] = 1;
                    $query->andWhere(['invoice_log.log_type_id' => 1]);
                }
            }

            if ($this->apr >= 0) {
                if ($this->apr == 1) {
                    $n[] = 2;
                    $query->andWhere(['invoice_log.log_type_id' => 2]);
                }
            }

            if ($this->pay >= 0) {
                if ($this->pay == 1) {
                    $n[] = 3;
                    $query->andWhere(['invoice_log.log_type_id' => 3]);
                }
            }

           // $query->andWhere(['in', 'invoice_log.log_type_id', $n]);
        }*/

        // grid filtering conditions
        $query->andFilterWhere([
            'invoice_items.id' => $this->id,
            'invoice_items.invoice_id' => $this->invoice_id,
            'invoice_items.track_id' => $this->track_id,
            'invoice_items.artist_id' => $this->artist_id,
            'invoice_items.isrc' => $this->isrc,
           // 'amount' => $this->amount,
            //'date_item' => $this->date_item,
           // 'last_update' => $this->last_update,
        ]);

       // $query->andFilterWhere(['like', 'isrc', $this->isrc]);

        return $dataProvider;
    }
}
