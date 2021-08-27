<?php

namespace frontend\models\search;

use common\models\TypeMessage;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class TypeMessageSearch extends TypeMessage
{

    public $type_order_ids;

    public function rules()
    {
        return [
            [['name','type_order_ids'], 'safe'],
        ];
    }

    public function search($params)
    {

        $session = Yii::$app->session;

        if (!isset($params['TypeMessageSearch'])) {
            if ($session->has('TypeMessageSearch')){
                $params['TypeMessageSearch'] = $session['TypeMessageSearch'];
            }
        }
        else{
            $session->set('TypeMessageSearch', $params['TypeMessageSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('TypeMessageSearchSort')){
                $params['sort'] = $session['TypeMessageSearchSort'];
            }
        }
        else{
            $session->set('TypeMessageSearchSort', $params['sort']);
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
        }
        else {
            $typeSort = SORT_ASC;
            $fieldSort = 'name';
        }

        $query = new Query();
        $query->addSelect([
            'type_message.id',
            'type_message.name',
            'type_message.type_order_id',
            'type_order.name as type_order_name',
        ])->from('type_message')
            ->leftJoin('type_order', 'type_order.id = type_message.type_order_id')
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

        $query->andFilterWhere(['ilike', 'type_message.name', $this->name]);
        $query->andFilterWhere(['IN', 'type_message.type_order_id', $this->type_order_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'type_order_name' => Yii::t('app', 'Вид поручения'),
        ]);

        return $labels;
    }

}