<?php
namespace app\controllers;
use yii\base\Exception;

use dektrium\user\controllers\SecurityController as BaseSecurityController;
class SecurityController extends BaseSecurityController
{
//    public $enableCsrfValidation = false;
// какого то хера выход не работает по схеме автора модуля
    public function actionLogout()
    {
        // так НЕ выходит :*( Подозреваю что из за Аяка что ломится часто на сервер и не успевает скрипт разлогиниться
        \Yii::$app->getUser()->logout();

        // а так выходит !!!!!!!!!!!!!!!!
        session_start();
        $_SESSION = array();
        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Тяжелая артелерия Срабатывает черезраз, лучше отключим и без нее работат
        // Finally, destroy the session. Тут бывает выдаеь оштбку
        //session_destroy();

        return $this->goHome();
    }


}


?>