<?php

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\AuthHelper;
use common\models\MovementModel;
use kartik\datecontrol\DateControl;
use kartik\select2\Select2;

$Js = <<<SCRIPT
function modalReload() {
    var data_form = $('#movement-form-id').serialize();
    $.ajax({
        url: $('#movement-form-id').attr('action'),
        method: 'POST',
        data: data_form,
        success: function(data){
        $('#modalContent').html(data);
    }
    });
};

$(document).ready(function () {
    $('#movementmodel-type_card :radio').change(function(){
        modalReload();
    });
});
SCRIPT;
$this->registerJs($Js);

//echo '<pre>'.print_r($model, true).'</pre>';
?>

<div>
    <?php $form = ActiveForm::begin(
        [
            'id' => 'movement-form-id',
            'enableAjaxValidation' => true,
            'validationUrl' => Url::to(['validate-form']),
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

    <?php if ($model->scenario == MovementModel::SCENARIO_CREATE): ?>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'type_card')->radioList(MovementModel::getTypeCardList()) ?>
            </div>

            <div class="col-md-12">
                <div id="card_new">
                    <?php if (isset($model->type_card) && ($model->type_card == MovementModel::NEW_CARD)): ?>
                        <div class="row">
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
                            <div class="col-md-8">
                                <?= $form->field($model, 'email')->textInput(['value' => $email, 'placeholder' => 'Введите почту']) ?>
                            </div>

                            <div class="col-md-4">
                                <?= $form->field($model, 'stabnum')->textInput(['placeholder' => 'Табельный номер']) ?>
                            </div>
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
                    <?php endif;?>

                    <?php if (isset($model->type_card) && ($model->type_card == MovementModel::EXISTING_CARD)): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'card_id')->widget(Select2::className(), [
                                    'data' => $card,
                                    'maintainOrder' => true,
                                    'options' => [
                                        'id' => 'card-id',
                                        'placeholder' => 'Выберите из списка',
                                    ],
                                    'pluginOptions' => [
                                        'allowClear' => true
                                    ],
                                ]) ?>
                            </div>
                        </div>
                    <?php endif;?>
                </div>
            </div>
        </div>

        <div class="row">
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
                ]) ?>
            </div>

            <div class="col-md-4">
                <?= $form->field($model, 'begin')->widget(DateControl::classname(), [
                    'options' => ['placeholder' => 'Введите дату ...',],
                    'type' => DateControl::FORMAT_DATE,
                    'widgetOptions' => [
                        'pluginOptions' => [
                            'autoclose' => true,
                            'startDate' => date('Y-m-d'),
                            'todayHighlight' => true,
                        ]
                    ]
                ]) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal-footer">
        <?= Html::submitButton(Yii::t('app', 'Сохранить'), ['class' => 'btn btn-rosatom btn-outline-success']) ?>
        <button type="button" class="btn btn-rosatom btn-outline-danger" data-dismiss="modal">Закрыть</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>
