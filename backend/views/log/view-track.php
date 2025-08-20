<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\LogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Аналитика');
$this->params['breadcrumbs'][] = $this->title;
?>
    <div class="log-index">
        <p>
            <?php // Html::a(Yii::t('app', 'Create Log'), ['create'], ['class' => 'btn btn-success']) ?>
        </p>

        <?php //Pjax::begin(); ?>
        <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

        <?php /*/ GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
           // 'track',
            [
                        'attribute' => 'track',
                        'value' => function($data){ return $data->tracks->name;},
            ],
            'type',
            'name',
            'referal',
            'ip',
            'country',
            'data',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); */?>

        <?php //Pjax::end(); ?>

        <div class="row">
            <div class=" col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>World Map</h2>
                        <!--  <ul class="nav navbar-right panel_toolbox">
                           <li><a href="#"><i class="fa fa-chevron-up"></i></a>
                           </li>
                          <li class="dropdown">
                             <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                             <ul class="dropdown-menu" role="menu">
                               <li><a href="#">Settings 1</a>
                               </li>
                               <li><a href="#">Settings 2</a>
                               </li>
                             </ul>
                           </li>
                           <li><a href="#"><i class="fa fa-close"></i></a>
                           </li>
                         </ul>-->
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">

                        <div id="echart_world_map" style="height:400px;"></div>

                    </div>
                </div>
            </div>

        </div>

    </div>

<?php


$js = <<<JS
 var myChart = echarts.init(document.getElementById('echart_world_map'));
    myChart.setOption({
        zoomOnScroll: false,
      title: {
        text: 'Просмотры по странам',
        subtext: 'тут отображаются только просмотры релиза',
        x: 'center',
        y: 'top'
      },
      tooltip: {
        trigger: 'item',
        formatter: function(params) {
          //var value = (params.value + '').split('.');
         // value = value[0].replace(/(\d{1,3})(?=(?:\d{3})+(?!\d))/g, '$1,') + '.' + value[1];
          return params.seriesName + '<br/>' + params.name + ' : ' + params.value;
        }
      },
      
      dataRange: {
        min: 0,
        max: $max,
        text: ['High', 'Low'],
        realtime: true,
        calculable: true,
        color: ['#087E65', '#26B99A', '#CBEAE3']
      },
      series: [{
        name: 'Страна',
        type: 'map',
        mapType: 'world',
        roam: true,
        mapLocation: {
          y: 50
        },
        itemStyle: {
          emphasis: {
            label: {
              show: true
            }
          }
        },
        data: $country
      }]
    });
        
      
JS;
$this->registerJs($js, \yii\web\View::POS_READY);