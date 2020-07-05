<?php

namespace app\components;

use app\components\MessageToTelegaApi;
use app\models\Groupstep;
use app\models\Students;
use app\models\Teacher;
use app\models\Timetable;
use Codeception\Lib\Generator\Group;
use yii\db\Exception;


// ПРИШЛОСЬ ПИСАТЬ КОПИЮ ПАРСЕРА ДЛЯ ВТОРОГО ФРЕЙМВОРКА ПО АДРЕСУ
// https://adminlb.itstep.org

//https://adminlb.itstep.org/exams#/exams
//все 540 экзаменов со списком преподов и студентов
//curl 'https://adminlb.itstep.org/exams/exams/get-all-exams-by-filter' -H 'pragma: no-cache' -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _ga=GA1.2.1216005458.1553626274; _gid=GA1.2.396186169.1557505979; _csrf=b502abb1b7c3a7120cf44a337ae3c3dbe7904f0aa1c8753795662b92aad0dc40a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22crC_-E6_1SjLdtlUBQqSuwNPftiusaPp%22%3B%7D; lang=ru; city_id=39; PHPSESSID=msois0scmkocq1ut7sqcjnrpp2; _identity=b8617851c0e7ba88a1bbfac796a9bb3c722d508925d3c2fa2b6dfbb1f50c12bfa%3A2%3A%7Bi%3A0%3Bs%3A9%3A%22_identity%22%3Bi%3A1%3Bs%3A17%3A%22%5B53%2Cnull%2C2592000%5D%22%3B%7D' -H 'accept-encoding: gzip, deflate, br' -H 'x-csrf-token: kq1xE4lWhG-gpWCJNscjqI1I7eb8xtAESilSLzvHyTPx3zJMpBOyMJH2CsVSs0_9zxmctYmxnlQsXTtaSKaZQw==' -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6' -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36' -H 'accept: application/json, text/plain, */*' -H 'cache-control: no-cache' -H 'authority: adminlb.itstep.org' -H 'x-requested-with: XMLHttpRequest' -H 'referer: https://adminlb.itstep.org/exams' --compressed


/**
 * module module definition class
 */
class ParserExamens
{
    /**
     * {@inheritdoc}
     */
    protected $loog_book_user;
    protected $loog_book_pass;

    public $file_cookies = 'cookies_parser_adminlb.txt';
    public $file_csrf = 'cookies_parser_adminlb_csrf.txt';
    public $curl_debug = FALSE;
    public $controllerNamespace = 'app\module\controllers';
    public $get_cs_rf = '';


//    public $telega;


    public function __construct()
    {

        $this->loog_book_user = \Yii::$app->params['logbook_manager_user'];
        $this->loog_book_pass = \Yii::$app->params['logbook_manager_password'];


        $this->get_auth();

        /** нужно было для оповещения собыйти внутри парсера нахождения нового раписания
         * здесь наврядли пригодится
         * $this->telega = new MessageToTelegaApi();
         * // инициализация
         * $this->telega->API_KEY = \Yii::$app->params['API_KEY'];
         * $this->telega->WEBHOOK_URL = \Yii::$app->params['WEBHOOK_URL'];
         * $this->telega->API_URL = 'https://api.telegram.org/bot' . $this->telega->API_KEY . '/';
         */

    }


    function get_cs_rf()
    {
        // КОСТЫЛЬ отклбючим дебаг он мешает
        $temp_debug = false;
        if ($this->curl_debug) $temp_debug = true;
        $this->curl_debug = false;
        //
        $content = $this->send_http_post('https://adminlb.itstep.org/#/', "GET", '');
        $this->curl_debug = $temp_debug;
        //
        if (empty($content)) {
            // считаем ранее записанное в файл
            $this->get_cs_rf = file_get_contents($this->file_csrf);
            // авторизация не понадобится
            return 'AuthedOk'; // 302 редирект и авторизация успешна за счет старого кукиес
        }
        // иначе парсим из страницы
        preg_match('/csrf-token" content="([^"]+)"/', $content, $matches, PREG_OFFSET_CAPTURE);
        // фиксируем в текстовом файле
        $fp = fopen($this->file_csrf, "w");
        fwrite($fp, $matches[1][0]);
        fclose($fp);
        // вдруг где еще пригодится
        $this->get_cs_rf = $matches[1][0];
        // возвращаем
        return "NewCsrf";
    }


