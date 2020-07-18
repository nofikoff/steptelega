<?php
/**
 * Created by PhpStorm.
 * User: Arnoldus
 * Date: 21.05.2019
 * Time: 19:37
 */

namespace app\components;


//https://adminlb.itstep.org/exams#/exams
//все 540 экзаменов со списком преподов и студентов
//curl 'https://adminlb.itstep.org/exams/exams/get-all-exams-by-filter' -H 'pragma: no-cache' -H 'cookie: __cfduid=df172b678643a836cce6cec2659f621691553626270; _ga=GA1.2.1216005458.1553626274; _gid=GA1.2.396186169.1557505979; _csrf=b502abb1b7c3a7120cf44a337ae3c3dbe7904f0aa1c8753795662b92aad0dc40a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22crC_-E6_1SjLdtlUBQqSuwNPftiusaPp%22%3B%7D; lang=ru; city_id=39; PHPSESSID=msois0scmkocq1ut7sqcjnrpp2; _identity=b8617851c0e7ba88a1bbfac796a9bb3c722d508925d3c2fa2b6dfbb1f50c12bfa%3A2%3A%7Bi%3A0%3Bs%3A9%3A%22_identity%22%3Bi%3A1%3Bs%3A17%3A%22%5B53%2Cnull%2C2592000%5D%22%3B%7D' -H 'accept-encoding: gzip, deflate, br' -H 'x-csrf-token: kq1xE4lWhG-gpWCJNscjqI1I7eb8xtAESilSLzvHyTPx3zJMpBOyMJH2CsVSs0_9zxmctYmxnlQsXTtaSKaZQw==' -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6' -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36' -H 'accept: application/json, text/plain, */*' -H 'cache-control: no-cache' -H 'authority: adminlb.itstep.org' -H 'x-requested-with: XMLHttpRequest' -H 'referer: https://adminlb.itstep.org/exams' --compressed


// Проект Телеграмм Расписание для студентов и преподов
// На выходе API на Yii2 (или .NET by Вово)
// Позже Егор на Питоне подключится к нашему API и релизует нам Телеграмм бота
//
// PHP проект by Олег:
//
// -TODO: Реализоваь обработчики исключений типа СМЕНА РАСПИСАНИЯ, НОВАЯ ПАРА, ПАРА ОТМЕНЕА
// -TODO: API реализуем по этой статье https://klisl.com/yii2-api-rest.html
// -TODO: Возможно прийдется отлавливать удаленых с логбука ТИЧЕРОВ
// -TODO: Возможно прийдется отлавливать удаленых с логбука ГРУППЫ
// -TODO: Подключить сервис логирования ошибок SENTRY
// -TODO ТИчер 73 удален увелоне расписания нет, както надо это пофксить неактивных тичекров удалять
// см в коде по стрингу: траблы не вижу расписание

//
use app\components\MessageToTelegaApi;
use app\models\Groupstep;
use app\models\Teacher;
use app\models\Timetable;
use Codeception\Lib\Generator\Group;
use yii\db\Exception;

/**
 * module module definition class
 */
class Parser
{
    /**
     * {@inheritdoc}
     */
    public $loog_book_user;
    public $loog_book_pass;
    public $loog_book_id_city;
    public $loog_book_id_city_cookies_string;
    //
    public $file_cookies = 'cookies.txt';
    //public $file_cookies = 'cookies.txt';
    public $curl_debug = FALSE;
    public $controllerNamespace = 'app\module\controllers';

    // буфер спарсенного расписани япо вей академи для экшена рассылок уведомлений
    public $curent_rasspisanie_in_mystat = [];

    public $telega;
    public $_telega_list_parser_messages = [];


    /**
     * {@inheritdoc}
     */
    public function __construct()
    {


        $this->telega = new MessageToTelegaApi();
        // инициализация
        $this->telega->API_KEY = \Yii::$app->params['API_KEY'];
        $this->telega->WEBHOOK_URL = \Yii::$app->params['WEBHOOK_URL'];
        $this->telega->API_URL = 'https://api.telegram.org/bot' . $this->telega->API_KEY . '/';


        // ЖИМТОМИР
        $this->loog_book_id_city_cookies_string="63c5f156f33750fada375e816fde6012258e3d72456bfb948ecb54fbea6058a1a%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bs%3A2%3A%2239%22%3B%7D";

        //ЧЕРНОВЦЫ
        //$this->loog_book_id_city_cookies_string="1482b0df4d97fd713ccc1ccca93ee9d6bea22776e845adffbf080f502c24ec2ea%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bi%3A46%3B%7D";


//        $this->curl_debug=1;
        // custom initialization code goes here
    }


