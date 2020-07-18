<b>Загрузка по академии на
    <?= \app\components\MyHelper::strTime2WeekDay($time) ?></b> <?php
echo \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d", $time));
echo "\n";

//print_r($model);
$ctime = '';
foreach ($model as $item) {

    $template = '    $room <a href="https://t.me/' . _BOT_NAME . '?start=grp=' . $item->group->id_group . '">$grp</a> $subj <a href="https://t.me/' . _BOT_NAME . '?start=tchr=' . $item->teacher->id_teacher . '">$tch</a>' . "\n";
    $vars = array(
        '$time' => $item->start_time,
        '$room' => Yii::$app->params['roomsId'][$item->room_id],
        '$subj' => trim($item->subject),
        '$tch' => trim($item->teacher->finame),
        '$grp' => trim($item->group->name_group),
        // В РАПСИАНИИ на всю  академю номер будет лишним '$num' => $item->countpara?' #'.$item->countpara:'',


    );
    if ($item->start_time != $ctime) {
        echo "\n⏱ <b>" . $item->start_time . "</b>\n";
        $ctime = $item->start_time;
    }
    echo strtr($template, $vars);

}
?>

<b>Загрузка по академии на
    <?= \app\components\MyHelper::strTime2WeekDay($time) ?></b> <?php
echo \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d", $time));

