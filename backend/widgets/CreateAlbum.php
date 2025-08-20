<?php

namespace backend\widgets;

use backend\models\Albums;
use yii\base\Widget;

class CreateAlbum extends Widget {

    public Albums $album;
    public function init() {
        $this->album = new Albums();
    }
    public function run() {
        return $this->render('_createAlbum',[
            'model' => $this->album
        ]);
    }
}