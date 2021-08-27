<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;

/**
 * This is the model class for table "movement".
 *
 * @property int $id
 * @property int|null $card_id
 * @property int|null $staffpos_id
 * @property int|null $department_id
 * @property string|null $begin
 * @property string|null $end
 *
 * @property Card $card
 * @property Department $department
 * @property Staffpos $staffpos
 */
class Movement extends ActiveRecord
{
    const NEW_CARD = 1;
    const EXISTING_CARD = 2;

    const SCENARIO_CREATE = 'scenario_create';
    const SCENARIO_MOVE = 'scenario_move';

    const EVENT_LOG_MOVEMENT = 4;

    const LOG_TEXT_ATTRS = [
        'begin', 'end',
    ];
    const LOG_FK_COMPOSITE_ATTRS = [
        'staffpos_id' => ['class' => Staffpos::class],
        'card_id' => ['class' => Card::class],
    ];

    public $pre_creator_id;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'movement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['card_id', 'staffpos_id'], 'required'],
            [['card_id', 'staffpos_id'], 'default', 'value' => null],
            [['card_id', 'staffpos_id'], 'integer'],
            [['begin', 'end'], 'safe'],
            [['card_id'], 'exist', 'skipOnError' => true, 'targetClass' => Card::className(), 'targetAttribute' => ['card_id' => 'id']],
            [['staffpos_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staffpos::className(), 'targetAttribute' => ['staffpos_id' => 'id']],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['card_id', 'staffpos_id', 'begin','stabnum','firstname', 'secondname', 'thirdname'];
        $scenarios[self::SCENARIO_MOVE] = ['staffpos_id', 'begin'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'card_id' => 'Сотрудник',
            'staffpos_id' => 'Штатная единица',
            'begin' => 'Дата начала',
            'end' => 'Дата окончания',
        ];
    }

    /**
     * Gets query for [[Card]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCard()
    {
        return $this->hasOne(Card::className(), ['id' => 'card_id']);
    }

    /**
     * Gets query for [[Staffpos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStaffpos()
    {
        return $this->hasOne(Staffpos::className(), ['id' => 'staffpos_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $this->refresh();
            $this->logChanges(Log::EVENT_INSERT, $changedAttributes);
        } else {
            $this->logChanges(Log::EVENT_UPDATE, $changedAttributes);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->logChanges(Log::EVENT_DELETE);
    }

    protected function logChanges($event, $changedAttributes = [])
    {
        $comments = [];

        switch ($event) {
            case Log::EVENT_INSERT:
                $text = '"%1$s" = "%3$s"; ';
                $comments[] = 'Запись создана. ';
                $comments = $this->getLogChanges($changedAttributes, $text, $comments);
                break;

            case Log::EVENT_UPDATE:
                $text = 'Атрибут "%1$s" изменён с "%2$s" на "%3$s"';
                $comments = $this->getLogChanges($changedAttributes, $text, $comments);
                break;

            case Log::EVENT_DELETE:
                $comments[] = 'Запись удалена';
                break;
        }

        if (count($comments)) {
            $eventLog = new EventLog([
                'user_id' => (empty(Yii::$app->user->id)) ? $this->pre_creator_id : Yii::$app->user->id,
                'type_event_id' => self::EVENT_LOG_MOVEMENT,
                'field_id' => $this->id,
                'comments' => $comments,
            ]);

            $eventLog->save(false);
        }
    }

    protected function getLogChanges($changedAttributes, $text, $comments)
    {
        foreach ($changedAttributes as $attr => $old_val) {
            $new_val = $this->$attr;
            if ($old_val != $new_val) {
                switch (true) {
                    case in_array($attr, self::LOG_TEXT_ATTRS):
                        $comments[] = sprintf($text, $this->getAttributeLabel($attr), $old_val, $new_val);
                        break;

                    case in_array($attr, array_keys(self::LOG_FK_COMPOSITE_ATTRS)):
                        $old_val_text = self::getCompositeValue($attr, $old_val);
                        $new_val_text = self::getCompositeValue($attr, $new_val);
                        $comments[] = sprintf($text, $this->getAttributeLabel($attr), $old_val_text, $new_val_text);
                        break;
                }
            }
        }

        return $comments;
    }

    protected static function getCompositeValue($attr, $id)
    {
        if ($id) {
            $fk_class = self::LOG_FK_COMPOSITE_ATTRS[$attr]['class'];
            $classInstance = $fk_class::findOne(['id' => $id]);

            if ($classInstance) {
                return $classInstance->getCompositeName();
            } else {
                // если id не нашли (например удалили из справочника или еще какая беда)
                return "id = $id";
            }
        }

        return null;
    }

    public static function getDataByCard($id)
    {
        $out = new Query();
        $out->addSelect([
            new Expression("concat('(', department.code, ') ', department.short_name) as department_name"),
            new Expression("concat('(', department.code, ') ', department.full_name) as department_full_name"),
            new Expression("concat('(', department.code, ') ', department.short_name, ' ', profession.name) as staffpos_name"),
            new Expression("concat(card.secondname, ' ', card.firstname, ' ', card.thirdname) as card_name"),
            'profession.name as profession_name',
        ])->from('movement')
          ->leftJoin('card', 'card.id = movement.card_id')
          ->leftJoin('staffpos', 'staffpos.id = movement.staffpos_id')
          ->leftJoin('department', 'department.id = staffpos.department_id')
          ->leftJoin('profession', 'profession.id = staffpos.profession_id')
          ->where(['movement.card_id' => $id])
          ->andWhere(['movement.end' => null]);

        return $out->one();
    }

    public static function checkCountByCard($card_id)
    {
        $sql = "
            select count(*)
            from movement
            where movement.card_id = :card_id
        ";
        $cnt = Yii::$app->db->createCommand($sql, ['card_id' => $card_id])->queryScalar();

        return ($cnt > 0);
    }

    /**
     * Функция добавляет перемещение в фоновом режиме
     * @param $card_id
     * @param $staffpos_id
     * @param $begin
     * @param $end
     */
    public function addMovement($card_id, $staffpos_id, $begin, $end, $creator_id)
    {
        $this->card_id = $card_id;
        $this->staffpos_id = $staffpos_id;
        $this->begin = $begin;
        $this->end = $end;
        $this->pre_creator_id = $creator_id;
        $this->save();
    }
}
