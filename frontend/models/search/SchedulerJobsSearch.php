<?php

namespace frontend\models\search;

use common\models\SchedulerJobs;
use common\models\ActiveDataProviderPpu;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class SchedulerJobsSearch extends SchedulerJobs
{
    public $period_type_ids;
    public $status_ids;

    public function rules()
    {
        return [
            [['job_action', 'desc', 'period_type_ids', 'status_ids'], 'safe'],
        ];
    }

    public function search($params)
    {
        $session = Yii::$app->session;

        if (!isset($params['SchedulerJobsSearch'])) {
            if ($session->has('SchedulerJobsSearch')){
                $params['SchedulerJobsSearch'] = $session['SchedulerJobsSearch'];
            }
        }
        else{
            $session->set('SchedulerJobsSearch', $params['SchedulerJobsSearch']);
        }

        if (!isset($params['sort'])) {
            if ($session->has('SchedulerJobsSearchSort')){
                $params['sort'] = $session['SchedulerJobsSearchSort'];
            }
        }
        else{
            $session->set('SchedulerJobsSearchSort', $params['sort']);
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
            $fieldSort = 'job_action';
        }

        $query = new Query();
        $query->addSelect([
            'scheduler_jobs.id as id',
            'scheduler_jobs.job_action as job_action',
            'scheduler_jobs.desc as desc',
            'scheduler_jobs.status as status',
            'scheduler_jobs.period_type as period_type',
            'scheduler_jobs.next_launch as next_launch',
        ])->from('scheduler_jobs')
        ;

        $dataProvider = new ActiveDataProviderPpu([
            'query' => $query,
        ]);

        $dataProvider->key = 'id';

        $dataProvider->setSort([
            'defaultOrder' => [$fieldSort => $typeSort],
            'attributes' => [
                'id',
                'job_action',
                'desc',
                'next_launch',
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

        $query->andFilterWhere(['ilike', 'scheduler_jobs.job_action', $this->job_action]);
        $query->andFilterWhere(['ilike', 'scheduler_jobs.desc', $this->desc]);
        $query->andFilterWhere(['IN', 'scheduler_jobs.status', $this->status_ids]);
        $query->andFilterWhere(['IN', 'scheduler_jobs.period_type', $this->period_type_ids]);

        return $dataProvider;
    }

}