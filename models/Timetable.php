<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "timetable".
 *
 * @property int $id_tt
 * @property string $start_date
 * @property string $start_time
 * @property string $stop_time
 * @property int $teacher_id
 * @property int $subject_id
 * @property int $group_id
 * @property string $subject
 * @property int $room_id
 *
 * @property Teacher $teacher
 * @property Groupstep $group
 */
class Timetable extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['start_date', 'start_time', 'stop_time', 'city_id'], 'safe'],
            [['teacher_id', 'subject_id', 'group_id', 'room_id', 'countpara'], 'integer'],
            [['subject'], 'string', 'max' => 200],
            [['start_date', 'start_time','group_id'], 'unique', 'targetAttribute' => ['start_date', 'start_time','group_id']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => Teacher::className(), 'targetAttribute' => ['teacher_id' => 'id_teacher']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Groupstep::className(), 'targetAttribute' => ['group_id' => 'id_group']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_tt' => 'Id Tt',
            'countpara' => 'Serial',
            'start_date' => 'Start Date',
            'start_time' => 'Start Time',
            'stop_time' => 'Stop Time',
            'teacher_id' => 'Teacher ID',
            'subject_id' => 'Subject ID',
            'group_id' => 'Group ID',
            'subject' => 'Subject',
            'room_id' => 'Room ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::className(), ['id_teacher' => 'teacher_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Groupstep::className(), ['id_group' => 'group_id']);
    }
}
