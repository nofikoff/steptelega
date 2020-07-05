<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\TelegramSession */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="telegram-session-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id_chat')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name_chat')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'updated')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
