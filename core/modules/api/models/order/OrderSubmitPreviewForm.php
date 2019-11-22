<?php
/**
 * @copyright ©2018 Lu Wei
 * @author Lu Wei
 * @link http://www.luweiss.com/
 * Created by IntelliJ IDEA
 * Date Time: 2018/7/26 15:42
 */


namespace app\modules\api\models\order;


use app\models\DiscountActivities;
use app\models\User;

class OrderSubmitPreviewForm extends OrderForm
{
    public function rules()
    {
        return parent::rules();
    }

    public function search()
    {

        if (!$this->validate())
            return $this->getErrorResponse();
        try{
            $mchList = $this->getMchListData();
            if($mchList['code'] == 1){
                return $mchList;
            }
            //TODO 外围统计多商户总订单金额 2019年8月28日15:52:13
            $order_total_price = 0;//默认值
            foreach ($mchList as $mch_item) {
                $order_total_price += $mch_item['total_price'];//Sum Order Price
            }
            \Yii::warning($order_total_price.'================BBBBBBBBBBBBBBBB====================='.$order_total_price,'info');
            //TODO 循环组装符合要求的优惠券  2019年8月28日17:28:49
            $new_coupon_list = [];
            foreach ($mchList as $mch_item) {
                foreach ($mch_item['coupon_list'] as $i => $coupon_item) {
                    \Yii::warning($order_total_price.'================AAAAAAAAAAAAAAAAAAA====================='.$i,'info');
                    if($order_total_price>=$coupon_item['min_price'])
                    {
                        \Yii::warning('================8484848====================='.$coupon_item['min_price'],'info');
                        $new_coupon_list[$i] = $coupon_item;
                    }
                }
            }

        }catch(\Exception $e){
            return [
                'code'=>1,
                'line' => $e->getLine(),
                'msg'=>$e->getMessage()
            ];
        }
        $user = User::findOne(['id' => $this->user_id]);

        //TODO 处理满减活动存在时  提示选择满减活动的问题 2019年9月11日16:59:32
        $discount_activities = DiscountActivities::findOne([
            'store_id' => $this->store_id,
            'is_delete' => 0,
            'is_join' => 2,
        ]);


        //========================满减组装业务逻辑 2019年9月16日15:35:40=================================
        $order_sum_price=0;//TODO 订单各商品总价  2019年10月11日11:25:06
        $temp_result = array();//TODO 临时数组
        $temp_active_result = array();//TODO 临时数组
        $full_cut_list=[];
        $full_reduction=0;//满减金额
        $discount=0;//折扣金额
        $goods_id_list = json_decode($discount_activities->goods_id_list);//解码json串
        foreach($mchList as $mch_item) {
            foreach ($mch_item['goods_list'] as $i => $goods_item) {
                $id=$goods_item['goods_id'];
                //TODO 判断该商品是否参加满减活动  并记录参加商品的单价  2019年9月16日15:12:50
                if (in_array($id, $goods_id_list)) {
                    $full_reduction+=$goods_item['price'];//叠加满减金额 2019年9月16日15:11:24
                }
                if (count($goods_id_list) == 0) {
                    $full_reduction += $goods_item['price'];//叠加满减金额 2019年9月16日15:11:24
                }

                \Yii::warning('================测试命中====================='.$id,'info');
            }

            //TODO 将商品各规格商品总价统计输出 判断是否满足满减活动要求  2019年10月11日11:26:07
//            foreach ($mch_item['goods_off_sub_list'] as $i => $goods_off_item) {
//                $key = $goods_off_item['goods_id'];
//                if(!isset($temp_result[$key])){
//                    $temp_result[$key] = $goods_off_item;
//
//                }else{
//                    $temp_result[$key]['price'] += $goods_off_item['price'];
//                }
//            }

            //TODO 筛选出正在进行的满减活动 2019年10月11日15:03:53
            $discount_activities_list = DiscountActivities::find()->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'is_join' => 2,
            ])->andWhere(['>', 'end_time', time()])->andWhere(['<', 'begin_time', time()])->all();

            //双循环、计算出参与满减活动的单商品总价
            foreach ($discount_activities_list as $d_item) {
                $goods_id_key_list = json_decode($d_item['goods_id_list']);//解码json串
                foreach ($mch_item['goods_off_sub_list'] as $i => $goods_off_item) {
                    $key = $goods_off_item['goods_id'];
                    if (in_array($key, $goods_id_key_list)) {
                        if (!isset($temp_result[$key]))
                        {
                            $temp_result[$key] = $goods_off_item;
                        } else {
                            $temp_result[$key]['price'] += $goods_off_item['price'];
                        }
                    }
                }
                \Yii::warning($d_item['goods_id_list'] . '==========goods_id_list==========', 'info');
            }


