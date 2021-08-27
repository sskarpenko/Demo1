<?php

namespace frontend\models\search;

use common\models\Staffpos;
use common\models\ActiveDataProviderPpu;
use common\models\Department;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\db\Expression;


class StaffposSearch extends Staffpos
{
    public $parent_ids;
    public $department_ids;
    public $profession_ids;
    public $actual_date_filter;

    public function rules()
    {
        return [
            [['parent_ids','profession_ids', 'department_ids', 'actual_date_filter'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['StaffposSearch'])) {
            if ($session->has('StaffposSearch')){
                $params['StaffposSearch'] = $session['StaffposSearch'];
            }
        }
        else{
            $session->set('StaffposSearch', $params['StaffposSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('StaffposSearchSort')){
                $params['sort'] = $session['StaffposSearchSort'];
            }
        }
        else{
            $session->set('StaffposSearchSort', $params['sort']);
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
        }
        else {
            $typeSort = SORT_ASC;
            $fieldSort = 'id';
        }

        $query = new Query();
        $query->addSelect([
            'staffpos.id',
            'staffpos.parent_id as parent_id',
            'staffpos.begin as begin',
            'staffpos.end as end',
            new Expression("concat('(', department.code, ') ', department.short_name) as department_name"),
            'profession.name as profession_name',
        ])->from('staffpos')
            ->leftJoin('department', 'department.id = staffpos.department_id')
            ->leftJoin('profession', 'profession.id = staffpos.profession_id')
        ;
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
//                'parent_id',
                'department_name',
                'profession_name',
            ]
        ]);

        $this->load($params);

        if ($this->actual_date_filter) $query->andWhere(['staffpos.end' => null]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['IN', 'staffpos.department_id', $this->department_ids]);
        $query->andFilterWhere(['IN', 'staffpos.profession_id', $this->profession_ids]);
        $query->andFilterWhere(['IN', 'staffpos.parent_id', $this->parent_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'department_name' => Yii::t('app', 'Подразделение'),
            'profession_name' => Yii::t('app', 'Профессия'),
        ]);

        return $labels;
    }

}