    //curl 'https://adminlb.itstep.org/students/pages?limit=50&page=1' -H 'pragma: no-cache' -H 'cookie: __cfduid=dfeb228d60c9d752c106519042aa6651e1557745701; _csrf=c36124ce31cae490787aa2209020c060e6ff089704470e03894d669cdc11c410a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22LYl2RqNJb9uz51AGFbtMmeNhrllt_Ly6%22%3B%7D; lang=ru; _ga=GA1.2.107532751.1557766389; city_id=39; _identity=b8617851c0e7ba88a1bbfac796a9bb3c722d508925d3c2fa2b6dfbb1f50c12bfa%3A2%3A%7Bi%3A0%3Bs%3A9%3A%22_identity%22%3Bi%3A1%3Bs%3A17%3A%22%5B53%2Cnull%2C2592000%5D%22%3B%7D; PHPSESSID=05d50dga8hkv6icbeltll66ujq' -H 'accept-encoding: gzip, deflate, br' -H 'x-csrf-token: RG9uMO586kv1E6R6aXayqOLpU6uyLf6MgknOtNNRcb8INgICvA2kAZcq0QBcR_PvpIsn5t9IsOTwJaLAjB0IiQ==' -H 'accept-language: en-US,en;q=0.9,ru;q=0.8' -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36' -H 'accept: application/json, text/plain, */*' -H 'cache-control: no-cache' -H 'authority: adminlb.itstep.org' -H 'x-requested-with: XMLHttpRequest' -H 'referer: https://adminlb.itstep.org/students' --compressed
    // price = 6 - это будущие группы
    function get_all_active_students_and_groups($price = '')
    {
        $new_groups = 0;
        $step_items = 100;
        $k = 0;
        for ($p = 1; $p <= 99; $p++) {

            echo $url = 'https://adminlb.itstep.org/students/pages?limit=' . $step_items . '&page=' . $p . '&price=' . $price;

            $headers =
                [
                    'pragma: no-cache',
                    'x-csrf-token: ' . $this->get_cs_rf,
                    'accept-language: en-US,en;q=0.9,ru;q=0.8',
                    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36',
                    'accept: application/json, text/plain, */*',
                    'cache-control: no-cache',
                    'authority: adminlb.itstep.org',
                    'x-requested-with: XMLHttpRequest',
                    'referer: https://adminlb.itstep.org/students',
                ];
            $a = json_decode($this->send_http_post($url, "GET", $headers), true);
            if (isset($a['studentsList']))
                foreach ($a['studentsList'] as $item) {
                    //
                    // ФИКСИРУЕМ НОВУЮ ГРУППУ

                    $model = new Groupstep();
                    $model->id_group = $item['id_tgroups'];
                    $model->name_group = $item['group_name'];
                    try {
                        if ($model->save()) {
                            echo "\n *** Новая группа " . $item['id_tgroups'] . " " . $item['group_name'] . "";
                            $new_groups++;
                        }
                    } catch (\Exception $e) {

                        echo "\n Такая группа есть " . $item['id_tgroups'] . " " . $item['group_name'] . "";
                        // это старая группа !!!! - проверим изменилось ли название
                        // это критичнот.к. нгаш парер расписания пока парскит по имени гурппы !!!!
                        $old_model = Groupstep::findOne([$item['id_tgroups']]);
                        // имя если и зменилось - обновим
                        // // раз мы тут значит такое ID занято и модель есть в базе if ($old_model)
                        if ($old_model->name_group != $item['group_name']) {
                            $old_model->name_group = $item['group_name'];
                            $old_model->save();
                        }
                        //  return $this->render('create', ['model' => $model]);
                    }
                    // END ФИКСИРУЕМ НОВУЮ ГРУППУ
                    //
                    // ФИКСИРУЕМ НОВОГО СТУДЕНТА
                    $model = new Students();

                    //$model->birthday = $item['xxxxx']; ОТСУТСВУЕТ !

                    $model->id_student = $item['id_stud'];
                    //
                    preg_match('/([^\d\.\(\)]+)/ui', $item['fio_stud'], $m);
                    $model->name_student = trim($m[1]);
                    $model->group_id = $item['id_tgroups'];
                    $model->phonenumber = $item['phone_stud'];
                    $model->address = $item['adr_stud'];
                    $model->email = $item['st_email'];
                    $model->logbook_id_streams = $item['id_streams'];
                    $model->logbook_status = $item['stat'];
                    $model->logbook_status_1c = $item['status_1C'];
                    try {
                        if ($model->save()) {

                        } else {
                            //echo "СТУДЕНТ NOT SAVED " . $k++;
                            //print_r($item);
                            //print_r($model->getAttributes());
                            //print_r($model->getErrors());
                            //TODO: "апдейтим статус группу и пр";///апдейтим статус группу и пр
                            //echo "апдейтим статус группу и пр";///апдейтим статус группу и пр

                        }
                    } catch (\Exception $e) {

                        //print_r($model);


//                     $model->addError(null, $e->getMessage());
//                      return $this->render('create', ['model' => $model]);
                    }
                    // END ФИКСИРУЕМ НОВОГО СТУДЕНТА
                    //
                }

            // определим максимальное количество страниц студентов
            if (isset($a['total'])) {
                $max_pages = round($a['total'] / $step_items) + 1;
                if ($max_pages == $p) break;
            }
        }//for;

        echo "\n НОВЫХ ГРУПП СОХРАНЕНО $new_groups \n";
    }


    /**
     *
     *
     *
     * СТАРЫЕ
     *
     *
     */

