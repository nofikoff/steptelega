<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Exames;

/**
 * ExamesSearch represents the model behind the search form of `app\models\Exames`.
 */
class ExamesSearch extends Exames
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_exames', 'group_id', 'teacher_id', 'name_spec'], 'integer'],
            [['date_start', 'date_end', 'updated'], 'safe'],
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
        $query = Exames::find();

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
            'id_exames' => $this->id_exames,
            'group_id' => $this->group_id,
            'teacher_id' => $this->teacher_id,
            'name_spec' => $this->name_spec,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'updated' => $this->updated,
        ]);

        return $dataProvider;
    }
}
