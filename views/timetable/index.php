<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TimetableSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Timetables';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="timetable-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Timetable', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_tt',
            'countpara',
            'city_id',
            'start_date',
            'start_time',
//            'stop_time',
            'teacher_id',
            //'subject_id',
            //'group_id',
            //'subject',
            'room_id',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
