<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Teacher */

$this->title = 'Препод: ' . $model->finame;
$this->params['breadcrumbs'][] = ['label' => 'Teachers', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->finame, 'url' => ['view', 'id' => $model->id_teacher]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="teacher-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
