<?php
namespace backend\widgets;
//use Yii;
use yii\base\Widget;
use backend\models\Artist;

class CreateArtist extends Widget {
    public Artist $artist;
    public function init() {
       //  assets\b2b\Assets::register($this->getView());
        $this->artist = new Artist();         
    }
    public function run() {
        return $this->render('_createArtist',[
                'model' => $this->artist,
        ]);
    }
}