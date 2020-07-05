<?php

//print_r($model);
$cdate = '';
foreach ($model as $item) {


    $date = \app\components\MyHelper::reverceDateFromAmeric($item->start_date);



    $template = '    $time $room "$subj"$num <a href="https://t.me/'._BOT_NAME.'?start=tchr='.$item->teacher->id_teacher.'">$tchr</a>' . "\n";
    $vars = array(
        '$time' => $item->start_time,
        '$room' => Yii::$app->params['roomsId'][$item->room_id],
        '$subj' => $item->subject,
        '$tchr' => $item->teacher->finame,
        '$num' => $item->countpara?' #'.$item->countpara:'',
    );
    if ($date != $cdate) {
        //echo "\n<b>" . $date . "</b> " . \app\components\MyHelper::strDate2WeekDay($item->start_date) . "\n";
        echo "\n" . \app\components\MyHelper::strDate2WeekDay($item->start_date) . " <b>" . $date . "</b>\n";
        $cdate = $date;
    }
    echo strtr($template, $vars);


}
