<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>
<!-- ============================
     PREMIUM TOP NAVIGATION BAR
     ============================ -->
<div class="top_nav">
    <nav class="navbar-nav" role="navigation">
        <!-- Left: Menu Toggle & Branding -->
        <div class="nav-left">
            <button class="nav_menu-toggle" id="menu_toggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="brand-logo">
                <span class="brand-text">blck.link</span>
            </div>
        </div>

        <!-- Right: User Menu & Actions -->
        <ul class="nav-right">
            <!-- User Profile Dropdown -->
            <li class="dropdown user-menu">
                <a href="javascript:void(0)" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <img
                        src="<?= Yii::$app->user->identity->img ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Crect width=%2240%22 height=%2240%22 fill=%22%236366f1%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2220%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3E%3C/text%3E%3C/svg%3E' ?>"
                        alt="User Avatar"
                        class="user-avatar"
                    />
                    <span class="user-name"><?= Yii::$app->user->identity->getFullName() ?? 'User' ?></span>
                    <i class="fas fa-chevron-down"></i>
                </a>

                <!-- Dropdown Menu -->
                <ul class="dropdown-menu dropdown-menu-right user-dropdown">
                    <!-- User Header -->
                    <li class="dropdown-header user-header">
                        <img
                            src="<?= Yii::$app->user->identity->img ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect width=%2264%22 height=%2264%22 fill=%22%236366f1%22/%3E%3C/svg%3E' ?>"
                            alt="User"
                            class="img-circle user-img"
                        />
                        <div class="user-info">
                            <p class="user-full-name"><?= Yii::$app->user->identity->getFullName() ?? 'Administrator' ?></p>
                            <p class="user-role">Admin</p>
                        </div>
                    </li>

                    <!-- Divider -->
                    <li class="divider"></li>

                    <!-- Profile Link -->
                    <li class="dropdown-item">
                        <a href="<?= Url::to(['user/view', 'id' => Yii::$app->user->id]) ?>">
                            <i class="fas fa-user"></i>
                            <span>Профіль</span>
                        </a>
                    </li>

                    <!-- Settings Link -->
                    <li class="dropdown-item">
                        <a href="<?= Url::to(['site/settings']) ?>">
                            <i class="fas fa-cog"></i>
                            <span>Параметри</span>
                        </a>
                    </li>

                    <!-- Divider -->
                    <li class="divider"></li>

                    <!-- Logout -->
                    <li class="dropdown-item">
                        <?= Html::a(
                            '<i class="fas fa-sign-out-alt"></i> <span>Вихід</span>',
                            ['/site/logout'],
                            [
                                'data-method' => 'post',
                                'class' => 'logout-link'
                            ]
                        ) ?>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</div>
<!-- /top navigation -->
