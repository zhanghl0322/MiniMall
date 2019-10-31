<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%lottery_user}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $wechat_union_id
 * @property integer $user_id
 * @property integer $oppty
 * @property integer $type
 * @property integer $start_time
 * @property integer $end_time
 * @property string $rule
 * @property integer $is_delete
 * TODO 新增用户剩余抽奖次数
 */

class LotteryUser extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%lottery_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id','user_id', 'oppty', 'type', 'start_time', 'end_time', 'is_delete'], 'integer'],
            [[ 'wechat_union_id'], 'string', 'max' => 255],
            [['rule'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'wechat_union_id' => '微信用户union id',
            'user_id' => '用户Id',
            'oppty' => '抽奖次数',
            'type' => '1.抽奖 2 赠送 3.兑换',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'rule' => '规则',
            'is_delete' => '状态',
        ];
    }
}
