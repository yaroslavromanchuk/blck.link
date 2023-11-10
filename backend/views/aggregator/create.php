<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Aggregator */

$this->title = Yii::t('app', 'Create Aggregator');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Aggregators'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="aggregator-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
