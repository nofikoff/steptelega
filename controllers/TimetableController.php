<?php

namespace app\controllers;

use app\components\MyHelper;
use app\models\Groupstep;
use app\models\Teacher;
use Yii;
use app\models\Timetable;
use app\models\TimetableSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TimetableController implements the CRUD actions for Timetable model.
 */
class TimetableController extends Controller
{
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
     * Lists all Timetable models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TimetableSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    // ВРЕМЯ В СЕКУНДАХ
    public function helperToday($time = '')
    {

        if (!$time) $time = time();
        $searchModel = Timetable::find()
            ->where("`start_date` = '" . date('Y-m-d', $time) . "'")
            ->orderBy('`start_time` ASC')
            ->all();

        if (!$searchModel) return "В Академии " . MyHelper::reverceDateFromAmeric(date('Y-m-d', $time)) . " выходной или Bot не видит расписание! ";


//        // сортировка по кабинетам
//        foreach ($searchModel as $item) {
//
//            $template = '    $room <a href="https://t.me/' . _BOT_NAME . '?start=grp=' . $item->group->id_group . '">$grp</a> $subj <a href="https://t.me/' . _BOT_NAME . '?start=tchr=' . $item->teacher->id_teacher . '">$tch</a>' . "\n";
//            $vars = array(
//                '$time' => $item->start_time,
//                '$room' => Yii::$app->params['roomsId'][$item->room_id],
//                '$subj' => trim($item->subject),
//                '$tch' => trim($item->teacher->finame),
//                '$grp' => trim($item->group->name_group),
//                // В РАПСИАНИИ на всю  академю номер будет лишним '$num' => $item->countpara?' #'.$item->countpara:'',
//
//
//            );
//            if ($item->start_time != $ctime) {
//                echo "\n⏱ <b>" . $item->start_time . "</b>\n";
//                $ctime = $item->start_time;
//            }
//            echo strtr($template, $vars);
//
//        }//














        $timetable_today =
            $this->renderPartial('/api/timetabletoday', [
                'model' => $searchModel,
                'time' => $time,
            ]);
        return $timetable_today;
    }

    // ВРЕМЯ В СЕКУНДАХ ..helperTodayFree
    public function helperTodayFree($time = '')
    {
        // формируем списко всех аудитория и по дефолту они фри
        $result_free = [];
        // пары
        foreach (Yii::$app->params['time_start'] as $para_str) {
            // комнаты
            foreach (Yii::$app->params['roomsId'] as $room_id => $room_str) {
                $result_free[$para_str][$room_str] = 'Free';
            }
        }

        // выбираем текущее расписание
        if (!$time) $time = time();
        $searchModel = Timetable::find()
            ->where("`start_date` = '" . date('Y-m-d', $time) . "'")
            ->orderBy('`start_time`,`room_id` ASC')
            ->all();
        //

        //подправляем массив расписания - занятыми аудиториями
        foreach ($searchModel as $item) {
                $result_free[$item->start_time][Yii::$app->params['roomsId'][$item->room_id]] = '<a href="https://t.me/' . _BOT_NAME . '?start=tchr=' . $item->teacher->id_teacher . '">' . $item->teacher->finame . '</a>';
        }

        if (!$searchModel) return "В Академии " . MyHelper::reverceDateFromAmeric(date('Y-m-d', $time)) . " выходной или Bot не видит расписание! ";


        // выводим на экран
        $timetable_today =
            $this->renderPartial('/api/timetablefreerooms', [
                'list' => $result_free,
                'time' => $time,
            ]);
        return $timetable_today;
    }


    public function actionToday()
    {
        return $this->helperToday();
    }

    // показыват текущие пары на главной
    public function helperTeacherTimeTableNowOnMain($id_teacher)
    {
        $searchModel = Timetable::find()
            ->where("`start_date` = CURDATE()")
            ->AndWhere("TIME(`start_time`) > CURRENT_TIME()")
            ->andwhere("`teacher_id` = '{$id_teacher}'")
            ->orderBy('`start_date`,`start_time` ASC')
            ->all();
        //МОДЕЛИ НЕТ  - ИМЕНИ НЕТ
        if (!$searchModel) return "\nСегодня <b>" . \app\components\MyHelper::strTime2WeekDay(time()) . " " . \app\components\MyHelper::reverceDateFromAmeric(date("Y-m-d")) . "</b>";
        return
            "\n\nСЕГОДНЯ " . trim($this->renderPartial('/api/teachertimetable', [
                'model' => $searchModel,
            ]), " \r\n");
    }

    // показыват текущие пары на главной
    public function helperGroupTimeTableNowOnMain($id_group)
    {
        $searchModel = Timetable::find()
            ->where("`start_date` = CURDATE()")
            ->AndWhere("TIME(`start_time`) > CURRENT_TIME()")
            ->andwhere("`group_id` = '{$id_group}'")
            ->orderBy('`start_date`,`start_time` ASC')
            ->all();
        //МОДЕЛИ НЕТ  - ИМЕНИ НЕТ
        if (!$searchModel) return "";
        return
            "\n\nСЕГОДНЯ " . trim($this->renderPartial('/api/grouptimetable', [
                'model' => $searchModel,
            ]), " \r\n");
    }

