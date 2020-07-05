<?php

namespace app\controllers;

use app\models\Groupstep;
use app\models\Teacher;
use app\models\ViberSubscribtion;
use Viber\Bot;
use Viber\Api\Sender;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

// TODO: запретиьт бот вызывать из чата групп - зачем спамить
// TODO: реализовать механизм, запрещающий отписку преподователям самих от себя и группам на свои уведомления

class ViberController extends Controller
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
                        'actions' => ['index'],
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


    public $debug = 1;


    // отключаем для API POST CSRF параметр
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function init()
    {

        parent::init();
    }

    public function actionIndex()
    {

        if (isset($_SERVER['HTTP_X_VIBER_CONTENT_SIGNATURE'])) {
            $signature = $_SERVER['HTTP_X_VIBER_CONTENT_SIGNATURE'];
        } elseif (isset($_GET['sig'])) {
            $signature = $_GET['sig'];
        }
        if (empty($signature)) {
            die("Viber must die!");
        }


//
//        $file = fopen('../runtime/logs/viber_input.log', "a");
//        fwrite($file, json_encode($_POST) . "\r\n");
//        fwrite($file, json_encode($_GET) . "\r\n");
//        fclose($file);


        $config = [
            'apiKey' => '49a207034aa7d6f5-f8fea5c369e7e3c-8ef11c9102ebd422',
            'webhookUrl' => 'https://steptelega.protection.kiev.ua/viber/',
        ];
        $apiKey = $config['apiKey'];

// reply name
        $botSender = new Sender([
            'name' => '⏱ Bot РАСПИСАНИЯ',
            //'avatar' => 'https://developers.viber.com/images/favicon.ico',
        ]);

// log bot interaction
        $log = new Logger('bot');
        $log->pushHandler(new StreamHandler('../runtime/logs/viber.log'));
        $bot = null;


        $_start_str_ = "\n\n... вернуться на старт\nviber://public?id=itstepzhitomir";

        $inline_start_button_menu =

            (new \Viber\Api\Keyboard\Button())
                ->setColumns(6)
                ->setActionType('reply')
                ->setActionBody('start')
                ->setText('🏠 Главная. ⏱ ' . date('H:i:s', time() + 60 * 60) . '');


//            [
//                ,
//                [
//                    ['text' => '-1 день', 'callback_data' => '/today/' . ($_curr_delta_inc - 1)],
//                    ['text' => 'Сегодня 🌐 ', 'callback_data' => '/today'],
//                    ['text' => '+1 день', 'callback_data' => '/today/' . ($_curr_delta_inc + 1)],
//                ],
//                [
//                    ['text' => 'Группы 👥', 'callback_data' => 'SelectFroup'],
//                    ['text' => 'Препод 👨‍🏫', 'callback_data' => 'SelectTeacher'],
//                    ['text' => 'Настройки', 'callback_data' => 'Settings'],
//                ]
//            ];
//
        $start_menu =

            [

                $inline_start_button_menu,
                (new \Viber\Api\Keyboard\Button())
                    ->setColumns(2)
                    ->setActionType('reply')
                    ->setActionBody('groupSelect')
                    ->setTextSize('small')
                    ->setText('Группы 👥'),
                (new \Viber\Api\Keyboard\Button())
                    ->setColumns(2)
                    ->setActionType('reply')
                    ->setActionBody('teacherSelect')
                    ->setTextSize('small')
                    ->setText('Препод. 👨‍'),
                (new \Viber\Api\Keyboard\Button())
                    ->setColumns(2)
                    ->setActionType('reply')
                    ->setActionBody('today')
                    ->setTextSize('small')
                    ->setText('Сегодня 🌐'),

            ];

        for ($i = 0; $i <= 8; $i++) {

            $buttons[] =
                (new \Viber\Api\Keyboard\Button())
                    ->setColumns(1)
                    ->setActionType('reply')
                    ->setActionBody('k' . $i)
                    ->setText('k' . $i);
        };

        try {
            // create bot instance
            $bot = new Bot(['token' => $apiKey]);
            $bot
                // first interaction with bot - return "welcome message"

                /** ИНИЦИАЛИЗАЦИЯ ЧАТБОТА */
                ->onConversation(function ($event) use ($bot, $botSender, $log, $start_menu) {

                    $log->info('onConversation handler');

                    return (new \Viber\Api\Message\Text())
                        ->setSender($botSender)
                        ->setText("Вас приветсвует Bot \n\"Компьютерной академии ШАГ\" Житомир\nНажмите кнопку внизу экрана")
                        ->setKeyboard(
                            (new \Viber\Api\Keyboard())
                                ->setButtons($start_menu)
                        );

                })
                //
                // when user subscribe to PA
                ->onSubscribe(function ($event) use ($bot, $botSender, $log) {
                    $log->info('onSubscribe handler');
                    $this->getClient()->sendMessage(
                        (new \Viber\Api\Message\Text())
                            ->setSender($botSender)
                            ->setText('Спасибо за подписку на наш ЧатБот!')
                    );
                })
                //

                /** START */
                ->onText('|start|s', function ($event) use ($bot, $botSender, $buttons, $start_menu, $log, $_start_str_) {
                    $receiverId = $event->getSender()->getId();


                    $client = $bot->getClient();
                    $client->sendMessage(
                        (new \Viber\Api\Message\CarouselContent())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setButtonsGroupColumns(6)
                            ->setButtonsGroupRows(6)
                            ->setBgColor('#FFFFFF')
                            ->setButtons([



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="https://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="http://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="http://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="http://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="http://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),



                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(6)
                                    ->setRows(6)
                                    ->setActionType('reply')
                                    ->setActionBody('https://www.google.com')
                                    ->setText('<a href="http://google.com">ВАЙБЕР МАСТДАЙ</a>‎

Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
Broaden your global reach with notifications to keep your customers informed. Enhanced Messages. Real-Time Insights. Highlights: An Easy-To-Use APIs, Helping To Create Delightful Customer Experience, 24/7 Expert And Global Support.
')
                                    ->setTextSize("regular")
                                    ->setTextVAlign("top")
                                    ->setTextHAlign("left"),
                                // ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png'),





                            ])
                    );

                    return $this;
                    return $this;
                    return $this;
                    return $this;
                    return $this;
                    return $this;
                    return $this;
                    return $this;

                    /** DEEP LINKS НА ПРЕПОДВ И ГРУППЫ*/
                    // см ниже в кнопках ты сам задал ссылку start=tchr=12=1

                    $b = explode('=', $event->getMessage()->getText());
                    //b[0]start
                    //b[1]tchr
                    //b[2]12 ID object
                    //b[3]1 - ноемр WEEK


                    if (isset($b[0])) {
                        if (empty($b[3])) $b[3] = 0;
                        // ЕСЛИ НОМЕР препода группы задан, формируем меню переключения НЕДЕЛь
                        // chr grp хърангится в переменной ДОБАВЛЕНО
                        $menu = [
                            [
                                ['text' => 'Эта неделя 📆', 'callback_data' => '/start=' . $b[1] . '=' . $b[2] . '=0'],
                                ['text' => '+1 неделя 📆', 'callback_data' => '/start=' . $b[1] . '=' . $b[2] . '=' . ($b[3] + 1)],
                            ],
                        ];
                        // тип объекта, ID объекта, номер недели
                        $this->showTimeTableForGroupORTeacer($b[0], $b[1], $b[2], $menu);
                        return;
                    }


                    /** /START -ГЛАВНАЯ СТРАНИЦА*/
                    // костыль, если пользователь подписан на один з гуппу или препода
                    // выводим на неделю распиание тут же после привествия
                    $b = $this->helpActionGetStatusbyChat($receiverId);
                    if (sizeof($b)) {
                        if (isset($b['teachers'])) {
                            $start_menu[] =
                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(3)
                                    ->setActionType('reply')
                                    ->setActionBody('start=tchr=' . array_keys($b['teachers'])[0] . '=0')
                                    ->setTextSize('small')
                                    ->setText('Эта неделя 📅');
                            $start_menu[] =
                                (new \Viber\Api\Keyboard\Button())
                                    ->setColumns(3)
                                    ->setActionType('reply')
                                    ->setActionBody('start=tchr=' . array_keys($b['teachers'])[0] . '=1')
                                    ->setTextSize('small')
                                    ->setText('След. неделя 📅');

                            // $start_menu = array_merge($start_menu, $menu);

                            $name = Teacher::findOne([array_keys($b['teachers'])[0]])->name_teacher;
                            $message_subscribed = "По умолчанию на неделю вам показывается расписание преподавателя <b>" . $name . "</b>";

                        } else if (isset($b['groups'])) {
                            // есть жертва на кого мы подписаны - покажем кнопки ег рсаписания
                            // ссылки формируем в стиле DEEP LINK - как они попадают нам из JSON
                            // чтобы о бщий обработчик на них был
                            $start_menu2 = [
                                [
                                    // $start_menu,
                                    (new \Viber\Api\Keyboard\Button())
                                        ->setColumns(2)
                                        ->setActionType('reply')
                                        ->setActionBody('start=grp=' . array_keys($b['groups'])[0] . '=0')
                                        ->setTextSize('small')
                                        ->setText('Эта неделя 📅'),
                                    (new \Viber\Api\Keyboard\Button())
                                        ->setColumns(2)
                                        ->setActionType('reply')
                                        ->setActionBody('start=grp=' . array_keys($b['groups'])[0] . '=1')
                                        ->setTextSize('small')
                                        ->setText('След. неделя 📅'),
                                ],
                            ];
                            //$start_menu = array_merge($start_menu, $menu);

                            $name = Groupstep::findOne([array_keys($b['groups'])[0]])->name_group;
                            $message_subscribed = "По умолчанию на неделю вам показывается расписание группы <b>" . $name . "</b>";
                            //
                        }

                        Yii::$app->params['message_intro1'] .= "\n\n" . $message_subscribed;

                    } else {
                        Yii::$app->params['message_intro1'] .= "\n\n" . Yii::$app->params['message_intro2'];
                    }


                    $bot->getClient()->sendMessage(
                        (new \Viber\Api\Message\Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            //->setText(strip_tags(Yii::$app->params['message_intro1']))
                            ->setText(Yii::$app->params['message_intro1'])
                            ->setKeyboard(
                                (new \Viber\Api\Keyboard())
                                    ->setButtons($start_menu)
                            )
                    );
//                    $bot->getClient()->sendMessage(
//                        (new \Viber\Api\Message\Text())
//                            ->setSender($botSender)
//                            ->setReceiver($receiverId)
//                            ->setText('Выберите группу из списка внизу экрана:' . $_start_str_)
//                            ->setKeyboard(
//                                (new \Viber\Api\Keyboard())
//                                    ->setButtons($start_menu)
//                            )
//                    );

                })
                ->onText('|group|s', function ($event) use ($bot, $botSender, $log, $_start_str_) {
                    $a = explode('-', $event->getMessage()->getText());

                    if (isset($a[1])) {
                        $receiverId = $event->getSender()->getId();
                        $bot->getClient()->sendMessage(
                            (new \Viber\Api\Message\Text())
                                ->setSender($botSender)
                                ->setReceiver($receiverId)
                                ->setText('Группа ' . $a[1] . $_start_str_)
//                                ->setKeyboard(
//                                    (new \Viber\Api\Keyboard())
//                                        ->setButtons($menu)
//                                )
                        );
                        return $this;
                    }


                    // ИНАЧЕ ВЫБОР ГРУППЫ
                    $groups = Groupstep::find()
                        ->where("`notactive` = 0")
                        ->orderBy('name_group')
                        ->all();
                    $i = 0;
                    foreach ($groups as $gr) {
                        // TODO максимум 24 строки?
                        if ($i++ > 71) break;
                        $menu[] =
                            (new \Viber\Api\Keyboard\Button())
                                ->setColumns(2)
                                ->setActionType('reply')
                                ->setActionBody('group-' . $gr->id_group)
                                ->setText($gr->name_group);
                    }
                    $log->info('click on button');
                    $receiverId = $event->getSender()->getId();
                    $bot->getClient()->sendMessage(
                        (new \Viber\Api\Message\Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText($event->getSender()->getId() . 'Выберите группу из списка внизу экрана:' . $_start_str_)
                            ->setKeyboard(
                                (new \Viber\Api\Keyboard())
                                    ->setButtons($menu)
                            )
                    );

                })
                ->onText('|teacher|s', function ($event) use ($bot, $botSender, $log, $start_menu, $_start_str_) {

//                    $a = explode('-', $event->getMessage()->getText());
//
//                    if (isset($a[1])) {
//                        $receiverId = $event->getSender()->getId();
//                        $bot->getClient()->sendMessage(
//                            (new \Viber\Api\Message\Text())
//                                ->setSender($botSender)
//                                ->setReceiver($receiverId)
//                                ->setText('Группа ' . $a[1] . $_start_str_)
////                                ->setKeyboard(
////                                    (new \Viber\Api\Keyboard())
////                                        ->setButtons($menu)
////                                )
//                        );
//                        return $this;
//                    }
//
//
//                    // ИНАЧЕ ВЫБОР ГРУППЫ
//                    $teachers = Teacher::find()
//                        ->where("`status_teacher` = 0")
//                        ->orderBy('name_teacher')
//                        ->all();
//                    $i = 0;
//                    foreach ($teachers as $tr) {
//                        // TODO максимум 24 строки?
//                        if ($i++ > 71) break;
//                        $menu[] =
//                            (new \Viber\Api\Keyboard\Button())
//                                ->setColumns(2)
//                                ->setActionType('reply')
//                                ->setActionBody('group-' . $tr->)
//                                ->setText($tr->name_group);
//                    }
//                    $log->info('click on button');
//                    $receiverId = $event->getSender()->getId();
//                    $bot->getClient()->sendMessage(
//                        (new \Viber\Api\Message\Text())
//                            ->setSender($botSender)
//                            ->setReceiver($receiverId)
//                            ->setText('Выберите группу из списка внизу экрана:' . $_start_str_)
//                            ->setKeyboard(
//                                (new \Viber\Api\Keyboard())
//                                    ->setButtons($menu)
//                            )
//                    );

                })
                ->onText('|today|is', function ($event) use ($bot, $botSender, $start_menu, $log, $_start_str_) {
                    $receiverId = $event->getSender()->getId();

                    $_curr_delta_inc = 0;
                    $time_in_sec = time() + $_curr_delta_inc * 60 * 60 * 24;
                    $a = new TimetableController(0, '');
                    $timetable_today = $a->helperToday($time_in_sec);


                    $bot->getClient()->sendMessage(
                        (new \Viber\Api\Message\Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText(strip_tags($timetable_today) . $_start_str_)
                            ->setKeyboard(
                                (new \Viber\Api\Keyboard())
                                    ->setButtons($start_menu)
                            )
                    );

                })
                //
                ->onText('|k\d+|is', function ($event) use ($bot, $botSender, $log, $buttons) {
                    $caseNumber = (int)preg_replace('|[^0-9]|s', '', $event->getMessage()->getText());
                    $log->info('onText demo handler #' . $caseNumber);
                    $client = $bot->getClient();
                    $receiverId = $event->getSender()->getId();
                    switch ($caseNumber) {
                        case 0:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Text())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setText('Basic keyboard layout<br><a href=\'http://google.com\'>zzzzzzz</a>')
                                    ->setKeyboard(
                                        (new \Viber\Api\Keyboard())
                                            ->setButtons(

                                                array_merge(
                                                    [
                                                        (new \Viber\Api\Keyboard\Button())
                                                            ->setActionType('reply')
                                                            ->setActionBody('btn-click')
                                                            ->setText('Tap this button')
                                                    ], $buttons)

                                            )
                                    )
                            );
                            break;
                        //

                        //
                        case 1:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Text())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setText('More buttons and styles')
                                    ->setKeyboard(
                                        (new \Viber\Api\Keyboard())
                                            ->setButtons([
                                                (new \Viber\Api\Keyboard\Button())
                                                    ->setBgColor('#8074d6')
                                                    ->setTextSize('small')
                                                    ->setTextHAlign('right')
                                                    ->setActionType('reply')
                                                    ->setActionBody('btn-click')
                                                    ->setText('Button 1'),

                                                (new \Viber\Api\Keyboard\Button())
                                                    ->setBgColor('#2fa4e7')
                                                    ->setTextHAlign('center')
                                                    ->setActionType('reply')
                                                    ->setActionBody('btn-click')
                                                    ->setText('Button 2'),

                                                (new \Viber\Api\Keyboard\Button())
                                                    ->setBgColor('#555555')
                                                    ->setTextSize('large')
                                                    ->setTextHAlign('left')
                                                    ->setActionType('reply')
                                                    ->setActionBody('btn-click')
                                                    ->setText('Button 3'),
                                            ])
                                    )
                            );
                            break;
                        //
                        case 2:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Contact())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setName('Novikov Bogdan')
                                    ->setPhoneNumber('+380000000000')
                            );
                            break;
                        //
                        case 3:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Location())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setLat(48.486504)
                                    ->setLng(35.038910)
                            );
                            break;
                        //
                        case 4:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Sticker())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setStickerId(114408)
                            );
                            break;
                        //
                        case 5:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Url())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setMedia('https://hcbogdan.com')
                            );
                            break;
                        //
                        case 6:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Picture())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setText('some media data')
                                    ->setMedia('https://developers.viber.com/img/devlogo.png')
                            );
                            break;
                        //
                        case 7:
                            $client->sendMessage(
                                (new \Viber\Api\Message\Video())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setSize(2 * 1024 * 1024)
                                    ->setMedia('http://techslides.com/demos/sample-videos/small.mp4')
                            );
                            break;
                        //
                        case 8:
                            $client->sendMessage(
                                (new \Viber\Api\Message\CarouselContent())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setButtonsGroupColumns(6)
                                    ->setButtonsGroupRows(6)
                                    ->setBgColor('#FFFFFF')
                                    ->setButtons([
                                        (new \Viber\Api\Keyboard\Button())
                                            ->setColumns(6)
                                            ->setRows(3)
                                            ->setActionType('open-url')
                                            ->setActionBody('https://www.google.com')
                                            ->setImage('https://i.vimeocdn.com/portrait/58832_300x300'),

                                        (new \Viber\Api\Keyboard\Button())
                                            ->setColumns(6)
                                            ->setRows(3)
                                            ->setActionType('reply')
                                            ->setActionBody('https://www.google.com')
                                            ->setText('<span style="color: #ffffff; ">Buy</span>')
                                            ->setTextSize("large")
                                            ->setTextVAlign("middle")
                                            ->setTextHAlign("middle")
                                            ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png')
                                    ])
                            );
                            break;
                    }
                })
                ->run();


        } catch (Exception $e) {
            $log->warning('Exception: ' . $e->getMessage());
            if ($bot) {
                $log->warning('Actual sign: ' . $bot->getSignHeaderValue());
                $log->warning('Actual body: ' . $bot->getInputBody());
            }
        }


    }


    public function helpActionGetStatusbyChat($chat_id)
    {
        // снизим на грузку на БД
        // вытащим в память сисок преподов и групп
        $query = Teacher::find()->orderBy('name_teacher ASC')->all();
        $teachers = ArrayHelper::map($query, 'id_teacher', 'name_teacher');
        $query = Groupstep::find()->orderBy('name_group ASC')->all();
        $groups = ArrayHelper::map($query, 'id_group', 'name_group');

        $models = ViberSubscribtion::findAll(['chat_id' => $chat_id]);
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
                            ['text' => 'Настройки', 'callback_data' => 'Settings']
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
                            ['text' => 'Настройки', 'callback_data' => 'Settings']
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


}