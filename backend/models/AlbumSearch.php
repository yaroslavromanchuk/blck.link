<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Albums;

/**
 * AlbumSearch represents the model behind the search form of `backend\models\Albums`.
 */
class AlbumSearch extends Albums
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'admin_id', 'artist_id', 'sharing', 'views', 'click', 'active', 'type_id'], 'integer'],
            [['artist_name', 'date', 'name', 'img', 'url', 'youtube_link', 'servise', 'date_added', 'last_update'], 'safe'],
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
        $query = Albums::find();

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
            'id' => $this->id,
            'admin_id' => $this->admin_id,
            'artist_id' => $this->artist_id,
            'date' => $this->date,
            'sharing' => $this->sharing,
            'views' => $this->views,
            'click' => $this->click,
            'active' => $this->active,
            'date_added' => $this->date_added,
            'last_update' => $this->last_update,
        ]);

        $query->andFilterWhere(['like', 'artist_name', $this->artist_name])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'img', $this->img])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'youtube_link', $this->youtube_link])
            ->andFilterWhere(['like', 'servise', $this->servise]);

        return $dataProvider;
    }
}
