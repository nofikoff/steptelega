<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Students;

/**
 * StudentsSearch represents the model behind the search form of `app\models\Students`.
 */
class StudentsSearch extends Students
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_student', 'group_id', 'telegram_notactive', 'auth_confirmed', 'status_notactive', 'logbook_id_streams', 'logbook_status', 'logbook_status_1c'], 'integer'],
            [['name_student', 'phonenumber', 'birthday', 'address', 'email', 'telegram_chat_id', 'auth_hesh', 'updated'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Students::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_student' => $this->id_student,
            'group_id' => $this->group_id,
            'birthday' => $this->birthday,
            'telegram_notactive' => $this->telegram_notactive,
            'auth_confirmed' => $this->auth_confirmed,
            'status_notactive' => $this->status_notactive,
            'logbook_id_streams' => $this->logbook_id_streams,
            'logbook_status' => $this->logbook_status,
            'logbook_status_1c' => $this->logbook_status_1c,
            'updated' => $this->updated,
        ]);

        $query->andFilterWhere(['like', 'name_student', $this->name_student])
            ->andFilterWhere(['like', 'phonenumber', $this->phonenumber])
            ->andFilterWhere(['like', 'address', $this->address])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'telegram_chat_id', $this->telegram_chat_id])
            ->andFilterWhere(['like', 'auth_hesh', $this->auth_hesh]);

        return $dataProvider;
    }
}
