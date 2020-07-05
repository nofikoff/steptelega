<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Students */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="students-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_student')->textInput() ?>

    <?= $form->field($model, 'name_student')->textInput(['maxlength' => true]) ?>

<!--    --><?//= $form->field($model, 'group_id')->textInput() ?>


    <?php


    echo $form->field($model, 'group_id')->widget(Select2::classname(), [
        'data' => $group_list,
        'options' => ['placeholder' => 'Select a state ...'],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]);

    ?>


    <?= $form->field($model, 'phonenumber')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'birthday')->textInput() ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'telegram_chat_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'telegram_notactive')->textInput() ?>

    <?= $form->field($model, 'auth_confirmed')->textInput() ?>

    <?= $form->field($model, 'auth_hesh')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status_notactive')->textInput() ?>

    <?= $form->field($model, 'logbook_id_streams')->textInput() ?>

    <?= $form->field($model, 'logbook_status')->textInput() ?>

    <?= $form->field($model, 'logbook_status_1c')->textInput() ?>

    <?= $form->field($model, 'updated')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
