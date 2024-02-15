<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ReleaseSearch represents the model behind the search form of `backend\models\Release`.
 */
class PercentageSearch extends Percentage
{

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['track_id'], 'integer'],
           // [['release_name', ], 'safe'],
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
        $query = Percentage::find();

        if (isset($params['track_id'])) {
            $query->where(['track_id' => $params['track_id']]);
        }

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
       // $query->andFilterWhere([
            //'track_id' => $this->track_id,
            //'date_add' => $this->date_add,
            //'last_update' => $this->last_update,
       // ]);

        return $dataProvider;
    }
}
