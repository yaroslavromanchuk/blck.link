<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */

$this->title = Yii::t('app', 'Редагування: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Контрагенти'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Редагування');
?>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
