<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Groupstep */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="groupstep-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name_group')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description_group')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'login_telega_group')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
