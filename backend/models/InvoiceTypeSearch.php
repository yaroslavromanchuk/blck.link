<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\InvoiceType;

/**
 * InvoiceTypeSearch represents the model behind the search form of `backend\models\InvoiceType`.
 */
class InvoiceTypeSearch extends InvoiceType
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_type_id'], 'integer'],
            [['invoice_type_name', 'date_add', 'last_update'], 'safe'],
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
        $query = InvoiceType::find();

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
            'invoice_type_id' => $this->invoice_type_id,
            'date_add' => $this->date_add,
            'last_update' => $this->last_update,
        ]);

        $query->andFilterWhere(['like', 'invoice_type_name', $this->invoice_type_name]);

        return $dataProvider;
    }
}
