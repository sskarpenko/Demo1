<h1 align="center">Цифровой ассистент персонала "Центр"</h1>

Система разработана с использованием фреймворка yii2

Для запуска потребуются:
 * nginx
 * PHP>=7.2
 * Postgres>=11.0
 * composer

Система содержит 2 модуля:
* frontend
* api

В nginx необходимо настроить виртуальные хосты на папки 
* frontend/web
* api/web

Пример настройки виртуальных хостов nginx и начальной (./yii init) инициализации  находится по адресу [Preparing application!](https://www.yiiframework.com/extension/yiisoft/yii2-app-advanced/doc/guide/2.0/en/start-installation#preparing-application)

СТРУКТУРА ПАПОК
-------------------

```
common
    config/              содержит общие настройки для всех модулей
console
    config/              содержит настройки для консольных приложений
    controllers/         содержит классы контроллеров (для комманд)
    migrations/          содержит миграции БД
backend
    на данный момент не используется
api
    assets/              содержит скрипты и файлы, например изображения
    config/              содержит настройки для api приложения
    controllers/         содержит классы Api контролллеров
    models/              содержит классы моделей для api
    web/                 точка входа для REST Api приложения
frontend
    assets/              содержит скрипты и файлы, например JavaScript и CSS
    config/              содержит настройки для frontend приложения
    controllers/         содержит классы Web контролллеров
    models/              содержит классы моделей для frontend
    views/               содержит файлы view для веб приложения
    web/                 точка входа для Web приложения
vendor/                  папка содержит зависимые библиотеки (появится после запуска composer install)
```
"# demo" 
"# demo" 
"# Demo" 
