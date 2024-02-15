<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\SubLabel */

$this->title = Yii::t('app', 'Create Sub Label');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sub Labels'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sub-label-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
