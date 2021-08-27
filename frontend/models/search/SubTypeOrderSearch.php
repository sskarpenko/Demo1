<?php

namespace frontend\models\search;

use common\models\SubTypeOrder;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class SubTypeOrderSearch extends SubTypeOrder
{
    public $type_order_ids;

    public function rules()
    {
        return [
            [['name', 'desc', 'type_order_ids'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['SubTypeOrderSearch'])) {
            if ($session->has('SubTypeOrderSearch')){
                $params['SubTypeOrderSearch'] = $session['SubTypeOrderSearch'];
            }
        }
        else{
            $session->set('SubTypeOrderSearch', $params['SubTypeOrderSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('SubTypeOrderSearchSort')){
                $params['sort'] = $session['SubTypeOrderSearchSort'];
            }
        }
        else{
            $session->set('SubTypeOrderSearchSort', $params['sort']);
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
            $fieldSort = 'name';
        }

        $query = new Query();
        $query->addSelect([
            'sub_type_order.id as id',
            'sub_type_order.name as name',
            'sub_type_order.desc as desc',
            'type_order.name as type_order_name',
        ])->from('sub_type_order')
            ->leftJoin('type_order', 'type_order.id = sub_type_order.type_order_id')
        ;

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';

        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'id',
                'name',
                'desc',
                'type_order_name',
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

        $query->andFilterWhere(['ilike', 'sub_type_order.name', $this->name]);
        $query->andFilterWhere(['ilike', 'sub_type_order.desc', $this->desc]);
        $query->andFilterWhere(['IN', 'sub_type_order.type_order_id', $this->type_order_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'type_order_name' => Yii::t('app', 'Тип поручения'),
        ]);
        return $labels;
    }

}