    public function helperTeacherTimeTable($id, $week = 0)
    {
        //вторая и последующие недели
        if ($week) {
            $start_date = date('Y-m-d', strtotime('next Monday', time() + (604800 * ($week - 1))));
            $stop_date = date('Y-m-d', strtotime('next Sunday', strtotime($start_date)));
            $name_week = MyHelper::stringNameWeek($start_date);
            if (MyHelper::stringNameWeek($start_date) == '') $name_week = "Неделя c " . MyHelper::reverceDateFromAmeric($start_date) . " по " . MyHelper::reverceDateFromAmeric($stop_date);
        } else {
            //текущая неделя остатки
            $start_date = date('Y-m-d');


            // Ярослав Иванович попросил не срывать прошедшии дни текущей недели
            $start_date = date('Y-m-d', strtotime('monday this week'));


            // если сегодня воскресение - то выводеим текущю датцу
            if (date('w') == 0) {
                // эир в том случае если дата старта двигается за сегодняшним днем
                //$stop_date = $start_date;
                $stop_date = date('Y-m-d');

            } else {    // иначе ближайшее воскресение
                $stop_date = date('Y-m-d', strtotime('next Sunday', time()));
            }


            $name_week = MyHelper::stringNameWeek($start_date);
            if (MyHelper::stringNameWeek($start_date) == '') $name_week = "Неделя c " . MyHelper::reverceDateFromAmeric($start_date) . " по " . MyHelper::reverceDateFromAmeric($stop_date);
        }
        $name_week .= "\nРасписание для преподавателя\n<b>" . Teacher::findOne($id)->name_teacher . "</b>";

        $searchModel = Timetable::find()
            ->where("`start_date` >= '" . $start_date . "'")
            ->andwhere("`start_date` <= '" . $stop_date . "'")
            ->andwhere("`teacher_id` = '{$id}'")
            ->orderBy('`start_date`,`start_time` ASC')
            ->all();
        //

//        if (file_exists(Yii::$app->params['absolut_path'] . "/web/images/teachers/t_{$id}.jpg")) {
//
//        }

        //МОДЕЛИ НЕТ  - ИМЕНИ НЕТ
        if (!$searchModel) return "$name_week  - отсуствует";


        $timetable_today =
            $this->renderPartial('/api/teachertimetable', [
                'model' => $searchModel,
            ]);
        //
        if (mb_strlen($timetable_today) > 600)
            return $name_week . "\n" . $timetable_today . "\n" . $name_week;
        else return $name_week . "\n" . $timetable_today;
    }


    public function helperGroupTimeTable($id, $week = 0)
    {
        //вторая и последующие недели
        if ($week) {
            $start_date = date('Y-m-d', strtotime('next Monday', time() + (604800 * ($week - 1))));
            $stop_date = date('Y-m-d', strtotime('next Sunday', strtotime($start_date)));
            $name_week = MyHelper::stringNameWeek($start_date);
            if (MyHelper::stringNameWeek($start_date) == '') $name_week = "Неделя c " . MyHelper::reverceDateFromAmeric($start_date) . " по " . MyHelper::reverceDateFromAmeric($stop_date);

        } else {
            //текущая неделя остатки
            //$start_date = date('Y-m-d');

            // Ярослав Иванович попросил не срывать прошедшии дни текущей недели
            $start_date = date('Y-m-d', strtotime('monday this week'));


            // если сегодня воскресение - то выводеим текущю датцу
            if (date('w') == 0) {
                // эир в том случае если дата старта двигается за сегодняшним днем
                //$stop_date = $start_date;
                $stop_date = date('Y-m-d');

            } else {    // иначе ближайшее воскресение
                $stop_date = date('Y-m-d', strtotime('next Sunday', time()));
            }
            $name_week = MyHelper::stringNameWeek($start_date);
            if (MyHelper::stringNameWeek($start_date) == '') $name_week = "Неделя c " . MyHelper::reverceDateFromAmeric($start_date) . " по " . MyHelper::reverceDateFromAmeric($stop_date);

        }
        $name_week .= "\nРасписание для группы 👥 <b>" . Groupstep::findOne($id)->name_group . "</b>";
        $searchModel = Timetable::find()
            ->where("`start_date` >= '" . $start_date . "'")
            ->andwhere("`start_date` <= '" . $stop_date . "'")
            ->andwhere("`group_id` = '{$id}'")
            ->orderBy('`start_date`,`start_time` ASC')
            ->all();
        //
        //МОДЕЛИ НЕТ  - ИМЕНИ НЕТ
        if (!$searchModel) return "$name_week  - отсуствует";

        $timetable_today =
            $this->renderPartial('/api/grouptimetable', [
                'model' => $searchModel,
            ]);

        if (mb_strlen($timetable_today) > 400)
            return $name_week . "\n" . $timetable_today . "\n" . $name_week;
        else return $name_week . "\n" . $timetable_today;
    }

    public function actionTT()
    {
        return $this->helperGroupTimeTable(2288, 0);
        //return $this->helperTeacherTimeTable(60,1);
    }


    /**
     * Displays a single Timetable model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Timetable model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Timetable();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_tt]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Timetable model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id_tt]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Timetable model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Timetable model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Timetable the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Timetable::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
