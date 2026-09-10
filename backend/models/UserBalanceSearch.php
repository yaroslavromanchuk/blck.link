<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form of `frontend\models\UserBalance`.
 */
class UserBalanceSearch extends UserBalance
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['balance_id','invoice_id', 'user_id', 'label_id', 'artist_id', 'track_id', 'currency_id', 'is_pay'], 'integer'],
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
        $query = UserBalance::find();

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
            'balance_id' => $this->balance_id,
            'invoice_id' => $this->invoice_id,
            'user_id' => $this->user_id,
            'label_id' => $this->label_id,
            'artist_id' => $this->artist_id,
            'track_id' => $this->track_id,
            'currency_id' => $this->currency_id,
        ]);

        if ($this->is_pay == 2 || $this->is_pay == 1) {

            if ($this->is_pay > 1) {
                $query->andWhere(['is_pay' => null]);
                //$query->andFilterWhere(['is NULL', 'is_pay', $this->is_pay]);
            } else {
                $query->andFilterWhere(['is_pay' => $this->is_pay]);
            }


        }



        return $dataProvider;
    }
}
