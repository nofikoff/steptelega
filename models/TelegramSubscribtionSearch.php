<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TelegramSubscribtion;

/**
 * TelegramSubscribtionSearch represents the model behind the search form of `app\models\TelegramSubscribtion`.
 */
class TelegramSubscribtionSearch extends TelegramSubscribtion
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_sb', 'teacher_id', 'group_id'], 'integer'],
            [['chat_id', 'updated'], 'safe'],
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
        $query = TelegramSubscribtion::find();

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
            'id_sb' => $this->id_sb,
            'teacher_id' => $this->teacher_id,
            'group_id' => $this->group_id,
            'updated' => $this->updated,
        ]);

        $query->andFilterWhere(['like', 'chat_id', $this->chat_id]);

        return $dataProvider;
    }
}
