<b>Свободные аудитории на
    <?= \app\components\MyHelper::strTime2WeekDay($time) ?></b> <?php
echo \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d", $time));
echo "\n";

//print_r($model);
$ctime = '';


//echo "\nКарантин. \nВсе аудитории свободны. Ну почти все ... :)\n\n";
//return ;


foreach (Yii::$app->params['time_start'] as $para_str) {
    // комнаты
    echo "\n⏱ <b>" . $para_str . "</b>\n";
    foreach (Yii::$app->params['roomsId'] as $room_id => $room_str) {
        // кроме комнаты типа ONLINE
        if ($room_id <> 15)
            echo '    ' . ($list[$para_str][$room_str] == 'Free' ? "<b>$room_str {$list[$para_str][$room_str]}</b>" : "$room_str {$list[$para_str][$room_str]}") . "\n";
    }
}
?>

<b>Свободные аудитории на
    <?= \app\components\MyHelper::strTime2WeekDay($time) ?></b> <?php
echo \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d", $time));

