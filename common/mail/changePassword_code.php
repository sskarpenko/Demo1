<?php
use yii\helpers\Html;

$link = Yii::$app->urlManager->createAbsoluteUrl(['/site/change-pass02', 'email' => $model->email]);
?>

<p>Сервис "Цифровой помощник"</p>
<p>Код подтверждения почтового ящика для смены пароля: <?= $model->email_code?>.</p>
<p>Ссылка на страницу подтверждения <?= Html::a(Html::encode($descLink), $link) ?></p>
