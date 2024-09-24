<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceType */

$this->title = Yii::t('app', 'Create Invoice Type');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Invoice Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-type-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
