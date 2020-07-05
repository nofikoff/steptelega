<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TelegramSession */

$this->title = 'Create Telegram Session';
$this->params['breadcrumbs'][] = ['label' => 'Telegram Sessions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="telegram-session-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
