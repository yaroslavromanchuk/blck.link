<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [

       /* 'css/font-awesome.min.css',
        'css/icheck/flat/green.css',
        'css/animate.min.css',*/
        //
        //'css/bootstrap.min_4.3.css',
        'css/site.css',
        'css/custom.css'
        
        
      //  'css/AdminLTE.min.css',
      //  'css/skin-black.min.css',
    ];
     public $js = [
     'js/chartjs/chart.min.js',
     'js/echart/echarts-all.js',
        // 'js/adminlte.min.js'
        /* 'js/moment/moment.min.js',
         'js/chartjs/chart.min.js',
         'js/nicescroll/jquery.nicescroll.min.js',
         'js/icheck/icheck.min.js',
         'js/wizard/jquery.smartWizard.js',
         'js/custom.js',
         'js/pace/pace.min.js',
         'js/validator/validator.js'*/
         
    ];
    public $depends = [
        'yii\web\YiiAsset',
        //'yii\bootstrap\BootstrapAsset',
        'yiister\gentelella\assets\Asset',
        
    ];
}
