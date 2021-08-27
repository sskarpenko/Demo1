<?php

namespace frontend\models\search;

use common\models\Orders;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\db\Expression;

class OrdersSearch extends Orders
{
    public $type_order_ids;
    public $sub_type_order_ids;
    public $creator_ids;
    public $status_order_ids;
    public $status_result_ids;

    public function rules()
    {
        return [
            [['creator_ids', 'type_order_ids', 'status_order_ids', 'status_result_ids', 'sub_type_order_ids', 'desc', 'reg_num'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;
        $user = Yii::$app->user;
        $userProfile = $user->identity->userProfile;

        if (!isset($params['OrdersSearch'])) {
            if ($session->has('OrdersSearch')){
                $params['OrdersSearch'] = $session['OrdersSearch'];
            }
        }
        else{
            $session->set('OrdersSearch', $params['OrdersSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('OrdersSearchSort')){
                $params['sort'] = $session['OrdersSearchSort'];
            }
        }
        else{
            $session->set('OrdersSearchSort', $params['sort']);
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
            $typeSort = SORT_DESC;
            $fieldSort = 'created_at';
        }

        $performerQuery = new Query();
//        $performerQuery->addSelect([
//            'orders_performer.orders_id',
//        ])->from('orders_performer')
//            ->where(['orders_performer.card_id' => $userProfile->card_id])->all();

        $performerQuery->addSelect([
            'orders_performer.orders_id',
        ])->from('orders_performer')
            ->leftJoin('orders', 'orders.id = orders_performer.orders_id')
            ->where(['and', ['orders_performer.card_id' => $userProfile->card_id], ['not in', 'orders.status_order', [Orders::STATUS_DRAFT]]])->all();

        $query = new Query();
        $query->addSelect([
            'orders.id',
            'orders.reg_num',
            'orders.sub_type_order_id',
            'orders.desc',
            'orders.created_at',
            'orders.status_order',
            'orders.status_result',
            new Expression("concat(card_creator.secondname, ' ', card_creator.firstname, ' ', card_creator.thirdname) as creator_name"),
            'sub_type_order.name as sub_type_order_name',
            'type_order.name as type_order_name',
            new Expression("
            (
                select count(*) 
                from orders_favorites 
                where orders_favorites.user_id = $user->id
                      and orders_favorites.orders_id = orders.id
            ) as favorites"),
        ])->from('orders')
            ->leftJoin('sub_type_order', 'sub_type_order.id = orders.sub_type_order_id')
            ->leftJoin('type_order', 'type_order.id = sub_type_order.type_order_id')
            ->leftJoin('user_profile as user_profile_creator', 'user_profile_creator.user_id = orders.creator_id')
            ->leftJoin('card as card_creator', 'card_creator.id = user_profile_creator.card_id')
        ;

        Orders::addAccessFilter($query, $performerQuery);

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';

        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'id',
                'created_at' => [
                    'asc' => ['favorites' => SORT_DESC, 'created_at' => SORT_ASC],
                    'desc' => ['favorites' => SORT_DESC, 'created_at' => SORT_DESC],
                ],
                'type_order_name'=> [
                    'asc' => ['favorites' => SORT_DESC, 'type_order_name' => SORT_ASC],
                    'desc' => ['favorites' => SORT_DESC, 'type_order_name' => SORT_DESC],
                ],
                'reg_num'=> [
                    'asc' => ['favorites' => SORT_DESC, 'reg_num' => SORT_ASC],
                    'desc' => ['favorites' => SORT_DESC, 'reg_num' => SORT_DESC],
                ],

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

        $query->andFilterWhere(['IN', 'orders.creator_id', $this->creator_ids]);
        $query->andFilterWhere(['IN', 'orders.status_order', $this->status_order_ids]);
        $query->andFilterWhere(['IN', 'orders.status_result', $this->status_result_ids]);
        $query->andFilterWhere(['IN', 'orders.sub_type_order_id', $this->sub_type_order_ids]);
        //$query->andFilterWhere(['IN', 'sub_type_order.type_order_id', $this->type_order_ids]);
        $query->andFilterWhere(['like', 'orders.desc', $this->desc]);
        $query->andFilterWhere(['=', 'orders.reg_num', $this->reg_num]);

        return $dataProvider;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels = ArrayHelper::merge($labels, [
            'creator_name' => Yii::t('app', 'Создатель поручения'),
            'sub_type_order_name' => Yii::t('app', 'Тип / подтип поручения'),
            'favorites' => Yii::t('app', 'Избранное'),
        ]);
        return $labels;
    }

}