            //该循环是用于找出符合满减推荐 2019年10月12日14:28:56
            foreach ($temp_result as $i => $goods_result) {
                foreach ($discount_activities_list as $d_item) {
                    $goods_id_key_list = json_decode($d_item['goods_id_list']);//解码json串
                    $key = $goods_result['goods_id'];
                    $active_id = $d_item['id'];
                    if (in_array($key, $goods_id_key_list)) {
                        if (!isset($temp_active_result[$active_id])) {
                            $temp_active_result[$active_id] = $d_item;
                        } else {
                            // $temp_result[$key]['price'] += $goods_off_item['price'];
                        }
                        \Yii::warning($goods_result['goods_id'] . '==========$temp_active_result$temp_active_result==========', 'info');
                    }
                }
            }
        }


        //TODO 满减折扣归集  2019年10月8日09:41:38
        if($full_reduction <= floatval($discount_activities->min_price1))
        {
            $discount = 0;
            $obj = (object)array('min_price' =>floatval( $discount_activities->min_price1), 'sub_price' => floatval( $discount_activities->sub_price1));
            $full_cut_list = [$obj];
        }
        //如果没设置满减商品表示全场通用
        if ($full_reduction >= floatval($discount_activities->min_price1)&&floatval($discount_activities->min_price1)>0) {
            $discount = $discount_activities->sub_price1;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price1), 'sub_price' => floatval($discount_activities->sub_price1));
//            $obj_make = (object)array('min_price' => floatval($discount_activities->min_price2), 'sub_price' => floatval($discount_activities->sub_price2));
            $full_cut_list = [$obj];
           // $full_cut_list = [$obj, $obj_make];
        }
        if ($full_reduction >= floatval($discount_activities->min_price2)&&floatval($discount_activities->min_price2)>0) {
            $discount = $discount_activities->sub_price2;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price2), 'sub_price' => floatval($discount_activities->sub_price2));
//            $obj_make = (object)array('min_price' => floatval($discount_activities->min_price3), 'sub_price' => floatval($discount_activities->sub_price3));
//            $full_cut_list = [$obj, $obj_make];
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price3)&&floatval($discount_activities->min_price3)>0) {
            $discount = $discount_activities->sub_price3;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price3), 'sub_price' => floatval($discount_activities->sub_price3));
//            $obj_make = (object)array('min_price' => floatval($discount_activities->min_price4), 'sub_price' => floatval($discount_activities->sub_price4));
//            $full_cut_list = [$obj, $obj_make];
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price4)&&floatval($discount_activities->min_price4)>0) {
            $discount = $discount_activities->sub_price4;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price4), 'sub_price' => floatval($discount_activities->sub_price4));
//            $obj_make = (object)array('min_price' => floatval($discount_activities->min_price5), 'sub_price' => floatval($discount_activities->sub_price5));
//            $full_cut_list = [$obj, $obj_make];
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price5)&&floatval($discount_activities->min_price5)>0) {
            $discount = $discount_activities->sub_price5;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price5), 'sub_price' => floatval($discount_activities->sub_price5));
//            $full_cut_list = [$obj];
            $full_cut_list = [$obj];
        }

        if(time() < $discount_activities->begin_time || time() > $discount_activities->end_time){
            $discount_activities=[];
            $full_cut_list=[];
            //return new ApiResponse(1, '满减活动暂未开始', []);
        }
        //处理无满减情况
        if($full_reduction==0){
            $discount_activities=[];
            $full_cut_list=[];
            //return new ApiResponse(1, '满减活动暂未开始', []);
        }
        //=========================================================
            return [
            'code' => 0,
            'msg' => 'OK',
            'data' => [
                'pay_type_list' => $this->getPayTypeList(),
                'address' => $this->address,
                'level' => $this->getLevelData(),
                'mch_list' => $mchList,
                'coupon_list' => $new_coupon_list,//优惠券改造 2019年8月28日11:35:55
                'balances'=>$user->money,//TODO 时时返回账户余额
                'integral'=>$this->integral,
                'order_total_price'=>$order_total_price,
                'goods_card_list' => $this->goodsCardList(),
                'discount_activities_list'=>$discount_activities,
                'full_reduction'=>$full_reduction,
                'discount'=>$discount,
                'full_cut_list'=>$full_cut_list,
                'active_goods_list'=>$temp_result , //满足活动商品
                'active_list'=> $temp_active_result  //满足活动商品
            ],
        ];
    }
}