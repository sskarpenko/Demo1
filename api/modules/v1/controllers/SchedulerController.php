<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use api\controllers\BaseApiController;
use common\models\SchedulerJobs;
use DateTime;

class SchedulerController extends BaseApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'get-list' => ['GET'],
                    'update-job' => ['POST'],
                ],
            ],
        ], $behaviors);

        return $behaviors;
    }

    public function actionGetList()
    {
        return SchedulerJobs::getSchedulerJobsList();
    }

    public function actionUpdateJob($id)
    {
        $nextLaunch = new DateTime();

        $model = SchedulerJobs::findOne($id);

        if ($model) {
            switch ($model->period_type) {
                case SchedulerJobs::PERIOD_DAILY:
                    $nextLaunch->modify('+1 day');
                    break;
                case SchedulerJobs::PERIOD_WEEKLY:
                    $nextLaunch->modify('+7 day');
                    break;
                case SchedulerJobs::PERIOD_MONTHLY:
                    $nextLaunch->modify('+1 month');
                    break;
            }
            $model->next_launch = $nextLaunch->format('Y-m-d H:i:s');
            return $model->save();
        }
        return false;
    }

}
