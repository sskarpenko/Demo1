<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\UserChangePass;
use common\helpers\MailHelper;
use DateTime;

class RecoveryController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'recovery-pass01' => ['POST'],
                    'recovery-pass02' => ['POST'],
                ],
            ]
        ], $behaviors);

        return $behaviors;
    }

    public function actionRecoveryPass01()
    {
        $today = new DateTime();
        $today->modify('+ 1 day');

        $post = Yii::$app->request->getBodyParams();
        if (isset($post['username']) && ($post['username'])) {
            $user = User::find()->where(['username' => $post['username'], 'status' => User::STATUS_ACTIVE])->one();
            if ($user) {
                $model = new UserChangePass();
                $model->email = $user->email;
                $model->active_till = $today->format('Y-m-d H:i:s');
                $model->generateEmailCode();
                $model->type_operation = UserChangePass::TYPE_CHANGE_PASSWORD;

                if (MailHelper::checkAvailableMailServer()) {
                    UserChangePass::deleteAll(['email' => $model->email]);
                    if ($model->sendMail()) {
                        if ($model->save()) {
                            return true;
                        } else {
                            return $this->responseErrors($model->errors);
                        }
                    }
                } else {
                    return 'Недоступен узел';
                }
            } else {
                return 'Недоступен узел';
            }
        }

        return $this->responseErrors('Ошибка POST параметров');
    }

    public function actionRecoveryPass02()
    {
        $today = new DateTime();

        $post = Yii::$app->request->getBodyParams();
        if (isset($post['username']) && isset($post['email_code']) && isset($post['password']) && ($post['username']) && $post['email_code'] && $post['password']) {
            $user = User::find()->where(['username' => $post['username'], 'status' => User::STATUS_ACTIVE])->one();
            if ($user) {
                $model = UserChangePass::find()->where(['and', ['email' => $user->email], ['email_code' => $post['email_code']], ['>=', 'active_till', $today->format('Y-m-d H:i:s')], ['email_code_valid' => 0]])->one();
                if ($model) {
                    $user->setPassword($post['password']);
                    $user->generateAccessToken();
                    if ($user->save()) {
                        $model->delete();
                        $user->refresh();
                        return $user->access_token;
                    } else {
                        return $this->responseErrors($user->errors);
                    }
                } else {
                    return 'Почта не подтверждена';
                }
            } else {
                return 'Не верный пользователь';
            }
        }

        return $this->responseErrors('Ошибка POST параметров');
    }

    protected function responseErrors($errors)
    {
        Yii::$app->response->setStatusCode(422);
        return [
            'errors' => $errors
        ];
    }
}
