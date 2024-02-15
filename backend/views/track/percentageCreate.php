<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Percentage */
/* @var $artist array */


$this->title = Yii::t('app', 'Додавання відсотку до треку');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Треки'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="track-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_formPercentage', [
        'model' => $model,
        'artist' => $artist,
    ]) ?>

</div>