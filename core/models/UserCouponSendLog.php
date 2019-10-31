<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%user_coupon_send_log}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $wechat_union_id
 * @property integer $coupon_id
 * @property integer $coupon_auto_send_id
 * @property integer $begin_time
 * @property integer $end_time
 * @property integer $is_expire
 * @property integer $is_use
 * @property integer $is_delete
 * @property integer $addtime
 * @property integer $type
 * @property integer $integral
 * @property string $price
 */
class UserCouponSendLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_coupon_send_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'wechat_union_id','coupon_id'], 'required'],
            [['store_id',  'coupon_id', 'coupon_auto_send_id', 'begin_time', 'end_time', 'is_expire', 'is_use', 'is_delete', 'addtime', 'type', 'integral'], 'integer'],
            [[ 'wechat_union_id'], 'string', 'max' => 255],
            [['price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => '店铺id',
            'wechat_union_id' => '微信用户union id',
            'coupon_id' => 'Coupon ID',
            'coupon_auto_send_id' => '自动发放id',
            'begin_time' => '有效期开始时间',
            'end_time' => '有效期结束时间',
            'is_expire' => '是否已过期：0=未过期，1=已过期',
            'is_use' => '是否已使用：0=未使用，1=已使用',
            'is_delete' => 'Is Delete',
            'addtime' => 'Addtime',
            'type' => '领取类型 0--平台发放 1--自动发放 2--领券中心领取 3--积分商城',
            'integral' => '支付积分',
            'price' => '支付价钱',
        ];
    }

    /**
     * 给CRM用户发放优惠券
     * @param integer $wechat_union_id 微信union_id
     * @param integer $coupon_id 优惠券id
     * @param integer $coupon_auto_send_id 自动发放id
     * @param integer $type 领券类型
     * @return boolean
     */
    public static function userAddCouponLog($wechat_union_id, $coupon_id, $coupon_auto_send_id = 0, $type = 0)
    {
        $user = User::findOne([
            'wechat_union_id' => $wechat_union_id,
            'type' => 1,
        ]);
        if (!$user) {
            return false;
        }

        $coupon = Coupon::findOne([
            'id' => $coupon_id,
            'is_delete' => 0,
        ]);
        if (!$coupon) {
            return false;
        }
        if ($coupon->total_count == 0) {
            return false;
        }
        $user_coupon = new UserCouponSendLog();
        if ($type == 2) {
            $res = UserCouponSendLog::find()->where(['is_delete'=>0,'type'=>2,'wechat_union_id'=>$wechat_union_id,'coupon_id'=>$coupon_id])->exists();
            if ($res) {
                return false;
            }
        }
        if ($coupon_auto_send_id) {
            $coupon_auto_send = CouponAutoSend::findOne([
                'id' => $coupon_auto_send_id,
                'is_delete' => 0,
            ]);
            if (!$coupon_auto_send) {
                return false;
            }
            if ($coupon_auto_send->send_times != 0) {
                $send_count = UserCoupon::find()->where([
                    'coupon_auto_send_id' => $coupon_auto_send->id,
                    'user_id' => $user->id,
                ])->count();
                if ($send_count && $send_count >= $coupon_auto_send->send_times) {
                    return false;
                }
            }
            $user_coupon->coupon_auto_send_id = $coupon_auto_send->id;
            $type = 1;
        }
        $user_coupon->type = $type;
        $user_coupon->store_id =1; //默认给 1  指定车海洋店铺  TODO:默认给 1  指定车海洋店铺  2019-06-04 17点09分
        $user_coupon->wechat_union_id = $user->wechat_union_id;
        $user_coupon->coupon_id = $coupon->id;
        if ($coupon->expire_type == 1) {
            $user_coupon->begin_time = time();
            $user_coupon->end_time = time() + max(0, 86400 * $coupon->expire_day);
        } elseif ($coupon->expire_type == 2) {
            $user_coupon->begin_time = $coupon->begin_time;
            $user_coupon->end_time = $coupon->end_time;
        }
        $user_coupon->is_expire = 0;
        $user_coupon->is_use = 0;
        $user_coupon->is_delete = 0;
        $user_coupon->addtime = time();

        $message=$wechat_union_id.'用户unionid'.$coupon_id.'优惠券'.$coupon_auto_send_id.'发放'.$type ;
        Yii::warning($message,'pay');
        return $user_coupon->save();
    }
    public function getCoupon()
    {
        return $this->hasOne(Coupon::className(), ['id'=>'coupon_id']);
    }
    public function getUser()
    {
//        return $this->hasOne(Coupon::className(),['coupon_id'=>'id']);
        return $this->hasOne(User::className(), ['wechat_union_id'=>'wechat_union_id']);
    }
}
