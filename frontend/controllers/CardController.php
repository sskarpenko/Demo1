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
use common\models\Employee;
use common\models\EventLog;
use common\models\Movement;
use common\models\User;
use common\models\UserProfile;
use common\helpers\AuthHelper;
use frontend\models\search\CardSearch;

class CardController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'clear-filter'],
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
        $searchModel = new CardSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $statusMovement = Movement::getDataByCard(Yii::$app->user->identity->userProfile->card_id) ? true : false;
        if (!$statusMovement) {
            Yii::$app->session->addFlash('warning', Yii::t('app', 'Текущая учётная запись в настоящий момент не привязана к структуре организации. Обратитесь к администратору Вашей организации.'));
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new Card();
        $event_logs = [];
        $email = '';
        $userProfile = Yii::$app->user->identity->userProfile;
        $model->company_id = $userProfile->company_id;
        $model->role = AuthHelper::RL_KEY_USER;
        $roles = AuthHelper::getAvailableRoles();
        $company = ArrayHelper::map(Company::find()->orderBy('short_name')->all(), 'id', 'short_name');

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->registerWithCard()) {
            $url = $this->getCurrentUrl();
            return $this->redirect($url);
        }
        $this->setCurrentUrl();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'event_logs' => $event_logs,
                'company' => $company,
                'email' => $email,
                'roles' => $roles,
                'title' => 'Добавить карточку сотрудника',
            ]);
        } else {
            return $this->render('_form', [
                'model' => $model,
                'event_logs' => $event_logs,
                'company' => $company,
                'email' => $email,
                'roles' => $roles,
                'title' => 'Добавить карточку сотрудника',
            ]);
        }
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $profile = UserProfile::findOne(['card_id' => $id]);
        $model->user_id = $profile->user_id;
        $model->role = Employee::getEmployeeRole($model->user_id);

        $event_logs = EventLog::find()->where(['type_event_id' => Card::EVENT_LOG_CARD, 'field_id' => $id])->orderBy(['created_at' => SORT_DESC])->all();
        $company = ArrayHelper::map(Company::find()->orderBy('short_name')->all(), 'id', 'short_name');
        $email = User::getUsernameByCard($id);
        $roles = AuthHelper::getAvailableRoles();

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->updateWithCard()) { //&& $model->save()) {
            $url = $this->getCurrentUrl();
            return $this->redirect($url);
        }
        $this->setCurrentUrl();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'event_logs' => $event_logs,
                'company' => $company,
                'email' => $email,
                'roles' => $roles,
                'title' => 'Карточка сотрудника',
            ]);
        } else {
            return $this->render('_form', [
                'model' => $model,
                'event_logs' => $event_logs,
                'company' => $company,
                'email' => $email,
                'roles' => $roles,
                'title' => 'Карточка сотрудника',
            ]);
        }
    }

    private function findModel($id)
    {
        $model = Card::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'Карточка не найдена'));
        }

        return $model;
    }

    public function actionDelete($id)
    {
        $this->setCurrentUrl();

        $url = $this->getCurrentUrl();
        if (Movement::checkCountByCard($id)) {
            Yii::$app->session->addFlash('error', Yii::t('app', 'Удаление карточки сотрудника невозможно. Есть ссылка в таблице Перемещения.'));
            return $this->redirect($url);
        }

        if (UserProfile::checkCountByCard($id)) {
            Yii::$app->session->addFlash('error', Yii::t('app', 'Удаление карточки сотрудника невозможно. Сотрудник зарегистрирован как пользователь.'));
            return $this->redirect($url);
        }

        if ($this->findModel($id)->delete()) {
            Yii::$app->session->addFlash('success', Yii::t('app', 'Карточка сотрудника удалена'));
        }
        return $this->redirect($url);
    }

    public function actionClearFilter()
    {
        $session = Yii::$app->session;
        if ($session->has('CardSearch')) {
            $session->remove('CardSearch');
        }

        if ($session->has('CardSearchSort')) {
            $session->remove('CardSearchSort');
        }

        return $this->redirect('index');
    }

    public function setCurrentUrl()
    {
        $session = Yii::$app->session;
        $session->set('CardReferrer', Yii::$app->request->referrer);
    }

    public function getCurrentUrl()
    {
        $session = Yii::$app->session;
        if ($session['CardReferrer'])
            return $session['CardReferrer'];

        return 'index';
    }
}