    // опытнымпутем выяснили что cs_rfв этом проекте по факту не нужен в запросах авторизации
    function get_cs_rf()
    {
        $content = file_get_contents("https://logbook.itstep.org/login/index#/");
        preg_match('/csrf-token" content="([^"]+)"/', $content, $matches, PREG_OFFSET_CAPTURE);
        return ($matches[1]);
    }


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

    function update_groups____()
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

    // ДОбавляем новых учителей, старых метим неактивными
    // ДОбавляем новых учителей, старых метим неактивными
    // ДОбавляем новых учителей, старых метим неактивными
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
            echo $teacher['id_teach'] . ' - ' . $teacher['fio_teach'] . "\r\n";

            try {
                if ($model->save()) {
                    $i++;

                } else {
                    $model = Teacher::findOne($teacher['id_teach']);
                    $model->status_teacher = 1;
                    $model->save(false);
                    // кто то вернулся из отпуска?
                }

            } catch (\Exception $e) {
                // $model->addError(null, $e->getMessage());
                //  return $this->render('create', ['model' => $model]);
            }
        }


        // защита от сбоя > 10
        if (sizeof($_actual_teacher_list) > 10) {
            // беремвсех активных и сравниваем с $_actual_teacher_list
            $_db_list_teacher = Teacher::getAllActiveTeachers(); // where status =1
            foreach ($_db_list_teacher as $teacher) {
                if (!in_array($teacher->id_teacher, $_actual_teacher_list)) {
                    // НЕ УДАЛЯЕМ НО ДЕЛАЕМ НЕ АКТИВНЫМ
                    $teacher->status_teacher = 0;
                    $teacher->save();
                    //
                } else {
                    // для получения групп надо перекобчить учителя не любого другого кроме глобального эккаунта
                    echo "\n\n{$teacher->id_teacher} {$teacher->name_teacher}  АКТИВНЫЙ !";

//                    $this->change_teacher($teacher->id_teacher);
//                    echo "\nchange_teacher - OK";
                    // !!!!!!!!
                    // ФУНКЦИОНАЛ ПЕРЕНСЕН  ВДРУГУЮ БИБЛИТЕКУ
                    // ТАМ ПАРСИМ ВСЕХ СТУДЕНТОВ и Их ГРУППЫ
                    // т.к. нередки ситуации когда нвую группу препод не видит
                    // до тех пора пока не пролведет хотябы одну пару
//                    $this->update_groups();
//                    echo "\nupdate_groups - OK\n";
                }
            }
        }

        echo "\r\nДобавлено новых учителей: $i\r\n";

    }

    function send_errors_admin_only_message($m)
    {
        // тут тока я получаю ошибки!!!!!!!!!!
        //'440046277';
        //foreach (\Yii::$app->params['admins_chat_id'] as $chat_id) {
        $this->telega->chat_id = '440046277';
        $this->telega->sendMessageAPI($m);
        //}

    }

    //по дефолту Житомр расписание $myCity_id=1 - какому горду принадлжеит это расписание
    function parse_shedul_json($id_teacher, $week = 0, $myCity_id = 1)
    {
        echo $json = $this->get_schedule($week);
        echo $hr = "\n*************************************************************\n\n";

        //\Yii::info("\n\nid_teacher = $id_teacher; week = $week \n $json $hr", 'parsershcheduler');

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
            $this->send_errors_admin_only_message($m);
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

                        // Костыль. Были исклчения когда группа в базе отсуствет а в расписании есть

                        try {
                            $room_id = \Yii::$app->params['roomsName'][$dayOfweeks["num_rooms"]];

                        } catch (\Exception $e) {

                            echo "\nКАБИНЕТ не определен" . $dayOfweeks["num_rooms"];
                            echo "\nКАБИНЕТ не определен" . $dayOfweeks["num_rooms"];
                            echo "\nКАБИНЕТ не определен" . $dayOfweeks["num_rooms"];
                            echo "\nКАБИНЕТ не определен" . $dayOfweeks["num_rooms"];
                            echo "\nКАБИНЕТ не определен" . $dayOfweeks["num_rooms"];

                            //print_r($e);
                            continue;

                        }

                        // если группа не определена = 1:UNDEFINED
                        // перебираем известннгые групп в справочнике с учетом того что на входе может быть нескольк огрупп через ЗП
                        // ИЛИ одна группа но с использованием ЗП в названии
                        // ПЕРВАЯ версия - у нас несолкьо групп чре запятую $item_group! Если не прокатывает
                        if ($myCity_id == 1) {
                            $group_id = empty($groups[trim($item_group)]) ? 1 : $groups[trim($item_group)]; // получаем id по имени
                            // ВТОРАЯ версия пробуем имя группы с запятой $dayOfweeks["groups"]
                            if ($group_id == 1) $group_id = empty($groups[trim($dayOfweeks["groups"])]) ? 1 : $groups[trim($dayOfweeks["groups"])];
                        } else {
                            // смотри таблицы группа и города в БД
                            // не определенная группа для других городов - равна IВ ГОРОДА
                            $group_id = $myCity_id;
                        }

                        $para = [
                            'start_date' => $dates[$dayOfweeks["weekday"]],
                            'start_time' => $dayOfweeks["l_start"],
                            'subject' => $dayOfweeks["short_name_spec"],
                            'teacher_id' => $id_teacher,
                            'group_id' => $group_id,
                            'room_id' => $room_id, // получаем id по имени
                            'city_id' => $myCity_id // какому городу принадлежит это расписание
                        ];

                        if ($para['group_id'] == 1) echo "****** ВНИМАНИЕ ГРУППА -=$item_group=- НЕ ОПРЕДЕЛЕНА ******";


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

        // ОБЯЗАТЕЛЬНЫЕ ПАРАМЕТРЫ
        $model->start_date = $para['start_date'];
        $model->start_time = $para['start_time'];
        $model->subject = $para['subject'];
        $model->teacher_id = $para['teacher_id'];
        $model->room_id = $para['room_id']; //
        $model->group_id = $para['group_id'];
        $model->city_id = $para['city_id'];


        try {
            if ($model->save()) {

                $m = '';
                // соолбщаем варнинг админу ЧТО ИМЯ ГРУППЫ UNDEFINED
                // админа подписать на группу ID = 1
                if ($para['group_id'] == 1) {
                    // СООБЩЕНИЕ ДЛЯ ВСЕХ кто подписан на эту группу Undefine
                    /** @var TODO ПЕРЕДАЛТЬ ПОЛУЧЕНИЕ РАСПИСАНИЯ от сюда https://adminlb.itstep.org/schedule#/groups $m
                     * тут есть и ID препода и ID группы и ID предмета и ID потока и полное название предмета
                     * пока мы парсим расписание из логбука где ID группы не известно и если его сменить - будет сбой
                     */
                    $m = "\n SMALL WARNING - группа есть в расписании, а в базе имя группы отсутстует. Возможно ее переименовали (смотри ниже есть отмененые пары на это же время?). UPDATED: Обычно НОВАЯ групра появляется через сутки в нашей БД. Занесу пока в расписание как UNDEFINED: \n";
                }
                //

                // О НОВЫХ ПАРАХ НА СЛЕД НЕДЕЛЕ НЕ СПАМИМ в понедельник утром
                // остальные дни все сообщаем по мере поступления
                $FirstDay1 = date("Y-m-d", strtotime('monday next week'));
                if (
                    // только о парах на след неделе и более
                    $model->start_date >= $FirstDay1
                    // в данный момент утро понедельника
                    and date('w') == 1
                    // утро
                    and (date('H') == 9 or date('H') == 10)

                ) {
                    echo "ОБнаружена новая пара на след неделю, но сейчас утро понедельника - молчим\n";
                    //
                } else {
                    $this->telega_list_parser_messages(
                        $model,
                        $m . "✏️ <b>ДОБАВЛЕНО</b>\n" . $this->message_template_para($model),
                        $model->teacher_id,
                        $model->group_id
                    );
                }
            } else {
                /** НЕ ОТКРЫВАЙ ЗАСРЕШЬ КОНСОЛЬ */
//                echo "NOT SAVED ВЕРОЯТНО ТАКАЯ ПАРА ЕСТЬ<br>\r\n";
//                print_r($model->getAttributes());
//                print_r($model->getErrors());
//                print_r($para);
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

        return $this->send_http_post($url, "POST", $headers, '');


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


    function get_groups___()
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

        // разлогиниваемся
        // надо для отладки
        //@unlink($this->file_cookies);


        $post_fields = '{"LoginForm":{"id_city":"39","username":"' . $this->loog_book_user . '","password":"' . $this->loog_book_pass . '"}}';
        $url = 'https://logbook.itstep.org/auth/login';
        $headers =
            [
                // не трогать - магия
                'Cookie: city_id='.$this->loog_book_id_city_cookies_string,
                //'Cookie: city_id=1482b0df4d97fd713ccc1ccca93ee9d6bea22776e845adffbf080f502c24ec2ea%3A2%3A%7Bi%3A0%3Bs%3A7%3A%22city_id%22%3Bi%3A1%3Bi%3A46%3B%7D',
                'origin: https://logbook.itstep.org',
                //ошибка безопасности
                //'x-csrf-token: qCtF_ObmoGocC61q94TaviGxXAiJG9twNFcg88cL3V_RYT2Ol97yCUpuyz6fyavdaOA3fM5Y7BcZNBmjgSaJCw==',
                'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6',
                'x-requested-with: XMLHttpRequest',
                'pragma: no-cache',
                'user-agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Mobile Safari/537.36',
                'content-type: application/json;charset=UTF-8',
                'accept: application/json, text/plain, */*',
                'cache-control: no-cache',
                'authority: logbook.itstep.org',
                'referer: https://logbook.itstep.org/login/index',
                'dnt: 1'
            ];
        $a = $this->send_http_post($url, "POST", $headers, $post_fields);
        if ($this->curl_debug) echo "\r\nАвторизация ответ: " . $a;

    }

    function set_cookie_idcity()
    {
        //       #HttpOnly_logbook.itstep.org	FALSE	/	FALSE	0	PHPSESSID	eb8n7h1nbhenbh8mftorgrff6q
        $cook_city = "#HttpOnly_logbook.itstep.org	FALSE	/	FALSE	0	city_id	".$this->loog_book_id_city_cookies_string;
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
        $this->set_cookie_idcity();

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
                    'message' => 'ПРЕПОД. ЗАМЕНЕН',
                    'IDMySQL' => $id_mysql,
                    'IDMyStatArr' => $key,
                ];
        }

        //дата/время тема препод ЕСТЬ - кабинет не найден = КАБИНЕТ ИЗМЕНЕН
        if ($mystat_p['room_id'] != $room_id) {
            return
                [
                    'changed' => 1,
                    // ВНИАНИЕ 'КАБИНЕТ ИЗМЕНЕН' ключ и к нему привязано далее условие!!
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
    //$send_old_teacher_id если препод был заменен - всем подписчиам старого препода тоже надо об этом знать
    function telega_list_parser_messages($model_para, $message, $send_teacher_id, $send_group_id, $send_old_teacher_id = '')
    {

        /** СКЛЕИВАЕМ РОДСТВЕННЫЕ СООБЩЕНИЯ (группа) В ОДНО **/

        $last_index = sizeof($this->_telega_list_parser_messages) - 1;
        if (
            isset($this->_telega_list_parser_messages[$last_index])
            and
            $send_teacher_id == $this->_telega_list_parser_messages[$last_index]['teacher_id']
            and
            $send_group_id == $this->_telega_list_parser_messages[$last_index]['group_id']

//  сообщения для одного препода и одной группы в одной сесси парсера можно объеденить
//            AND
//            $model_para->start_date == $this->_telega_list_parser_messages[$last_index]['start_date']
//            AND
//            $model_para->subject == $this->_telega_list_parser_messages[$last_index]['subject']

        ) {
            $this->_telega_list_parser_messages[$last_index]['message'] .= "\r\n\r\n" . $message;

        } else {
            $this->_telega_list_parser_messages[] = [
                'para_id' => $model_para->id_tt,
                'message' => $message,
                'teacher_id' => $send_teacher_id,
                'old_teacher_id' => $send_old_teacher_id,
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
                // ПАРА ИЗМЕНЕНА
                $message = "❌ <b>" . $changed['message'] . "</b>\n";
                // СТАРАЯ ПАРА
                $message .= $this->message_template_para($model);

                // -1 - признак что пара была отменена
                // если пара отменена - сюда не заходим подправим сообщение
                // если пара отменена - сюда не заходим подправим сообщение
                // если пара отменена - сюда не заходим
                if ($changed['IDMyStatArr'] == -1) {

                    $this->telega_list_parser_messages($model, $message, $model->teacher_id, $model->group_id);
                    // УДАЛЯЕМ отмененую пару
                    // УДАЛЯЕМ отмененую пару
                    //
                    // УДАЛЯЕМ старую из перебираемых
                    // УДАЛЯЕМ старую из перебираемых
                    $del_subject = $model->subject;
                    $del_group_id = $model->group_id;
                    //
                    $model->delete();
                    // выбрать всех ее родствников и пронумировать
                    // выбрать всех ее родствников и пронумировать
                    $bs = Timetable::find()
                        ->where([
                            'subject' => $del_subject,
                            'group_id' => $del_group_id,
                        ])
                        ->orderBy(['start_date' => SORT_ASC, 'start_time' => SORT_ASC])
                        ->all();
                    $i = 1; // дятел
                    foreach ($bs as $b) {
                        $b->countpara = $i++;
                        $b->save();
                    }
                    // КОНЕЦ // УДАЛЯЕМ старую из перебираемых

                    continue;
                }

                $old_id_teacher = $model->teacher_id;
                //
                // УДАЛЯЕМ старую из перебираемых
                // УДАЛЯЕМ старую из перебираемых
                $del_subject = $model->subject;
                $del_group_id = $model->group_id;
                //
                $model->delete();
                // выбрать всех ее родствников и пронумировать
                // выбрать всех ее родствников и пронумировать
                $bs = Timetable::find()
                    ->where([
                        'subject' => $del_subject,
                        'group_id' => $del_group_id,
                    ])
                    ->orderBy(['start_date' => SORT_ASC, 'start_time' => SORT_ASC])
                    ->all();
                $i = 1;
                foreach ($bs as $b) {
                    $b->countpara = $i++;
                    $b->save();
                }
                // КОНЕЦ // УДАЛЯЕМ старую из перебираемых


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
                // оступы с учтоем прикеиться к предыдущему сообщениюближе
                $message .= "\n<b>ОБНОВЛЕННЫЕ ДАННЫЕ:</b>\n" . $this->message_template_para($model) . "";

                // TODO ШЛЕМ СООБЩЕНИЕ - пишем измененую и шлем сообщение ВСЛУЧАЕ ЕЛИС НОВАЯ ПАРА об обновленной паре в базе
                /**$this->saveNewTimeTableANDSendMessage($this->curent_rasspisanie_in_mystat[$changed['IDMyStatArr']]);**/

                // если препод сменился - назначаем собвытие привязанное к ID старого препода
                // ВНИМАНИЕ склеить 2 пары в одну в этом случае не получится т.к. преподы разные и идут друг за другом
                // 1 пара - лвум преподам рассылдется, 2 пара двум преподам рассылается !!
                if ($model->teacher_id == $old_id_teacher) {
                    $old_id_teacher = '';
                }


                // если кабинет заменен в будущем через больше чем черз 2 дня, то пропускаем ничего не шлем
                if ($changed['message'] == 'КАБИНЕТ ИЗМЕНЕН') {
                    $timestamp = strtotime($model->start_date);
                    $delta = $timestamp - time();
                    // более 2 суток
                    if ($delta > 172800) {
                        continue;
                    }
                }
                // менее 2 суток кабинет


                //
                $this->telega_list_parser_messages($model, $message, $model->teacher_id, $model->group_id, $old_id_teacher);
                //                    $this->telega->chat_id = '440046277';
                //                    $this->telega->sendMessageAPI($message);
            } else {

                //echo "- Измненений нет.\r\n";
                //echo "- Измненений нет.\r\n";
                //echo "- Измненений нет.\r\n";
            }

        }
        //echo "compare_what_changed_and_deleted END !!!!!!!!! \r\n";
        return;

    }


    private function message_template_para($model)
    {

        //$date = date('d-m-Y', strtotime($model->start_date));
        $dayMounth = MyHelper::reverceDateFromAmeric($model->start_date);
        $room_name = \Yii::$app->params['roomsId'][$model->room_id];
        // орядковый номер новой пары тут скорей всег еще отсуствует, по этому бесполезно тут считать ее
        // порядковый номер рассчитывается в конце после всех экзекуций
        $num = $model->countpara ? ' #' . $model->countpara : '';

        return "🗓 " . MyHelper::stringNameWeek($model->start_date) . " " .
            $this->strDate2WeekDay($model->start_date) . " {$dayMounth}, {$room_name}, {$model->start_time} <b>\"{$model->subject}\"</b>
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
