<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

class DemoController extends Controller
{
public function actionIndex() {
    echo 'Demmo';
    return ExitCode::OK;
}
}