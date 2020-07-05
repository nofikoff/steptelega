<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Exames */

$this->title = 'Create Exames!!!!!';
$this->params['breadcrumbs'][] = ['label' => 'Exames', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exames-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
