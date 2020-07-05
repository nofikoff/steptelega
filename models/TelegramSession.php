<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "telegram_session".
 *
 * @property string $id_chat
 * @property string $name_chat
 * @property string $updated
 */
class TelegramSession extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'telegram_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_chat'], 'required'],
            [['updated', 'name_chat'], 'safe'],
            [['id_chat'], 'string', 'max' => 100],
            [['name_chat'], 'string', 'max' => 250],
            [['id_chat'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_chat' => 'Id Chat',
            'name_chat' => 'Name Chat',
            'updated' => 'Updated',
        ];
    }
}
