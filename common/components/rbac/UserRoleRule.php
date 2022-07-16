<?php
namespace common\components\rbac;

use Yii;
use yii\rbac\Rule;
use yii\helpers\ArrayHelper;
use common\models\User;

/*
 * Создаем класс правил.
 * Сравнивается роль текущего пользователя с ролью, которая необходима для получения доступа
 */
class UserRoleRule extends Rule
{
    public $name = 'userRole'; //название данного правила
    /*
     * $user - id текущего пользователя
     * $item - объект роли которую проверяем у текущего пользователя
     * $params - параметры, которые можно передать для проведеня проверки в данный класс
     */
    public function execute($user, $item, $params)
    {
        //Получаем объект текущего пользователя из базы
        $user = ArrayHelper::getValue($params, 'user', User::findOne($user));

        if ($user) {
            $role = $user->role;

            if ($item->name === 'admin') {
                return $role == User::ROLE_ADMIN;
            }
            elseif ($item->name === 'moder') {
                return $role == User::ROLE_ADMIN || $role == User::ROLE_MODER;
            }
            elseif ($item->name === 'manager') {
                return $role == User::ROLE_ADMIN || $role == User::ROLE_MODER|| $role == User::ROLE_MANAGER;
            }elseif ($item->name === 'user') {
                return $role == User::ROLE_ADMIN || $role == User::ROLE_MODER || $role == User::ROLE_MANAGER ||  $role == User::ROLE_USER;
            }
        }

        return false;
    }
}