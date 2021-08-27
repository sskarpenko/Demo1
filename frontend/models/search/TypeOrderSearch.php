<?php

namespace frontend\models\search;

use common\models\TypeOrder;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class TypeOrderSearch extends TypeOrder
{
    public function rules()
    {
        return [
            [['name', 'desc'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['TypeOrderSearch'])) {
            if ($session->has('TypeOrderSearch')){
                $params['TypeOrderSearch'] = $session['TypeOrderSearch'];
            }
        }
        else{
            $session->set('TypeOrderSearch', $params['TypeOrderSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('TypeOrderSearchSort')){
                $params['sort'] = $session['TypeOrderSearchSort'];
            }
        }
        else{
            $session->set('TypeOrderSearchSort', $params['sort']);
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
            'type_order.id as id',
            'type_order.name as name',
            'type_order.desc as desc',
        ])->from('type_order')
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

        $query->andFilterWhere(['ilike', 'type_order.name', $this->name]);
        $query->andFilterWhere(['ilike', 'type_order.desc', $this->desc]);

        return $dataProvider;
    }

}