    function get_schedule($week = 0)
    {
        //curl 'https://logbook.itstep.org/schedule/get-schedule'
        // -H 'pragma: no-cache'
        // -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270;_csrf=f8496401b9de8e6eb5aaeed9740cd86866afb709c183a742cd89416f516bb4d9a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22EBHIhyZIoCVB4bAqKCzYsLHfKsWk2H2C%22%3B%7D; _ga=GA1.2.1216005458.1553626274; _gid=GA1.2.192126779.1553626274; city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D;PHPSESSID=617kec7vtk9rnbtoh3qtprt611'
        // -H 'origin: https://logbook.itstep.org'
        // -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6'
        // -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36'
        // -H 'content-type: application/json;charset=UTF-8'
        // -H 'accept: application/json, text/plain, */*'
        // -H 'cache-control: no-cache'
        // -H 'authority: logbook.itstep.org'
        // -H 'referer: https://logbook.itstep.org/' --data-binary '{"week":1}'

        $url = 'https://logbook.itstep.org/schedule/get-schedule';
        $headers =
            [
                'pragma: no-cache',
                // здесь не надо - один раз сохранили в куках и все
                //'cookie: city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D',
                'origin: https://logbook.itstep.org',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/',

            ];

        return $this->send_http_post($url, "POST", $headers, '{"week":' . $week . '}');


    }

    function update_groups()
    {
        echo $a = $this->get_groups();
        echo "\n***************************************************************\n";
        $a = json_decode($a, true);
        $i = 0;
        if ($a and sizeof($a))
            foreach ($a as $group) {
                $model = new Groupstep();
                $model->id_group = $group['id_tgroups'];
                $model->name_group = $group['name_tgroups'];
                try {
                    if ($model->save()) $i++;

                } catch (\Exception $e) {
                    // $model->addError(null, $e->getMessage());
                    //  return $this->render('create', ['model' => $model]);
                }
            }
        echo "\r\nДобавлено новых групп: $i\r\n";

    }

    function update_teachers_and_groups()
    {

        $json = $this->get_teachers();
        $a = json_decode($json, true);

        $i = 0;
        $_actual_teacher_list = [];
        foreach ($a as $teacher) {
            $_actual_teacher_list[] = $teacher['id_teach'];
            //
            $model = new Teacher();
            $model->id_teacher = $teacher['id_teach'];
            $model->name_teacher = $teacher['fio_teach'];
            $model->status_teacher = 1;

            try {
                if ($model->save()) $i++;

            } catch (\Exception $e) {
                // $model->addError(null, $e->getMessage());
                //  return $this->render('create', ['model' => $model]);
            }
        }

        // защита от сбоя > 10
        if (sizeof($_actual_teacher_list) > 10) {
            $_db_list_teacher = Teacher::getAllActiveTeachers();
            foreach ($_db_list_teacher as $teacher) {
                if (!in_array($teacher->id_teacher, $_actual_teacher_list)) {
                    // НЕ УДАЛЯЕМ НО ДЕЛАЕМ НЕ АКТИВНЫМ
                    $teacher->status_teacher = 0;
                    $teacher->save();
                    //
                } else {
                    // для получения групп надо перекобчить учителя не любого другого кроме глобального эккаунта
                    echo "\n\n{$teacher->id_teacher} {$teacher->name_teacher} : ";
                    $this->change_teacher($teacher->id_teacher);
                    echo "\nchange_teacher - OK";
                    $this->update_groups();
                    echo "\nupdate_groups - OK\n";
                }
            }
        }

        echo "\r\nДобавлено новых учителей: $i\r\n";

    }

    function send_admin_only_message($m)
    {
        //'440046277';
        foreach (\Yii::$app->params['admins_chat_id'] as $chat_id) {
            $this->telega->chat_id = $chat_id;
            $this->telega->sendMessageAPI($m);
        }

    }

