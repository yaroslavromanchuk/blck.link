<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */

$this->title = Yii::t('app', 'Создание артиста');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Артисты'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="artist-create">
    <?= $this->render('_form_create', [
        'model' => $model,
    ]) ?>

</div>
