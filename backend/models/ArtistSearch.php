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
            [['id', 'active', 'deposit', 'deposit_1', 'reliz', 'percentage'], 'integer'],
            [['name',  'phone', 'email'], 'safe'],
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

        if (Yii::$app->user->identity->type != 1) {
            $query->andFilterWhere(['!=', 'id', Artist::label]);
            $query->andFilterWhere(['admin_id' => Yii::$app->user->identity->id]);
        }

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['=', 'percentage', $this->email]);

        $query->andFilterWhere(['>=', 'deposit', $this->deposit])
        ->andFilterWhere(['>=', 'deposit_1', $this->deposit_1]);

        return $dataProvider;
    }
}
