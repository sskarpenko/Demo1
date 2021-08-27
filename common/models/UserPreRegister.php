<?php
namespace common\models;

use thamtech\uuid\helpers\UuidHelper;
use Yii;
use yii\db\ActiveRecord;
use common\helpers\AuthHelper;
use common\helpers\MailHelper;
use DateTime;
use Exception;
use Throwable;

/**
 * This is the model class for table "user_pre_register".
 *
 * @property int $id
 * @property string $email
 * @property string|null $email_code
 * @property bool|null $email_code_valid
 * @property string|null $active_till
 * @property int $type_operation
 */
class UserPreRegister extends ActiveRecord
{
    const SCENARIO_REGISTER = 'scenario_register';
    const TYPE_REGISTER = 10;

    public $tmp_email_code;

    public $stabnum;
    public $first_name;
    public $second_name;
    public $third_name;
//    public $phone;
    public $password;
    public $password_repeat;
    public $username;
    public $company_name;
    public $uuid;
    public $demo_data;

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
            [['email'], 'validateUniqueEmail'],
            [['email_code'], 'default', 'value' => null],
            [['email_code', 'tmp_email_code'], 'string'],
            [['email_code_valid'], 'boolean'],
            [['active_till'], 'safe'],

            [['first_name', 'second_name', 'third_name', 'company_name'], 'string', 'max' => 255],
            [['stabnum'], 'match', 'pattern' => '/^\d{3,8}$/', 'enableClientValidation' => false, 'message' => Yii::t('app', 'Цифровое знаечение, от трёх до восьми символов')],
            [['second_name', 'stabnum', 'company_name'], 'required', 'on' => [self::SCENARIO_REGISTER]],

