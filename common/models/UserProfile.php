<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

/* This is the model class for table "user_profile".
 *
 * @property int $id
* @property int|null $user_id
* @property string|null $first_name
* @property string|null $second_name
* @property string|null $third_name
* @property int|null $phone
* @property string|null $email
* @property bool|null $sign_chief
* @property int|null $card_id
* @property int|null $department_id
* @property string|null $uuid
* @property int|null $company_id
*
 * @property Card $card
* @property Company $company
* @property Department $department
* @property User $user
*/
class UserProfile extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'phone', 'card_id', 'department_id', 'company_id'], 'default', 'value' => null],
            [['user_id', 'phone', 'card_id', 'department_id', 'company_id'], 'integer'],
//            [['first_name', 'second_name', 'third_name'], 'required'],
            [['second_name'], 'required'],
            [['first_name', 'second_name', 'third_name'], 'string', 'max' => 255],
            [['sign_chief'], 'boolean'],
            [['email'], 'string', 'max' => 64],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['card_id'], 'exist', 'skipOnError' => true, 'targetClass' => Card::className(), 'targetAttribute' => ['card_id' => 'id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['uuid'], 'thamtech\uuid\validators\UuidValidator'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'first_name' => 'Имя',
            'second_name' => 'Фамилия',
            'third_name' => 'Отчество',
            'phone' => 'Телефон',
            'card_id' => 'ФИО сотрудника',
            'department_id' => 'Подразделение',
            'sign_chief' => 'Признак руководителя',
            'company_id' => 'Компания',
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
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Card]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCard()
    {
        return $this->hasOne(Card::className(), ['id' => 'card_id']);
    }

    /**
     * Gets query for [[Department]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department::className(), ['id' => 'department_id']);
    }

    public function getFullName()
    {
        return trim($this->second_name . ' ' . $this->first_name . ' ' . $this->third_name);
    }

    public static function checkCountByCard($card_id)
    {
        $sql = "
            select count(*)
            from user_profile
            where user_profile.card_id = :card_id
        ";

        $cnt = Yii::$app->db->createCommand($sql, ['card_id' => $card_id])->queryScalar();
        return ($cnt > 0);
    }

    public static function getProfileData($id)
    {
        $query = new Query();
        $query->addSelect([
            'user.id as user_id',
            'user.username',
            'user_profile.first_name',
            'user_profile.second_name',
            'user_profile.third_name',
            'user_profile.card_id',
            'user_profile.uuid',
            'user_profile.company_id',
            'movement.id as movement_id',
            'movement.staffpos_id',
            'staffpos.department_id',
            'staffpos.profession_id'
        ])->from('user')
            ->leftJoin('user_profile', 'user_profile.user_id = "user".id')
            ->leftJoin('movement', 'movement.card_id = user_profile.card_id')
            ->leftJoin('staffpos', 'staffpos.id = movement.staffpos_id')
            ->where(['"user".id' => $id, 'movement.end' => null]);

        return $query->one();
    }
}
