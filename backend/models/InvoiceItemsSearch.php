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
        
            if ($this->note == 1 || $this->note == -1) {
                $query->leftJoin(InvoiceLog::tableName() . ' as l_note', 'l_note.invoice_id = invoice_items.invoice_id and l_note.artist_id = invoice_items.artist_id and l_note.log_type_id = 1');
                
                if ($this->note == 1) {
                    $query->andWhere(['not', ['l_note.log_id' => null]]);
                   // $query->andWhere(['l_note.log_type_id' => 1]);
                } else {
                    $query->andWhere(['l_note.log_id' => null]);
                }
            }

            if ($this->apr == 1 || $this->apr == -1) {
                $query->leftJoin(InvoiceLog::tableName() . ' as l_apr', 'l_apr.invoice_id = invoice_items.invoice_id and l_apr.artist_id = invoice_items.artist_id and l_apr.log_type_id = 2');
                
                if ($this->apr == 1) {
                    $query->andWhere(['not', ['l_apr.log_id' => null]]);
                  //  $query->andWhere(['invoice_log.log_type_id' => 2]);
                } else {
                    $query->andWhere(['l_apr.log_id' => null]);
                }
            }

            if ($this->pay == 1 || $this->pay == -1) {
                $query->leftJoin(InvoiceLog::tableName() . ' as l_pay', 'l_pay.invoice_id = invoice_items.invoice_id and l_pay.artist_id = invoice_items.artist_id and l_pay.log_type_id = 3');
                
                if ($this->pay == 1) {
                    $query->andWhere(['not', ['l_pay.log_id' => null]]);
                   // $query->andWhere(['invoice_log.log_type_id' => 3]);
                } else {
                    $query->andWhere(['l_pay.log_id' => null]);
                }
            }

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
