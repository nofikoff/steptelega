<?php

namespace app\controllers;

use app\components\MessageToTelegaApi;
use app\components\MyHelper;
use app\components\Parser;
use app\components\ParserExamens;
use app\models\Exames;
use app\models\Groupstep;
use app\models\Teacher;
use app\models\TelegramSubscribtion;
use app\models\Timetable;
use JsonSchema\Exception\JsonDecodingException;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;


class SiteController extends Controller
{


    public $curl_debug = 0;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => 'yii\filters\AccessControl',
                'rules' => [
//                    [
//                        // любой экшен - но не контролер
//                        // если указать нуно контроллер - пиши бихевире внутри этого контролеера
//                        'actions' => ['init', 'webhook'],
//                        'allow' => true,
//                        'roles' => ['?'],
//                    ],

                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function () {
                    return Yii::$app->response->redirect(['user/login']);
                },
            ],

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */


    public function actionExamen()
    {
//start_date
//start_time
//group_id
        exit;
        exit;
        exit;
        exit;

        // !!! Важно !!!! Экзамен должен принадлежать тичеру иначе ошибка
        $id_student = '16014';
        $id_examen = '1448';
        $mark = '9';

        echo $id_teacher = '60'; // Novikov 60
        echo "<br>\n";


        $a = new Parser();
        $a->change_teacher($id_teacher);


        //curl 'https://logbook.itstep.org/exams/set-mark' -H 'Accept: application/json, text/plain, */*' -H 'Referer: https://logbook.itstep.org/' -H 'Origin: https://logbook.itstep.org' -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36' -H 'Content-Type: application/json;charset=UTF-8' --data-binary '{"mark":"10","id_stud":"16735","id":"351"}' --compressed
        //curl 'https://logbook.itstep.org/exams/set-mark' -H 'Accept: application/json, text/plain, */*' -H 'Referer: https://logbook.itstep.org/' -H 'Origin: https://logbook.itstep.org' -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36' -H 'Content-Type: application/json;charset=UTF-8' --data-binary '{"mark":"10","id_stud":"16735","id":"351"}' --compressed
        $url = 'https://logbook.itstep.org/exams/set-mark';


        $headers =
            [


                'Accept: application/json, text/plain, */*',
                'Referer: https://logbook.itstep.org/',
                'Origin: https://logbook.itstep.org',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36',
                'Content-Type: application/json;charset=UTF-8'


            ];
        echo $a->send_http_post($url, "POST", $headers, '{"mark":"' . $mark . '","id_stud":"' . $id_student . '","id":"' . $id_examen . '"}');
    }

    function jsonFixer($Str)
    {
        $StrArr = str_split($Str);
        $NewStr = '';
        foreach ($StrArr as $Char) {
            $CharNo = ord($Char);
            if ($CharNo == 163) {
                $NewStr .= $Char;
                continue;
            } // keep £
            if ($CharNo > 31 && $CharNo < 127) {
                $NewStr .= $Char;
            }
        }
        return $NewStr;
    }

    public function actionIndex()
    {
        return $this->render(
            'index'
        );
    }

    /**
     * Login action.
     *
     * @return Response|string
     */

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
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
//        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookies);
//        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookies);
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


        return $server_output;
    }


}
