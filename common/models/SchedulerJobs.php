<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use DateTime;

/**
 * This is the model class for table "scheduler_jobs".
 *
 * @property int $id
 * @property string $job_action
 * @property string|null $desc
 * @property int|null $status
 * @property int|null $period_type
 * @property int|null $job_type
 * @property string|null $next_launch
 * @property int $creator_id
 *
 * @property User $creator
 */
class SchedulerJobs extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    const PERIOD_DAILY = 5;
    const PERIOD_WEEKLY = 10;
    const PERIOD_MONTHLY = 15;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'scheduler_jobs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_action', 'creator_id', 'status', 'period_type'], 'required'],
            [['desc'], 'string'],
            [['status', 'period_type', 'job_type', 'creator_id'], 'default', 'value' => null],
            [['status', 'period_type', 'job_type', 'creator_id'], 'integer'],
            [['next_launch'], 'safe'],
            [['job_action'], 'string', 'max' => 255],
            [['creator_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['creator_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'job_action' => 'Задание',
            'desc' => 'Описание',
            'status' => 'Статус',
            'period_type' => 'Период запуска',
            'job_type' => 'Тип задания',
            'next_launch' => 'Следующий запуск',
            'creator_id' => 'Creator ID',
        ];
    }

    /**
     * Gets query for [[Creator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(User::className(), ['id' => 'creator_id']);
    }

    public static function getStatus()
    {
        return [
            '0' => Yii::t('app', 'Не активено'),
            '1' => Yii::t('app', 'Активено'),
        ];
    }

    public static function getPeriod()
    {
        return [
            '5' => Yii::t('app', 'Ежедневно'),
            '10' => Yii::t('app', 'Еженедельно'),
            '15' => Yii::t('app', 'Ежемесячно'),
        ];
    }

    public static function getSchedulerJobsList()
    {
        $dateFrom = new DateTime();
        $dateTo = new DateTime();
        $dateTo->modify('+1 day');

        $query = new Query();
        $query->addSelect([
            'scheduler_jobs.id as id',
            'scheduler_jobs.job_action as job_action',
            'scheduler_jobs.next_launch as next_launch',
        ])->from('scheduler_jobs')
            ->where(['and', ['status' => SchedulerJobs::STATUS_ACTIVE], ['>=', 'next_launch', $dateFrom->format('Y-m-d H:i:s')], ['<', 'next_launch', $dateTo->format('Y-m-d H:i:s')]])
        ;

        return $query->all();
    }

}
