<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "exames".
 *
 * @property int $id_exames
 * @property int $group_id
 * @property int $teacher_id
 * @property int $name_spec
 * @property string $date_start
 * @property string $date_end
 * @property string $updated
 *
 * @property Teacher $teacher
 * @property Groupstep $group
 */
class Exames extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exames';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_exames', 'group_id', 'teacher_id', 'name_spec', 'date_start', 'date_end'], 'required'],
            [['id_exames', 'group_id', 'teacher_id'], 'integer'],
            [['date_start', 'date_end', 'updated'], 'safe'],
            [['id_exames'], 'unique'],
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
            'id_exames' => 'Id Exames',
            'group_id' => 'Group ID',
            'teacher_id' => 'Teacher ID',
            'name_spec' => 'Name Spec',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'updated' => 'Updated',
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
