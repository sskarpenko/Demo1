<?php
namespace common\helpers;

use Yii;
use yii\web\Response;

class WebHelper
{
    /**
     * Функция проверяет доступность web-ресурса
     * @param $url
     * @return mixed
     */
    protected static function checkURL($url) {
        if (strpos($url,'https://') === false){
            $url = 'https://' . $url;
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);

        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $retcode;
    }

    /**
     * Функция возвращает результат доступности web-ресурса
     * @param $url
     * @return bool
     */
    public static function checkOKURL($url) {
        $retcode = self::checkURL($url);

        if ($retcode == 200) {
            return true;
        } else {
            $message = 'Недоступен узел ' . $url;
            Yii::error($message);

            return false;
        }
    }
}