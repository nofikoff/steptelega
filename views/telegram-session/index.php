<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TelegramSessionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Telegram Sessions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="telegram-session-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Telegram Session', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id_chat',
            'name_chat',
            'updated',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
