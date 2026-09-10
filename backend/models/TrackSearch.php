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
        $query = Track::find()
            ->alias('t')
            ->with([
                'aggregator',
            ])
            ->select([
                't.*',
                'GROUP_CONCAT(ag.name) as aggregator_names'
            ])
            ->leftJoin(['t2a' => 'track_to_aggregator'], 't2a.track_id = t.id')
            ->leftJoin(['ag' => 'aggregator'], 'ag.aggregator_id = t2a.aggregator_id')
            ->groupBy('t.id');
        ;

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
            't.artist_id' => $this->artist_id,
            //'track.date' => $this->date,
            't.sharing' => $this->sharing,
            't.views' => $this->views,
            't.click' => $this->click,
            't.active' => $this->active,
           // 'artist.label_id' => $this->label_id,
        ]);

        if ($this->is_album == 1) {
            $query->andFilterWhere(['t.is_album' => $this->is_album]);
        }

        $query->andFilterWhere(['like', 't.artist_name', $this->artist_name])
            ->andFilterWhere(['like', 't.name', '%' .$this->name.'%', false])
            ->andFilterWhere(['like', 't.url', $this->url])
            ->andFilterWhere(['like', 't.tag', $this->tag])
            ->andFilterWhere(['like', "t.isrc", str_replace('-', '', $this->isrc), false])
            ->andFilterWhere(['>=', 't.date', $this->date])
            //->andFilterWhere(['<', 'date', '2025-01-01'])
            ->andFilterWhere(['>=', 't.date_added', $this->date_added]);
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
