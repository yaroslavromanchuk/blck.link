<?php 
$mybarChartLink = [];

foreach ($link as $l){
    $mybarChart['labels'][] = $l['name'];
    $mybarChart['data'][] = $l['ctn'];
}
$l_l = '"';
$l_l .= implode('", "', $mybarChart['labels']);
$l_l.= '"';
$d_l = implode(", ", $mybarChart['data']);

$mybarChartServise = [];

foreach ($servise as $s){
    $mybarChartServise['labels'][] = $s['name'];
    $mybarChartServise['data'][] = $s['ctn'];
}
$l_s = '"';
$l_s .= implode('", "', $mybarChartServise['labels']);
$l_s.= '"';
$d_s = implode(", ", $mybarChartServise['data']);


//use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Track */

$this->title = Yii::t('app', 'Аналитика Релиза: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Релизы'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Аналитика');
?>
<div class="track-analitiks">
<div class="row">
    <div class="col-md-6 col-sm-6 col-xs-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2>Переходы по сервисам<small></small></h2>
                  <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
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
                    <!--<li><a class="close-link"><i class="fa fa-close"></i></a>
                    </li>-->
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <canvas id="mybarChartServise"></canvas>
                </div>
              </div>
            </div>
    <div class="col-md-6 col-sm-6 col-xs-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2>Переходы по ссылкам артиста в подвале<small></small></h2>
                  <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                   <li class="dropdown">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                      <ul class="dropdown-menu" role="menu">
                          <?php
                          foreach ($mybarChart['labels'] as $l){
                              echo '<li><a href="#">'.$l.'</a></li>';
                          }
                          ?>
                          <li><a href="#">All</a></li>
                        </li>
                      </ul>
                    </li>
                    <!--<li><a class="close-link"><i class="fa fa-close"></i></a>
                    </li>-->
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <canvas id="mybarChartLink"></canvas>
                </div>
              </div>
            </div>
</div>
    </div>
<?php
echo '<pre>';
//print_r($link);



echo '</pre>';
?>
<?php
$js = <<<JS
    Chart.defaults.global.legend = {
      enabled: false
    };
        // Bar chart
    var ctx = document.getElementById("mybarChartServise");
    var mybarChartServise = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [$l_s],
        datasets: [{
          label: '# Click',
          backgroundColor: "#26B99A",
          data: [$d_s]
        }]
      },
      options: {
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true
            }
          }]
        }
      }
    });
        var ctx = document.getElementById("mybarChartLink");
    var mybarChartLink = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [$l_l],
        datasets: [{
          label: '# Click',
          backgroundColor: "#03586A",
          data: [$d_l]
        }]
      },
      options: {
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true
            }
          }]
        }
      }
    });
JS;
    $this->registerJs($js, \yii\web\View::POS_READY);

