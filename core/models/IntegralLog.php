<?php

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

/**
 * This is the model class for table "{{%integral_log}}".
 *
 * @property string $id
 * @property string $user_id
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
class IntegralLog extends \yii\db\ActiveRecord
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
        return '{{%integral_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'content', 'addtime', 'username', 'operator', 'store_id', 'operator_id'], 'required'],
            [['user_id', 'integral', 'addtime', 'store_id', 'operator_id', 'type'], 'integer'],
            [['content'], 'string'],
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
            'user_id' => '用户id',
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

    public static function userAddIntegralLog($user_id, $integral,$content)
    {
        $integral = $integral;
        $user_id = $user_id;
        $rechangeType = 1;//积分充值
        $user = User::findOne(['id' => $user_id, 'store_id' => 1]);//店铺归属

        $integralLog = new IntegralLog();
        $integralLog->user_id = $user->id;
        $register = new Register();
        $register->store_id =1;
        $register->user_id = $user->id;
        $register->register_time = $content;
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 100;//CRM 调用数据
        if ($rechangeType == '2') {
            $register->integral = '-' . $integral;
        } elseif ($rechangeType == '1') {
            $register->integral = $integral;
        }
        $register->save();
        if (!$user) {
            return [
                'code' => 1,
                'msg' => '用户不存在，或已删除',
            ];
        }
        if (empty($integral)) {
            return [
                'code' => 1,
                'msg' => '积分设置不正确',
            ];
        }
        if ($rechangeType == '2') {
            if ($integral > $user->integral) {
                return [
                    'code' => 1,
                    'msg' => '用户当前积分不足',
                ];
            }
            $user->integral -= $integral;
        } elseif ($rechangeType == '1') {
            $user->integral += $integral;
            $user->total_integral += $integral;
        }
        if (!$user->save()) {
            return [
                'code' => 1,
                'msg' => '操作失败！请重试',
            ];
        }
        if ($rechangeType == '2') {
            $integralLog->content = "管理员： CRM 后台操作账号：" . $user->nickname . " 积分扣除：" . $integral . " 积分";
        } elseif ($rechangeType == '1') {
            $integralLog->content = "管理员： CRM 后台操作账号：" . $user->nickname . " 积分充值：" . $integral . " 积分";
        }
        $integralLog->integral = $integral;
        $integralLog->addtime = time();
        $integralLog->username = trim($user->nickname) ? $user->nickname : '未知';
        $integralLog->operator =  'CRM';
        $integralLog->store_id = 1;
        $integralLog->operator_id = 1;

        if ($integralLog->save()) {
            return [
                'code' => 0,
                'msg' => '操作成功',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '操作失败',
            ];
        }

    }

    //IntegralLog::userAddIntegralLog($user->id, $value['integral'],$value['content']);
    /**
     * 2019年10月24日15:24:00  商城金额兑换洗车金
     * @param $user_id 用户ID
     * @param $integral 扣减值
     * @param $content 描述
     * @param int $rechangeType 默认2 账户余额兑换洗车金  扣除 金额
     * @return array
     */
    public static function userRechargeIntegralLog($user_id, $integral,$content,$rechangeType=2)
    {
        $user = User::findOne(['id' => $user_id, 'store_id' => 1]);//店铺归属

        $integralLog = new IntegralLog();
        $integralLog->user_id = $user->id;
        $register = new Register();
        $register->store_id =1;
        $register->user_id = $user->id;
        $register->register_time = $content;
        $register->addtime = time();
        $register->continuation = 0;
        $register->type = 99;//商城余额兑换洗车机显示
        if ($rechangeType == '2') {
            $register->integral = '-' . $integral;
        } elseif ($rechangeType == '1') {
            $register->integral = $integral;
        }
        $register->save();
        if (!$user) {
            return [
                'code' => 1,
                'msg' => '用户不存在，或已删除',
            ];
        }
        if (empty($integral)) {
            return [
                'code' => 1,
                'msg' => '积分设置不正确',
            ];
        }
        if ($rechangeType == '2') {
            if ($integral > $user->integral) {
                return [
                    'code' => 1,
                    'msg' => '用户当前积分不足',
                ];
            }
            $user->integral -= $integral;
        } elseif ($rechangeType == '1') {
            $user->integral += $integral;
            $user->total_integral += $integral;
        }
        if (!$user->save()) {
            return [
                'code' => 1,
                'msg' => '操作失败！请重试',
            ];
        }
        if ($rechangeType == '2') {
            $integralLog->content = "管理员： CRM 后台操作账号：" . $user->nickname . " 余额扣除：" . $integral . " 元";
        } elseif ($rechangeType == '1') {
            $integralLog->content = "管理员： CRM 后台操作账号：" . $user->nickname . " 余额充值：" . $integral . " 元";
        }
        $integralLog->integral = $integral;
        $integralLog->addtime = time();
        $integralLog->username = trim($user->nickname) ? $user->nickname : '未知';
        $integralLog->operator =$user->nickname;//用户微信昵称
        $integralLog->store_id = $user->store_id;
        $integralLog->operator_id = 1;

        if ($integralLog->save()) {
            return [
                'code' => 0,
                'msg' => '操作成功',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '操作失败',
            ];
        }

    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $data = $insert ? json_encode($this->attributes) : json_encode($changedAttributes);
        CommonActionLog::storeActionLog('', $insert, 0, $data, $this->id);
    }


}
