<?php
namespace frontend\controllers;

use Yii;
use yii\bootstrap4\ActiveForm;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use common\models\Card;
use common\models\Company;
use common\models\EventLog;
use common\models\Movement;
use common\models\MovementModel;
use common\models\Staffpos;
use DateTime;
use frontend\models\search\MovementSearch;

class MovementController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'clear-filter', 'finish', 'move', 'validate-form'],
                        'allow' => true,
                        'roles' => ['rl_admin', 'rl_key_user'],
                    ]
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new MovementSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $staffpos = ArrayHelper::map(Staffpos::getStaffposName(), 'id', 'name');
        $card = ArrayHelper::map(Card::getCardFullName(), 'id', 'name');
        $statusMovement = Movement::getDataByCard(Yii::$app->user->identity->userProfile->card_id) ? true : false;

        if (!$statusMovement) {
            Yii::$app->session->addFlash('warning', Yii::t('app', 'Текущая учётная запись в настоящий момент не привязана к структуре организации. Обратитесь к администратору Вашей организации.'));
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'staffpos' => $staffpos,
            'card' => $card,
        ]);
    }

    public function actionCreate()
    {
        $model = new MovementModel(['scenario' => MovementModel::SCENARIO_CREATE]);
        $model->type_card = Movement::NEW_CARD;

        $userProfile = Yii::$app->user->identity->userProfile;
        $model->company_id = $userProfile->company_id;
        $company = ArrayHelper::map(Company::find()->orderBy('short_name')->all(), 'id', 'short_name');

        $staffpos = ArrayHelper::map(Staffpos::getStaffposName(), 'id', 'name');
        $card = ArrayHelper::map(Card::getCardFullName(), 'id', 'name');

        if (Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post());
        } else {
            if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->register()) {
                $url = $this->getCurrentUrl();
                return $this->redirect($url);
            }
        }
        $this->setCurrentUrl();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_create', [
                'model' => $model,
                'staffpos' => $staffpos,
                'card' => $card,
                'company' => $company,
                'title' => 'Новое перемещение',
            ]);
        } else {
            return $this->render('_create', [
                'model' => $model,
                'staffpos' => $staffpos,
                'card' => $card,
                'company' => $company,
                'title' => 'Новое перемещение',
            ]);
        }
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $event_logs = EventLog::find()->where(['type_event_id' => Staffpos::EVENT_LOG_STAFFPOST, 'field_id' => $id])->orderBy(['created_at' => SORT_DESC])->all();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', [
                'model' => $model,
                'event_logs' => $event_logs,
                'title' => 'Перемещение',
            ]);
        } else {
            return $this->render('view', [
                'model' => $model,
                'event_logs' => $event_logs,
                'title' => 'Перемещение',
            ]);
        }
    }

    public function actionMove($id)
    {
        //$this->setCurrentUrl();
        $url = $this->getCurrentUrl();
        $modelOld = $this->findModel($id);

        if ($modelOld->end) {
            //$url = $this->getCurrentUrl();
            Yii::$app->session->addFlash('error', Yii::t('app', 'Штатная единица закрыта. Перемещение невозможно'));
            return $this->redirect($url);
        }

        $model = new Movement(['scenario' => Movement::SCENARIO_MOVE]);
        $model->card_id = $modelOld->card_id;
        $beginDate = new DateTime();
        $model->begin = Yii::$app->formatter->asDate($beginDate, 'php:Y-m-d');
        $staffpos = ArrayHelper::map(Staffpos::getStaffposName(), 'id', 'name');

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $endDate = new DateTime();
            $endDate->modify('-1 day');
            $modelOld->end = Yii::$app->formatter->asDate($endDate, 'php:Y-m-d');
            $modelOld->save();
            //$url = $this->getCurrentUrl();
            return $this->redirect($url);
        }
        $this->setCurrentUrl();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'staffpos' => $staffpos,
                'title' => 'Перевод сотрудника',
            ]);
        } else {
            return $this->render('_form', [
                'model' => $model,
                'staffpos' => $staffpos,
                'title' => 'Перевод сотрудника',
            ]);
        }
    }

    public function actionFinish($id)
    {
        $this->setCurrentUrl();
        $url = $this->getCurrentUrl();
        $model = $this->findModel($id);

        if ($model->end) {
            Yii::$app->session->addFlash('error', Yii::t('app', 'Сотрудник уволен. Повторное увольнение невозможно'));
            return $this->redirect($url);
        }

        $endDate = new DateTime();
        $model->end = Yii::$app->formatter->asDate($endDate, 'php:Y-m-d');
        $model->load(Yii::$app->request->post());

        if ($model->save()) {
            Yii::$app->session->addFlash('success', Yii::t('app', 'Сотрудник уволен.'));
        }
        return $this->redirect($url);
    }

    private function findModel($id)
    {
        $model = Movement::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'Штатная единица не найдена'));
        }

        return $model;
    }

    public function actionDelete($id)
    {
        $this->setCurrentUrl();

        if ($this->findModel($id)->delete()) {
            Yii::$app->session->addFlash('success', Yii::t('app', 'Перемещение удалено'));
        }

        $url = $this->getCurrentUrl();
        return $this->redirect($url);
    }

    public function actionClearFilter()
    {
        $session = Yii::$app->session;
        if ($session->has('MovementSearch')) {
            $session->remove('MovementSearch');
        }

        if ($session->has('MovementSearchSort')) {
            $session->remove('MovementSearchSort');
        }

        return $this->redirect('index');
    }

    public function setCurrentUrl()
    {
        $session = Yii::$app->session;
        $session->set('MovementReferrer', Yii::$app->request->referrer);
    }

    public function getCurrentUrl()
    {
        $session = Yii::$app->session;
        if ($session['MovementReferrer'])
            return $session['MovementReferrer'];

        return 'index';
    }

    public function actionValidateForm()
    {
        $model = new MovementModel();
        $model->load(Yii::$app->request->post());

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        throw new BadRequestHttpException(Yii::t('app', 'Ошибка запроса'));
    }
}
