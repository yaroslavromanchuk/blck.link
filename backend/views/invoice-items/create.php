<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceItems */

$this->title = Yii::t('app', 'Create Invoice Items');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Invoice Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-items-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
