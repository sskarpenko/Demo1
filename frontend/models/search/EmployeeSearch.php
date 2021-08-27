<?php
namespace frontend\models\search;

use Yii;
use yii\base\Model;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\models\ActiveDataProviderPpu;
use common\models\Employee;

class EmployeeSearch extends Model
{
    public $id = null;
//    public $staff_pos_ids = null;
    public $fio = null;
    public $phone = null;
    public $user_profile_id = null;
    public $username = null;
    public $roles = null;

    public function rules()
    {
        return [
            [['id', 'fio', 'phone', 'user_profile_id', 'username', 'roles'], 'safe'],
        ];
    }

    public function search($params)
    {
        $user = Yii::$app->user;
        $session = Yii::$app->session;

        if (!isset($params['EmployeeSearch'])) {
            if ($session->has('EmployeeSearch')) {
                $params['EmployeeSearch'] = $session['EmployeeSearch'];
            }
        } else {
            $session->set('EmployeeSearch', $params['EmployeeSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('EmployeeSearchSort')) {
                $params['sort'] = $session['EmployeeSearchSort'];
            }
        } else {
            $session->set('EmployeeSearchSort', $params['sort']);
        }

        if (isset($params['sort'])) {
            $pos = stripos($params['sort'], '-');
            if ($pos !== false) {
                $typeSort = SORT_DESC;
                $fieldSort = substr($params['sort'], 1);
            } else {
                $typeSort = SORT_ASC;
                $fieldSort = $params['sort'];
            }
        } else {
            $typeSort = SORT_ASC;
            $fieldSort = 'fio';
        }

        $query = new Query();
        $query->addSelect([
            'user.id',
            new Expression("CONCAT(card.secondname, ' ', card.firstname, ' ', card.thirdname) as fio"),
            'user_profile.phone as phone',
            'user_profile.user_id as user_id',
            'user_profile.id as user_profile_id',
            'user.status',
            'user.username',
            new Expression("
            (
                select string_agg(auth_assignment.item_name, ',' order by auth_assignment.item_name) 
                from auth_assignment 
                left join auth_item on (auth_item.name = auth_assignment.item_name) 
                where auth_assignment.user_id = \"user\".id::varchar
                      and auth_item.type = 1
            ) as roles"),
        ])->from('user')
          ->leftJoin('user_profile', 'user_profile.user_id = "user".id')
          ->leftJoin('card', 'card.id = user_profile.card_id');

        if (!$user->can('rl_admin')) {
            $query->andWhere(new Expression("exists (
            select 1
            from auth_assignment
            where auth_assignment.user_id = \"user\".id::varchar and auth_assignment.item_name <> 'rl_admin')"));
        }

        Employee::addAccessFilter($query);
        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';
        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'fio',
                'phone',
                'username',
            ]
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

//        $query->andFilterWhere(['IN', 'user_staff_pos.staff_pos_id', $this->staff_pos_ids]);
//        $query->andFilterWhere(['ilike', "CONCAT(user_profile.second_name, ' ', user_profile.first_name, ' ', user_profile.third_name)", $this->fio]);
        $query->andFilterWhere(['ilike', "CONCAT(card.secondname, ' ', card.firstname, ' ', card.thirdname)", $this->fio]);
        $query->andFilterWhere(['ilike', 'user.username', $this->username]);
        $query->andFilterWhere(['=', 'user_profile.phone', $this->phone]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'fio' => Yii::t('app', 'ФИО сотрудника'),
            'phone' => Yii::t('app', 'Телефон'),
            'status' => Yii::t('app', 'Статус'),
            'role' => Yii::t('app', 'Роль'),
            'username' => Yii::t('app', 'Имя пользователя'),
        ]);

        return $labels;
    }
}
