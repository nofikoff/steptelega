<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ViberSubscribtion */

$this->title = 'Update Viber Subscribtion: ' . $model->id_sb;
$this->params['breadcrumbs'][] = ['label' => 'Viber Subscribtions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id_sb, 'url' => ['view', 'id' => $model->id_sb]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="viber-subscribtion-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
