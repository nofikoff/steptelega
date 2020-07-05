<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "groupstep".
 *
 * @property int $id_group
 * @property string $name_group
 * @property string $description_group
 * @property string $login_telega_group
 *
 * @property Timetable[] $timetables
 */
class Groupstep extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'groupstep';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_group', 'description_group'], 'string', 'max' => 150],
            [['login_telega_group'], 'string', 'max' => 45],
            [['notactive', 'not_in_timetable_today'], 'safe']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'name_group' => 'Name Group',
            'description_group' => 'Description Group',
            'login_telega_group' => 'Login Telega Group',
        ];
    }


    static function detectNotActiveByTameTable() {
        Yii::$app->getDb()->createCommand("UPDATE `groupstep` set `not_in_timetable_today`=1")->query();
        $stat_prepod_active = groupstep::find()->joinWith('timetables')->where('start_date >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)')->groupBy('group_id')->All();


        //SELECT * FROM `timetable` tt LEFT JOIN teacher t ON id_teacher = teacher_id WHERE start_date >= DATE_ADD(CURDATE(), INTERVAL -1 DAY) group by teacher_id ORDER BY `t`.`name_teacher` DESC

        foreach ($stat_prepod_active as $item) {
            $item->not_in_timetable_today=0;
            $item->save();
        }
    }

    static function getAllGroups(){

        $model = Groupstep::find()->select('name_group,id_group')->asArray()->all();
        foreach ($model as $item) {

            $a[$item['name_group']]=$item['id_group'];
        }
        return $a;

    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetables()
    {
        return $this->hasMany(Timetable::className(), ['group_id' => 'id_group']);
    }
}
