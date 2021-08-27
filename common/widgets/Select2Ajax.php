<?php
namespace common\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;
use kartik\select2\Select2;

class Select2Ajax extends Select2
{
    public $minimumInputLength = 3;

    public $ajaxOptions = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!isset($this->ajaxOptions['url'])) {
            throw new InvalidConfigException("\"url\" property must be specified.");
        }

        if (!isset($this->ajaxOptions['initValueText'])) {
            throw new InvalidConfigException("\"initValueText\" property must be specified.");
        }

        $errorLoading = Yii::t('app', 'Ожидание результата ...');
        $loadingMore = Yii::t('app', 'Загрузка ...');
        $noResults = Yii::t('app', 'Результаты не найдены');
        $searching = Yii::t('app', 'Поиск ...');

        $pluginOptions = [
            'minimumInputLength' => $this->ajaxOptions['minimumInputLength'],
            'language' => [
                'errorLoading' => new JsExpression("function () { return '$errorLoading'; }"),
                'inputTooShort' => new JsExpression("function (args) {
                                                          var remainingChars = args.minimum - args.input.length;
                                                          var message = 'Пожалуйста, введите еще хотя бы ' + remainingChars + ' символ';
                                                          switch (remainingChars) {
                                                            case 5: message += 'ов'; break;
                                                            case 4: message += 'а'; break;
                                                            case 3: message += 'а'; break;
                                                            case 2: message += 'а'; break;
                                                            case 1: break;
                                                          }
                                                          
                                                          return message;
                                        }"),
                'loadingMore' => new JsExpression("function () { return '$loadingMore'; }"),
                'noResults' => new JsExpression("function () { return '$noResults'; }"),
                'searching' => new JsExpression("function () { return '$searching'; }")
            ],
            'ajax' => [
                'url' => $this->ajaxOptions['url'],
                'dataType' => 'json',
                'data' => new JsExpression('function(params) { return {q:params.term}; }')
            ],
            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
            'templateResult' => new JsExpression('function(data) { return data.text; }'),
            'templateSelection' => new JsExpression('function (data) { return data.text; }')
        ];

        $this->pluginOptions = array_replace($this->pluginOptions, $pluginOptions);

        $this->initValueText = $this->ajaxOptions['initValueText']; // set the initial display text

        parent::run();
    }
}