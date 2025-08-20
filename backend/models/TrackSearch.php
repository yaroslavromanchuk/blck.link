<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use Yii;

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
            [['artist_id', 'sharing', 'is_album', 'views', 'click', 'active'], 'integer'],
            [['artist_name', 'date', 'date_added', 'name', 'url', 'isrc'], 'safe'],
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
                    'date_added',
                    'artist_name' =>[
                        'label' =>  'Артист'
                    ],
                    'views',
                    'click'
                ],
                'enableMultiSort' => false,
                'defaultOrder' => [
                    'date_added' => SORT_DESC
                    ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

       /// $query->innerJoin(User::tableName(), 'user.id = track.admin_id');
       // $query->innerJoin(Artist::tableName(), 'artist.id = track.artist_id');

        // grid filtering conditions
        $query->andFilterWhere([
          //  'id' => $this->id,
            'track.artist_id' => $this->artist_id,
            //'track.date' => $this->date,
            'track.sharing' => $this->sharing,
            'track.views' => $this->views,
            'track.click' => $this->click,
            'track.active' => $this->active,
           // 'artist.label_id' => $this->label_id,
        ]);

        if ($this->is_album == 1) {
            $query->andFilterWhere(['track.is_album' => $this->is_album]);
        }

        $query->andFilterWhere(['like', 'track.artist_name', $this->artist_name])
            ->andFilterWhere(['like', 'track.name', '%' .$this->name.'%', false])
            ->andFilterWhere(['like', 'track.url', $this->url])
            ->andFilterWhere(['like', 'track.tag', $this->tag])
            ->andFilterWhere(['like', "track.isrc", str_replace('-', '', $this->isrc), false])
            ->andFilterWhere(['>=', 'track.date', $this->date])
            //->andFilterWhere(['<', 'date', '2025-01-01'])
            ->andFilterWhere(['>=', 'track.date_added', $this->date_added]);
        ;
       //    ->andFilterWhere(['like', 'apple', $this->apple]) 
         //  ->andFilterWhere(['like', 'boom', $this->boom]) 
         //  ->andFilterWhere(['like', 'spotify', $this->spotify]) 
         //  ->andFilterWhere(['like', 'youtube', $this->youtube])
         //  ->andFilterWhere(['like', 'googleplaystore', $this->googleplaystore])
         //  ->andFilterWhere(['like', 'vk', $this->vk])
         //  ->andFilterWhere(['like', 'deezer', $this->deezer])
         //  ->andFilterWhere(['like', 'yandex', $this->yandex]);

        //$query->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id]);


        return $dataProvider;
    }
}
