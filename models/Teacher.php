<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "teacher".
 *
 * @property int $id_teacher
 * @property string $name_teacher
 * @property string $login_telega_teacher
 * @property int $status_teacher
 *
 * @property Timetable[] $timetables
 */
class Teacher extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'teacher';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status_teacher'], 'integer'],
            [['name_teacher', 'phonenumber', 'login_telega_teacher'], 'string', 'max' => 150],
            [['login_telega_teacher'], 'unique'],
            [['not_in_timetable_today'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_teacher' => 'Id Teacher',
            'name_teacher' => 'Name Teacher',
            'login_telega_teacher' => 'Login Telega Teacher',
            'status_teacher' => 'Status Teacher',
        ];
    }

    static function detectNotActiveByTameTable() {
        Yii::$app->getDb()->createCommand("UPDATE `teacher` set `not_in_timetable_today`=1")->query();
        $stat_prepod_active = Teacher::find()->joinWith('timetables')->where('start_date >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)')->groupBy('teacher_id')->All();
        //SELECT * FROM `timetable` tt LEFT JOIN teacher t ON id_teacher = teacher_id WHERE start_date >= NOW() group by teacher_id
        foreach ($stat_prepod_active as $item) {
            $item->not_in_timetable_today=0;
            $item->save();
        }
//        $stat_group_active = Groupstep::find()->joinWith('timetables')->where('start_date > NOW()')->groupBy('group_id')->count();

    }




    static function getAllActiveTeachers()
    {

        $model = Teacher::find()->where(['status_teacher' => 1])->all();

        return $model;

    }

    //полной ФИО в Фамилия + Инициалы
    public function GetInitioals()
    {
        //бля ! двойные пробелы !!! встречаются в имени
        $this->name_teacher = str_replace("  ", " ", $this->name_teacher);

        $sername_ia = explode(' ', $this->name_teacher);
        //ФИО инициалы
        return $sername_ia[0]
            . (isset($sername_ia[1]) ? ' ' . mb_substr($sername_ia[1], 0, 1) . '.' : '')
            . (isset($sername_ia[2]) ? ' ' . mb_substr($sername_ia[2], 0, 1) . '.' : '');
    }

    //полной ФИО в Фамилия + Имя
    public function GetFiname()
    {
        //бля ! двойные пробелы !!! встречаются в имени
        $this->name_teacher = str_replace("  ", " ", $this->name_teacher);

        $sername_ia = explode(' ', $this->name_teacher);
        //ФИО инициалы
        return $sername_ia[0]
            . (isset($sername_ia[1]) ? ' ' . $sername_ia[1] : '');
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetables()
    {
        return $this->hasMany(Timetable::className(), ['teacher_id' => 'id_teacher']);
    }
}
