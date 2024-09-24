<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\OwnershipType */

$this->title = Yii::t('app', 'Create Ownership Type');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ownership Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ownership-type-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
