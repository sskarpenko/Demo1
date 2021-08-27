<?php

use yii\bootstrap4\Modal;
use yii\helpers\Html;
use yii\helpers\Url;
use frontend\assets\AppAsset;
use kartik\grid\GridView;

AppAsset::register($this);

$this->title = Yii::t('app', 'Карточки сотрудников');
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
                <?= Html::button(Yii::t('app', 'Добавить карточку'), ['value' => Url::to('/card/create'), 'title' => 'Добавить карточку', 'class' => 'btn btn-rosatom btn-outline-success modalButton']) ?>
                <?= Html::a('Очистить фильтр', ['/card/clear-filter'], ['class' => 'btn btn-rosatom btn-outline-danger']) ?>
            </p>

            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Показать <?=$dataProvider->pagination->pageSize?> <span class="caret"></span>
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
                'attribute' => 'stabnum',
                'value' => function ($data) {
                    return Html::a($data['stabnum'], '#', ['value' => Url::to(['/card/update', 'id' => $data['id']]), 'title' => Yii::t('app', 'Редактирование карточки'), 'class' => 'showModalButton']);
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'ntext',
                'attribute' => 'secondname',
                'value' => function ($data) {
                    return $data['secondname'];
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 25%; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'raw',
                'attribute' => 'firstname',
                'value' => function ($data) {
                    return Html::a($data['firstname'], '#', ['value' => Url::to(['/card/update', 'id' => $data['id']]), 'title' => Yii::t('app', 'Редактирование карточки'), 'class' => 'showModalButton']);
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 25%; vertical-align: middle;'
                ],
            ],
            [
                'format' => 'ntext',
                'attribute' => 'thirdname',
                'value' => function ($data) {
                    return $data['thirdname'];
                },
                'contentOptions' => [
                    'style' => 'white-space: normal; width: 25%; vertical-align: middle;'
                ],
            ],
/*            [
                'class' => 'kartik\grid\ActionColumn',
                'hiddenFromExport' => true,
                'contentOptions' => [
                    'style' => 'width:80px;  min-width:80px; max-width:80px;'
                ],

                'buttons' => [
                    'all' => function ($url, $model) {
                        return '<div class="btn-group">'.ButtonDropdown::widget([
                            'label' => '...',
                            'options' => ['class' => 'btn btn-link dropleft'],
                            'dropdown' => [
                                'options' => ['class' => 'dropdown-menu'],
                                'items' => [
                                    [
                                        'label' => 'Редактировать',
                                        'url' => '#',
                                        'linkOptions' => ['value'=> Url::to(['/card/update', 'id' => $model['id']]), 'title' => 'Редактирование карточки','class'=>'modalButton'],
                                    ],
                                    [
                                        'label' => 'Удалить',
                                        'url' => Url::to(['/card/delete', 'id' => $model['id']]),
                                        'linkOptions' => [
                                            'data' => [
                                                'confirm' => Yii::t('app', 'Вы действительно хотите удалить карточку?'),
                                                'method' => 'post',
                                            ],
                                        ]
                                    ],

                                ],
                            ],
                        ]).'</div>';

                    },
                ],
                'template' => '{all}'
            ],*/
        ],
    ]); ?>
</div>
