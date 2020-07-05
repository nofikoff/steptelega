<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\GroupstepSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Groupsteps';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="groupstep-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Groupstep', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_group',
            'name_group',
            'description_group',
            'login_telega_group',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
