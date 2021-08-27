<?php

namespace common\models;

use yii\data\ActiveDataProvider;


class ActiveDataProviderPpu extends ActiveDataProvider
{
    public function init(){
        $this->pagination->pageSizeLimit = [1,500];
        parent::init();
    }
}