    function parse_shedul_json($id_teacher, $week = 0)
    {
        echo $json = $this->get_schedule($week);
        echo $hr = "\n*************************************************************\n\n";
        \Yii::info("\n\nid_teacher = $id_teacher; week = $week \n $json $hr", 'parsershcheduler');

        $a = json_decode($json, true);
        $groups = Groupstep::getAllGroups();

//$row-смотреть таблицу расписанийц в лог бук перебираем построчно таблицу
//$row-смотреть таблицу расписанийц в лог бук перебираем построчно таблицу
//$row-смотреть таблицу расписанийц в лог бук перебираем построчно таблицу
//$row-смотреть таблицу расписанийц в лог бук перебираем построчно таблицу
//$row-смотреть таблицу расписанийц в лог бук перебираем построчно таблицу
        $dates = $a['dates'];

        if (!isset($a['body']) or !is_array($a['body'])) {
            echo $m = "\r\n\r\n *************************** ФАТАЛ ЕРОР - ВНИМАНИЕ с ТИЧЕРОМ $id_teacher траблы не вижу расписание *****************************\r\n";
            $this->send_admin_only_message($m);
            die ("ОСТАНОВИИСЬ ЧТОБЫ НЕ ЗАТЕРЕТИЬ РАСПИСАНИЕ ОСТАЛЬНЫХ");
            die ("ОСТАНОВИИСЬ ЧТОБЫ НЕ ЗАТЕРЕТИЬ РАСПИСАНИЕ ОСТАЛЬНЫХ");
            die ("ОСТАНОВИИСЬ ЧТОБЫ НЕ ЗАТЕРЕТИЬ РАСПИСАНИЕ ОСТАЛЬНЫХ");
            return;
        }

        //н акапливаем кэш раписания для экшена измненных уведомлений
        //$key - день недели - до этого рапсиание случайно острот ировано п о строкам
        foreach ($a['body'] as $items) {
            foreach ($items as $key => $item) {
                $result[$key][] = $item;
            }
        }
//            var_dump($num_row);
//            var_dump($row);

        //перебираем дни недели     $num_dayOfweek-номер дня недели  $dayOfweeks-все пары на этой строке
        if (!empty($result))
            foreach ($result as $num_dayOfweek => $currantParyDay) {
                foreach ($currantParyDay as $dayOfweeks) {


                    // если англисйки то может несколько групп
                    $list_groups = explode(',', $dayOfweeks["groups"]);

                    foreach ($list_groups as $item_group) {
                        //
                        $para = [
                            'start_date' => $dates[$dayOfweeks["weekday"]],
                            'start_time' => $dayOfweeks["l_start"],
                            'subject' => $dayOfweeks["short_name_spec"],
                            'teacher_id' => $id_teacher,
                            'group_id' => $groups[trim($item_group)], // получаем id по имени
                            'room_id' => \Yii::$app->params['roomsName'][$dayOfweeks["num_rooms"]], // получаем id по имени
                        ];
                        //пишем и шлем мессадж если новая пара
                        $this->saveNewTimeTableANDSendMessage($para);

                        //накапливаем кэш раписания для экшена измненных уведомлений
                        $this->curent_rasspisanie_in_mystat[] = $para;

                    }
                }

            }
//        print_r($this->curent_rasspisanie_in_mystat);
//        exit;

    }


    function saveNewTimeTableANDSendMessage($para)
    {

        $model = new Timetable();
        $model->start_date = $para['start_date'];
        $model->start_time = $para['start_time'];
        $model->subject = $para['subject'];
        $model->teacher_id = $para['teacher_id'];
        $model->room_id = $para['room_id']; //
        $model->group_id = $para['group_id'];


        try {
            if ($model->save()) {
                $this->telega_list_parser_messages($model, "➕ <b>ДОБАВЛЕНО</b>\n" . $this->message_template_para($model), $model->teacher_id, $model->group_id);

            } else {
                /**   echo "NOT SAVED ВЕРОЯТНО ТАКАЯ ПАРА ЕСТЬ<br>\r\n";
                 * print_r($model->getAttributes());
                 * print_r($model->getErrors());*/
            }


        } catch (\Exception $e) {
            echo "\r\n\r\n";
            echo " ОШИБКА ЗАПИСИ ПАРЫ А БАЗУ МОЖЕТ УДАЛИТЬ ЭТО СООБЩЕНИЕ ?????????????? проверь код";
            echo $e->getMessage();
            echo "\r\n\r\n";

        }


    }


    function change_teacher($id)
    {
        //curl 'https://logbook.itstep.org/auth/change-user'
        // -H 'pragma: no-cache'
        // -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _csrf=f8496401b9de8e6eb5aaeed9740cd86866afb709c183a742cd89416f516bb4d9a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22EBHIhyZIoCVB4bAqKCzYsLHfKsWk2H2C%22%3B%7D; _ga=GA1.2.1216005458.1553626274; city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D; _gid=GA1.2.814541618.1554307057; PHPSESSID=lhblm1auch01bft7fi9pv95mfe'
        // -H 'origin: https://logbook.itstep.org'
        // -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6'
        // -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36'
        // -H 'content-type: application/json;charset=UTF-8'
        // -H 'accept: application/json, text/plain, */*'
        // -H 'cache-control: no-cache'
        // -H 'authority: logbook.itstep.org'
        // -H 'referer: https://logbook.itstep.org/'
        // --data-binary '{"id_user":"56"}'


        $url = 'https://logbook.itstep.org/auth/change-user';
        $headers =
            [
                'pragma: no-cache',
                // здесь не надо - один раз сохранили в куках и все
                //'cookie: city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D',
                'origin: https://logbook.itstep.org',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/',

            ];

        return $this->send_http_post($url, "POST", $headers, '{"id_user":"' . $id . '"}');


    }

