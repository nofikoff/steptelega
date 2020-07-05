<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ViberSubscribtionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Viber Subscribtions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="viber-subscribtion-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Viber Subscribtion', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_sb',
            'chat_id',
            'chat_name',
            'teacher_id',
            'group_id',
            //'updated',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
