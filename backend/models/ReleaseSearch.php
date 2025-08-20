<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use Yii;

/**
 * ReleaseSearch represents the model behind the search form of `backend\models\Release`.
 */
class ReleaseSearch extends Release
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['release_id'], 'integer'],
            [['release_name', 'date_add',], 'safe'],
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
        $query = Release::find();

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

        $query->innerJoin(User::tableName(), 'user.id = releases.admin_id');

        // grid filtering conditions
        $query->andFilterWhere([
            'releases.release_id' => $this->release_id,
            'releases.date_add' => $this->date_add,
            //'last_update' => $this->last_update,
        ]);

        $query->andFilterWhere(['user.label_id' => Yii::$app->user->identity->label_id]);
        $query->andFilterWhere(['like', 'releases.release_name', $this->release_name]);

        return $dataProvider;
    }
}
