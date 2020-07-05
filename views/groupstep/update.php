<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Groupstep */

$this->title = 'Update Groupstep: ' . $model->id_group;
$this->params['breadcrumbs'][] = ['label' => 'Groupsteps', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id_group, 'url' => ['view', 'id' => $model->id_group]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="groupstep-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
