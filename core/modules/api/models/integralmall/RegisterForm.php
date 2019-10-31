<?php
/**
 * Created by PhpStorm.
 * User: zc
 * Date: 2018/5/29
 * Time: 21:55
 */

namespace app\modules\api\models\integralmall;

use app\models\IntegralSetting;
use app\models\User;
use app\modules\api\models\ApiModel;
use app\models\Register;
use app\models\LotteryUser;

class RegisterForm extends ApiModel
{
    public $store_id;
    public $user_id;
    public $register_time;

    public function rules()
    {
        return [
            [['store_id', 'user_id', 'register_time'], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'store_id' => 'Store ID',
            'user_id' => 'User ID',
            'register_time' => '签到日期',
        ];
    }

    public function save()
    {
        $register = new Register();
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $first_day = Register::find()->where(['store_id' => $this->store_id, 'user_id' => $this->user_id, 'type' => 1])->orderBy('addtime DESC')->asArray()->one();
        $setting = IntegralSetting::find()->where(['store_id' => $this->store_id])->asArray()->one();
        $user = User::findOne(['id' => $this->user_id, 'store_id' => $this->store_id]);
        if (!$setting) {
            return [
                'code' => 1,
                'msg' => '签到未设置'
            ];
        }
        $day1 = $first_day['continuation'];
        $first_day['register_time'] = strtotime(date('Ymd', strtotime($first_day['register_time'])));
//        $first_day['register_time'] = explode("/",$first_day['register_time']);
//        $first_day['register_time'] = implode("", $first_day['register_time']);
        $date = strtotime(date('Ymd', time()));
        $day = $date - $first_day['register_time'];
        if ($date == $first_day['register_time']) {
            return [
                'code' => 1,
                'msg' => '已签到'
            ];
        }
        $day = ($date - $first_day['register_time']) / 86400;
        if ($day >= 1 && $day < 2) {
            $day1++;
        } else {
            $day1 = 1;
        }
        $score = $setting['register_integral'];
        if ($day1 >= $setting['register_continuation']) {
            $score = $setting['register_reward'] + $setting['register_integral'];
        } else {
            $score = $setting['register_integral'];
        }
        $register->store_id = $this->store_id;
        $register->user_id = $this->user_id;
        $register->register_time = $this->register_time;
        $register->addtime = time();
        $register->continuation = $day1;
        $register->integral = $score;
        $register->type = 1;
        $user->integral += $score;
        if ($user->save()) {
            if ($register->save()) {
                return [
                    'code' => 0,
                    'msg' => '签到成功',
                    'data' => [
                        'continuation' => $register->continuation
                    ]
                ];
            } else {
                return $this->getErrorResponse($register);
            }
        } else {
            return $this->getErrorResponse($user);
        }
    }

    //TODO 积分兑换抽奖次数  20 一次  2019年7月22日14:08:27
    public function ExchangeDrawSave()
    {
        \Yii::warning($this->user_id.'==兑换抽奖ExchangeDrawSave=='.$this->store_id,'info');
        $register = new Register();
        $register->store_id = $this->store_id;
        $register->user_id = $this->user_id;
        $register->register_time = '..';
        $register->addtime = time();
        $register->continuation = 0;
        $register->integral = -20;
        $register->type = 99;
        $user = User::findOne(['id' => $this->user_id, 'store_id' => $this->store_id]);

        //TODO 往抽奖表写入记录  Allon  2019年7月23日14:37:46
        $lottery_user = new LotteryUser();
        $lottery_user->store_id=$user->store_id;
        $lottery_user->wechat_union_id=$user->wechat_union_id;
        $lottery_user->user_id=$this->user_id;
        $lottery_user->oppty+=1;
        $lottery_user->type=1;
        $lottery_user->start_time=time();
        $lottery_user->end_time=time();
        $lottery_user->rule='积分兑换';
        $lottery_user->is_delete=0;

        $user->integral -= 20;
        //使用事务处理提交
        $t = \Yii::$app->db->beginTransaction();
        if (!$user->save()) {
            $t->rollBack();
            return $this->getErrorResponse($register);
            if (!$register->save()) {
                $t->rollBack();
                return $this->getErrorResponse($register);
                if (!$lottery_user->save()) {
                    $t->rollBack();
                    return $this->getErrorResponse($lottery_user);
                }else {
                    $t->commit();
                    return [
                        'code' => 0,
                        'msg' => '兑换成功',
                        'data' => [
                            'integral' => $user->integral,
                            'id' => $register->id
                        ]
                    ];
                }
            }
        }
    }
}
