<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\StudentsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Students';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="students-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Students', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_student',
            'name_student',
            'group_id',
            'phonenumber',
//            'birthday',
            'address',
            'email:email',
//            'telegram_chat_id',
//            'telegram_notactive',
//            'auth_confirmed',
//            'auth_hesh',
            'status_notactive',
            'logbook_id_streams',
            'logbook_status',
            'logbook_status_1c',
            'updated',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
