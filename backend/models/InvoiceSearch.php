<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Invoice;

/**
 * InvoiceSearch represents the model behind the search form of `backend\models\Invoice`.
 */
class InvoiceSearch extends Invoice
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'invoice_type', 'aggregator_id', 'currency_id', 'aggregator_report_id', 'invoice_status_id', 'quarter', 'year'], 'integer'],
            [['total'], 'number'],
            [['date_added', 'last_update'], 'safe'],
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
        $query = Invoice::find();

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
            'invoice_id' => $this->invoice_id,
            'invoice_type' => $this->invoice_type,
            'aggregator_id' => $this->aggregator_id,
            'invoice_status_id' => $this->invoice_status_id,
            'aggregator_report_id' => $this->aggregator_report_id,
            'currency_id' => $this->currency_id,
            'total' => $this->total,
            'date_added' => $this->date_added,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'last_update' => $this->last_update,
        ]);

        return $dataProvider;
    }
}
