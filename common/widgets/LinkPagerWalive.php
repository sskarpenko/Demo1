<?php
namespace common\widgets;

use yii\widgets\LinkPager;

/**
 * Created by PhpStorm.
 * User: SS
 * Date: 11.02.2020
 * Time: 21:17
 */
class LinkPagerWalive extends LinkPager
{
    public $maxButtonCount = 5;
    public $firstPageLabel = 'Первая';
    public $lastPageLabel = 'Последняя';
    public $nextPageLabel = '&raquo;';
    public $prevPageLabel = '&laquo;';
}
?>