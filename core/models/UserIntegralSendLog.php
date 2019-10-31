<?php

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

/**
 * This is the model class for table "{{%user_integral_send_log}}".
 *
 * @property string $id
 * @property string $wechat_union_id
 * @property string $content
 * @property string $integral
 * @property string $addtime
 * @property string $username
 * @property string $operator
 * @property string $store_id
 * @property string $operator_id
 * @property integer $type
 * @property string $pic_url
 * @property string $explain
 */
class UserIntegralSendLog extends \yii\db\ActiveRecord
{
    /**
     * 数据类型：积分
     */
    const TYPE_INTEGRAL = 0;

    /**
     * 数据类型：金额
     */
    const TYPE_BALANCE = 1;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_integral_send_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[ 'content', 'addtime', 'username', 'operator', 'store_id', 'operator_id'], 'required'],
            [[ 'integral', 'addtime', 'store_id', 'operator_id', 'type'], 'integer'],
            [['content'], 'string'],
            [[ 'wechat_union_id'], 'string', 'max' => 255],
            [['username', 'operator', 'pic_url', 'explain'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wechat_union_id' => '微信用户union id',
            'content' => '描述',
            'integral' => '输入值',
            'addtime' => '添加时间',
            'username' => '用户名',
            'operator' => '操作者',
            'store_id' => 'Store ID',
            'operator_id' => '分销商id',
            'type' => '数据类型 0--积分修改 1--余额修改',
            'pic_url' => '图片',
            'explain' => '说明',
        ];
    }


    public function getUser()
    {
        return $this->hasOne(User::className(), ['wechat_union_id'=>'wechat_union_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $data = $insert ? json_encode($this->attributes) : json_encode($changedAttributes);
        CommonActionLog::storeActionLog('', $insert, 0, $data, $this->id);
    }


}
