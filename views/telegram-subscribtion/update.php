<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TelegramSubscribtion */

$this->title = 'Update Telegram Subscribtion: ' . $model->id_sb;
$this->params['breadcrumbs'][] = ['label' => 'Telegram Subscribtions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id_sb, 'url' => ['view', 'id' => $model->id_sb]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="telegram-subscribtion-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
