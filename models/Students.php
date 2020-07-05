<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "students".
 *
 * @property int $id_student
 * @property string $name_student
 * @property int $group_id
 * @property string $phonenumber
 * @property string $birthday
 * @property string $address
 * @property string $email
 * @property string $telegram_chat_id
 * @property int $telegram_notactive
 * @property int $auth_confirmed
 * @property string $auth_hesh
 * @property int $status_notactive
 * @property int $logbook_id_streams
 * @property int $logbook_status
 * @property int $logbook_status_1c
 * @property string $updated
 *
 * @property Groupstep $group
 */
class Students extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'students';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_student', 'name_student', 'group_id', 'phonenumber' ], 'required'],
            [['id_student', 'group_id', 'telegram_notactive', 'auth_confirmed', 'status_notactive', 'logbook_id_streams', 'logbook_status', 'logbook_status_1c'], 'integer'],
            [['birthday', 'updated'], 'safe'],
            [['name_student', 'address', 'auth_hesh'], 'string', 'max' => 250],
            [['phonenumber'], 'string', 'max' => 15],
            [['email', 'telegram_chat_id'], 'string', 'max' => 100],
            [['id_student'], 'unique'],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Groupstep::className(), 'targetAttribute' => ['group_id' => 'id_group']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_student' => 'Id Student',
            'name_student' => 'Name Student',
            'group_id' => 'Group ID',
            'phonenumber' => 'Phonenumber',
            'birthday' => 'Birthday',
            'address' => 'Address',
            'email' => 'Email',
            'telegram_chat_id' => 'Telegram Chat ID',
            'telegram_notactive' => 'Telegram Notactive',
            'auth_confirmed' => 'Auth Confirmed',
            'auth_hesh' => 'Auth Hesh',
            'status_notactive' => 'Status Notactive',
            'logbook_id_streams' => 'Logbook Id Streams',
            'logbook_status' => 'Logbook Status',
            'logbook_status_1c' => 'Logbook Status 1c',
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
}
