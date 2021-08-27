<?php
use yii\helpers\Html;

$link = Yii::$app->urlManager->createAbsoluteUrl(['/site/invite-stage', 'invite' => $model->email_code]);
?>

<p>Сервис "Цифровой помощник"</p>
<p>Ссылка на страницу подтверждения <?= Html::a(Html::encode($descLink), $link) ?></p>
