<?php
/**
 * Created by PhpStorm.
 * User: SS
 * Date: 31.05.2021
 * Time: 21:18
 */

namespace common\helpers;


class FilterHelper
{
    const ALL_DATE = 0;
    const ACTUAL_DATE = 1;

    public static function getDataList()
    {
        return [
            '1' => 'Только актуальные',
        ];
    }

}