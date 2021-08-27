<?php

namespace frontend\models\search;

use common\models\OrdersPerformer;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class OrdersPerformerSearch extends OrdersPerformer
{

    public $status_result_ids;
    public $name;

    public function rules()
    {
        return [
            [['name', 'status_result_ids'], 'safe'],
        ];
    }

    public function search($params, $id)
    {
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
            'orders_performer.id',
            'orders_performer.status_result',
            new Expression("concat(card.secondname, ' ', card.firstname, ' ', card.thirdname) as name"),
        ])->from('orders_performer')
            ->leftJoin('card', 'card.id = orders_performer.card_id')
            ->where(['orders_id'=>$id])
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

        $query->andFilterWhere(['ilike', new Expression("concat(card.secondname, ' ', card.firstname, ' ', card.thirdname)"), $this->name]);
        $query->andFilterWhere(['IN', 'orders_performer.status_result', $this->status_result_ids]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'name' => Yii::t('app', 'ФИО исполнителя'),
            'status_result' => Yii::t('app', 'Результат выполнения'),
        ]);

        return $labels;
    }

}