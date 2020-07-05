<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Groupstep */

$this->title = 'Create Groupstep';
$this->params['breadcrumbs'][] = ['label' => 'Groupsteps', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="groupstep-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
