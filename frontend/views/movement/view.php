<?php

use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\AuthHelper;
use kartik\bs4dropdown\Dropdown;

//echo '<pre>'.print_r($model, true).'</pre>';
?>

<div>
    <?php $form = ActiveForm::begin(
        [
            'id' => 'movement-form-id',
            'enableAjaxValidation' => true,
        ]
    ); ?>

    <table>
        <tr>
            <td width="100%">
                <?php echo '<h3>' . Html::label($title) . '</h3>'; ?>
            </td>

            <td>
                <div class="d-flex align-items-start justify-content-end pb-1">
                    <?= Html::button('<span class="fas fa-chevron-left"></span>', [
                        'id' => 'prev-btn',
                        'value' => Url::toRoute(['movement/view']),
                        'title' => Yii::t('app', 'Показать предыдущую запись'),
                        'name' => Yii::t('app', 'Просмотр перемещения'),
                        'class' => 'btn btn-modal-actions showModalButton',
                        'data' => [
                            'params' => [
                                'id' => $model['id'],
                            ]
                        ],
                        'disabled' => true
                    ]); ?>

                    <?= Html::button('<span class="fas fa-chevron-right"></span>', [
                        'id' => 'next-btn',
                        'value' => Url::toRoute(['movement/view']),
                        'title' => Yii::t('app', 'Показать следующую запись'),
                        'name' => Yii::t('app', 'Просмотр перемещения'),
                        'class' => 'btn btn-modal-actions showModalButton',
                        'data' => [
                            'params' => [
                                'id' => $model['id'],
                            ]
                        ],
                        'disabled' => true
                    ]); ?>

                    <div class="dropdown dropdown-modal-actions dropleft">
                        <?php
                            echo Html::button('<i class="fa fa-ellipsis-h text-secondary"></i>', [
                                'id' => 'dropdownMenuButton',
                                'class' => 'btn btn-modal-actions',
                                'title' => Yii::t('app', 'Действия'),
                                'data-toggle' => 'dropdown',
                                'aria-haspopup' => 'true',
                                'aria-expanded' => 'false'
                            ]);

                            echo Dropdown::widget([
                                'items' => [
                                    [
                                        'label' => Yii::t('app', 'Удалить'),
                                        'url' => Url::toRoute(['delete', 'id' => $model['id']]),
                                        'linkOptions' => [
                                            'data' => [
                                                'confirm' => Yii::t('app', 'Вы действительно хотите удалить эту запись?'),
                                                'method' => 'post',
                                            ],
                                        ],
                                    ],
                                    [
                                        'label' => 'Перевести сотрудника',
                                        'url' => '#',
                                        'linkOptions' => ['value'=> Url::to(['/movement/move', 'id' => $model['id']]), 'title' => 'Перевод сотрудника', 'class' => 'modalButton'],
                                    ],
                                    [
                                        'label' => 'Уволить сотрудника',
                                        'url' => Url::to(['/movement/finish', 'id' => $model['id']]),
                                        'linkOptions' => [
                                            'data' => [
                                                'confirm' => Yii::t('app', 'Вы действительно хотите уволить сотрудника?'),
                                                'method' => 'post',
                                            ],
                                        ]
                                    ],
                                ],
                                'options' => [
                                    'aria-labelledby' => 'dropdownMenuButton',
                                ],
                            ]);
                        ?>
                    </div>

                    <?= Html::button('<i class="fa fa-times text-secondary"></i>', [
                        'url' => '#',
                        'class' => 'btn btn-modal-actions hideModalButton',
                        'title' => Yii::t('app', 'Закрыть'),
                    ]) ?>
                </div>
            </td>
        </tr>
    </table>
    <?= $form->errorSummary($model); ?>

    <?php $this->beginBlock('mainData'); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label" for="department_id"><?= $model->getAttributeLabel('card_id') ?></label>
                    <input type="text" id="card_id" class="form-control"
                           title="<?= ($model->card_id) ? $model->card->getCompositeName() : "" ?>"
                           value="<?= ($model->card_id) ? $model->card->getCompositeName() : "" ?>" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label" for="staffpos_id"><?= $model->getAttributeLabel('staffpos_id') ?></label>
                    <input type="text" id="staffpos_id" class="form-control"
                           title="<?= ($model->staffpos_id) ? $model->staffpos->getCompositeName() : "" ?>"
                           value="<?= ($model->staffpos_id) ? $model->staffpos->getCompositeName() : "" ?>" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="begin"><?= $model->getAttributeLabel('begin') ?></label>
                    <input type="text" id="begin" class="form-control"
                           title="<?= ($model->begin) ? Yii::$app->formatter->asDate($model->begin) : "" ?>"
                           value="<?= ($model->begin) ? Yii::$app->formatter->asDate($model->begin) : "" ?>" readonly>

                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="end"><?= $model->getAttributeLabel('end') ?></label>
                    <input type="text" id="end" class="form-control"
                           title="<?= ($model->end) ? Yii::$app->formatter->asDate($model->end) : "" ?>"
                           value="<?= ($model->end) ? Yii::$app->formatter->asDate($model->end) : "" ?>" readonly>

                </div>
            </div>
        </div>
    <?php $this->endBlock('mainData'); ?>

    <?php $this->beginBlock('logs'); ?>
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th class="vertical-center" style="width: 25%"><?= Yii::t('app', 'Дата и время') ?></th>
                    <th class="vertical-center" style="width: 25%"><?= Yii::t('app', 'Пользователь') ?></th>
                    <th class="vertical-center" style="width: 50%"><?= Yii::t('app', 'Изменения') ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($event_logs as $log): ?>
                    <tr>
                        <td class="text-center">
                            <div class="">
                                <?= Yii::$app->formatter->asDatetime($log->created_at) ?>
                            </div>
                        </td>

                        <td>
                            <div class="">
                                <?= Html::encode($log->user->userProfile->getFullName()) ?>
                            </div>
                        </td>

                        <td>
                            <?php
                                $firstComment = $log->comments[0];
                                $cnt = count($log->comments)
                            ?>

                            <?php if ($cnt == 1): ?>
                                <div class="">
                                    <?= Html::encode($firstComment) ?>
                                </div>
                            <?php else: ?>
                                <div class="">
                                    <div class="">
                                        <?= Html::encode($firstComment) ?>
                                    </div>

                                    <div class="">
                                        <?php foreach ($log->comments as $key => $comments): ?>
                                            <?php if ($key > 0): ?>
                                                <div style="padding: 0.5rem 0">
                                                    <?= Html::encode($comments) . '<br>' ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php $this->endBlock('logs'); ?>

    <?php
        if ($model->isNewRecord) {
            echo $this->blocks['mainData'];
        } else {
            if (AuthHelper::canViewLogs()) {
                echo Tabs::widget([
                    'items' => [
                        [
                            'label' => 'Основные данные',
                            'content' => $this->blocks['mainData'],
                            'active' => true
                        ],
                        [
                            'label' => 'История изменений',
                            'content' => $this->blocks['logs'],
                        ],
                    ],
                ]);
            } else {
                echo $this->blocks['mainData'];
            }
        }
    ?>

    <div class="modal-footer">
        <button type="button" class="btn btn-rosatom btn-outline-danger" data-dismiss="modal">Закрыть</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>
