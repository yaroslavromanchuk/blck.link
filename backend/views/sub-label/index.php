<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\SubLabelSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Sub Labels');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sub-label-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Sub Label'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
       // 'filterModel' => $searchModel,
        'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
            'id',
            'name',
            'percentage',
            'percentage_distribution',
            //'url:url',
            //'description',
            //'logo',
            'active:boolean',
            'date_added',
            //'last_update',

            [
                'class' => 'yii\grid\ActionColumn',
                 'template' => '{view} {update}', //{delete}
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
