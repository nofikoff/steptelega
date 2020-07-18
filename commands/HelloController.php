<?php
/**
 *
 * Исключить из Комита
 * HelloController.php можно не исключать т.к. класов авторизации не будет
 * /components/Parser.php
 * /components/ParserExamens.php
 * эти исключить обязательно в них пароли и автолризация
 * / не комиттить папку /config там пароли к БД и Телеграму - передать его в ручнуюб
 *
 */

namespace app\commands;

use app\components\MyHelper;
use app\components\Parser;
use app\components\ParserExamens;
use app\models\Groupstep;
use app\models\Teacher;
use app\models\TelegramSubscribtion;

//use app\module\parser;
use app\models\Timetable;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\ArrayHelper;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{


    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */

    public function actionTest()
    {



        while ($a = Timetable::find()
            ->where(['countpara' => 0])
            ->one()) {
            // выбрать всех ее родствников и пронумировать
            $bs = Timetable::find()
                ->where([
                    'subject' => $a->subject,
                    'group_id' => $a->group_id,
                ])
                ->orderBy(['start_date' => SORT_ASC, 'start_time' => SORT_ASC])
                ->all();
            $i = 1;
            foreach ($bs as $b) {
                $b->countpara = $i++;
                $b->save();
                echo $i;
                echo " " . $b->start_date . " - " . $b->start_time . "; ";
            }
            echo "\r\n ";

        }


    }

    public function actionCronDaily__()
    {
        // фотки собираем с преподов _ НЕ ИСПОЛЬЗУЕТСЯ  т.к. фоток всего несколько
        $teachers = Teacher::getAllActiveTeachers();
        foreach ($teachers as $teacher) {
            $_ext_file_ = "https://mystatfiles.itstep.org/photos/zhitomir/teach/t_{$teacher->id_teacher}.jpg";
            if (MyHelper::is_url_exist($_ext_file_)) {
                echo $_ext_file_ . "\n ";
                file_put_contents(\Yii::$app->params['absolut_path'] . "/web/images/teachers/t_{$teacher->id_teacher}.jpg", fopen($_ext_file_, 'r'));
            }
        }
    }

    public function actionCron()
    {
        //ВСЕ НОВОЕ РАСПИСАНИЕ В ОДИН МАССИВ!!
        $a = new Parser();

        //define('_BOT_NAME', 'StepToday_bot');
        define('_BOT_NAME', 'ITStepZhitomir_bot');


        /**  НЕ ТРОГАЙ !!!!  */
        /**  НЕ ТРОГАЙ !!!!  */
        /**  НЕ ТРОГАЙ !!!!  */
        // ЧЕРНОВЦЫ
        // ЧЕРНОВЦЫ
        // ЧЕРНОВЦЫ
        // разлогиниваемся
        @unlink("/home/steptelega/public_html/cookies.txt");
        $a->loog_book_user = \Yii::$app->params['logbook_Chernovtsy_Zhitomir_my_login'];
        $a->loog_book_pass = \Yii::$app->params['logbook_Chernovtsy_Zhitomir_my_pass'];
        // черновцы 46
        $a->loog_book_id_city = "46";
        $a->get_auth();

        // переключим город дропдаун в логбуке
        // переключим город дропдаун в логбуке
        $headers =
            [
                'authority: logbook.itstep.org',
                'pragma: no-cache',
                'cache-control: no-cache',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: same-origin',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'referer: https://logbook.itstep.org/',
                'accept-language: en-UA,en;q=0.9,ru-AU;q=0.8,ru;q=0.7,en-US;q=0.6',
            ];
        $b = $a->send_http_post("https://logbook.itstep.org/auth/change-city?city=" . $a->loog_book_id_city, "GET", $headers);

        // этоту тта не надо
        //$a->change_teacher(60);
        // пишем в мое распиание 60
        $a->parse_shedul_json(60, 0, 3);
        $a->parse_shedul_json(60, 1, 3);
        $a->parse_shedul_json(60, 2, 3);
        $a->parse_shedul_json(60, 3, 3);


        /**  НЕ ТРОГАЙ !!!!  */
        /**  НЕ ТРОГАЙ !!!!  */
        /**  НЕ ТРОГАЙ !!!!  */
        // ЛУЦК
        // ЛУЦК
        // ЛУЦК
        //@unlink("/home/steptelega/public_html/cookies.txt");

        $a->loog_book_user = \Yii::$app->params['logbook_Lutsk_my_login'];
        $a->loog_book_pass = \Yii::$app->params['logbook_Lutsk_my_pass'];
        $a->loog_book_id_city = "null";
        $a->get_auth();
        // этоту тта не надо
        //$a->change_teacher(60);
        // пишем в мое распиание 60
        $a->parse_shedul_json(60, 0, 2);
        $a->parse_shedul_json(60, 1, 2);
        $a->parse_shedul_json(60, 2, 2);
        $a->parse_shedul_json(60, 3, 2);


        // DONT MOVE !!! -2019.09 сервис закрыт с внешнего мира по IP https://adminlb.itstep.org/
        // договорился - открыли
        //здесь получаем списки студентов а заодно актуальный справочник групп с их ID
        //если парсить с логубка -список новых групп получам без id тока по имени
        //
        $b = new ParserExamens();
        $b->get_all_active_students_and_groups();


        $a->loog_book_user = \Yii::$app->params['logbook_manager_user'];
        $a->loog_book_pass = \Yii::$app->params['logbook_manager_password'];
        $a->loog_book_id_city = "39";
        $a->get_auth();
        // здесь под глобальным эккаунтом обновляем активный справочник тичеров
        // отключаем удаленных тичеров статус = 0
        $a->update_teachers_and_groups();

        // для получения групп надо перекобчить сессию на эккаунт учителя не любого другого кроме глобального эккаунта
        $teachers = Teacher::getAllActiveTeachers();
        foreach ($teachers as $teacher) {
            echo "\r\n ********** " . $teacher->id_teacher . " - МЕНЯЕМ И ПАРСИМ 4 НЕДЕЛ/И ТИЧЕРА !!!!!!!!!" . $teacher->name_teacher . " \r\n\r\n";
            $a->change_teacher($teacher->id_teacher);
            echo "\nchange_teacher - OK";

            // две недели
            // СНАЧАЛА ПИШЕМ ВСЕ ПАРЫ ЧТО МОЖЕМ ПИСАТЬ (без учета измененных)
            $a->parse_shedul_json($teacher->id_teacher, 0);
            $a->parse_shedul_json($teacher->id_teacher, 1);
            $a->parse_shedul_json($teacher->id_teacher, 2);
            $a->parse_shedul_json($teacher->id_teacher, 3);
        }

        // отмечаем тичеров и группы у которых нет расписания - НЕт раписания и не показываем в боте
        // отмечаем тичеров и группы у которых нет расписания - НЕт раписания и не показываем в боте
        Groupstep::DetectNotActiveByTameTable();
        Teacher::DetectNotActiveByTameTable();


        // здесь экшен рассылки уведомлений
        // сохраним все текущие пары на неделю для сравнения и поиска измненых и отмененых
        // если распсание есть на неделю, сравним его с БД
        if (sizeof($a->curent_rasspisanie_in_mystat)) {
            // сравниваем реальный вектор спарсенных пар с базой что мы получили
            // здесь важны сообщения об имзененных парах - те что не сохранились
            $a->compare_what_changed_and_deleted();
            echo "******** МАССИВ СООБЩЕНИЙ В ЭТОЙ ИТЕРАЦИИ ***********";
            echo "******** МАССИВ СООБЩЕНИЙ В ЭТОЙ ИТЕРАЦИИ ***********";
            echo "******** МАССИВ СООБЩЕНИЙ В ЭТОЙ ИТЕРАЦИИ ***********";

            print_r($a->_telega_list_parser_messages);
            $_log_sended_chats = [];

            // перебираем журнал сообщений на отправку
            foreach ($a->_telega_list_parser_messages as $para) {

                // на входе сообщение и ID группы и пепода кого это касатся
                // задача разослать всем кто подписан на препода или гроуппу
                // НО не слать одно и тоже сообщение дважды за данную итерацию
                // веротяно придется накапливать массив отправленных сообщений ID пары уникален
                // и пропускать для конкрентного ID чат повтор ID пары
                //            // Отправка сообщения всем кто подписан -
                //            // на входе id_group id_teacher для данной пары
                // сначала получим всех кто подписан на данную группу или ттиичера и им разошлем
                $s = TelegramSubscribtion::find()
                    // учитываем что если препод заменился - старому подписчикам тоже шлем сообщени
                    // итого это сообщение шлем подписчикам текущей грурппы и двух препподво - если они поменялись
                    ->where("`group_id` = '{$para['group_id']}' OR `teacher_id` = '{$para['teacher_id']}' OR `teacher_id` = '{$para['old_teacher_id']}'")
                    ->all();

                $admin_control_list_recipients_str = '';
                // рассылка подписчикам
                if (!empty($s))
                    foreach ($s as $subsc) {
                        // если еще об этой паре небыол оповещение вданной итерации
                        if (!isset($_log_sended_chats[$subsc->chat_id][$para['message']])) {
                            $a->telega->chat_id = $subsc->chat_id;


                            $a->telega->add_message_start_link = true;
                            $a->telega->sendMessageAPI($para['message']);
                            $_log_sended_chats[$subsc->chat_id][$para['message']] = 1;
                            $admin_control_list_recipients_str .= " To: <b>{$subsc->chat_name}</b> {$subsc->chat_id}\n";
                        }
                    }


                // контрольное сообщениеадмину, если выше админ сам ранее лично не получил
                // '440046277';
                // админ тоже человек - больше 20 сообщений за час не шлем (быват и 200-300)
                // СООБЩАЕМ ТОЛЬКО ЕСЛИ ЛЕНТА КОРТОКАЯ
                if (sizeof($a->_telega_list_parser_messages) < 20) {
                    foreach (\Yii::$app->params['admins_chat_id'] as $chat_id) {
                        // если админ ранее получил это уведомление, нет смысла ему слать  повтоно, проверим его ID
                        if (!isset($_log_sended_chats[$chat_id][$para['message']])) {
                            if (empty($admin_control_list_recipients_str)) $admin_control_list_recipients_str = 'На это уведомление не было подписчиков';
                            $a->telega->chat_id = $chat_id;
                            $a->telega->add_message_start_link = true;
                            $a->telega->sendMessageAPI("   **** АДМИН КОПИЯ ****\n{$admin_control_list_recipients_str}\n\n" . $para['message']);
                            $_log_sended_chats[$chat_id][$para['message']] = 1;
                        }
                    }
                }
            }
        }
        // чтбы контролер не ругался надо экзит


        // перенумеруем полученные пары сколько всего пар и пр
        // перенумеруем полученные пары сколько всего пар и пр
        // перенумеруем полученные пары сколько всего пар и пр
        // TODO: ЕСЛИ удаляем пару, то тоже пересчитываем порядковы номер пары см выше  есть?
        while ($a = Timetable::find()
            ->where(['countpara' => 0])
            ->one()) {
            // выбрать всех ее родствников и пронумировать
            $bs = Timetable::find()
                ->where([
                    'subject' => $a->subject,
                    'group_id' => $a->group_id,
                ])
                ->orderBy(['start_date' => SORT_ASC, 'start_time' => SORT_ASC])
                ->all();
            $i = 1;
            foreach ($bs as $b) {
                $b->countpara = $i++;
                $b->save();
                echo $i;
                echo " ";
            }
            echo "\r\n ";
        }


        exit;
        exit;
        exit;

    }


}
