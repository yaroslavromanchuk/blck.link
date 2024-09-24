<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceType */

$this->title = Yii::t('app', 'Update Invoice Type: {name}', [
    'name' => $model->invoice_type_id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Invoice Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->invoice_type_id, 'url' => ['view', 'id' => $model->invoice_type_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="invoice-type-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
