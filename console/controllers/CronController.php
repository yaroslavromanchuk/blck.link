<?php

namespace app\controllers;

use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class CronController extends Controller
{
    /**
     * List cron.
     * This is used for shell completion.
     * @since 2.0.11
     */
    public function actionIndex() {
        echo User::findOne(1)->username;
        echo '+++++';
        return ExitCode::OK;
    }
}