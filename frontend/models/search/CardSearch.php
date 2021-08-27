<?php

namespace frontend\models\search;

use common\models\Card;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class CardSearch extends Card
{
    public function rules()
    {
        return [
            [['stabnum', 'firstname', 'secondname', 'thirdname'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['CardSearch'])) {
            if ($session->has('CardSearch')){
                $params['CardSearch'] = $session['CardSearch'];
            }
        }
        else{
            $session->set('CardSearch', $params['CardSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('CardSearchSort')){
                $params['sort'] = $session['CardSearchSort'];
            }
        }
        else{
            $session->set('CardSearchSort', $params['sort']);
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
            $fieldSort = 'stabnum';
        }

        $query = new Query();
        $query->addSelect([
            'card.id as id',
            'card.stabnum as stabnum',
            'card.firstname as firstname',
            'card.secondname as secondname',
            'card.thirdname as thirdname',
        ])->from('card')
        ;
        Card::addAccessFilter($query);
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';

        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'id',
                'stabnum',
                'firstname',
                'secondname',
                'thirdname',
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

        $query->andFilterWhere(['ilike', 'card.stabnum', $this->stabnum]);
        $query->andFilterWhere(['ilike', 'card.firstname', $this->firstname]);
        $query->andFilterWhere(['ilike', 'card.secondname', $this->secondname]);
        $query->andFilterWhere(['ilike', 'card.thirdname', $this->thirdname]);

        return $dataProvider;
    }

}