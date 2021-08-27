<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use common\helpers\AuthHelper;
use common\helpers\MailHelper;
use DateTime;
use Exception;
use thamtech\uuid\helpers\UuidHelper;
use Throwable;

/**
 * This is the model class for table "card".
 *
 * @property int $id
 * @property string|null $stabnum
 * @property string|null $firstname
 * @property string|null $secondname
 * @property string|null $thirdname
 * @property int|null $company_id
 *
 * @property Movement[] $movements
 * @property UserProfile[] $userProfiles
 */

class Card extends ActiveRecord
{
    const EVENT_LOG_CARD = 1;

    const LOG_TEXT_ATTRS = [
        'stabnum', 'firstname', 'secondname', 'thirdname', 'email', 'role'
    ];

    public $email;
    public $pre_creator_id;
    public $role;
    public $user_id;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'card';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['stabnum', 'secondname'], 'required'],
            [['stabnum'], 'match', 'pattern' => '/^\d{3,8}$/', 'enableClientValidation'=> false, 'message'=> Yii::t('app', 'Цифровое значение, от 3 до 8 символов')],
            [['firstname', 'secondname', 'thirdname'], 'string', 'max' => 64],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['email'], 'email'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stabnum' => 'Табельный номер',
            'firstname' => 'Имя',
            'secondname' => 'Фамилия',
            'thirdname' => 'Отчество',
            'company_id' => 'Компания',
            'email' => 'Электронная почта',
            'role' => 'Роль пользователя',
        ];
    }

    /**
     * Gets query for [[Company]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * Gets query for [[Movements]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovements()
    {
        return $this->hasMany(Movement::className(), ['card_id' => 'id']);
    }

    /**
     * Gets query for [[UserProfiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserProfiles()
    {
        return $this->hasMany(UserProfile::className(), ['card_id' => 'id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $this->refresh();
            $this->logChanges(Log::EVENT_INSERT, $changedAttributes);
        } else {
            $this->logChanges(Log::EVENT_UPDATE, $changedAttributes);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->logChanges(Log::EVENT_DELETE);
    }

    protected function logChanges($event, $changedAttributes = [])
    {
        $comments = [];

        switch ($event) {
            case Log::EVENT_INSERT:
                $text = '"%1$s" = "%3$s"; ';
                $comments[] = 'Запись создана. ';
                $comments = $this->getLogChanges($changedAttributes, $text, $comments);
                break;

            case Log::EVENT_UPDATE:
                $text = 'Атрибут "%1$s" изменён с "%2$s" на "%3$s"';
                $comments = $this->getLogChanges($changedAttributes, $text, $comments);
                break;

            case Log::EVENT_DELETE:
                $comments[] = 'Запись удалена';
                break;
        }

        if (count($comments)) {
            $eventLog = new EventLog([
                'user_id' => (empty(Yii::$app->user->id)) ? $this->pre_creator_id : Yii::$app->user->id,
                'type_event_id' => self::EVENT_LOG_CARD,
                'field_id' => $this->id,
                'comments' => $comments,
            ]);

            $eventLog->save(false);
        }
    }

    protected function getLogChanges($changedAttributes, $text, $comments)
    {
        foreach ($changedAttributes as $attr => $old_val) {
            $new_val = $this->$attr;
            if ($old_val != $new_val) {
                switch (true) {
                    case in_array($attr, self::LOG_TEXT_ATTRS):
                        $comments[] = sprintf($text, $this->getAttributeLabel($attr), $old_val, $new_val);
                        break;
                }
            }
        }

        return $comments;
    }

    public function getCompositeName()
    {
        return trim($this->secondname . ' ' . $this->firstname . ' ' . $this->thirdname);
    }

    public static function getCardFullName()
    {
        $out = new Query();
        $out->addSelect([
            'card.id as id',
            new Expression("CONCAT(card.secondname, ' ', card.firstname, ' ', card.thirdname) as name"),
        ])->from('card')
          ->orderBy('name');
        self::addAccessFilter($out);

        return $out->all();
    }

    public static function getEmployeeCard()
    {
        $out = new Query();
        $out->addSelect([
            'card.id as id',
            new Expression("CONCAT(card.secondname, ' ', card.firstname, ' ', card.thirdname) as name"),
        ])->from('card')
          ->leftJoin('movement', 'movement.card_id = card.id')
          ->where(['movement.end' => null])
          ->orderBy('name');
        self::addAccessFilter($out);

        return $out->all();
    }

    public static function getPerformerCardByParent($parent_id)
    {
        $out = new Query();
        $out->addSelect([
            'card.id as id',
            new Expression("CONCAT(card.secondname, ' ', card.firstname, ' ', card.thirdname) as name"),
        ])->from('movement')
          ->leftJoin('card', 'card.id = movement.card_id')
          ->leftJoin('staffpos', 'staffpos.id = movement.staffpos_id')
          ->where(['movement.end' => null, 'staffpos.end' => null, 'staffpos.parent_id' => $parent_id])
          ->orderBy('name');

        return $out->all();
    }

    public static function addAccessFilter($query)
    {
        $user = Yii::$app->user;
        if (AuthHelper::needCompanyRestriction()) {
            if ($user->id) {
                $userProfile = $user->identity->userProfile;
                $query->andWhere(['card.company_id' => $userProfile->company_id]);
            }
        }

        return $query;
    }

    /**
     * Функция добавляет карточку сотрудника в фоновом режиме
     * @param string $stabnum
     * @param string $firstname
     * @param string $secondname
     * @param string $thirdname
     * @param int $company_id
     * @return int
     */
    public function addCard($stabnum, $firstname, $secondname, $thirdname, $company_id, $creator_id)
    {
        $this->stabnum = $stabnum;
        $this->firstname = $firstname;
        $this->secondname = $secondname;
        $this->thirdname = $thirdname;
        $this->company_id = $company_id;
        $this->pre_creator_id = $creator_id;
        $this->save();

        return $this->id;
    }

    /**
     * Функция добавляет карточку сотрудника и создаёт пользователя для этого сотрудника
     * @return bool
     * @throws \Throwable
     */
    public function registerWithCard()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($this->save()) {
                $this->refresh();
            } else {
                $this->addErrors($$this->errors);
                $transaction->rollBack();
                return false;
            }

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
                $role = $auth->getRole($this->role);
                $auth->assign($role, $user->id);

                $userProfile = new UserProfile([
                    'user_id' => $user->id,
                    'first_name' => $this->firstname,
                    'second_name' => $this->secondname,
                    'third_name' => $this->thirdname,
                    'phone' => '',
                    'card_id' => $this->id,
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

    /**
     * Обновляет карточку сотрудника и роль
     * @return bool
     * @throws Throwable
     */
    public function updateWithCard()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($this->save()) {
                $this->refresh();
            } else {
                $this->addErrors($$this->errors);
                $transaction->rollBack();
                return false;
            }

            $auth = Yii::$app->authManager;
            $role = $auth->getRole($this->role);
            // удаляем текущие роли
            $auth->revokeAll($this->user_id);
            // присваиваем новую роль
            $auth->assign($role, $this->user_id);

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
