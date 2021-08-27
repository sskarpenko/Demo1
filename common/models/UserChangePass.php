<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use common\helpers\MailHelper;
use Exception;
use Throwable;

/**
 * This is the model class for table "user_pre_register".
 *
 * @property int $id
 * @property string $email
 * @property int|null $email_code
 * @property bool|null $email_code_valid
 * @property string|null $active_till
 * @property int $type_operation
 */
class UserChangePass extends ActiveRecord
{
    const SCENARIO_CHANGE_PASS = 'scenario_change_pass';
    const TYPE_CHANGE_PASSWORD = 20;

    public $tmp_email_code;
    public $password;
    public $password_repeat;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_pre_register';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email'], 'required'],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'validateExistingEmail'],
            [['email_code'], 'default', 'value' => null],
            [['email_code', 'tmp_email_code'], 'integer'],
            [['email_code_valid'], 'boolean'],
            [['active_till'], 'safe'],
            [['type_operation'], 'integer'],

            [['password_repeat', 'password'], 'string', 'min' => 6],
            [['password_repeat', 'password'], 'required', 'on' => [self::SCENARIO_CHANGE_PASS]],
            ['password_repeat', 'validatePasswordRepeat', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => [self::SCENARIO_CHANGE_PASS]],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CHANGE_PASS] = ['password', 'password_repeat'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'E-mail пользователя',
            'email_code' => 'Код подтверждения',
            'tmp_email_code' => 'Код подтверждения',
            'password' => 'Пароль',
            'password_repeat' => 'Повтор пароля',
            'type_operation' => 'Тип операции',
        ];
    }

    public function init()
    {
        parent::init();
        //$this->on(self::EVENT_AFTER_INSERT, [$this, 'sendMail']);
    }

    public function generateEmailCode()
    {
        $this->email_code = rand(100000, 999999);
    }

    public function validateExistingEmail($attribute, $params, $validator)
    {
        $email = $this->$attribute;
        $existingUser = User::find()->where(['email' => $email])->one();

        if (!$existingUser) {
            $this->addError($attribute, Yii::t('app', 'Не верно указана почта'));
        }
    }

    public function validatePasswordRepeat($attribute, $params, $validator)
    {
        if ($this->password != $this->{$attribute}) {
            $this->addError($attribute, Yii::t('app', 'Не верно указан повтор пароля'));
        }
    }

    public function changePass()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = User::find()->where(['email' => $this->email, 'status' => User::STATUS_ACTIVE])->one();
            if ($user) {
                $user->setPassword($this->password);
                $user->generateAccessToken();
                if (!$user->save()) {
                    $this->addErrors($user->errors);
                    $transaction->rollBack();
                    return false;
                }
            } else {
                $transaction->rollBack();
                return false;
            }

            $transaction->commit();

        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    public function sendMail()
    {
        switch ($this->type_operation) {
            case UserChangePass::TYPE_CHANGE_PASSWORD:
                $type_action = MailHelper::CHANGE_PASSWORD;
                break;

            case UserInvite::TYPE_INVITE:
                $type_action = MailHelper::HELLO;
                break;
        }

        return MailHelper::preRegСonfirmationCode($this, $type_action);
    }
}