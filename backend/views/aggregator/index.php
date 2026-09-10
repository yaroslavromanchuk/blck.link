<?php

use backend\models\OwnershipType;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\AggregatorSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Агрегатори');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Додати агрегатора'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Завантажити звіт'), ['upload-report'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

           // 'aggregator_id',
            'name',
            [
                'attribute' => 'service_type_id',
                'value' => function($data) {
                    return $data->serviceType->name;
                },
            ],
            //'ownership_type',
            [
                'attribute' => 'ownership_type',
                'value' => function($data) {
                    return $data->ownershipType->name;
                },
            ],
            [
                'attribute' => 'ownership_types',
                'value' => function($data) {
        
        $ids = array_column(
            $data->aggregatorToOwnershipTypes,
            'ownership_type_id'
        );
                    $d = OwnershipType::find()
                        ->select('name')
                        ->where(['in', 'id', $ids])
                        ->column();
                    return implode(', ', $d);
                }
            ],
            //'description',
            [
                    'attribute' => 'type_use_id',
                'value' => function($data) {
                        return $data->type->name;
                }
            ],
            [
                'attribute' => 'service_id',
                'value' => function($data) {
                    return $data->service->name;
                }
            ],
            [
                'attribute' => 'currency_id',
                'value' => function($data) {
                    return $data->currency->getName();
                },
            ],
            //'date_add:date',
           // 'last_update:date',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
