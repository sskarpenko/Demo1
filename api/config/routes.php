<?php
return [
    'POST v1/login' => '/v1/login/login',
    'POST v1/register' => '/v1/register/register',

    'GET v1/<controller:[\w-]+>/<id:\d+>' => 'v1/<controller>/view',
    'POST v1/<controller:[\w-]+>/<action:[\w-]+>/<id:\d+>' => 'v1/<controller>/<action>',
    'DELETE v1/<controller:[\w-]+>/delete/<id:\d+>' => 'v1/<controller>/delete',
];