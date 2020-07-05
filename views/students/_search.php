<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\StudentsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="students-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id_student') ?>

    <?= $form->field($model, 'name_student') ?>

    <?= $form->field($model, 'group_id') ?>

    <?= $form->field($model, 'phonenumber') ?>

    <?= $form->field($model, 'birthday') ?>

    <?php // echo $form->field($model, 'address') ?>

    <?php // echo $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'telegram_chat_id') ?>

    <?php // echo $form->field($model, 'telegram_notactive') ?>

    <?php // echo $form->field($model, 'auth_confirmed') ?>

    <?php // echo $form->field($model, 'auth_hesh') ?>

    <?php // echo $form->field($model, 'status_notactive') ?>

    <?php // echo $form->field($model, 'logbook_id_streams') ?>

    <?php // echo $form->field($model, 'logbook_status') ?>

    <?php // echo $form->field($model, 'logbook_status_1c') ?>

    <?php // echo $form->field($model, 'updated') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
