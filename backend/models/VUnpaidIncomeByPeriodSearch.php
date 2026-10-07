<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class VUnpaidIncomeByPeriodSearch extends Model
{
    public $artist_id;
    public $currency_id;
    public $year;
    public $quarter;
    
    public function rules(): array
    {
        return [
            [['artist_id', 'currency_id', 'year', 'quarter'], 'integer'],
        ];
    }
    
    public function search(array $params): ActiveDataProvider
    {
        $query = VUnpaidIncomeByPeriod::find()
            ->alias('v')
            ->with([
                'currency',
            ])
            ->select([
                'v.*',
                'c.currency_name',
            ])
            ->leftJoin(['c' => 'currency'], 'c.currency_id = v.currency_id');
        
        // для кабінету артиста
        $query->andWhere(['v.artist_id' => $this->artist_id]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'year' => SORT_DESC,
                    'quarter' => SORT_ASC,
                ],
            ],
        ]);
        
        $this->load($params);
        
        if (!$this->validate()) {
            return $dataProvider;
        }
        
        $query->andFilterWhere([
            'v.currency_id' => $this->currency_id,
            'v.year'        => $this->year,
            'v.quarter'     => $this->quarter,
        ]);
        
        return $dataProvider;
    }
}
