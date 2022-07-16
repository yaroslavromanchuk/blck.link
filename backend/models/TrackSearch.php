<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Track;

/**
 * TrackSearch represents the model behind the search form of `backend\models\Track`.
 */
class TrackSearch extends Track
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['artist_id', 'sharing', 'views', 'click', 'active'], 'integer'],
            [['artist', 'date', 'name', 'img', 'url', 'youtube_link', 'tag'], 'safe'],
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
        $query = Track::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
             'sort'=> [
                'attributes' => [
                 //  'id' =>[
                    //   'default' => SORT_ASC,
                  //  ],
                    'date',
                    'artist' =>[
                        'label' =>  'Артист'
                    ],
                    'views',
                    'click'
        ],
                'enableMultiSort' => false,
                'defaultOrder' => [
                    'date' => SORT_DESC
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
          //  'id' => $this->id,
            'artist_id' => $this->artist_id,
            'date' => $this->date,
            'sharing' => $this->sharing,
            'views' => $this->views,
            'click' => $this->click,
            'active' => $this->active,
        ]);

        $query->andFilterWhere(['like', 'artist', $this->artist])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'tag', $this->tag])
            ->andFilterWhere(['like', 'youtube_link', $this->youtube_link]); 
       //    ->andFilterWhere(['like', 'apple', $this->apple]) 
         //  ->andFilterWhere(['like', 'boom', $this->boom]) 
         //  ->andFilterWhere(['like', 'spotify', $this->spotify]) 
         //  ->andFilterWhere(['like', 'youtube', $this->youtube])
         //  ->andFilterWhere(['like', 'googleplaystore', $this->googleplaystore])
         //  ->andFilterWhere(['like', 'vk', $this->vk])
         //  ->andFilterWhere(['like', 'deezer', $this->deezer])
         //  ->andFilterWhere(['like', 'yandex', $this->yandex]);

        return $dataProvider;
    }
}
