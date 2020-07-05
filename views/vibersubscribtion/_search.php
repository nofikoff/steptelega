<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ViberSubscribtionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="viber-subscribtion-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id_sb') ?>

    <?= $form->field($model, 'chat_id') ?>

    <?= $form->field($model, 'chat_name') ?>

    <?= $form->field($model, 'teacher_id') ?>

    <?= $form->field($model, 'group_id') ?>

    <?php // echo $form->field($model, 'updated') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
