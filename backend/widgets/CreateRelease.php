<?php

namespace backend\widgets;

use yii\base\Widget;
use backend\models\Release;

class CreateRelease extends Widget {

    public Release $release;
    public function init() {
        $this->release = new Release();
    }
    public function run() {
        return $this->render('_createRelease',[
            'model' => $this->release
        ]);
    }
}