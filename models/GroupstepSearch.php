<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Groupstep;

/**
 * GroupstepSearch represents the model behind the search form of `app\models\Groupstep`.
 */
class GroupstepSearch extends Groupstep
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_group'], 'integer'],
            [['name_group', 'description_group', 'login_telega_group'], 'safe'],
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
        $query = Groupstep::find();

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
            'id_group' => $this->id_group,
        ]);

        $query->andFilterWhere(['like', 'name_group', $this->name_group])
            ->andFilterWhere(['like', 'description_group', $this->description_group])
            ->andFilterWhere(['like', 'login_telega_group', $this->login_telega_group]);

        return $dataProvider;
    }
}
