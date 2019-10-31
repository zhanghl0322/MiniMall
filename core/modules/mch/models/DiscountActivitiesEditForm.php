<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/24
 * Time: 11:59
 */

namespace app\modules\mch\models;

use app\models\DiscountActivities;
use app\models\UserCoupon;

/**
 * @property DiscountActivities $discount_activities
 */
class DiscountActivitiesEditForm extends MchModel
{
    public $store_id;
    public $discount_activities;

    public $name;
    public $discount_type;
    public $min_price1;
    public $sub_price1;
    public $min_price2;
    public $sub_price2;
    public $min_price3;
    public $sub_price3;
    public $min_price4;
    public $sub_price4;
    public $min_price5;
    public $sub_price5;
    public $discount;
    public $expire_type;
    public $expire_day;
    public $begin_time;
    public $end_time;
    public $total_count;
    public $is_join;
    public $sort;
    public $cat_id_list;
    public $appoint_type;
    public $goods_id_list;
    public $rule;

    public function rules()
    {
        return [
            [['name'], 'trim'],
            [['name', 'discount_type', 'min_price1', 'sub_price1','min_price2', 'sub_price2','min_price3', 'sub_price3','min_price4', 'sub_price4','min_price5', 'sub_price5', 'expire_type',  'begin_time', 'end_time'], 'required'],
            [['sort'], 'integer', 'min' => 0, 'max' => 999999],
            [['expire_day'], 'integer', 'min' => 0, 'max' => 999],
            [['min_price1', 'sub_price1','min_price2', 'sub_price2','min_price3', 'sub_price3','min_price4', 'sub_price4','min_price5', 'sub_price5'], 'number', 'min' => 0, 'max' => 999999],
            [['is_join'], 'in', 'range' => [1, 2]],
            [['sort'], 'default', 'value' => 100],
            [['cat_id_list', 'goods_id_list'], 'safe'],
            [['appoint_type'], 'integer', 'min' => 0],
            [['rule'], 'string', 'max' => 1000],
        ];
    }

    public function attributeLabels()
    { 
        return [
            'name' => '满减活动名称',
            'discount_type' => '满减活动类型',
            'min_price1' => '最低消费金额',
            'sub_price1' => '优惠金额',
            'min_price2' => '最低消费金额',
            'sub_price2' => '优惠金额',
            'min_price3' => '最低消费金额',
            'sub_price3' => '优惠金额',
            'min_price4' => '最低消费金额',
            'sub_price4' => '优惠金额',
            'min_price5' => '最低消费金额',
            'sub_price5' => '优惠金额',
            'discount' => '折扣率',
            'expire_type' => '到期类型',
            'expire_day' => '有效天数',
            'begin_time' => '有效期开始时间',
            'end_time' => '有效期结束时间',
            'total_count' => '发放总数量',
            'is_join' => '加入领券中心',
            'sort' => '排序',
            'cat_id_list' => '商品分类id',
            'appoint_type' => '指定类别或商品',
            'goods_id_list' => '指定商品id',
            'rule' => '使用说明',
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $this->discount_activities->name = $this->name;
        $this->discount_activities->discount_type = 2;//默认满减
        $this->discount_activities->min_price1 = $this->min_price1;
        $this->discount_activities->sub_price1 = $this->sub_price1;
        $this->discount_activities->min_price2 = $this->min_price2;
        $this->discount_activities->sub_price2 = $this->sub_price2;
        $this->discount_activities->min_price3 = $this->min_price3;
        $this->discount_activities->sub_price3 = $this->sub_price3;
        $this->discount_activities->min_price4 = $this->min_price4;
        $this->discount_activities->sub_price4 = $this->sub_price4;
        $this->discount_activities->min_price5 = $this->min_price5;
        $this->discount_activities->sub_price5 = $this->sub_price5;
        $this->discount_activities->maximum_price=0;
        $this->discount_activities->discount = 0;
        $this->discount_activities->expire_type = 2;//指定有效期
        $this->discount_activities->expire_day =0;
        $this->discount_activities->begin_time = strtotime($this->begin_time . ' 00:00:00');
        $this->discount_activities->end_time = strtotime($this->end_time . ' 23:59:59');
        $this->discount_activities->is_join = $this->is_join;
        $this->discount_activities->sort = $this->sort;
        $this->discount_activities->rule = $this->rule;
        $this->discount_activities->appoint_type = $this->appoint_type;
        $old_cat_id_list = json_decode($this->discount_activities->cat_id_list);

        if($this->discount_activities->begin_time>2000000000 || $this->discount_activities->end_time>2000000000){
            return [
                'code' => 1,
                'msg' => '有效期范围超过限制'
            ];
        }
        if (count($old_cat_id_list) < 1) {
            $this->discount_activities->cat_id_list = \Yii::$app->serializer->encode($this->cat_id_list);
        } else {
            if ($this->cat_id_list) {
                $new_cat_id_list = array_merge($old_cat_id_list, $this->cat_id_list);
                $this->discount_activities->cat_id_list = \Yii::$app->serializer->encode($new_cat_id_list);
            }
        }
        $old_goods_id_list = json_decode($this->discount_activities->goods_id_list);
        if (count($old_goods_id_list) < 1) {
            $this->discount_activities->goods_id_list = \Yii::$app->serializer->encode($this->goods_id_list);
        } else {
            if ($this->goods_id_list) {
                $new_goods_id_list = array_merge($old_goods_id_list, $this->goods_id_list);
                $this->discount_activities->goods_id_list = \Yii::$app->serializer->encode($new_goods_id_list);
            }
        }

        if ($this->discount_activities->isNewRecord) {
            $this->discount_activities->store_id = $this->store_id;
            $this->discount_activities->addtime = time();
        } else {
//            $coupon_count = UserCoupon::find()->where(['store_id' => $this->store_id, 'is_delete' => 0, 'coupon_id' => $this->coupon->id, 'type' => 2])->count();
//            if ($coupon_count > $this->total_count && $this->total_count != -1) {
//                return [
//                    'code' => 1,
//                    'msg' => '优惠券总数不得小于已领取总数'
//                ];
//            }
        }
        if ($this->discount_activities->save()) {
            return [
                'code' => 0,
                'msg' => '保存成功',
            ];
        } else {
            return $this->getErrorResponse($this->discount_activities);
        }
    }
}
