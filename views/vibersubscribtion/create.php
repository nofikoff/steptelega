<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ViberSubscribtion */

$this->title = 'Create Viber Subscribtion';
$this->params['breadcrumbs'][] = ['label' => 'Viber Subscribtions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="viber-subscribtion-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
