<?php

use yii\bootstrap4\Modal;
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\FilterHelper;
use frontend\assets\AppAsset;
use kartik\grid\GridView;
use kartik\select2\Select2;

AppAsset::register($this);

$this->title = Yii::t('app', 'Перемещения');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="flex-main-content">
    <?php
        Modal::begin([
            'title' => '<h3 id="modalHeader"></h3>',
            'id' => 'modal',
            'size' => 'modal-lg',
        ]);
        echo "<div id='modalContent'></div>";
        Modal::end();
    ?>

    <div class="d-flex flex-row grid-toolbar">
        <div class="col-md-4">
            <h3><?= Html::encode($this->title) ?></h3>
        </div>

        <div class="col-md-8 d-flex justify-content-end align-items-end">
            <p>
                <?= Html::button(Yii::t('app', 'Добавить перемещение'), ['value' => Url::to('/movement/create'), 'title' => 'Добавить перемещение', 'class' => 'btn btn-rosatom btn-outline-success modalButton']) ?>
                <?= Html::a('Очистить фильтр', ['/movement/clear-filter'], ['class' => 'btn btn-rosatom btn-outline-danger']) ?>
            </p>

            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Показать <?= $dataProvider->pagination->pageSize ?> <span class="caret"></span>
                </button>

                <ul class="dropdown-menu exp-btn">
                    <li><?= Html::a(20, Url::current(['per-page' => 20]), ['class' => 'dropdown-item']) ?></li>
                    <li><?= Html::a(50, Url::current(['per-page' => 50]), ['class' => 'dropdown-item']) ?></li>
                    <li><?= Html::a(100, Url::current(['per-page' => 100]), ['class' => 'dropdown-item']) ?></li>
                </ul>
            </div>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'responsive' => false,
        'pager' => [
            'class' => '\common\widgets\LinkPagerWalive',
        ],
        'options' => ['class' => 'table-sm grid-view'],
        'columns' => [
            [
                'format' => 'raw',
                'attribute' => 'card_name',
                'filter' => Select2::widget([
                    'theme' => Select2::THEME_DEFAULT,
                    'model' => $searchModel,
                    'attribute' => 'card_ids',
                    'value' => $searchModel['card_ids'],
                    'data' => $card,
                    'options' => [
                        'placeholder' => Yii::t('app', 'Выберите ...'),
                        'multiple' => true,
                        'class' => 'label-warning'
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function ($data) {
                    return Html::a($data['card_name'], '#', ['value' => Url::to(['/movement/view', 'id' => $data['id']]), 'title' => 'Просмотр перемещения', 'class' => 'showModalButton']);
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 25%; text-align: center; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'raw',
                'attribute' => 'staffpos_name',
                'filter' => Select2::widget([
                    'theme' => Select2::THEME_DEFAULT,
                    'model' => $searchModel,
                    'attribute' => 'staffpos_ids',
                    'value' => $searchModel['staffpos_ids'],
                    'data' => $staffpos,
                    'options' => [
                        'placeholder' => Yii::t('app', 'Выберите ...'),
                        'multiple' => true,
                        'class' => 'label-warning'
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function ($data) {
                    return $data['staffpos_name'];
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; text-align: center; width: 25%; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'raw',
                'attribute' => 'parent_staffpos_name',
                'filter' => Select2::widget([
                    'theme' => Select2::THEME_DEFAULT,
                    'model' => $searchModel,
                    'attribute' => 'parent_staffpos_ids',
                    'value' => $searchModel['parent_staffpos_ids'],
                    'data' => $staffpos,
                    'options' => [
                        'placeholder' => Yii::t('app', 'Выберите ...'),
                        'multiple' => true,
                        'class' => 'label-warning'
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function ($data) {
                    return ($data['staffpos_parent_id']) ? $data['parent_staffpos_name'] : "";
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; text-align: center; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'raw',
                'attribute' => 'begin',
                'value' => function ($data) {
                    return Html::a($data['begin'],  '#', ['value' => Url::to(['/movement/view', 'id' => $data['id']]), 'title' => 'Просмотр перемещения', 'class' => 'showModalButton']);
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 10%; text-align: center; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'raw',
                'attribute' => 'end',
                'filter' => Select2::widget([
                    'theme' => Select2::THEME_DEFAULT,
                    'model' => $searchModel,
                    'attribute' => 'actual_date_filter',
                    'value' => $searchModel['actual_date_filter'],
                    'data' => FilterHelper::getDataList(),
                    'options' => [
                        'placeholder' => Yii::t('app', 'Выберите ...'),
                        'multiple' => false,
                        'class' => 'label-warning'
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function ($data) {
                    return $data['end'];
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 10%; text-align: center; vertical-align: middle;'
                ],
            ],
        ],
    ]); ?>
</div>