            [['password_repeat', 'password'], 'string', 'min' => 6],
            [['password_repeat', 'password'], 'required', 'on' => [self::SCENARIO_REGISTER]],
            ['password_repeat', 'validatePasswordRepeat', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => [self::SCENARIO_REGISTER]],
            [['demo_data'], 'boolean'],
            [['type_operation'], 'integer'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['first_name', 'second_name', 'third_name', 'stabnum', 'company_name', 'password', 'password_repeat', 'demo_data'];
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
            'email_code_valid' => 'Email Code Valid',
            'active_till' => 'Active Till',

            'phone' => 'Телефон',
            'first_name' => 'Имя',
            'second_name' => 'Фамилия',
            'third_name' => 'Отчество',
            'password' => 'Пароль',
            'password_repeat' => 'Повтор пароля',
            'username' => 'Имя пользователя',
            'company_name' => 'Наименование пространства',
            'stabnum' => 'Идентификатор/Табельный номер',
            'type_operation' => 'Тип операции',
        ];
    }

    public function init()
    {
        parent::init();
       // $this->on(self::EVENT_AFTER_INSERT, [$this, 'sendMail']);
    }

    public function generateEmailCode()
    {
        $this->email_code = (string)rand(100000, 999999);
    }

    public function validateUniqueEmail($attribute, $params, $validator)
    {
        $email = $this->$attribute;
        $duplicateUser = User::find()->where(['email' => $email])->one();

        if ($duplicateUser) {
            $this->addError($attribute, Yii::t('app', 'Почта зарегистрирована для другого пользователя'));
        }
    }

    public function validatePasswordRepeat($attribute, $params, $validator)
    {
        if ($this->password != $this->{$attribute}) {
            $this->addError($attribute, Yii::t('app', 'Не верно указан повтор пароля'));
        }
    }

    public function validateUniqueUsername($attribute, $params, $validator)
    {
        $user = $this->$attribute;
        $duplicateUser = User::findOne(['username' => $user]);

        if ($duplicateUser) {
            $this->addError($attribute, Yii::t('app', 'Имя пользователя уже задействовано для другого пользователя'));
        }
    }

    public function validateUserPhone($attribute, $params, $validator)
    {
        $phone = $this->{$attribute};
        $user = User::findByPhone($phone);
        if ($user) {
            if ($this->id) {
                if ($this->id != $user->id) {
                    $this->addError($attribute, Yii::t('app', 'Пользователь с таким номером телефона уже зарегистрирован'));
                }
            } else {
                $this->addError($attribute, Yii::t('app', 'Пользователь с таким номером телефона уже зарегистрирован'));
            }
        }
    }

    public function register()
    {
        $today = new DateTime();
        $today->modify('+ 14 day');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->username = $this->email;
            $user->email = $this->email;
            $user->status = User::STATUS_ACTIVE;
            $user->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            $user->generateAuthKey();
            $user->generateAccessToken();

            if ($user->save()) {
                $user->refresh();
            } else {
                $this->addErrors($user->errors);
                $transaction->rollBack();
                return false;
            }
            // add role
            $auth = Yii::$app->authManager;
            $role = $auth->getRole(AuthHelper::RL_KEY_USER);
            $auth->assign($role, $user->id);

            $company = new Company();
            $company->short_name = $this->company_name;
            $company->sign_demo = $this->demo_data;
            $company->active_till = $today->format('Y-m-d H:i:s');
            $company->creator_id = $user->id;
            if ($company->save()) {
                $company->refresh();
            } else {
                $this->addErrors($company->errors);
                $transaction->rollBack();
                return false;
            }

            $card = new Card();
            $card->stabnum = $this->stabnum;
            $card->firstname = $this->first_name;
            $card->secondname = $this->second_name;
            $card->thirdname = $this->third_name;
            $card->company_id = $company->id;
            $card->pre_creator_id = $user->id;
            if ($card->save()) {
                $card->refresh();
            } else {
                $this->addErrors($card->errors);
                $transaction->rollBack();
                return false;
            }

            $userProfile = new UserProfile([
                'user_id' => $user->id,
                'first_name' => $this->first_name,
                'second_name' => $this->second_name,
                'third_name' => $this->third_name,
                'card_id' => $card->id,
                'sign_chief' => false,
                'uuid' => UuidHelper::uuid(),
                'company_id' => $company->id,
            ]);

            if (!$userProfile->save()) {
                $this->addErrors($user->errors);
                $transaction->rollBack();
                return false;
            }

            if ($this->demo_data) {
                $this->addDemoData($company->id, $card->id, $user->id);
            }
            $this->sendMail(MailHelper::HELLO);

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

    public function sendMail($type_action)
    {
        return MailHelper::preRegСonfirmationCode($this, $type_action);
    }

    /**
     * Функция добавляет в систему демоданные для зарегистрировавшегося пользователя
     * @param $company_id
     * @param $main_card_id
     */
    public function addDemoData($company_id, $main_card_id, $creator_id)
    {
        $profession_id_1 = 14;
        $profession_id_2 = 11;
        $profession_id_3 = 13;
        $profession_id_4 = 12;

        $dep = new Department();
        $department_parent_id = $dep->addDepartment('', '200', 'Команда проекта', 'Команда проекта', $company_id, date('Y-m-d'), '', $creator_id);

        // Добавим отдел руководства, создадим штатное расписание отдела и наймём сотрудника
        $dep_current = new Department();
        $department_current_parent_id = $dep_current->addDepartment($department_parent_id, '200-1', 'Руководство', 'Руководство', $company_id, '', '', $creator_id);

        $staffpos_parent = new Staffpos();
        $staffpos_parent_id = $staffpos_parent->addStaffPos('', $department_current_parent_id, $profession_id_1, '', '', $creator_id);

        $movement_current = new Movement();
        $movement_current->addMovement($main_card_id, $staffpos_parent_id, date('Y-m-d'), '', $creator_id);

        // Добавим отдел дизайнеров, создадим штатное расписание отдела и наймём сотрудника
        $dep_current = new Department();
        $department_current_parent_id = $dep_current->addDepartment($department_parent_id, '200-2', 'Дизайнеры', 'Дизайнеры', $company_id, '', '', $creator_id);

        $staffpos_parent = new Staffpos();
        $staffpos_current_id = $staffpos_parent->addStaffPos($staffpos_parent_id, $department_current_parent_id, $profession_id_2, '', '', $creator_id);

        $card_current = new Card();
        $card_current_id = $card_current->addCard('100', 'Первый', 'Сотрудник', 'Дизайнер', $company_id, $creator_id);

        $movement_current = new Movement();
        $movement_current->addMovement($card_current_id, $staffpos_current_id, date('Y-m-d'), '', $creator_id);

        // Добавим отдел программистов, создадим штатное расписание отдела и наймём двух сотрудников
        $dep_current = new Department();
        $department_current_parent_id = $dep_current->addDepartment($department_parent_id, '200-3', 'Программисты', 'Программисты', $company_id, '', '', $creator_id);

        $staffpos_parent = new Staffpos();
        $staffpos_current_id = $staffpos_parent->addStaffPos($staffpos_parent_id, $department_current_parent_id, $profession_id_3, '', '', $creator_id);

        $card_current = new Card();
        $card_current_id = $card_current->addCard('200', 'Второй', 'Сотрудник', 'Программист', $company_id, $creator_id);

        $movement_current = new Movement();
        $movement_current->addMovement($card_current_id, $staffpos_current_id, date('Y-m-d'), '', $creator_id);

        $staffpos_parent = new Staffpos();
        $staffpos_current_id = $staffpos_parent->addStaffPos($staffpos_parent_id, $department_current_parent_id, $profession_id_3, '', '', $creator_id);

        $card_current = new Card();
        $card_current_id = $card_current->addCard('300', 'Третий', 'Сотрудник', 'Программист', $company_id, $creator_id);

        $movement_current = new Movement();
        $movement_current->addMovement($card_current_id, $staffpos_current_id, date('Y-m-d'), '', $creator_id);

        // Добавим отдел тестировщиков, создадим штатное расписание отдела и наймём сотрудника
        $dep_current = new Department();
        $department_current_parent_id = $dep_current->addDepartment($department_parent_id, '200-4', 'Тестировщики', 'Тестировщики', $company_id, '', '', $creator_id);

        $staffpos_parent = new Staffpos();
        $staffpos_current_id = $staffpos_parent->addStaffPos($staffpos_parent_id, $department_current_parent_id, $profession_id_4, '', '', $creator_id);

        $card_current = new Card();
        $card_current_id = $card_current->addCard('400', 'Четвёртый', 'Сотрудник', 'Тестировщик', $company_id, $creator_id);

        $movement_current = new Movement();
        $movement_current->addMovement($card_current_id, $staffpos_current_id, date('Y-m-d'), '', $creator_id);
    }
}
