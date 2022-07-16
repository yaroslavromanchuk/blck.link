<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */

$this->title = Yii::t('app', 'Редактирование артиста: {name}', [
    'name' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Артисты'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Редактирование');
?>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
