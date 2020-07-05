<?php

namespace app\components;

//чтобы сообщить телеграмму наш адрес хука (сертификат нафиг отключсили гдето в п араметрах)
//https://api.telegram.org/bot819281180:AAEKXL1es2JmGLgqY9Dt58GjXbD69jc4AqI/setWebhook?url=https://steptelega.protection.kiev.ua/api/init
//https://api.telegram.org/bot698408457:AAES7vj2YVnt7tpGMoHQpbyxcG2gh7tkJa8/setWebhook?url=https://steptelega.protection.kiev.ua/api/webhook

//проверить инфо по Вебхуку тут
//апдейт - херня, правду не показывает смотри логи Yii2
//https://api.telegram.org/bot819281180:AAEKXL1es2JmGLgqY9Dt58GjXbD69jc4AqI/getWebhookInfo

// хер вам - это если вэбхук не используется
// Взять ID чата https://api.telegram.org/bot819281180:AAEKXL1es2JmGLgqY9Dt58GjXbD69jc4AqI/getUpdates
// чтобы получить ID группы, которая шлет команды боту
// после добавления его в группу поищи в логах строку вида
// {"message_id":3772,"from":{"id":819281180,"is_bot":true,"first_name":"StepToday","username":"StepToday_bot"},"chat":{"id":-1001161487189,"title":"WEB_182",
// -1001161487189 - искомый номер
// !!!!!!!!! достаточно в общий чат где добавлен бот написать /help@StepToday_bot

//


// код обработчкиа взял тут https://stackoverflow.com/questions/33303110/got-problems-with-webhook-to-telegram-bot-api

use app\models\TelegramSubscribtion;
//use Yii;
use yii\helpers\ArrayHelper;

class MessageToTelegaApi
{
    public $debug;

    public $API_KEY;
    //private $BOT_NAME;
    public $API_URL;
    public $WEBHOOK_URL;

    public $message_in_telegapi; // text
    public $message_on_webhook; // text
    public $message_id;
    public $is_callback = 0;
    public $chat_id;
    public $user_name;
    public $disable_web_page_preview = 1;


    public $requestAPIMethodGET = true;
    public $keyboard_type = '';
    public $keyboard_buttons = [];
    public $reply_markup = [];
    public $add_message_start_link = false;
    public $add_message_notice_see_bottom = false;
    public $send_this_photo_url = '';
    public $send_message_parse_mode = 'HTML'; //markdown HTML

    //public $webhook_sended_message_by_update



    // принимаемв входные параметры вебхука
    public function __construct()
    {
        $content = file_get_contents("php://input");

        \Yii::info("\nDEBUG: $content", 'webhook');

        $update = json_decode($content, true);
        if (!$update) {
            // receive wrong update, must not happen
            \Yii::info("\nReceive wrong update, must not happen : $content", 'webhook');
            return;
        }
        // пока логируем все что вебхук принимает
        if ($this->debug)
            \Yii::info("\n" . json_encode($update), 'webhook');

        // мультимеди или текст см в под массиве
        if (isset($update["message"])) {
            // ловим входящий ТЕКСТОВЫЙ запрос на веюхук
            // возможно это мультимедиа на входе и текста нет
            $this->message_on_webhook = isset($update['message']['text']) ? $update['message']['text'] : '';
            $this->message_id = $update['message']['message_id'];
            $this->chat_id = $update['message']['chat']['id'];
            // важно тут  имя поймать это не инлайн командф а пецуоманды и мультиммдиа - пишется в сессию базу
//            if (isset($update['message']['chat']['first_name'])) $this->user_name .= " 1 " . $update['message']['chat']['first_name'];
//            if (isset($update['message']['chat']['last_name'])) $this->user_name .= " 2 " . $update['message']['chat']['last_name'];
//            if (isset($update['message']['chat']['username'])) $this->user_name .= " 3 " . $update['message']['chat']['username'];
            if (isset($update['message']['from']['first_name'])) $this->user_name .= "" . $update['message']['from']['first_name'];
            if (isset($update['message']['from']['last_name'])) $this->user_name .= "" . $update['message']['from']['last_name'];
            if (isset($update['message']['from']['username'])) $this->user_name .= " (" . $update['message']['from']['username'].")";

            return;
        } // ЭТО колбэк клаиатуры ИНЛАЙН
        else if (isset($update["callback_query"])) {
            // ловим входящий ТЕКСТОВЫЙ запрос на веюхук
            // возможно это мультимедиа на входе и текста нет
            $this->is_callback = 1;
            $this->message_on_webhook = isset($update['callback_query']['data']) ? $update['callback_query']['data'] : '';
            $this->message_id = $update['callback_query']['message']['message_id'];
            $this->chat_id = $update['callback_query']['message']['chat']['id'];
            $this->user_name = '';
            if (isset($update['callback_query']['message']['chat']['title'])) $this->user_name .= $update['callback_query']['message']['chat']['title'];
            if (isset($update['callback_query']['message']['chat']['first_name'])) $this->user_name .= $update['callback_query']['message']['chat']['first_name'];
            if (isset($update['callback_query']['message']['chat']['last_name'])) $this->user_name .= " " . $update['callback_query']['message']['chat']['last_name'];
            if (isset($update['callback_query']['message']['chat']['username'])) $this->user_name .= " (" . $update['callback_query']['message']['chat']['username'] . ")";
            //
            return;
        }
        \Yii::info("\nЧТО ЭТО БЫЛО - на вход что то  подали но это не ТЕЛЕГРАММ: $content", 'webhook');
    }




