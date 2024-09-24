<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Ownership */

$this->title = Yii::t('app', 'Create Ownership');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ownerships'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ownership-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
