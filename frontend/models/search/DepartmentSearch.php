<?php

namespace frontend\models\search;

use common\models\Department;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\db\Expression;


class DepartmentSearch extends Department
{
    public $department_ids;
    public $actual_date_filter;

    public function rules()
    {
        return [
            [['code','short_name','full_name', 'department_ids', 'actual_date_filter'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['DepartmentSearch'])) {
            if ($session->has('DepartmentSearch')){
                $params['DepartmentSearch'] = $session['DepartmentSearch'];
            }
        }
        else{
            $session->set('DepartmentSearch', $params['DepartmentSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('DepartmentSearchSort')){
                $params['sort'] = $session['DepartmentSearchSort'];
            }
        }
        else{
            $session->set('DepartmentSearchSort', $params['sort']);
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
            $fieldSort = 'code';
        }

        $query = new Query();
        $query->addSelect([
            'department.id',
            'department.code as code',
            'department.short_name as short_name',
            'department.full_name as full_name',
            'department.begin as begin',
            'department.end as end',
            'department.parent_id as parent_id',
            new Expression("concat('(', parent_department.code, ') ', parent_department.short_name) as parent_department_name"),
        ])->from('department')
            ->leftJoin('department as parent_department', 'parent_department.id = department.parent_id')
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
                'code',
                'short_name',
                'full_name',
                'begin',
                'end',
                'parent_department_name',
            ]
        ]);

        $this->load($params);

        if ($this->actual_date_filter) $query->andWhere(['department.end' => null]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'department.code', $this->code]);
        $query->andFilterWhere(['like', 'department.short_name', $this->short_name]);
        $query->andFilterWhere(['like', 'department.full_name', $this->full_name]);
        $query->andFilterWhere(['IN', 'department.parent_id', $this->department_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'parent_department_name' => Yii::t('app', 'Головное подразделение'),
        ]);

        return $labels;
    }

}