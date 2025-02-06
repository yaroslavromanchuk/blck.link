<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use Yii;

/**
 * ArtistSearch represents the model behind the search form of `backend\models\Artist`.
 */
class ArtistSearch extends Artist
{
    public $reliz;
    /**
     * {@inheritdoc}
     */
    public function rules(): array
	{
        return [
            [['id', 'label_id', 'active', 'deposit', 'deposit_1', 'reliz', 'percentage', 'last_payment_invoice'], 'integer'],
            [['name',  'phone', 'email', 'date_last_payment'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
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
    public function search(array $params): ActiveDataProvider
	{
        $query = Artist::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'enableMultiSort' => false,
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
    		],
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
            'active' => $this->active,
            //'reliz' => $this->reliz,
        ]);

        $query->andFilterWhere(['label_id' => Yii::$app->user->identity->label_id]);

        $query->andFilterWhere(['like', 'name', '%'.$this->name.'%', false])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'date_last_payment', $this->date_last_payment])
            ->andFilterWhere(['=', 'last_payment_invoice', $this->last_payment_invoice])
            ->andFilterWhere(['=', 'percentage', $this->percentage]);

        $query->andFilterWhere(['>=', 'deposit', $this->deposit])
        ->andFilterWhere(['>=', 'deposit_1', $this->deposit_1]);

        return $dataProvider;
    }
}