    // Отправка сообщения с клавой или без
    public function sendMessageAPI($message)
    {

//        $a = array(
//            'chat_id' => $chat_id,
//            "text" => 'Hello',
//            'reply_markup' => array(
//                'keyboard' => array(array('Hello', 'Hi')),
//                'one_time_keyboard' => true,
//                'resize_keyboard' => true)
//        );


        // в констракт засунуть не вариант
        // многи переменные зддесь иницириуются после созджания объекта
        switch ($this->keyboard_type) {
            case 'keyboard':
                $this->requestAPIMethodGET = false;
                $this->reply_markup = [
                    'keyboard' => $this->keyboard_buttons,
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true
                ];
                break;
            case 'inline_keyboard':
                $this->requestAPIMethodGET = false;
                $this->reply_markup = [
                    'inline_keyboard' => $this->keyboard_buttons,
                ];
                break;
            case 'remove_keyboard':
                $this->requestAPIMethodGET = false;
                $this->reply_markup = [
                    'remove_keyboard' => true,
                ];

        }


        // не может быть пустым
        if ($message == '') $message = '...';

        if ($this->add_message_start_link)
            $message .= "\r\n\r\n<i> ... вернуться на</i> /start";

        if ($this->add_message_notice_see_bottom)
            $message .= "\r\nСм. список внизу экрана под полем для ввода:";

        if (!empty($this->send_this_photo_url)) {
            $this->apiTelegaRequestPOST(
                "sendPhoto",
                [
                    "chat_id" => $this->chat_id,
                    "photo" => $this->send_this_photo_url,
                    "caption" => $message,
                ]
            );

        } else if ($this->requestAPIMethodGET)


            $this->apiTelegaRequestGET(
                "sendMessage",
                [
                    "chat_id" => $this->chat_id,
                    "text" => $message,
                    "parse_mode" => $this->send_message_parse_mode,
                    "disable_web_page_preview" => $this->disable_web_page_preview,
                ]
            );


        else
            $this->apiTelegaRequestPOST(
                "sendMessage",

                [
                    "chat_id" => $this->chat_id,
                    "text" => $message,
                    "parse_mode" => $this->send_message_parse_mode,
                    "reply_markup" => $this->reply_markup,
                    "disable_web_page_preview" => $this->disable_web_page_preview,
                ]
            );

    }

    // ПРОСТО логируем что нам ответил API Telega
    private function exec_curl_request_to_api_telega($handle)
    {
        $response = curl_exec($handle);

        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            \Yii::info("Curl returned error $errno: $error\n", 'telegram');
            curl_close($handle);
            return false;
        }

        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);
        if ($http_code >= 500) {
            // do not wat to DDOS TELEGRAM server if something goes wrong
            \Yii::info("do not wat to DDOS TELEGRAM server if something goes wrong\n", 'telegram');
            sleep(10);
            return false;
        } else if ($http_code != 200) {
            $response = json_decode($response, true);
            \Yii::info("Request has failed with error {$response['error_code']}: {$response['description']}\n", 'telegram');
            if ($http_code == 401) {
                throw new Exception('Invalid access token provided');
            }
            return false;
        } else {
            $response = json_decode($response, true);
            if (isset($response['description'])) {
                if ($this->debug)
                    \Yii::info("Request was successfull: {$response['description']}\n", 'telegram');
            }
            $response = $response['result'];
            if ($this->debug)
                \Yii::info("\n" . json_encode($response), 'telegram');

        }
        return $response;
    }

    // если параметры вектором - и нет клавы - то просто GET
    private function apiTelegaRequestGET($method, $parameters)
    {
        if (!is_string($method)) {
            \Yii::info("Method name must be a string\n", 'telegram');
            return false;
        }

//        if (!empty($this->message_id)) {
//            $method = 'editMessageText';
//            $parameters['message_id'] = 2302;
//        }


        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            \Yii::info("Parameters must be an array\n", 'telegram');
            return false;
        }

        // шифруем значения в JSON
        foreach ($parameters as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = $this->API_URL . $method . '?' . http_build_query($parameters);
        if ($this->debug)
            \Yii::info("API request: \n" . $url, 'telegram');


        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);

        return $this->exec_curl_request_to_api_telega($handle);
    }

    // если параметры не вектор - то постом JSON
    private function apiTelegaRequestPOST($method, $parameters)
    {
//        print_r($this);
//        print_r($parameters);
//        exit;

        if (!is_string($method)) {
            \Yii::info("Method name must be a string\n", 'telegram');
            return false;
        }

        // update_message edit_message если это колбек
        if (!empty($this->message_id) AND $this->is_callback AND $this->keyboard_type != 'keyboard') {
            $method = 'editMessageText';
            $parameters['message_id'] = $this->message_id;
        }


        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            \Yii::info("Parameters must be an array\n", 'telegram');
            return false;
        }

        $parameters["method"] = $method;

        $handle = curl_init($this->API_URL);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        return $this->exec_curl_request_to_api_telega($handle);
    }

}

