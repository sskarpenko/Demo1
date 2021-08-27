<?php
namespace frontend\controllers;

use common\models\User;
use common\models\UserCompanies;
use Yii;
use yii\bootstrap4\ActiveForm;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use common\models\LoginForm;
use common\models\Movement;
use common\models\SendQuestionModel;
use common\models\UserChangePass;
use common\models\UserInvite;
use common\models\UserPreRegister;
use common\helpers\MailHelper;
use DateTime;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
//                'only' => ['logout', 'index', 'class-menu', 'login'],
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'terms', 'mqtt-data', 'reg-stage01', 'reg-stage02', 'reg-stage03', 'change-pass01', 'change-pass02', 'change-pass03', 'invite-stage'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout', 'index', 'class-menu', 'change-password', 'mqtt-data', 'invite-stage', 'not-found', 'send-question', 'error'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    /*public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }*/

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $user = User::findOne(Yii::$app->user->id);

        $statusMovement = Movement::getDataByCard(Yii::$app->user->identity->userProfile->card_id) ? true : false;
        if (!$statusMovement) {
            Yii::$app->session->addFlash('warning', Yii::t('app', 'Текущая учётная запись в настоящий момент не привязана к структуре организации. Обратитесь к администратору Вашей организации.'));
        }

        return $this->render('index');
    }

    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        switch ($exception->statusCode) {
            case 404:
                if (Yii::$app->user->isGuest) {
                    $this->layout = 'main-login';
                }
                return $this->render('notfound');

            default:
                return $this->render('error', [
                    'name' => 'Регламентные работы',
                    'message' => 'Недоступен узел',
                ]);
        }
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionRegStage01()
    {
        $today = new DateTime();
        $today->modify('+ 1 day');
        $model = new UserPreRegister();
        $model->active_till = $today->format('Y-m-d H:i:s');
        $model->generateEmailCode();
        $model->type_operation = UserPreRegister::TYPE_REGISTER;

        if ($model->load(Yii::$app->request->post())) {
            // Проверим простую доступность почтового сервера
            if (MailHelper::checkAvailableMailServer()) {
                UserPreRegister::deleteAll(['email' => $model->email]);
                if ($model->sendMail(MailHelper::REGISTRATION)) {
                    if ($model->save()) {
                        Yii::$app->session->addFlash('success', Yii::t('app', 'Код подтверждения отправлен на почту'));
                        return $this->redirect(['/site/reg-stage02', 'email' => $model->email]);
                    }
                }
            } else {
                return $this->render('register_error', [
                    'name' => 'Регламентные работы',
                    'message' => 'Недоступен узел',
                ]);
            }
        }

        return $this->render('registr_stage01', [
            'model' => $model,
        ]);
    }

    public function actionRegStage02($email)
    {
        $today = new DateTime();
        $model = UserPreRegister::find()->where(['and', ['email' => $email], ['>=', 'active_till', $today->format('Y-m-d H:i:s')], ['email_code_valid' => 0]])->one();
        if ($model) {
            if ($model->load(Yii::$app->request->post())) {
                if ($model->email_code == $model->tmp_email_code) {
                    $model->email_code_valid = 1;
                    if ($model->save()) {
                        Yii::$app->session->addFlash('success', Yii::t('app', 'Почта подтверждена'));
                        return $this->redirect(['/site/reg-stage03', 'email' => $model->email]);
                    }
                } else {
                    Yii::$app->session->addFlash('error', Yii::t('app', 'Почта не подтверждена'));
                    return $this->redirect('/site/login');
                }
            }

            return $this->render('registr_stage02', [
                'model' => $model,
            ]);
        } else {
            Yii::$app->session->addFlash('error', Yii::t('app', 'Код активации просрочен или использован'));
            return $this->redirect('/site/reg-stage01');
        }
    }

    public function actionRegStage03($email)
    {
        $today = new DateTime();
        $model = UserPreRegister::find()->where(['and', ['email' => $email], ['>=', 'active_till', $today->format('Y-m-d H:i:s')], ['email_code_valid' => 1]])->one();
        if ($model) {
            $model->scenario = UserPreRegister::SCENARIO_REGISTER;

            if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if ($model->load(Yii::$app->request->post()) && $model->save(false) && $model->register()) {
                UserPreRegister::deleteAll(['email' => $model->email]);
                Yii::$app->session->addFlash('success', Yii::t('app', 'Пользователь создан'));
                $modelLogin = new LoginForm();
                $modelLogin->username = $model->email;
                $modelLogin->password = $model->password;
                if ($modelLogin->login()) {
                    return $this->goBack();
                } else {
                    return $this->redirect('/site/login');
                }
            }

            return $this->render('registr_stage03', [
                'model' => $model,
            ]);
        }

        return $this->redirect('/site/login');
    }

    public function actionChangePass01()
    {
        $today = new DateTime();
        $today->modify('+ 1 day');

        $model = new UserChangePass();
        $model->active_till = $today->format('Y-m-d H:i:s');
        $model->generateEmailCode();
        $model->type_operation = UserChangePass::TYPE_CHANGE_PASSWORD;

        if ($model->load(Yii::$app->request->post())) {
            // Проверим простую доступность почтового сервера
            if (MailHelper::checkAvailableMailServer()) {
                UserChangePass::deleteAll(['email' => $model->email]);
                if ($model->sendMail()) {
                    if ($model->save()) {
                        Yii::$app->session->addFlash('success', Yii::t('app', 'Код подтверждения отправлен на почту'));
                        return $this->redirect(['/site/change-pass02', 'email' => $model->email]);
                    }
                }
            } else {
                return $this->render('register_error', [
                    'name' => 'Регламентные работы',
                    'message' => 'Недоступен узел',
                ]);
            }
        }

        return $this->render('change_pass_stage01', [
            'model' => $model,
        ]);
    }

    public function actionChangePass02($email)
    {
        $today = new DateTime();
        $model = UserChangePass::find()->where(['and', ['email' => $email], ['>=', 'active_till', $today->format('Y-m-d H:i:s')], ['email_code_valid' => 0]])->one();
        if ($model) {
            if ($model->load(Yii::$app->request->post())) {
                if ($model->email_code == $model->tmp_email_code) {
                    $model->email_code_valid = 1;
                    if ($model->save()) {
                        Yii::$app->session->addFlash('success', Yii::t('app', 'Почта подтверждена'));
                        return $this->redirect(['/site/change-pass03', 'email' => $model->email]);
                    }
                } else {
                    Yii::$app->session->addFlash('error', Yii::t('app', 'Почта не подтверждена'));
                    return $this->redirect('/site/login');
                }
            }

            return $this->render('change_pass_stage02', [
                'model' => $model,
            ]);
        } else {
            Yii::$app->session->addFlash('error', Yii::t('app', 'Код активации просрочен или использован'));
            return $this->redirect('/site/reg-stage01');
        }
    }

    public function actionChangePass03($email)
    {
        $today = new DateTime();
        $model = UserChangePass::find()->where(['and', ['email' => $email], ['>=', 'active_till', $today->format('Y-m-d H:i:s')], ['email_code_valid' => 1]])->one();
        if ($model) {
            $model->scenario = UserChangePass::SCENARIO_CHANGE_PASS;

            if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if ($model->load(Yii::$app->request->post()) && $model->save(false)) {
                if ($model->changePass()) {
                    if ($model->type_operation == UserInvite::TYPE_INVITE) {
                        $model->sendMail();
                    }

                    UserChangePass::deleteAll(['email' => $model->email]);
                    Yii::$app->session->addFlash('success', Yii::t('app', 'Пароль изменен'));
                    $modelLogin = new LoginForm();
                    $modelLogin->username = $model->email;
                    $modelLogin->password = $model->password;
                    if ($modelLogin->login()) {
                        return $this->goBack();
                    } else {
                        return $this->redirect('/site/login');
                    }
                } else {
                    return $this->redirect('/site/login');
                }
            }

            return $this->render('change_pass_stage03', [
                'model' => $model,
            ]);
        }

        return $this->redirect('/site/login');
    }

    public function actionInviteStage()
    {
        if (Yii::$app->user->isGuest) {
            $request = Yii::$app->request;
            $invite_code = $request->get('invite');
            $today = new DateTime();
            $model = UserInvite::find()->where(['and', ['email_code' => $invite_code], ['>=', 'active_till', $today->format('Y-m-d H:i:s')]])->one();

            if ($model) {
                $model->email_code_valid = 1;
                if ($model->save()) {
                    Yii::$app->session->addFlash('success', Yii::t('app', 'Приглашение принято'));
                    return $this->redirect(['/site/change-pass03', 'email' => $model->email]);
                }
            } else {
                return $this->render('notfound');
            }
        } else {
            return $this->redirect('/site/not-found');
        }
    }

    public function actionNotFound()
    {
        return $this->render('notfound');
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionClassMenu()
    {
        $session = Yii::$app->session;
        if ($session->has('class_menu')) {
            $session->remove('class_menu');
        } else {
            $session->set('class_menu', 'active');
        }

        return true;
    }

    public function actionSendQuestion()
    {
        $model = new SendQuestionModel();
        $model->user = Yii::$app->user->identity->username;

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->requestFiles = UploadedFile::getInstances($model, 'requestFiles');

            if ($model->validate() && $model->sendQuestionToSupport()) {
                Yii::$app->session->addFlash('success', 'Обращение успешно отправлено');
                $url = $this->getCurrentUrl();
                return $this->redirect($url);
            } else {
                Yii::$app->session->addFlash('error', 'Обращение не отправлено. Повторите позднее');
            }
        }
        $this->setCurrentUrl();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('send_question', [
                'model' => $model,
            ]);
        } else {
            return $this->render('send_question', [
                'model' => $model
            ]);
        }
    }

    public function actionMqttData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $uuid = '';
        $mqttUser = '';
        $mqttPass = '';

        if (!Yii::$app->user->isGuest) {
            $profile = Yii::$app->user->identity->userProfile;
            $uuid = $profile->uuid;
            if ($uuid) {
                $mqttUser = Yii::$app->params['mqttUser'];
                $mqttPass = Yii::$app->params['mqttPass'];
            }
        }

        return ['user_uuid' => $uuid, 'mqttUser' => $mqttUser, 'mqttPass' => $mqttPass];
    }

    public function actionTerms()
    {
        return Yii::$app->response->sendFile(Yii::getAlias('@frontend/assets/downloads/terms.pdf'), 'terms.pdf',['mimeType'=>'application/pdf']);
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
