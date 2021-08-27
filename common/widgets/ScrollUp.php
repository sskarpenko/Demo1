<?php

namespace common\widgets;

use yii\base\Widget;

class ScrollUp extends Widget
{
    public function run()
    {
        return $this->render('scroll_up', []);
    }
}