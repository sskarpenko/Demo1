<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\helpers\MailHelper;
use DateTime;
use Exception;
use thamtech\uuid\helpers\UuidHelper;
use Throwable;

class MovementModel extends Model
{
    public $type_card;
    public $stabnum;
    public $firstname;
    public $secondname;
    public $thirdname;
    public $begin;
    public $staffpos_id;
    public $card_id;
    public $company_id;
    public $email;

    const NEW_CARD = 1;
    const EXISTING_CARD = 2;

    const SCENARIO_CREATE = 'scenario_create';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['secondname', 'stabnum'], 'required',
                'when' => function($model) {
                    if ($model->type_card == self::NEW_CARD) return true;
                    return false;
                },
                'whenClient' => "function (attribute, value) {
                var type_card = $('#movementmodel-type_card :checked').val();
                return type_card == 1;
                }"
            ],
            [['card_id'], 'required',
                'when' => function($model) {
                    if ($model->type_card == self::EXISTING_CARD) return true;
                    return false;
                },
                'whenClient' => "function (attribute, value) {
                var type_card = $('#movementmodel-type_card :checked').val();
                return type_card == 2;
                }"
            ],
            [['staffpos_id', 'type_card', 'begin'], 'required'],
            [['card_id', 'staffpos_id'], 'default', 'value' => null],
            [['card_id', 'staffpos_id'], 'integer'],
            [['begin'], 'safe'],

            [['staffpos_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staffpos::className(), 'targetAttribute' => ['staffpos_id' => 'id']],
            ['type_card', 'in', 'range' => [self::NEW_CARD, self::EXISTING_CARD]],

            [['card_id'], 'exist', 'skipOnError' => true, 'targetClass' => Card::className(), 'targetAttribute' => ['card_id' => 'id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['stabnum'], 'match', 'pattern' => '/^\d{3,8}$/','enableClientValidation'=> false, 'message'=> Yii::t('app', 'Цифровое знаечение, от трех до восьми символов')],
            [['firstname', 'secondname', 'thirdname'], 'string', 'max' => 64],
            [['email'], 'email'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['card_id', 'staffpos_id', 'begin', 'stabnum', 'firstname', 'secondname', 'thirdname', 'type_card', 'company_id', 'email'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'card_id' => 'Сотрудник',
            'company_id' => 'Компания',
            'staffpos_id' => 'Штатная единица',
            'begin' => 'Дата начала',
            'stabnum' => 'Табельный номер',
            'firstname' => 'Имя',
            'secondname' => 'Фамилия',
            'thirdname' => 'Отчество',
            'type_card' => 'Тип карточки',
            'email' => 'Электронная почта',
        ];
    }

    public static function getTypeCardList()
    {
        return [
            '1' => Yii::t('app', 'Новый сотрудник'),
            '2' => Yii::t('app', 'Существующий сотрудник'),
        ];
    }

    public function register()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            switch ($this->type_card) {
                case self::NEW_CARD:
                    $modelCard = new Card();
                    $modelCard->firstname = $this->firstname;
                    $modelCard->secondname = $this->secondname;
                    $modelCard->thirdname = $this->thirdname;
                    $modelCard->stabnum = $this->stabnum;
                    $modelCard->company_id = $this->company_id;

                    if ($modelCard->save()) {
                        $modelCard->refresh();
                    } else {
                        $this->addErrors($this->errors);
                        $transaction->rollBack();
                        return false;
                    }
                    $this->card_id = $modelCard->id;

                    // Если сотрудника не существует среди учётных записей, то зарегистрируем его
                    if (!User::findByUsername($this->email)) {
                        $user = new User([
                            'username' => $this->email,
                            'email' => $this->email,
                            'status' => User::STATUS_ACTIVE,
                        ]);
                        $user->generateAuthKey();
                        $user->generateAccessToken();

                        if ($user->save()) {
                            $user->refresh();
                        } else {
                            $this->addErrors($user->errors);
                            $transaction->rollBack();
                            return false;
                        }
                        $auth = Yii::$app->authManager;
                        $role = $auth->getRole('rl_key_user');
                        $auth->assign($role, $user->id);

                        $userProfile = new UserProfile([
                            'user_id' => $user->id,
                            'first_name' => $this->firstname,
                            'second_name' => $this->secondname,
                            'third_name' => $this->thirdname,
                            'phone' => '',
                            'card_id' => $this->card_id,
                            'sign_chief' => false,
                            'uuid' => UuidHelper::uuid(),
                            'company_id' => $this->company_id,
                        ]);

                        if (!$userProfile->save()) {
                            $this->addErrors($user->errors);
                            $transaction->rollBack();
                            return false;
                        }

                        if (!MailHelper::checkAvailableMailServer()) {
                            $this->addErrors($user->errors);
                            $transaction->rollBack();
                            return false;
                        } else {
                            $today = new DateTime();
                            $today->modify('+ 7 day');

                            // Отправим пользователю письмо, что его зарегистрировали в системе
                            $invite = new UserInvite();
                            $invite->email = $this->email;
                            $invite->active_till = $today->format('Y-m-d H:i:s');
                            $invite->generateEmailLinkInvite();
                            $invite->type_operation = UserInvite::TYPE_INVITE;

                            if (!$invite->save()) {
                                $this->addErrors($user->errors);
                                $transaction->rollBack();
                                return false;
                            }

                            if (!$invite->sendMail()) {
                                $this->addErrors($user->errors);
                                $transaction->rollBack();
                                return false;
                            }

                            $fullName = $this->secondname . ' ' . $this->firstname . ' ' . $this->thirdname;
                            Yii::$app->session->setFlash('success', 'Приглашение пользователю ' . $this->email . ' (' . trim($fullName) . ') для работы в системе успешно отправлено');
                        }
                    }

                    $modelMovement = new Movement();
                    $modelMovement->card_id = $this->card_id;
                    $modelMovement->staffpos_id = $this->staffpos_id;
                    $modelMovement->begin = $this->begin;

                    if (!$modelMovement->save()) {
                        $this->addErrors($modelMovement->errors);
                        $transaction->rollBack();
                        return false;
                    }
                    break;

                case self::EXISTING_CARD:
                    $modelMovement = new Movement();
                    $modelMovement->card_id = $this->card_id;
                    $modelMovement->staffpos_id = $this->staffpos_id;
                    $modelMovement->begin = $this->begin;

                    if (!$modelMovement->save()) {
                        $this->addErrors($modelMovement->errors);
                        $transaction->rollBack();
                        return false;
                    }
                    break;
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
}
