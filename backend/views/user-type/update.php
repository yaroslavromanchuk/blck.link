<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\UserType */

$this->title = Yii::t('app', 'Update User Type: {name}', [
    'name' => $model->user_type_id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->user_type_id, 'url' => ['view', 'id' => $model->user_type_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="user-type-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
