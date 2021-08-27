<?php
namespace api\modules\v1\models;

use yii\base\Model;
use common\models\Event;
use common\models\User;

class LoginForm extends Model
{
    public $username;
    public $password;

    private $_user;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Табельный номер',
            'password' => 'Пароль',

        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Пользователь или пароль введены неверно.');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return $this->_user['access_token'];
        }
        return null;
    }


    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

    public function registerLoginEvent()
    {
        $event = new Event([
            'event_type_id' => Event::EVENT_LOGIN,
            'user_id' => $this->_user['id'],
        ]);
        $event->save();
    }
}
