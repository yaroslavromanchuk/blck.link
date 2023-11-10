<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Release */

$this->title = Yii::t('app', 'Update Release: {name}', [
    'name' => $model->release_id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Releases'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->release_id, 'url' => ['view', 'id' => $model->release_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="release-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
