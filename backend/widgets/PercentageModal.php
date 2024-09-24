<?php

namespace backend\widgets;

use backend\models\Perc;
use backend\models\Percentage;
use backend\models\Track;
use yii\base\Widget;

class PercentageModal extends Widget {
    public Perc $model;
    public ?int $trackId;
    public function init() {
        $data = Percentage::find()
            ->select(['track_to_percentage.id', 'track_to_percentage.track_id', 'track_to_percentage.artist_id', 'track_to_percentage.percentage',
                'artist.name as artist_name',
                'ownership.id as ownership_id', 'ownership.name as ownership_name',
                'ownership_type.id as ownership_type_id', 'ownership_type.name as type_name', ])
            ->from('track_to_percentage')
            ->innerJoin('track', 'track.id = track_to_percentage.track_id')
            ->innerJoin('artist', 'artist.id = track_to_percentage.artist_id')
            ->leftJoin('ownership_type', 'ownership_type.id = track_to_percentage.ownership_type')
            ->leftJoin('ownership', 'ownership.id = ownership_type.ownership_id')
            ->where(['track_to_percentage.track_id' => $this->trackId])
            ->orderBy('track_to_percentage.artist_id')
            ->asArray()
            ->all();

       // echo '<pre>';
       //// print_r($data);
       // echo '</pre>';
        $mdata = [];

        foreach ($data as $item) {
            $mdata[$item['ownership_id']][$item['ownership_type_id']][$item['artist_name'] . ': ' . $item['type_name']] = $item;
        }

       // echo '<pre>';
       // print_r($mdata);
      //  echo '</pre>';
        //exit;

        $this->model = new Perc();
        $this->model->track_id = $this->trackId;
        $this->model->data = $mdata;
    }
    public function run() {
        return $this->render('__percentageModal',[
            'model' => $this->model,
            'track' => Track::findOne($this->trackId),
        ]);
    }
}