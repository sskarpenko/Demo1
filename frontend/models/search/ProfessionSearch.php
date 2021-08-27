<?php
namespace frontend\models\search;

use Yii;
use yii\db\Query;
use common\models\ActiveDataProviderPpu;
use common\models\Profession;

class ProfessionSearch extends Profession
{
    public function rules()
    {
        return [
            [['name'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['ProfessionSearch'])) {
            if ($session->has('ProfessionSearch')) {
                $params['ProfessionSearch'] = $session['ProfessionSearch'];
            }
        } else {
            $session->set('ProfessionSearch', $params['ProfessionSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('ProfessionSearchSort')) {
                $params['sort'] = $session['ProfessionSearchSort'];
            }
        } else {
            $session->set('ProfessionSearchSort', $params['sort']);
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
            $fieldSort = 'name';
        }

        $query = new Query();
        $query->addSelect([
            'profession.id as id',
            'profession.name as name',
        ])->from('profession');

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

        $query->andFilterWhere(['ilike', 'profession.name', $this->name]);

        return $dataProvider;
    }

    public function clearFilter()
    {
        $session = Yii::$app->session;
        if ($session->has('ProfessionSearch')) {
            $session->remove('ProfessionSearch');
        }

        if ($session->has('ProfessionSearchSort')) {
            $session->remove('ProfessionSearchSort');
        }
    }
}
