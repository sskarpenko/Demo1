<?php
namespace api\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class BaseApiController extends Controller
{
    /**
     * Reformat response
     * @link https://www.yiiframework.com/doc/guide/2.0/en/runtime-handling-errors#customizing-error-response-format
     * @param $event
     */
    public static function responseBeforeSend($event)
    {
        $response = $event->sender;
        if ($response->data !== null && !empty(Yii::$app->request->get('suppress_response_code'))) {
            $response->data = [
                'success' => $response->isSuccessful,
                'data' => $response->data,
            ];
            $response->statusCode = 200;
        }
    }

    /**
     * Response with error status code 422
     * @link https://github.com/yiisoft/yii2/blob/master/docs/guide-ru/rest-error-handling.md
     * @param $errors array errors array
     * @return array array to be published
     */
    protected function responseErrors($errors)
    {
        Yii::$app->response->setStatusCode(422);
        return [
            'errors' => $errors
        ];
    }

    public function behaviors(){
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
            ]
        ];
    }

    /**
     * Send image
     * @param $imgFullPath
     * @throws \yii\web\ServerErrorHttpException
     */
    public function responseImage($imgFullPath)
    {
        $response = Yii::$app->getResponse();
        $response->headers->set('Content-Type', mime_content_type($imgFullPath));
        $response->format = Response::FORMAT_RAW;
        if ( !is_resource($response->stream = fopen($imgFullPath, 'r')) ) {
            throw new ServerErrorHttpException('File not found');
        }

        return $response->send();
    }
}
