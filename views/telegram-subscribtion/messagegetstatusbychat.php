<b>У вас оформлена подписка на уведомления об изменениях в расписании для:</b>
<?php

if (isset($model['teachers'])) {
    echo "\r\n<b>Преподаватели:</b>\r\n";
    foreach ($model['teachers'] as $key => $item) {
        echo " - <a href='https://t.me/"._BOT_NAME."?start=tchr=" . $key . "'>" . $item . "</a>\r\n";
    }
}

if (isset($model['groups'])) {
    echo "\r\n<b>Группы:</b>\r\n";
    foreach ($model['groups'] as $key => $item) {
        echo " - <a href='https://t.me/"._BOT_NAME."?start=grp=" . $key . "'>" . $item . "</a>\r\n";
    }
}
