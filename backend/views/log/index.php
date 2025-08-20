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
</div>