    function get_rooms()
    {
        //curl 'https://logbook.itstep.org/tasks/get-rooms' -X POST
        // -H 'pragma: no-cache'
        // -H 'origin: https://logbook.itstep.org'
        // -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6'
        // -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36'
        // -H 'accept: application/json, text/plain, */*'
        // -H 'cache-control: no-cache'
        // -H 'authority: logbook.itstep.org'
        // -H 'referer: https://logbook.itstep.org/'
        // -H 'content-length: 0'

        $url = 'https://logbook.itstep.org/tasks/get-rooms';
        $headers =
            [
                'pragma: no-cache',
                // здесь не надо - один раз сохранили в куках и все
                //'cookie: city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D',
                'origin: https://logbook.itstep.org',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/',
                'content-length: 0'

            ];

        return $this->send_http_post($url, "GET", $headers);


    }


    function parse_rooms_json()
    {


        $json = $this->get_rooms();
//        die($json);
//        die($json);
//        die($json);
        $a = json_decode($json, true);

//        foreach ($a as $item) {
//            echo $item['id_rooms'] . " => '{$item['num_rooms']}', \n";
//        }
//
//        foreach ($a as $item) {
//            echo "'{$item['num_rooms']}'" . " => {$item['id_rooms']}, \n";
//        }

        // print_r($a);
        exit;


    }

    function get_teachers()
    {
        //curl 'https://logbook.itstep.org/auth/get-teach-list' -X POST
        // -H 'pragma: no-cache'
        // -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _csrf=f8496401b9de8e6eb5aaeed9740cd86866afb709c183a742cd89416f516bb4d9a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22EBHIhyZIoCVB4bAqKCzYsLHfKsWk2H2C%22%3B%7D; _ga=GA1.2.1216005458.1553626274; city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D; _gid=GA1.2.814541618.1554307057; PHPSESSID=lhblm1auch01bft7fi9pv95mfe'
        // -H 'origin: https://logbook.itstep.org'
        // -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6'
        // -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36'
        // -H 'accept: application/json, text/plain, */*'
        // -H 'cache-control: no-cache'
        // -H 'authority: logbook.itstep.org'
        // -H 'referer: https://logbook.itstep.org/'
        // -H 'content-length: 0'
        $url = 'https://logbook.itstep.org/auth/get-teach-list';
        $headers =
            [
                'pragma: no-cache',
                // здесь не надо - один раз сохранили в куках и все
                //'cookie: city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D',
                'origin: https://logbook.itstep.org',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/',
                'content-length: 0'

            ];

        return $this->send_http_post($url, "POST", $headers, '');


    }


    function get_groups()
    {
        //curl 'https://logbook.itstep.org/students/get-groups-list' -X POST
        // -H 'pragma: no-cache'
        // -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _csrf=f8496401b9de8e6eb5aaeed9740cd86866afb709c183a742cd89416f516bb4d9a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22EBHIhyZIoCVB4bAqKCzYsLHfKsWk2H2C%22%3B%7D; _ga=GA1.2.1216005458.1553626274; city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D; _gid=GA1.2.1332491620.1554050974; PHPSESSID=87hp4f6nn9lq5sn0hl5692r6li; _gat_gtag_UA_115970085_3=1' -H 'origin: https://logbook.itstep.org' -H 'accept-encoding: gzip, deflate, br' -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6' -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36' -H 'accept: application/json, text/plain, */*' -H 'cache-control: no-cache'
        // -H 'authority: logbook.itstep.org'
        // -H 'referer: https://logbook.itstep.org/'
        // -H 'content-length: 0'


        $url = 'https://logbook.itstep.org/students/get-groups-list';
        $headers =
            [
                'pragma: no-cache',
                // здесь не надо - один раз сохранили в куках и все
                //'cookie: city_id=63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D',
                'origin: https://logbook.itstep.org',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/',
                'content-length: 0',

            ];

        return $this->send_http_post($url, "POST", $headers, '');


    }


    function get_auth()
    {
        // с куками
        $_result_ = $this->get_cs_rf();
        if ($_result_ == 'AuthedOk') return true;

        // разлогиниваемся
        // надо для отладки
        //@unlink($this->file_cookies);
        //curl 'https://adminlb.itstep.org/user/login' -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _ga=GA1.2.1216005458.1553626274; _gid=GA1.2.396186169.1557505979; _csrf=b502abb1b7c3a7120cf44a337ae3c3dbe7904f0aa1c8753795662b92aad0dc40a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22crC_-E6_1SjLdtlUBQqSuwNPftiusaPp%22%3B%7D; lang=ru; city_id=39; PHPSESSID=6vgd944vihf67iraj0a54bv3n2' -H 'origin: https://adminlb.itstep.org' -H 'accept-encoding: gzip, deflate, br' -H 'x-csrf-token: T8wX5IKAGZC0QTlZAMjwoyO6dvfCmC0dCNsth8uGBuksvlS7r8Uvz4USUxVkvJz2YesHpLfvY01ur0TyuOdWmQ==' -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6' -H 'x-requested-with: XMLHttpRequest' -H 'pragma: no-cache' -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36' -H 'content-type: application/json;charset=UTF-8' -H 'accept: application/json, text/plain, */*' -H 'cache-control: no-cache' -H 'authority: adminlb.itstep.org' -H 'referer: https://adminlb.itstep.org/' --data-binary '{"loginForm":{"username":"zimina","password":"XXXXXXXXXXXXX"},"city_id":"39"}' --compressed
        $post_fields = '{"loginForm":{"username":"' . $this->loog_book_user . '","password":"' . $this->loog_book_pass . '"},"city_id":"39"}';
        $url = 'https://adminlb.itstep.org/user/login';
        $headers =
            [
                'origin: https://adminlb.itstep.org',
                'x-csrf-token: ' . $this->get_cs_rf,
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'x-requested-with: XMLHttpRequest',
                'pragma: no-cache',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: adminlb.itstep.org',
                'referer: https://adminlb.itstep.org/',
            ];
        $a = $this->send_http_post($url, "POST", $headers, $post_fields);
        if ($this->curl_debug) echo "\r\nАвторизация ответ: " . $a;

    }

