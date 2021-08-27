<?php

use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Movement;
use common\helpers\AuthHelper;
use kartik\bs4dropdown\Dropdown;
use kartik\select2\Select2;

//echo '<pre>'.print_r($model, true).'</pre>';
if ($model->isNewRecord) {
    $readonly = false;
    $placeholder = 'Введите почту';
} else {
    $readonly = true;
    $placeholder = '';
}
?>

<div>
    <?php $form = ActiveForm::begin(
        [
            'id' => 'card-form-id',
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
                    <?php if (!$model->isNewRecord): ?>
                        <?= Html::button('<span class="fas fa-chevron-left"></span>', [
                            'id' => 'prev-btn',
                            'value' => Url::toRoute(['card/update']),
                            'title' => Yii::t('app', 'Показать предыдущую запись'),
                            'name' => Yii::t('app', 'Редактировать карточку сотрудника'),
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
                            'value' => Url::toRoute(['card/update']),
                            'title' => Yii::t('app', 'Показать следующую запись'),
                            'name' => Yii::t('app', 'Редактировать карточку сотрудника'),
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
                                    ],
                                    'options' => [
                                        'aria-labelledby' => 'dropdownMenuButton',
                                    ],
                                ]);
                            ?>
                        </div>
                    <?php endif; ?>

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
                <?= $form->field($model, 'email')->textInput(['value' => $email, 'readonly' => $readonly, 'placeholder' => $placeholder]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'stabnum')->textInput(['placeholder' => 'Табельный номер']) ?>
            </div>

            <?php if (AuthHelper::canEditCompanyId() && Yii::$app->params['changeCompany']): ?>
                <div class="col-md-8">
                    <?= $form->field($model, 'company_id')->widget(Select2::className(), [
                        'data' => $company,
                        'maintainOrder' => true,
                        'options' => [
                            'id' => 'company-id',
                            'placeholder' => 'Выберите из списка',
                        ],
                        'pluginOptions' => [
                            'allowClear' => true
                        ],
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'secondname')->textInput(['placeholder' => 'Фамилия']) ?>
            </div>

            <div class="col-md-4">
                <?= $form->field($model, 'firstname')->textInput(['placeholder' => 'Имя']) ?>
            </div>

            <div class="col-md-4">
                <?= $form->field($model, 'thirdname')->textInput(['placeholder' => 'Отчество']) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'role')->widget(Select2::class, [
                    'data' => $roles,
                    'maintainOrder' => true,
                    'options' => [
                        'id' => 'role',
                        'placeholder' => 'Выберите из списка',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]) ?>
            </div>
        </div>

        <?php if (!$model->isNewRecord): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label" for="department_name_id"><?= Yii::t('app', 'Подразделение') ?></label>
                        <input type="text" id="department_name_id" class="form-control" readonly value="<?= (Movement::getDataByCard($model->id))? Html::encode(Movement::getDataByCard($model->id)['department_full_name']): "Не указано" ?>">
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label" for="profession_name_id"><?= Yii::t('app', 'Профессия') ?></label>
                        <input type="text" id="profession_name_id" class="form-control" readonly value="<?= (Movement::getDataByCard($model->id))? Html::encode(Movement::getDataByCard($model->id)['profession_name']): "Не указано" ?>">
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        <?= Html::submitButton(Yii::t('app', 'Сохранить'), ['class' => 'btn btn-rosatom btn-outline-success']) ?>
        <button type="button" class="btn btn-rosatom btn-outline-danger" data-dismiss="modal">Закрыть</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>
