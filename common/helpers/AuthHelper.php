<?php
namespace common\helpers;

use Yii;

class AuthHelper
{
    const RL_ADMIN = 'rl_admin';
    const RL_KEY_USER = 'rl_key_user';
    const RL_CHIEF = 'rl_chief';
    const RL_VIEW_USER = 'rl_view_user';

    const DICTIONARY_EDIT_ROLES = [self::RL_ADMIN, self::RL_KEY_USER];
    const UNIVERSAL_DICTIONARY_EDIT_ROLES = [self::RL_ADMIN];
    const ORDERS_EDIT_ROLES = [self::RL_ADMIN, self::RL_KEY_USER];
    const LOG_VIEW_ROLES = [self::RL_ADMIN, self::RL_KEY_USER];
    const ORDERS_SELF_VIEW = [self::RL_KEY_USER, self::RL_VIEW_USER];
    const COMPANY_SELF_VIEW = [self::RL_KEY_USER, self::RL_VIEW_USER];
    const EDIT_COMPANY_ID = [self::RL_ADMIN];
    const IS_ADMIN = [self::RL_ADMIN];

    public static function getRoles()
    {
        return [
            'rl_admin' => 'Администратор',
            'rl_key_user' => 'Менеджмент',
//            'rl_chief' => 'Руководитель',
            'rl_view_user' => 'Конечный исполнитель',
        ];
    }

    public static function getUserRoles($user_id)
    {
        $roles = Yii::$app->authManager->getRolesByUser($user_id);
        $roles = array_keys($roles);
        $roles = array_filter($roles, function ($val) {
            return ($val != 'guest');
        });
        $roles = array_values($roles);

        return $roles;
    }

    public static function canEditDictionary()
    {
        $user = Yii::$app->user;
        foreach (self::DICTIONARY_EDIT_ROLES as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function canEditUniversalDictionary()
    {
        $user = Yii::$app->user;
        foreach (self::UNIVERSAL_DICTIONARY_EDIT_ROLES as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function canEditOrders()
    {
        $user = Yii::$app->user;
        foreach (self::ORDERS_EDIT_ROLES as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function canEditCompanyId()
    {
        $user = Yii::$app->user;
        foreach (self::EDIT_COMPANY_ID as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function canViewLogs()
    {
        $user = Yii::$app->user;
        foreach (self::LOG_VIEW_ROLES as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function needUserRestriction() // ограничение на отображение распоряжений
    {
        $user = Yii::$app->user;
        foreach (self::ORDERS_SELF_VIEW as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function needCompanyRestriction()
    {
        $user = Yii::$app->user;
        foreach (self::COMPANY_SELF_VIEW as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    public static function isAdmin()
    {
        $user = Yii::$app->user;
        foreach (self::IS_ADMIN as $role) {
            if ($user->can($role)) return true;
        }
        return false;
    }

    /**
     * Получается список доступных для редактирования ролей для данного пользователя
     * @return array
     */
    public static function getAvailableRoles()
    {
        $user = Yii::$app->user;
        $roles = AuthHelper::getRoles();

        if (!$user->can('rl_admin')) {
            unset($roles['rl_admin']);
        }

        return $roles;
    }
}
