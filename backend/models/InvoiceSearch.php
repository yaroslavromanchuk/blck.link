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
            [['invoice_id', 'invoice_type', 'aggregator_id', 'currency_id', 'aggregator_report_id', 'invoice_status_id', 'quarter', 'year', 'label_id'], 'integer'],
            [['total'], 'number'],
            [['date_added', 'last_update'], 'safe'],
            [['note', 'apr', 'pay'], 'number'],
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
            'sort' => [
                'defaultOrder' => [
                    'invoice_id' => SORT_DESC
                ]
            ],
            'pagination' => [
                'pageSize' => 25,
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

        if ($this->label_id == 999999) {
            $query->andFilterWhere(['>', 'label_id', 0]);
        } else
            $query->andFilterWhere(['label_id' => $this->label_id]);{
        }
        
        
        if ($this->note == 1 || $this->note == -1 ) {
            
            $query->leftJoin(InvoiceLog::tableName() . ' as l_note', 'l_note.invoice_id = invoice.invoice_id and l_note.log_type_id = 1');
            
            if ($this->note == 1) {
                $query->andWhere(['not', ['l_note.log_id' => null]]);
                // $query->andWhere(['l_note.log_type_id' => 1]);
            } else {
                $query->andWhere(['l_note.log_id' => null]);
            }
        }
        
        if ($this->apr == 1 || $this->apr == -1) {
            $query->leftJoin(InvoiceLog::tableName() . ' as l_apr', 'l_apr.invoice_id = invoice.invoice_id and l_apr.log_type_id = 2');
            
            if ($this->apr == 1) {
                $query->andWhere(['not', ['l_apr.log_id' => null]]);
                //  $query->andWhere(['invoice_log.log_type_id' => 2]);
            } else {
                $query->andWhere(['l_apr.log_id' => null]);
            }
        }
        
        if ($this->pay == 1 || $this->pay == -1) {
            $query->leftJoin(InvoiceLog::tableName() . ' as l_pay', 'l_pay.invoice_id = invoice.invoice_id and l_pay.log_type_id = 3');
            
            if ($this->pay == 1) {
                $query->andWhere(['not', ['l_pay.log_id' => null]]);
                // $query->andWhere(['invoice_log.log_type_id' => 3]);
            } else {
                $query->andWhere(['l_pay.log_id' => null]);
            }
        }
        
        // $query->andWhere(['in', 'invoice_log.log_type_id', $n]);
    

        return $dataProvider;
    }
}
