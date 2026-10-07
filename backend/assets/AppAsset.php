<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
        'css/site.css',
        'css/custom.css',
        'css/premium-enhancements.css',
        'css/premium-animations.css'
    ];

    public $js = [
        'js/chartjs/chart.min.js',
        'js/echart/echarts-all.js',
        'js/premium.js',
        'js/ui-animations.js'
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yiister\gentelella\assets\Asset',
    ];
}

