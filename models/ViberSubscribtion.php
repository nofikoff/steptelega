<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "viber_subscribtion".
 *
 * @property int $id_sb
 * @property string $chat_id
 * @property string $chat_name
 * @property int $teacher_id
 * @property int $group_id
 * @property string $updated
 *
 * @property Groupstep $group
 * @property Teacher $teacher
 */
class ViberSubscribtion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'viber_subscribtion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_sb', 'chat_id'], 'required'],
            [['id_sb', 'teacher_id', 'group_id'], 'integer'],
            [['updated','chat_name'], 'safe'],
            [['chat_id'], 'string', 'max' => 50],
            [['chat_name'], 'string', 'max' => 250],
            [['id_sb'], 'unique'],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Groupstep::className(), 'targetAttribute' => ['group_id' => 'id_group']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => Teacher::className(), 'targetAttribute' => ['teacher_id' => 'id_teacher']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_sb' => 'Id Sb',
            'chat_id' => 'Chat ID',
            'chat_name' => 'Chat Name',
            'teacher_id' => 'Teacher ID',
            'group_id' => 'Group ID',
            'updated' => 'Updated',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Groupstep::className(), ['id_group' => 'group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::className(), ['id_teacher' => 'teacher_id']);
    }
}
