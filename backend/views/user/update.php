<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model frontend\models\User */

$this->title = Yii::t('app', 'Редактирование профиля: {name}', [
    'name' => $model->lastName.' '.$model->firstName,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Пользователи'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->lastName.' '.$model->firstName, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Редактирование');
?>
<div class="user-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    <div style="color:#999;margin:1em 0">
                    Если вы забыли свой пароль, вы можете <?= Html::a('восстановить пароль', ['site/request-password-reset']) ?>.
                </div>

</div>