    function set_cookie_idcity_____()
    {
        //       #HttpOnly_logbook.itstep.org	FALSE	/	FALSE	0	PHPSESSID	eb8n7h1nbhenbh8mftorgrff6q
        $cook_city = "#HttpOnly_logbook.itstep.org	FALSE	/	FALSE	0	city_id	63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D";
        $mode = (!file_exists($this->file_cookies)) ? 'w' : 'a';
        $cookFile = fopen($this->file_cookies, $mode);
        fwrite($cookFile, "" . $cook_city);
        fclose($cookFile);

    }

    function send_http_post($url, $method, $headers, $post_fields = '')
    {


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if (!empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookies);
// Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


// DEBUG
        if ($this->curl_debug) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        }

        $server_output = curl_exec($ch);
        if ($this->curl_debug) echo "\r\nCurl ответ: " . $server_output;


// DEBUG
        if ($this->curl_debug) {
            print_r(curl_getinfo($ch));
        }

        curl_close($ch);

        // Магия, генерим на стороне клиента куки city_id в локальном хранилище
        // Без него авторизация и другие запросы не проканают
        //$this->set_cookie_idcity();

        return $server_output;
    }


    // возвращаем ID MYSQL пары старой что надо удалить
    // возвращаем ID вектора $this->curent_rasspisanie_in_mystat новой пары что надо сохранить
    /*
     *       * [teacher_id] => 97
         * [group_id] => 2542
         * [start_date] => 2019-05-10
         * [start_time] => 19:30
         * [subject] => HTML+CSS
         * [room_id] => 5
     */
    private function compare_by_vektor($start_date, $start_time, $teacher_id, $group_id, $subject = '', $room_id, $id_mysql)
    {
        //echo "Из MyStat $start_date, $start_time, tchr: $teacher_id ,  grp: $group_id , $subject , room $room_id \r\n";
        if (!sizeof($this->curent_rasspisanie_in_mystat)) {
            return
                [
                    'changed' => 0,
                    'message' => 'ВЕКТОР MYSTAT ПУСТ',
                    'IDMySQL' => $id_mysql,
                    'IDMyStatArr' => 0,
                ];
        }


        /** ВЕКТОР curent_rasspisanie_in_mystat
         *
         * [teacher_id] => 97
         * [group_id] => 2542
         * [start_date] => 2019-05-10
         * [start_time] => 19:30
         * [subject] => HTML+CSS
         * [room_id] => 5
         */

        /** MYSQL
         *
         * [id_tt] => 282
         * [start_date] => 2019-05-04
         * [start_time] => 12:00
         * [teacher_id] => 5
         * [group_id] => 2309
         * [subject] => Ul/UX
         * [room_id] => 6
         */

        //дата/время не найдены = ПАРА ОТМЕНЕНА
        $flag = 0;
        foreach ($this->curent_rasspisanie_in_mystat as $key => $mystat_p) {
            //$key - на чем остановилсиь !!
            if ($mystat_p['start_date'] == $start_date and $mystat_p['start_time'] == $start_time and $mystat_p['group_id'] == $group_id) {
                $flag = 1;
                break;
            }
        }
        if ($flag == 0) return
            [
                'changed' => 1,
                'message' => 'ПАРА ОТМЕНЕНА',
                'IDMySQL' => $id_mysql,
                'IDMyStatArr' => -1,
            ];

        //дата/время ЕСТЬ мы ее нашли выше $key - тема не найдена = ПАРА ЕСТЬ ТЕМА ИЗМЕНЕНА
        if ($subject and $mystat_p['subject'] != $subject) {
            return
                [
                    'changed' => 1,
                    'message' => 'ТЕМА ПАРЫ ИЗМЕНЕНА',
                    'IDMySQL' => $id_mysql,
                    'IDMyStatArr' => $key,
                ];
        }

        //дата/время тема ЕСТЬ - препод не найден = ПРЕПОД ЗАМЕНЕН
        if ($mystat_p['teacher_id'] != $teacher_id) {
            return
                [
                    'changed' => 1,
                    'message' => 'ПРЕПОД ЗАМЕНЕН',
                    'IDMySQL' => $id_mysql,
                    'IDMyStatArr' => $key,
                ];
        }

        //дата/время тема препод ЕСТЬ - кабинет не найден = КАБИНЕТ ИЗМЕНЕН
        if ($mystat_p['room_id'] != $room_id) {

            // если кабинет заменен сегодня завтра то сообщаем иначе молчим
            $timestamp = strtotime($start_date);
            $delta = $timestamp - time();
            // более 2 суток
            if ($delta > 172800) {
                return
                    [
                        'changed' => 0,
                        'message' => 'ПРО КАБИНЕТ МОЛЧИМ',
                        'IDMySQL' => 0,
                        'IDMyStatArr' => 0,
                    ];
            }

            // менее 2 суток
            return
                [
                    'changed' => 1,
                    'message' => 'КАБИНЕТ ИЗМЕНЕН',
                    'IDMySQL' => $id_mysql,
                    'IDMyStatArr' => $key,
                ];
        }

        //$this->print_r($mystat_p);
        //'ПАРЫ ИДЕНТИЧНЫ'
        return
            [
                'changed' => 0,
                'message' => 'ПАРЫ ИДЕНТИЧНЫ',
                'IDMySQL' => 0,
                'IDMyStatArr' => 0,
            ];

    }
    /**
     * 'id_tt' => 'Id Tt',
     * 'start_date' => 'Start Date',
     * 'start_time' => 'Start Time',
     * 'stop_time' => 'Stop Time',
     * 'teacher_id' => 'Teacher ID',
     * 'group_id' => 'Group ID',
     * 'subject' => 'Subject',
     * 'room_id' => 'Room ID',
     */
    // инкерментируем глобалдьынй масив _telega_list_parser_messages
    // таким образом чтобы каждая следующая запись сообщения для рассылки добавллялсь конкатенацией к предыдущей
    // если их отичает только время пары
    // важно указать ID получател\ т.к. в некоторых случая когда препод мнеяется получатели два
    function telega_list_parser_messages($model_para, $message, $send_teacher_id, $send_group_id)
    {

        // СКЛЕИВАЕМ РОДСТВЕННЫЕ СООБЩЕНИЯ В ОДНО
        // СКЛЕИВАЕМ РОДСТВЕННЫЕ СООБЩЕНИЯ В ОДНО
        // СКЛЕИВАЕМ РОДСТВЕННЫЕ СООБЩЕНИЯ В ОДНО

        $last_index = sizeof($this->_telega_list_parser_messages) - 1;
        if (
            isset($this->_telega_list_parser_messages[$last_index])
            and
            $send_teacher_id == $this->_telega_list_parser_messages[$last_index]['teacher_id']
            and
            $send_group_id == $this->_telega_list_parser_messages[$last_index]['group_id']
            and
            $model_para->start_date == $this->_telega_list_parser_messages[$last_index]['start_date']
            and
            $model_para->subject == $this->_telega_list_parser_messages[$last_index]['subject']

        ) {
            $this->_telega_list_parser_messages[$last_index]['message'] .= "\r\n\r\n" . $message;

        } else {
            $this->_telega_list_parser_messages[] = [
                'para_id' => $model_para->id_tt,
                'message' => $message,
                'teacher_id' => $send_teacher_id,
                'group_id' => $send_group_id,
                'start_date' => $model_para->start_date,
                'subject' => $model_para->subject,
            ];
        }
    }

    // чтоизменилось в будущих расписаниях
    function compare_what_changed_and_deleted()
    {
        // DEBUG
        // DEBUG
        // DEBUG
        //$this->print_r($this->curent_rasspisanie_in_mystat);
        echo "\r\n\r\n";

        /**
         * [teacher_id] => 97
         * [group_id] => 2542
         * [start_date] => 2019-05-10
         * [start_time] => 19:30
         * [subject] => HTML+CSS
         * [room_id] => 5
         */

        echo "\n<h1>compare_what_changed_and_deleted</h1>\n";

        //$stmt = $this->dbh->query("SELECT * FROM schedulers WHERE sdate >= CURDATE()");
        $stmt = Timetable::findBySql("SELECT * FROM timetable WHERE `start_date` >= CURDATE() ORDER by `start_date`,`start_time`")->all();

        /**
         * [id_tt] => 282
         * [start_date] => 2019-05-04
         * [start_time] => 12:00
         * [teacher_id] => 5
         * [group_id] => 2309
         * [subject] => Ul/UX
         * [room_id] => 6
         */

        // тут уже обновленные расписания
        foreach ($stmt as $model) {

            // на входе в compare_by_vektor запись старого расписания из MySQL
            // внутри массив свежего спарсенного расписния для сравнинеия
            $changed = $this->compare_by_vektor(
                $model->start_date,
                $model->start_time,
                $model->teacher_id,
                $model->group_id,
                $model->subject,
                $model->room_id,
                $model->id_tt //mySQL id record
            );
            // есть измнения в расписании
            // надо удалить старУе пары и сохранить измененнУю новую


            if ($changed['changed']) {
                $message = "❌ <b>" . $changed['message'] . "</b>\n";
                // СТАРАЯ ПАРА
                $message .= $this->message_template_para($model);

                // -1 - признак что пара была отменена
                // если пара отменена - сюда не заходим подправим сообщение
                // если пара отменена - сюда не заходим подправим сообщение
                // если пара отменена - сюда не заходим
                if ($changed['IDMyStatArr'] == -1) {
                    // если модель удалена то надо
//                    $this->_telega_list_parser_messages[] = [
//                        'para_id' => $model->id_tt,
//                        'message' => $message,
//                        'group_id' => $model->group_id,
//                        'teacher_id' => $model->teacher_id,
//                    ];
                    $this->telega_list_parser_messages($model, $message, $model->teacher_id, $model->group_id);
                    // УДАЛЯЕМ отмененую пару
                    // УДАЛЯЕМ отмененую пару
                    $model->delete();
                    continue;
                }

                $old_id_teacher = $model->teacher_id;
                //
                // УДАЛЯЕМ старую из перебираемых
                // УДАЛЯЕМ старую из перебираемых
                $model->delete();
                //
                try {
                    //пишем обновленные данные на место удаленной - типа апдейта
                    // нен путать с созданием новой модели пары из массива с парсенных
                    // объеденить их не получится тк нга входе там массив тут модель из БД
                    // объеденить их не получится тк нга входе там массив тут модель из БД
                    $model = new Timetable();
                    $model->start_date = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['start_date'];
                    $model->start_time = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['start_time'];
                    $model->teacher_id = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['teacher_id'];
                    $model->group_id = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['group_id'];
                    $model->subject = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['subject'];
                    $model->room_id = $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['room_id'];
                    $model->save(false);
                    //
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }

                // НОВАЯ ПАРА - ВТОРАЯ ЧАСТЬ СООБЩЕНИЯ
                // НОВАЯ ПАРА - ВТОРАЯ ЧАСТЬ СООБЩЕНИЯ
                $message .= "\n\n<b>ОБНОВЛЕННЫЕ ДАННЫЕ</b>:\n" . $this->message_template_para($model) . "\n\n";

                // TODO ШЛЕМ СООБЩЕНИЕ - пишем измененую и шлем сообщение ВСЛУЧАЕ ЕЛИС НОВАЯ ПАРА об обновленной паре в базе
                /**$this->saveNewTimeTableANDSendMessage($this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]);**/
                // если модель удалена то надо
//                $this->_telega_list_parser_messages[] = [
//                    'para_id' => $model->id_tt,
//                    'message' => $message,
//                    'group_id' => $model->group_id,
//                    'teacher_id' => $model->teacher_id,
//                ];

                $this->telega_list_parser_messages($model, $message, $model->teacher_id, $model->group_id);


                // если препод сменился - отправляем обоим
                if ($model->teacher_id != $old_id_teacher) {
//                    $this->_telega_list_parser_messages[] = [
//                        'para_id' => $model->id_tt,
//                        'message' => $message,
//                        'group_id' => $model->group_id,
//                        'teacher_id' => $this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]['teacher_id'],
//                    ];
                    $this->telega_list_parser_messages($model, $message, $old_id_teacher, $model->group_id);

                }

                //                    $this->telega->chat_id = '440046277';
                //                    $this->telega->sendMessageAPI($message);
            } else {
                //echo "- Измненений нет.\r\n";
            }

        }
        //echo "compare_what_changed_and_deleted END !!!!!!!!! \r\n";
        return;

    }


    private function message_template_para($model)
    {
        //$date = date('d-m-Y', strtotime($model->start_date));
        $date = MyHelper::reverceDateFromAmeric($model->start_date);
        $room_name = \Yii::$app->params['roomsId'][$model->room_id];
        return "🗓 " . $this->strDate2WeekDay($model->start_date) . " {$date}, {$room_name}, {$model->start_time}
<b>{$model->subject}</b>
<a href='https://t.me/" . _BOT_NAME . "?start=tchr={$model->teacher->id_teacher}'>{$model->teacher->name_teacher}</a>
<a href='https://t.me/" . _BOT_NAME . "?start=grp={$model->group->id_group}'>{$model->group->name_group}</a>";
    }

    private function strDate2WeekDay($ddate)
    {
        $w = [
            'ВОСКРЕСЕНЬЕ', 'ПОНЕДЕЛЬНИК', 'ВТОРНИК', 'СРЕДА', 'ЧЕТВЕРГ', 'ПЯТНИЦА', 'СУББОТА'
        ];
        return $w[date("w", strtotime($ddate))];
    }


}
