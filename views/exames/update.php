<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Exames */

$this->title = 'Update Exames: ' . $model->id_exames;
$this->params['breadcrumbs'][] = ['label' => 'Exames', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id_exames, 'url' => ['view', 'id' => $model->id_exames]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="exames-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
