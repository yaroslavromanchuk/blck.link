<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>
<!-- top navigation -->
<div class="top_nav">
    <div class="nav_menu">
        <nav class="" role="navigation">
            <div class="nav toggle">
                <a id="menu_toggle" href="javascript:void(0)"><i class="fa fa-bars"></i></a>
            </div>

            <ul class="nav navbar-nav navbar-right">
                <li class="">
                    <a href="javascript:void(0)" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <img src="<?= Yii::$app->user->identity->img ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Crect width=%2240%22 height=%2240%22 fill=%22%236366f1%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2220%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3E%3C/text%3E%3C/svg%3E' ?>" alt="User Avatar" class="img-circle">
                        <span class="fa fa-angle-down"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-usermenu pull-right">
                        <li class="user-header" style="text-align: center; padding: 15px 10px;">
                            <img style="margin: 15px auto 10px; display: block;" src="<?= Yii::$app->user->identity->img ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect width=%2264%22 height=%2264%22 fill=%22%236366f1%22/%3E%3C/svg%3E' ?>" class="img-circle" alt="User Image"/>
                            <p style="margin: 0; color: #111827; font-weight: 600;">
                                <?= Yii::$app->user->identity != null ? Yii::$app->user->identity->getFullName() : '' ?>
                                <small style="display: block; color: #6366f1; margin-top: 4px;">Admin</small>
                            </p>
                        </li>
                        <li><a href="<?= Url::to(['user/view', 'id' => Yii::$app->user->id]) ?>">Профіль</a></li>
                        <li>
                            <?= Html::a(
                                '<i class="fa fa-sign-out pull-right"></i> Вихід',
                                ['/site/logout'],
                                ['data-method' => 'post', 'class' => '']
                            ) ?>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</div>
<!-- /top navigation -->
