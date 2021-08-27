<?php
namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $sourcePath = '@frontend/assets';

    public $css = [
        'css/adminlte.min.css',
        'css/fonts/rosatom/Rosatom-Regular_stylesheet.css',
        'css/rosatom.css',
        'css/scrollup.css',
    ];
    public $js = [
        'js/scrollup.js',
        'js/mqttws31.js',
        'js/ra.js',
        'js/modal.js',
        'js/RecordRTC.min.js',
        'js/voice-recorder.js',
    ];
    // Нужно заранее включать все используемые assets, иначе при ajax обновлениях случаются проблемы
    // https://stackoverflow.com/questions/43166647/synchronous-ajax-error-with-yii2-activeform
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
        'yii\bootstrap4\BootstrapPluginAsset',
        'frontend\assets\FontAwesomeAsset',
        'yii\web\JqueryAsset',
        'kartik\sidenav\SideNavAsset',
        'yii\widgets\ActiveFormAsset',
        'yii\widgets\MaskedInputAsset',
        'yii\validators\ValidationAsset',
        'kartik\select2\Select2Asset',
        'kartik\depdrop\DepDropAsset',
        'kartik\depdrop\DepDropExtAsset',
        'kartik\grid\GridViewAsset',
        'kartik\dialog\DialogAsset',
        'yii\grid\GridViewAsset',
        'kartik\dialog\DialogBootstrapAsset',
        'kartik\dialog\DialogYiiAsset',
        'kartik\grid\GridExportAsset',
        'kartik\grid\GridResizeColumnsAsset',
        'kartik\select2\ThemeKrajeeBs4Asset',
        'kartik\select2\Select2KrajeeAsset',
        'kartik\base\WidgetAsset',
        'dmstr\adminlte\web\AdminLteAsset',
    ];

    public $publishOptions = [
        'forceCopy' => YII_ENV_DEV
    ];
}
