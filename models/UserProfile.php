<?php

namespace app\models;
use dektrium\user\models\Profile as BaseProfile;

class UserProfile extends BaseProfile
{

    /** @inheritdoc */
    public function rules()
    {
        return [
            // username rules
            ['mobile', 'required', 'on' => ['register', 'connect', 'create', 'update']],
            ['mobile', 'match', 'pattern' => '/^[0-9]+$/'],
            ['mobile', 'string', 'min' => 9, 'max' => 15],
            ['mobile', 'unique'],
            ['mobile', 'trim'],

// серъезные глюки с полем name - не сохраняет данные
// гдето затык или в том что имя зарезервировано или еще rules и пр проверил - пофиг проще создать свое поле
	    ['name2', 'safe'],

        ];
    }


    public function attributeLabels()
    {
        return [
            'gravatar_email' => 'На Gravatar.com ваш email',
            'name2' => 'Iм\'я',
            'mobile' => 'Телефон',
            'bio' => 'Коментар',
        ];
    }





}


?>