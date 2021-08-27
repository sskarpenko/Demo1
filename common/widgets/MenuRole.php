<?php
namespace common\widgets;

use dmstr\adminlte\widgets\Menu;

class MenuRole extends Menu
{
    public $userAccess = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        // fill current user access array
        $user = \Yii::$app->user;
        if (!$user->isGuest) {
            $auth = \Yii::$app->authManager;

            $userRoles = $auth->getRolesByUser($user->id);
            foreach ($userRoles as $role) {
                if ($role->name == 'guest') continue;
                $this->userAccess[] = $role->name;
            }

            $userPermissions = $auth->getPermissionsByUser($user->id);
            foreach ($userPermissions as $permission) {
                $this->userAccess[] = $permission->name;
            }
        }
        parent::run();
    }

    /**
     * @inheritdoc
     */
    protected function renderItem($item)
    {
        if (isset($item['access'])) {
            // check user access
            if (count(array_intersect($this->userAccess, $item['access'])) == 0) {
                return '';
            }
        }
        return parent::renderItem($item);
    }
}