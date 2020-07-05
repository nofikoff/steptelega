<?php

namespace app\controllers;

use app\components\MessageToTelegaApi;
use app\components\MyHelper;
use app\models\Groupstep;
use app\models\Students;
use app\models\Teacher;
use app\models\TelegramSession;
use app\models\TelegramSubscribtion;
use app\models\Timetable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;


class ApiController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => 'yii\filters\AccessControl',
                'rules' => [
                    [
                        // любой экшен - но не контролер
                        // если указать нуно контроллер - пиши бихевире внутри этого контролеера
                        'actions' => ['init', 'webhook'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],

                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function () {
                    return Yii::$app->response->redirect(['user/login']);
                },
            ],

        ];
    }

    private $telega;
    public $debug = 0;
    public $inline_start_button_menu = [];
    public $subscrbtion_menu = [];
    public $start_menu = [];
    public $today_menu = [];


    // отключаем для API POST CSRF параметр
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function init()
    {
        $this->telega = new MessageToTelegaApi();
        // инициализация
        $this->telega->API_KEY = Yii::$app->params['API_KEY'];
        $this->telega->WEBHOOK_URL = Yii::$app->params['WEBHOOK_URL'];
        $this->telega->API_URL = 'https://api.telegram.org/bot' . $this->telega->API_KEY . '/';
        $this->telega->debug = $this->debug;


        parent::init();
    }

    // WebHoook СТАРЫЙ БОТ @StepToday
    public function actionInit()
    {
        // ВНИМАНИЕ ЭТО СТАРЫЙ БОТ
        define('_BOT_NAME', 'StepToday_bot');

        /**
         * ЭТО ДЛЯ ТЕСТОВ
         * $a = new MyHelper();
         * $a->actionInit();
         * exit;
         * exit;
         * exit;
         **/
        $this->telega = new MessageToTelegaApi();
        // инициализация
        $this->telega->API_KEY = Yii::$app->params['API_KEY0'];
        $this->telega->WEBHOOK_URL = Yii::$app->params['WEBHOOK_URL0'];
        $this->telega->API_URL = 'https://api.telegram.org/bot' . $this->telega->API_KEY . '/';
        $this->telega->debug = $this->debug;
        $this->telega->sendMessageAPI(
            " По техническим причинам бот сменил название и переехал сюда <a href='https://t.me/ITStepZhitomir_bot'>@ITStepZhitomir_bot</a>
Этот бот StepToday остается для тестов
"
        );
        return;
    }

