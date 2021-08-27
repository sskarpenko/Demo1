<?php

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;
use common\models\Movement;
use kartik\select2\Select2;

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

    <?php if ($model->scenario == Movement::SCENARIO_MOVE): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label" for="card_id"><?= $model->getAttributeLabel('card_id') ?></label>
                    <input type="text" id="card_id" class="form-control"
                           title="<?= ($model->card_id) ? $model->card->compositeName : "" ?>"
                           value="<?= ($model->card_id) ? $model->card->compositeName : "" ?>" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'staffpos_id')->widget(Select2::className(), [
                    'data' => $staffpos,
                    'maintainOrder' => true,
                    'options' => [
                        'id' => 'staffpos-id',
                        'placeholder' => 'Выберите из списка',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ])
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal-footer">
        <?= Html::submitButton(Yii::t('app', 'Сохранить'), ['class' => 'btn btn-rosatom btn-outline-success']) ?>
        <button type="button" class="btn btn-rosatom btn-outline-danger" data-dismiss="modal">Закрыть</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>
