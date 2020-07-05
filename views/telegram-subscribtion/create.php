<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TelegramSubscribtion */

$this->title = 'Create Telegram Subscribtion';
$this->params['breadcrumbs'][] = ['label' => 'Telegram Subscribtions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="telegram-subscribtion-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
