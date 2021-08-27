<?php
namespace frontend\models\search;

use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\models\ActiveDataProviderPpu;
use common\models\Card;
use common\models\Department;
use common\models\Movement;

class MovementSearch extends Movement
{
    public $card_ids;
    public $staffpos_ids;
    public $parent_staffpos_ids;
    public $actual_date_filter;

    public function rules()
    {
        return [
            [['card_ids','staffpos_ids', 'parent_staffpos_ids', 'actual_date_filter'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['MovementSearch'])) {
            if ($session->has('MovementSearch')) {
                $params['MovementSearch'] = $session['MovementSearch'];
            }
        } else {
            $session->set('MovementSearch', $params['MovementSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('MovementSearchSort')){
                $params['sort'] = $session['MovementSearchSort'];
            }
        } else {
            $session->set('MovementSearchSort', $params['sort']);
        }

        if (isset($params["sort"])) {
            $pos = stripos($params["sort"], '-');
            if ($pos !== false) {
                $typeSort = SORT_DESC;
                $fieldSort = substr($params["sort"], 1);
            } else {
                $typeSort = SORT_ASC;
                $fieldSort = $params["sort"];
            }
        } else {
            $typeSort = SORT_ASC;
            $fieldSort = 'card_name';
        }

        $query = new Query();
        $query->addSelect([
            'movement.id',
            'movement.begin as begin',
            'movement.end as end',
            'staffpos.parent_id as staffpos_parent_id',
            new Expression("concat('(', department.code, ') ', department.short_name, ' ', profession.name) as staffpos_name"),
            new Expression("concat('(', parent_department.code, ') ', parent_department.short_name, ' ', parent_profession.name) as parent_staffpos_name"),
            new Expression("concat(card.secondname, ' ', card.firstname, ' ', card.thirdname) as card_name"),
        ])->from('movement')
          ->leftJoin('card', 'card.id = movement.card_id')
          ->leftJoin('staffpos', 'staffpos.id = movement.staffpos_id')
          ->leftJoin('department', 'department.id = staffpos.department_id')
          ->leftJoin('profession', 'profession.id = staffpos.profession_id')
          ->leftJoin('staffpos as parent_staffpos', 'parent_staffpos.id = staffpos.parent_id')
          ->leftJoin('department as parent_department', 'parent_department.id = parent_staffpos.department_id')
          ->leftJoin('profession as parent_profession', 'parent_profession.id = parent_staffpos.profession_id');

        Card::addAccessFilter($query);
        Department::addAccessFilter($query);
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';

        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'id',
                'begin',
                'end',
                'staffpos_name',
                'parent_staffpos_name',
                'card_name',
            ]
        ]);

        $this->load($params);
        if ($this->actual_date_filter) $query->andWhere(['movement.end' => null]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['IN', 'movement.card_id', $this->card_ids]);
        $query->andFilterWhere(['IN', 'movement.staffpos_id', $this->staffpos_ids]);
        $query->andFilterWhere(['IN', 'staffpos.parent_id', $this->parent_staffpos_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'staffpos_name' => Yii::t('app', 'Штатная единица'),
            'parent_staffpos_name' => Yii::t('app', 'Головная штатная единица'),
            'card_name' => Yii::t('app', 'Сотрудник'),
        ]);

        return $labels;
    }
}
