<?php

namespace app\components;

// namespace app\components; // For Yii2 Basic (app folder won't actually exist)
use app\controllers\TelegramSubscribtionController;
use app\controllers\TimetableController;
use app\models\Groupstep;
use app\models\Teacher;
use app\models\TelegramSubscribtion;
use Yii;

class MyHelper
{
    public $debug;
    public $telega;

    public static function hello($name)
    {
        return "Hello $name";
    }

    public static function strDate2WeekDay($ddate)
    {
        $w = ['ВОСКРЕСЕНЬЕ', 'ПОНЕДЕЛЬНИК', 'ВТОРНИК', 'СРЕДА', 'ЧЕТВЕРГ', 'ПЯТНИЦА', 'СУББОТА'];
        return $w[date("w", strtotime($ddate))];
    }

    public static function strTime2WeekDay($time)
    {
        $w = ['ВОСКРЕСЕНЬЕ', 'ПОНЕДЕЛЬНИК', 'ВТОРНИК', 'СРЕДА', 'ЧЕТВЕРГ', 'ПЯТНИЦА', 'СУББОТА'];
        return $w[date("w", $time)];
    }


    public static function reverceDateFromAmeric($date)
    {
        $months = array(1 => 'Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря');

        //return date('d ' . $months[date('n')], strtotime($date)) . " " . $date;
        return date('d', strtotime($date)) . " " . $months[date('n', strtotime($date))];

        // переписал в обычный вормат см выше
//        $date = explode('-', $date);
//        return "{$date[2]}-{$date[1]}-{$date[0]}";
    }


    public static function dateToMinimum($date)
    {

        return date("j M", strtotime($date));

        $date = explode('-', $date);
        return "{$date[2]}-{$date[1]}-{$date[0]}";
    }


    public static function is_url_exist($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            $status = true;
        } else {
            $status = false;
        }
        curl_close($ch);
        return $status;
    }


    // WebHoook СТАРЫЙ БОТ @StepToday
    public function actionInit()
    {

    }


    private function removeMenu()
    {
        $this->telega->keyboard_type = 'remove_keyboard';
        $this->telega->add_message_start_link = false;
        $this->telega->sendMessageAPI('...');
        return;
    }

    function showTimeTableForGroupORTeacer_____($type_group, $id_obj, $week, $menu)
    {
        switch ($type_group) {

            case 'grp':

                //определим текущйи статус для чата и объекта подписки
                if (($subscribtion = TelegramSubscribtion::findOne(['chat_id' => $this->telega->chat_id, 'group_id' => $id_obj])) !== null) {
                    $button = ['text' => 'Отписаться ❌', 'callback_data' => 'Send=UnSubscribe=grp=' . $id_obj];
                } else {
                    $button = ['text' => 'Подписаться ✔️', 'callback_data' => 'Send=Subscribe=grp=' . $id_obj];
                }
                $menu[] = [
                    $button,
                    ['text' => 'Настройки', 'callback_data' => 'Settings']
                ];


                $a = new TimetableController(0, '');
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $menu;
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI(
                    $a->helperGroupTimeTable($id_obj, $week)
                );
                // запоминаем выбранный объект в сессии
                //TelegramSession::updateORcreateTSession($this->telega->chat_id, 1, $id_obj);
                return;

            case 'tchr':

                //определим текущйи статус для чата и объекта подписки
                if (($subscribtion = TelegramSubscribtion::findOne(['chat_id' => $this->telega->chat_id, 'teacher_id' => $id_obj])) !== null) {
                    $button = ['text' => 'Отписаться ❌', 'callback_data' => 'Send=UnSubscribe=tchr=' . $id_obj];
                } else {
                    $button = ['text' => 'Подписаться ✔️', 'callback_data' => 'Send=Subscribe=tchr=' . $id_obj];
                }
                $menu[] = [
                    $button,
                    ['text' => 'Настройки', 'callback_data' => 'Settings']
                ];


                $a = new TimetableController(0, '');
                $this->telega->keyboard_type = 'inline_keyboard';
                $this->telega->keyboard_buttons = $menu;
                $this->telega->add_message_start_link = false;
                $this->telega->sendMessageAPI(
                    $a->helperTeacherTimeTable($id_obj, $week)
                );
                // запоминаем выбранный объект в сессии
                //TelegramSession::updateORcreateTSession($this->telega->chat_id, 2, $id_obj);
                return;
        }
    }


    // тру если группа подописана
    // фолс для новых групп и юзверей
    public function catchGroupsubscr()
    {
        //если группа
        if (mb_stripos($this->telega->chat_id, "-") === 0) {
            $a = new TelegramSubscribtionController(0, '');
            // если групп подписана на обновления, блокируем вебхук собщения для эотй группы
            if (sizeof($a->helpActionGetStatusbyChat($this->telega->chat_id)))
                return true;
        }
        return false;
    }

    // ЭТА НЕДЕЛЯ, СЛЕДУЮЩАЯ НЕДЕЛЯ или пусто
    public static function stringNameWeek($Date)
    {

        $FirstDay0 = date("Y-m-d", strtotime('monday this week'));
        $LastDay0 = date("Y-m-d", strtotime('sunday this week'));
        $FirstDay1 = date("Y-m-d", strtotime('monday next week'));
        $LastDay1 = date("Y-m-d", strtotime('sunday next week'));

        // третья неделя
        if ($Date > $LastDay1)
            return;

        // прошлая неделя
        if ($Date < $FirstDay0)
            return;

        // вторая неделя
        if ($Date >= $FirstDay1 && $Date <= $LastDay1) {
            if (date('N') == 7) {
                return "НАСТУПАЮЩАЯ НЕДЕЛЯ";
            }
            return "СЛЕДУЮЩАЯ НЕДЕЛЯ";
        }

        // эта неделя
        if ($Date >= $FirstDay0 && $Date <= $LastDay0) {
            if (date('N') == 7) {
                return "УХОДЯЩАЯ НЕДЕЛЯ";
            }
            return "ЭТА НЕДЕЛЯ";
        }

    }


}
