<?php
namespace common\helpers;

use Yii;
use Swift_TransportException;

class MailHelper
{
    const REGISTRATION = 10;
    const CHANGE_PASSWORD = 20;
    const INVITE = 30;
    const HELLO = 40;

    /**
     * Функция проверяет доступность почтового сервера
     * @return bool
     */
    public static function checkAvailableMailServer()
    {
        if (Yii::$app->mailer->useFileTransport) {
           return true;
        }

        $transport_host = Yii::$app->mailer->transport->getHost();
        if (isset($transport_host) && (WebHelper::checkOKURL($transport_host))) {
            return true;
        } else {
            return false;
        }
    }

    public static function preRegСonfirmationCode($model, $type_action)
    {
        if (isset($model->email)) {
            switch ($type_action) {
                case self::REGISTRATION:
                    $fileName = 'registration_code.php';
                    $descLink = 'Регистрация нового пользователя. Код подтверждения';
                    break;

                case self::CHANGE_PASSWORD:
                    $fileName = 'changePassword_code.php';
                    $descLink = 'Восстановление пароля пользователя. Код подтверждения';
                    break;

                case self::INVITE:
                    $fileName = 'invite_code.php';
                    $descLink = 'Приглашение пользователя. Код подтверждения';
                    break;

                case self::HELLO:
                    if (!Yii::$app->params['hello_mail_after_first_join']) {
                        return false;
                    }

                    $fileName = 'hello_registration.php';
                    $descLink = 'Личный кабинет';
                    break;
            }

            $to = [];
            $to[$model->email] = $model->email;
            $result_send = self::sendNotification($model, $descLink, $fileName, 'Код подтверждения сервиса "Цифровой помощник"', $to);

            return $result_send;
        }
    }

    protected static function sendNotification($model, $descLink, $fileName, $subject, $to)
    {
        $message = Yii::$app->mailer->compose($fileName, [
            'model' => $model,
            'descLink' => $descLink,
        ])
            ->setSubject(Yii::t('app', $subject))
            ->setFrom(Yii::$app->params['fromMailUser'])
            ->setTo($to);

        try {
            $result = $message->send();
        } catch (Swift_TransportException $e) {
            switch ($e->getCode()) {
                case 554:
                    Yii::$app->session->addFlash('error', Yii::t('app', 'Ошибка при отправке почты. Нет действительных получателей'));
                    break;

                default:
                    Yii::$app->session->addFlash('error', Yii::t('app', 'Ошибка при отправке почты'));
                    break;
            }

            $error_message = $e->getMessage() . '. Почтовый адрес: ' . $message;
            Yii::error('Ошибка при отправке почты: ' . $error_message);
            $result = false;
        }

        return $result;
    }

    /*
     * Функция отправляет почту техподдержке сервиса на лету (не загружая приложенные файлы на сервер)
     */
    public static function sendQuestion($model)
    {
        $message = Yii::$app->mailer->compose('send_question.php', [
            'model' => $model,
        ])
            ->setSubject('Обращение пользователя')
            ->setFrom(Yii::$app->params['fromMailUser'])
            ->setTo(Yii::$app->params['email_support']);

        foreach ($model->requestFiles as $file) {
            $content_file = file_get_contents($file->tempName);
            $message->attachContent($content_file, [
               'fileName' => $file->name,
               'contentType' => $file->type,
            ]);
        }

        if (!$message->send()) {
            return false;
        }

        return true;
    }
}