// WebHoook
    public function actionWebhook()
    {
        define('_BOT_NAME', 'ITStepZhitomir_bot');

        //        // инициализация см в констуркторе
        //        $this->telega->API_KEY = Yii::$app->params['API_KEY0'];
        //        $this->telega->WEBHOOK_URL = Yii::$app->params['WEBHOOK_URL0'];
        //        $this->telega->API_URL = 'https://api.telegram.org/bot' . $this->telega->API_KEY . '/';
        //        $this->telega->debug = $this->debug;


        //  МЕТКА ТЕКУЩЕГО ДНЯ ЧТО ОТОБРАЖАЕТСЯ
        $_curr_delta_inc = 0;
        if (
            mb_stripos($this->telega->message_on_webhook, "/today/") === 0 OR mb_stripos($this->telega->message_on_webhook, "/free/") === 0
        ) {
            $_curr_delta_inc = explode('/', $this->telega->message_on_webhook);
            // указан инкремент
            if (isset($_curr_delta_inc[2])) {
                $_curr_delta_inc = $_curr_delta_inc[2] * 1;
            } else {
                // /today
                $_curr_delta_inc = 0;
            }
        }

        //
        $this->inline_start_button_menu =
            [
                ['text' => '🏠 Главная. ⏱ ' . date('H:i:s', time()) . '', 'callback_data' => '/start'],
            ];

        $this->start_menu =
            [
                $this->inline_start_button_menu,
                [
                    ['text' => '-1 день', 'callback_data' => '/today/' . ($_curr_delta_inc - 1)],
                    ['text' => 'Сегодня 🌐 ', 'callback_data' => '/today'],
                    ['text' => '+1 день', 'callback_data' => '/today/' . ($_curr_delta_inc + 1)],
                ],
                [
                    ['text' => 'Группы 👥', 'callback_data' => 'SelectFroup'],
                    ['text' => 'Препод 👨‍🏫', 'callback_data' => 'SelectTeacher'],
                    ['text' => 'Настройки', 'callback_data' => '/settings'],
                ]
            ];

        $this->today_menu =
            [
                $this->inline_start_button_menu,
                [
                    ['text' => '-1 день', 'callback_data' => '/free/' . ($_curr_delta_inc - 1)],
                    //Свободные аудитории
                    ['text' => 'Free 🆓 ', 'callback_data' => '/free'],
                    ['text' => '+1 день', 'callback_data' => '/free/' . ($_curr_delta_inc + 1)],
                ],
                [
                    ['text' => '-1 день', 'callback_data' => '/today/' . ($_curr_delta_inc - 1)],
                    ['text' => 'Сегодня 🌐 ', 'callback_data' => '/today'],
                    ['text' => '+1 день', 'callback_data' => '/today/' . ($_curr_delta_inc + 1)],
                ],

            ];


        $this->subscrbtion_menu =
            [
                $this->inline_start_button_menu,
                [
                    ['text' => 'Группы 👥', 'callback_data' => 'SelectFroup'],
                    ['text' => 'Препод 👨‍🏫', 'callback_data' => 'SelectTeacher'],
                ],
                [
                    ['text' => 'Выключить все уведомления ❌', 'callback_data' => '/settings=DelAll'],
                ]
            ];


        /** ЕСТЬ ЗАПРОС */
        if ($this->telega->message_on_webhook) {

            /** ЧАТ студентов */
            // ЕСЛИ Бота юзает какойто чат студентов - гасим его попытки использовать

            if ($this->catchGroupsubscr() ) {

                //
//                $this->telega->sendMessageAPI(
//                   // "Диалог с ботом заблокирован в этом чате. Для личного использования см. <a href='https://t.me/ITStepZhitomir_bot'>ITStepZhitomir_bot</a>"
//                    "..."
//                );
                return;
            }

            /** АДМИНСКИЕ КОМАНДЫ */
            if (mb_stripos($this->telega->message_on_webhook, "/subteachers") === 0) {
                $a = TelegramSubscribtion::find()
                    ->where('teacher_id is not NULL')
                    ->all();
                $m = "НА ПРЕПОДОВ ПОДПИСАНЫ:\n";
                $count = 0;

                foreach ($a as $item) {
                    $template = ' * WHO:$who / НА:<b>$teacher_name</b>' . "\n";
                    $vars = array(
                        '$who' => $item->chat_name,
                        '$chat_id' => $item->chat_id,
                        '$teacher_name' => $item->teacher->name_teacher,
                    );
                    // обходимограчние по размеру сообщения
                    $count++;
                    if ($count > 50) {
                        $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                        $m = '';
                        $count = 0;
                    }
                    $m .= strtr($template, $vars);
                }
                $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                return;
            }
            if (mb_stripos($this->telega->message_on_webhook, "/subgroups") === 0) {
                $a = TelegramSubscribtion::find()
                    ->where('group_id is not NULL')
                    ->all();
                $m = "НА ГРУППЫ ПОДПИСАНЫ:\n";
                $count = 0;
                foreach ($a as $item) {
                    $template = ' * WHO:$who / НА:<b>$teacher_name</b>' . "\n";
                    $vars = array(
                        '$who' => $item->chat_name,
                        '$chat_id' => $item->chat_id,
                        '$teacher_name' => $item->group->name_group,
                    );
                    $m .= strtr($template, $vars);

                    // обходимограчние по размеру сообщения
                    $count++;
                    if ($count > 50) {
                        $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                        $m = '';
                        $count = 0;
                    }

                }
                $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                return;
            }
            if (mb_stripos($this->telega->message_on_webhook, "/subusers") === 0) {
                $a = TelegramSession::find()
                    //->where('group_id is not NULL')
                    ->orderBy('updated ASC')
                    ->all();
                $m = "ВСЕ ПОЛЬЗОВАТЕЛИ:\n";
                $count = 0;
                foreach ($a as $item) {
                    $template = ' * $date <b>$who</b>' . "\n";
                    $vars = array(
                        '$who' => $item->name_chat,
                        '$date' => $item->updated,
                    );
                    $m .= strtr($template, $vars);

                    // обходимограчние по размеру сообщения
                    $count++;
                    if ($count > 50) {
                        $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                        $m = '';
                        $count = 0;
                    }
                }
                $this->telega->sendMessageAPI($m . "\n ... /subteachers /subgroups /subusers");
                return;
            }


            /** /START */
            /** DEEP LINKS */
            // всегда и веде первый чтбы мож
            //но было на него выйти при любом условии!!!
            if (mb_stripos($this->telega->message_on_webhook, "/start") === 0) {

                /** DEEP LINKS НА ПРЕПОДВ И ГРУППЫ*/
                // см ниже в кнопках ты сам задал ссылку /start tchr=12=1
                // то на входе текст типа : "text":"\/start tchr=13=1"
                // то на входе текст типа : "text":"\/start tchr=13=1"
                $a = explode(' ', $this->telega->message_on_webhook);
                //
                if (isset($a[1])) {
                    $b = explode('=', $a[1]);
                    if (isset($b[0])) {
                        if (empty($b[2])) $b[2] = 0;


                        // ЕСЛИ НОМЕР препода группы задан, формируем меню переключения НЕДЕЛь
                        // chr grp хърангится в переменной ДОБАВЛЕНО
                        $menu = [
                            [
                                ['text' => '-1 нед. 🗓', 'callback_data' => '/start ' . $b[0] . '=' . $b[1] . '=' . ($b[2] - 1)],
                                ['text' => 'Эта неделя', 'callback_data' => '/start ' . $b[0] . '=' . $b[1] . '=0'],
                                ['text' => '+1 нед. 🗓', 'callback_data' => '/start ' . $b[0] . '=' . $b[1] . '=' . ($b[2] + 1)],
                            ],
                        ];
                        // тип объекта, ID объекта, номер недели
                        $this->showTimeTableForGroupORTeacer($b[0], $b[1], $b[2], $menu);
                        return;
                    }
                }

                /** /START -ГЛАВНАЯ СТРАНИЦА*/
                // DEEP LINK не разобран или просто /start
                // DEEP LINK не разобран или просто /start
                // DEEP LINK не разобран или просто /start
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $this->start_menu;
                $tod_ay = "Сегодня <b>" . \app\components\MyHelper::strTime2WeekDay(time()) . " " . \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d")) . "</b>";


                // костыль, если пользователь подписан на один з гуппу или препода
                // выводим на неделю распиание тут же после привествия
                $b = $this->helpActionGetStatusbyChat($this->telega->chat_id);
                if (sizeof($b)) {
                    if (isset($b['teachers'])) {
                        // есть жертва на кого мы подписаны - покажем кнопки ег рсаписания
                        // ссылки формируем в стиле DEEP LINK - как они попадают нам из JSON
                        // чтобы о бщий обработчик на них был
                        $menu = [
                            [
                                ['text' => '-1 нед. 🗓', 'callback_data' => '/start tchr=' . array_keys($b['teachers'])[0] . '=-1'],
                                ['text' => 'Эта неделя', 'callback_data' => '/start tchr=' . array_keys($b['teachers'])[0] . '=0'],
                                ['text' => '+1 нед. 🗓', 'callback_data' => '/start tchr=' . array_keys($b['teachers'])[0] . '=1'],
                            ],
                        ];
                        $this->telega->keyboard_buttons = array_merge($this->start_menu, $menu);
                        $name = Teacher::findOne([array_keys($b['teachers'])[0]])->name_teacher;
                        $message_subscribed = "По умолчанию вам показывается расписание преподавателя <b>" . $name . "</b>";
                        $a = new TimetableController(0, '');
                        $message_subscribed .= $a->helperTeacherTimeTableNowOnMain(array_keys($b['teachers'])[0]);


                    } else if (isset($b['groups'])) {
                        // есть жертва на кого мы подписаны - покажем кнопки ег рсаписания
                        // ссылки формируем в стиле DEEP LINK - как они попадают нам из JSON
                        // чтобы о бщий обработчик на них был
                        $menu = [
                            [
                                ['text' => '-1 нед. 🗓', 'callback_data' => '/start grp=' . array_keys($b['groups'])[0] . '=-1'],
                                ['text' => 'Эта неделя', 'callback_data' => '/start grp=' . array_keys($b['groups'])[0] . '=0'],
                                ['text' => '+1 нед. 🗓', 'callback_data' => '/start grp=' . array_keys($b['groups'])[0] . '=1'],
                            ],
                        ];

                        $this->telega->keyboard_buttons = array_merge($this->start_menu, $menu);
                        $name = Groupstep::findOne([array_keys($b['groups'])[0]])->name_group;
                        $message_subscribed = "По умолчанию вам показывается расписание группы 👥 <b>" . $name . "</b>";
                        $a = new TimetableController(0, '');
                        $message_subscribed .= $a->helperGroupTimeTableNowOnMain(array_keys($b['groups'])[0]);

                        //
                    }


                    Yii::$app->params['message_intro1'] .=
                        "\n" .
                        Yii::$app->params['message_help'] .
                        "\n\n" .
                        $message_subscribed;
                } else {
                    Yii::$app->params['message_intro1'] .= "\n\n" .
                        Yii::$app->params['message_intro2'] . "\n\n" .
                        Yii::$app->params['message_help'] .
                        "\n{$tod_ay}\n\n";
                }
                $this->telega->sendMessageAPI(Yii::$app->params['message_intro1']);


                // ЮЗВЕР первый раз используе бота и запускает /start не через кнопку ГЛАВНАЯ с колбеком
                // а впервые зашел на бота и вызвал контексное меню /start
                // запишем его
                if (!$this->telega->is_callback) {
                    $subscribtion = new TelegramSession();
                    $subscribtion->id_chat = strval($this->telega->chat_id);
                    $subscribtion->name_chat = (strval($this->telega->user_name) == '') ? strval($this->telega->chat_id) : strval($this->telega->user_name);
                    $subscribtion->save();
                }


                return;
            }

            /** /help */
            if (mb_stripos($this->telega->message_on_webhook, "/help") === 0) {
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $this->start_menu;

                $stat_stud_all = Students::find()->count();

                $stat_par_active = Timetable::find()->where('`start_date` > NOW()')->count();
                $stat_par_all = Timetable::find()->count() - $stat_par_active;

                $stat_prepod_all = Teacher::find()->count();
                //$stat_prepod_active = Teacher::find()->joinWith('timetables')->where('start_date > NOW()')->groupBy('teacher_id')->count();
                $stat_prepod_active = Teacher::find()->where('not_in_timetable_today = 0')->count();

                $stat_group_all = Groupstep::find()->count();
                //$stat_group_active = Groupstep::find()->joinWith('timetables')->where('start_date > NOW()')->groupBy('group_id')->count();
                $stat_group_active = Teacher::find()->where('not_in_timetable_today = 0')->count();


                $stat_users_all = TelegramSession::find()->count();

                $stat_users_subsc_prep = TelegramSubscribtion::find()->where('`teacher_id` IS NOT NULL')->groupBy('teacher_id')->count();
                $stat_users_subsc_group = TelegramSubscribtion::find()->where('`group_id` IS NOT NULL')->groupBy('group_id')->count();

                $sub_users_prep = TelegramSubscribtion::find()->where('`teacher_id` IS NOT NULL')->groupBy('chat_id')->count();
                $sub_users_group = TelegramSubscribtion::find()->where('`group_id` IS NOT NULL')->groupBy('chat_id')->count();

                // превью для хелпа на полиграфе
                $this->telega->disable_web_page_preview = 0;

                //

                $this->telega->sendMessageAPI(
                    "<b>СПРАВОЧНИК</b> 
Bot адрес @ITStepZhitomir_bot
Ваш ChatID = <b>" . $this->telega->chat_id . "</b>
Инструкция <a href='https://telegra.ph/ITStepZhitomir-bot-Instrukciya-06-12'>здесь</a>

<b>ПАРЫ ПО ВРЕМЕНИ:</b>
   №1 09:00-10:20
   №2 10:30-11:50
   №3 12:00-13:20
   №4 13:30-14:50
   №5 15:00-16:20
   №6 16:30-17:50
   №7 18:00-19:20
   №8 19:30-20:50

<b>СТАТИСТИКА:</b>
Всего пользователей/чатов: <b>$stat_users_all</b>.
Подписаны на обновления в расписании:
   <b>$sub_users_prep</b> пользователей/чатов подписаны на <b>$stat_users_subsc_prep</b> препод.; 
   <b>$sub_users_group</b> пользователей/чатов подписаны на <b>$stat_users_subsc_group</b> групп; 

В активном расписании (запланированные пары):
   <b>$stat_par_active</b> пар (завершено <b>$stat_par_all</b> c 15.04.19);
   <b>$stat_stud_all</b> студентов всего;
   <b>$stat_group_active</b> групп (всего <b>$stat_group_all</b>);
   <b>$stat_prepod_active</b> преподавателей (всего <b>$stat_prepod_all</b>);
 
<b>РАЗРАБОТЧИКИ:</b>
 - студент группы РПЗСТ-о161: Олег Статкевич
 - студент группы РПЗСТ-о161: Никита Юрченко
 - руководитель проекта: преподаватель <a href='https://t.me/Nofikoff'>Руслан Новиков</a>
  
 Ждем ваши рекомендации и пожелания!

");
                return;
            }

            /** TODAY */
            if (mb_stripos($this->telega->message_on_webhook, "/today") === 0) {
                $_curr_delta_inc = explode('/', $this->telega->message_on_webhook);
                // указан инкремент
                if (isset($_curr_delta_inc[2])) {
                    $_curr_delta_inc = $_curr_delta_inc[2] * 1;
                } else {
                    // /today
                    $_curr_delta_inc = 0;
                }

                $time_in_sec = time() + $_curr_delta_inc * 60 * 60 * 24;
                $a = new TimetableController(0, '');
                // расписание
                $timetable_today = $a->helperToday($time_in_sec);

                $this->telega->keyboard_buttons = $this->today_menu;
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI($timetable_today);
                return;
            }

            /** TODAY FREE*/
            if (mb_stripos($this->telega->message_on_webhook, "/free") === 0) {
                $_curr_delta_inc = explode('/', $this->telega->message_on_webhook);
                // указан инкремент
                if (isset($_curr_delta_inc[2])) {
                    $_curr_delta_inc = $_curr_delta_inc[2] * 1;
                } else {
                    // /today
                    $_curr_delta_inc = 0;
                }

                $time_in_sec = time() + $_curr_delta_inc * 60 * 60 * 24;
                $a = new TimetableController(0, '');
                // расписание
                $timetable_today = $a->helperTodayFree($time_in_sec);

                $this->telega->keyboard_buttons = $this->today_menu;
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI($timetable_today);
                return;
            }

            /** SETTINGS */
            /** НА ЧТО ПОДПИСАН */
            // всегда и веде первый чтбы можно было на него выйти при любом условии!!!
            if (mb_stripos($this->telega->message_on_webhook, "/settings") === 0) {

                $message = '';
                // СБРОСИТЬ ВСЕ ЭКШЕН
                if (mb_stripos($this->telega->message_on_webhook, "/settings=DelAll") === 0) {
                    $sql = "DELETE FROM `telegram_subscribtion` WHERE  `chat_id` = '" . $this->telega->chat_id . "'";
                    Yii::$app->db->createCommand($sql)->execute();
                    $message .= "\n\n<b>🔕 Вы успешно отписаны от рассылки всех уведомлений!</b>\n\n";
                    // спрячем кнопку Выключить все уведомленияч
                    unset($this->subscrbtion_menu[2]);
                } else {

                    //$a = new TelegramSubscribtionController(0, '');
                    $message .= "🛠 <b>Настройки</b>. <i>Для включения/выключения уведомлений - выберите соответствующую Группу или Преподавателя и там нажмите кнопку Подписаться.</i>";
                    $message .=
                        " " .
                        Yii::$app->params['_m_notice_2'] .
                        " " .
                        Yii::$app->params['message_help'];

                    $message .= "\n\n" . $this->helpMessageGetStatusbyChat($this->telega->chat_id);
                }

                $this->telega->keyboard_buttons = $this->subscrbtion_menu;
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI($message);
                return;
            }


            /** ГРУППЫ */
            if (mb_stripos($this->telega->message_on_webhook, "SelectFroup") === 0) {
                //
                $groups = Groupstep::find()
                    ->where("`not_in_timetable_today` = 0")
                    ->orderBy('name_group')
                    ->all();
                $j = -1;
                $i = 0;
                $result = [];
                foreach ($groups as $gr) {
                    if ($i % 3 == 0) $j++;
                    $i++;
                    $result[$j][] = ['text' => $gr->name_group, 'callback_data' => '/start grp=' . $gr->id_group];
                }
                $result = array_merge([$this->inline_start_button_menu], $result, [$this->inline_start_button_menu]);
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $result;
                $this->telega->add_message_start_link = false;
                $this->telega->add_message_notice_see_bottom = false;
                $this->telega->sendMessageAPI(
                    'Если группа <b>отсутсвует в списке</b> - её еще не поставили расписание'
                );

                return;
            }

            /** ПРЕПОДЫ */
            if (mb_stripos($this->telega->message_on_webhook, "SelectTeacher") === 0) {
                //
                $groups = Teacher::find()
                    ->where("`not_in_timetable_today` = 0")
                    ->orderBy('name_teacher')
                    ->all();
                $j = -1;
                $i = 0;
                $result = [];
                foreach ($groups as $gr) {
                    if ($i % 3 == 0) $j++;
                    $i++;
                    //ФИО инициалы
                    $result[$j][] = [
                        'text' => $gr->initioals,
                        'callback_data' => '/start tchr=' . $gr->id_teacher
                    ];
                }
                $result = array_merge([$this->inline_start_button_menu], $result, [$this->inline_start_button_menu]);
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $result;
                $this->telega->add_message_start_link = false;
                $this->telega->add_message_notice_see_bottom = false;
                $this->telega->sendMessageAPI(
                    "Если преподаватель <b>отсутсвует в списке</b> - ему еще не поставили пары в расписании"
                );
                return;
            }


            /** ОТПИСКА ПОДПИСКА - ЗАОДНО ПОКАЗЫВАЕМ ТЕУЩУЮ НЕДЕЛЮ*/
            if (mb_stripos($this->telega->message_on_webhook, "Send=") === 0) {
                //ссылка выида Send=UnSubscribe=grp=22
                // ДЕЙСТВИЕ
                if (sizeof(explode('=', $this->telega->message_on_webhook)) > 1) {
                    //$a = new TelegramSubscribtionController(0, '');
                    $message = $this->NoticeSubcribeUnsubscribe($this->telega->message_on_webhook, $this->telega->chat_id, $this->telega->user_name);
                }

                //ОТОБРАЖЕНИЕ ДЕЙСТВИЯ
                //ссылка выида Send=UnSubscribe=grp=22
                $d = explode('=', $this->telega->message_on_webhook);
                $menu = [
                    [
                        ['text' => '-1 нед. 🗓', 'callback_data' => '/start ' . $d[2] . '=' . $d[3] . '=-1'],
                        ['text' => 'Эта неделя', 'callback_data' => '/start ' . $d[2] . '=' . $d[3] . '=0'],
                        ['text' => '+1 нед. 🗓', 'callback_data' => '/start ' . $d[2] . '=' . $d[3] . '=1'],
                    ],
                ];
                // тип объекта, ID объекта, номер недели
                //$type_group, $id_obj, $week, $menu
                $this->showTimeTableForGroupORTeacer($d[2], $d[3], 0, $menu);

                return;
            }

            /** THE END */
            /** THE END */
            /** THE END */


            // текстовый запрос не распознан
            //            $this->telega->keyboard_type = 'keyboard';
            //            $this->telega->keyboard_buttons = $menu;
            $this->telega->add_message_start_link = true;
            //$this->cleanFlagSecondMenuLevel();
            //$this->removeMenu();
            $this->telega->sendMessageAPI('Круто! Но я не понял : ' . $this->telega->message_on_webhook);
            return;
            //
        } else {


            /** ЧАТ студентов */
            // ЕСЛИ Бота юзает какойто чат студентов - гасим его попытки использовать
            // молчим - ничего не говорим если кто то вышел или зашел в грпуппу - обычно эти кмнады у нас не отлавливаются
            if ($this->catchGroupsubscr()) {

//                $this->telega->sendMessageAPI(
//
//                // "Диалог с ботом заблокирован в этом чате. Для личного использования см. <a href='https://t.me/ITStepZhitomir_bot'>ITStepZhitomir_bot</a>"
//                    "..."
//                );

                return;
            }


            //тут в примере стоял apiRequestWebhook - НО ОН НЕ РАБОТАЕТ !!!
            //            $this->telega->keyboard_type = 'keyboard';
            //            $this->telega->keyboard_buttons = $menu;
            $this->telega->add_message_start_link = true;
            //$this->cleanFlagSecondMenuLevel();
            //$this->removeMenu();
            $this->telega->sendMessageAPI("Я еще учусь и понимаю только текстовые команды");
            // если кто то просто вебхуку дергает безтолку
            // если кто то просто вебхуку дергает безтолку
            die('Telegram forever!');
            return;
        }
    }


    private function removeMenu()
    {
        $this->telega->keyboard_type = 'remove_keyboard';
        $this->telega->add_message_start_link = false;
        $this->telega->sendMessageAPI('...');
        return;
    }

    function showTimeTableForGroupORTeacer($type_group, $id_obj, $week, $menu)
    {
        switch ($type_group) {

            case 'grp':

                //определим текущйи статус для чата и объекта подписки
                if (($subscribtion = TelegramSubscribtion::findOne(['chat_id' => $this->telega->chat_id, 'group_id' => $id_obj])) !== null) {
                    $button = ['text' => 'Отписаться ❌', 'callback_data' => 'Send=UnSubscribe=grp=' . $id_obj];
                } else {
                    $button = ['text' => 'Подписаться❔️', 'callback_data' => 'Send=Subscribe=grp=' . $id_obj];
                }

                $menu = array_merge(
                    [
                        $this->inline_start_button_menu,
                        [
                            $button,
                            ['text' => 'Настройки 🛠', 'callback_data' => '/settings']
                        ]
                    ],
                    $menu);

                $a = new TimetableController(0, '');
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $menu;
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI(
                    $a->helperGroupTimeTable($id_obj, $week)
                );
                return;

            case 'tchr':

                //определим текущйи статус для чата и объекта подписки
                if (($subscribtion = TelegramSubscribtion::findOne(['chat_id' => $this->telega->chat_id, 'teacher_id' => $id_obj])) !== null) {
                    $button = ['text' => 'Отписаться ❌', 'callback_data' => 'Send=UnSubscribe=tchr=' . $id_obj];
                } else {
                    $button = ['text' => 'Подписаться❔️', 'callback_data' => 'Send=Subscribe=tchr=' . $id_obj];
                }
                $menu = array_merge(
                    [
                        $this->inline_start_button_menu,
                        [
                            $button,
                            ['text' => 'Настройки 🛠', 'callback_data' => '/settings']
                        ]
                    ],
                    $menu);


                $a = new TimetableController(0, '');
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $menu;
                //$this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI(
                    $a->helperTeacherTimeTable($id_obj, $week)
                );
                return;
        }
    }


    // тру если группа подописана
    // фолс для новых групп и юзверей
    public function catchGroupsubscr()
    {
        //если группа
        if (mb_stripos($this->telega->chat_id, "-") === 0) {
            // если групп подписана на обновления, блокируем вебхук собщения для эотй группы
            if (sizeof($this->helpActionGetStatusbyChat($this->telega->chat_id)))
                return true;
        }
        return false;
    }


    public function helpActionGetStatusbyChat($chat_id)
    {
        // снизим на грузку на БД
        // вытащим в память сисок преподов и групп
        $query = Teacher::find()->orderBy('name_teacher ASC')->all();
        $teachers = ArrayHelper::map($query, 'id_teacher', 'name_teacher');
        $query = Groupstep::find()->orderBy('name_group ASC')->all();
        $groups = ArrayHelper::map($query, 'id_group', 'name_group');

        $models = TelegramSubscribtion::findAll(['chat_id' => $chat_id]);
        //запись про тичера?
        if ($models) {
            $result = [];
            foreach ($models as $model) {
                if ($model->teacher_id) {
                    $result['teachers'][$model->teacher_id] = $teachers[$model->teacher_id];
                } else if ($model->group_id) {
                    $result['groups'][$model->group_id] = $groups[$model->group_id];
                }
            }
            return $result;
        } else {
            return [];
        }
    }


    public function helpMessageGetStatusbyChat($chat_id)
    {
        $search_arr = $this->helpActionGetStatusbyChat($chat_id);
        if (!$search_arr) {
            //кнопку Выключить все уведомления
            unset($this->subscrbtion_menu[2]);
            return "Вы не подписаны на уведомления про изменение в расписании.";
        }

        $timetable =
            $this->renderPartial('/telegram-subscribtion/messagegetstatusbychat', [
                'model' => $search_arr,
            ]);
        return $timetable;

    }
    //НА входе текст вида
    //Notices=Subscribe=grp=2581
    //Notices=UnSubscribe=grp=3333
    public function NoticeSubcribeUnsubscribe($str, $chat_id, $chat_name)
    {
        $a = explode('=', $str);
        $action = $a[1];
        $type_obj = $a[2];
        $id_obj = $a[3];
        //
        switch ($type_obj) {
            case 'grp':
                $name_filed_obj = 'group_id';
                break;
            case 'tchr':
                $name_filed_obj = 'teacher_id';
                break;
        }

        $message = "Неизвестная ошибка";
        switch ($action) {
            case 'Subscribe':
                $subscribtion = new TelegramSubscribtion();
                $subscribtion->chat_id = $chat_id;
                $subscribtion->chat_name = $chat_name;
                $subscribtion->{$name_filed_obj} = $id_obj;
                if ($subscribtion->save(false))
                    $message = "<b>Подписка на уведомления оформлена</b>";
//                else
//                    $message = "Ошибка. Не могу подписать:
//                '$str'
//                '$chat_id'
//                '$name_filed_obj'
//                '$id_obj'";

                break;
            case 'UnSubscribe':
                if (($subscribtion = TelegramSubscribtion::findOne(['chat_id' => $chat_id, $name_filed_obj => $id_obj])) != null) {
                    $subscribtion->delete();
                    $message = "<b>Подписка на уведомления отменена</b>";
                }
//                else
//                    $message = "Ошибка. Не могу отписать:  $str $chat_id";
                break;
        }
        return $message;
    }


    public function helpActionCleanAllSubscr($chat_id)
    {
        $models = TelegramSubscribtion::findAll(['chat_id' => $chat_id]);
        if ($models)
            foreach ($models as $model) {
                $model->delete();
            }
    }


}
