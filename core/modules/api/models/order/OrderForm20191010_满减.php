<?php
/**
 * @copyright ©2018 Lu Wei
 * @author Lu Wei
 * @link http://www.luweiss.com/
 * Created by IntelliJ IDEA
 * Date Time: 2018/7/26 15:17
 */


namespace app\modules\api\models\order;


use app\models\Address;
use app\models\Attr;
use app\models\AttrGroup;
use app\models\BargainOrder;
use app\models\BargainUserOrder;
use app\models\Cart;
use app\models\Cat;
use app\models\common\CommonGoods;
use app\models\Coupon;
use app\models\CouponAutoSend;
use app\models\DiscountActivities;
use app\models\Form;
use app\models\FreeDeliveryRules;
use app\models\Goods;
use app\models\GoodsCat;
use app\models\Level;
use app\models\Mch;
use app\models\MchOption;
use app\models\Option;
use app\models\Order;
use app\models\PostageRules;
use app\models\Shop;
use app\models\Store;
use app\models\TerritorialLimitation;
use app\models\User;
use app\models\UserCoupon;
use app\modules\api\models\ApiModel;

class OrderForm extends ApiModel
{

    public $mch_list;
    public $address_id;
    public $use_coupon_id;
    public $use_sub_price;
    public $longitude;
    public $latitude;

    public $store_id;
    public $store;
    public $user_id;
    /** @var User $user */
    protected $user;
    protected $address;
    protected $level;
    protected $integral;


    public function rules()
    {
        $rules = [
            ['mch_list', 'required'],
            ['address_id', 'integer'],
            ['use_coupon_id', 'integer'],
            ['use_sub_price', 'integer'],
            ['mch_list', function ($attr, $params) {
                $data = \Yii::$app->serializer->decode($this->mch_list);
                if (!$data) {
                    $this->addError($attr, "{$attr}数据格式错误。");
                }
                $this->mch_list = $data;
            }],
            ['mch_list', function ($attr, $params) {
                foreach ($this->mch_list as $i => &$mch) {
                    if (!is_array($mch['goods_list'])) {
                        $this->addError($attr, "{$attr}[{$i}]['goods_list']必须是一个数组。");
                        return;
                    }
                }
            }],
            [['longitude', 'latitude'], 'trim']
        ];

        return $rules;
    }

    public function afterValidate()
    {
        $this->user = User::findOne($this->user_id);
        $this->level = $this->getLevelData();
        $this->address = $this->getAddressData();
        $this->integral = [
            'forehead' => 0,
            'forehead_integral' => 0,
            'integration' => $this->store->integration
        ];
        parent::afterValidate();
    }

    //TODO 获取店铺优惠券List 2019年8月28日11:41:56
    protected function getMchCouponListData($submit = false)
    {
        foreach ($this->mch_list as $i => &$mch) {
            $this->getCouponList($mch);
            $this->getGoodsList($mch['goods_list']);
            if (empty($mch['goods_list'])) {
                throw new \Exception('商品不存在', 1);
            }
        }
    }


    protected function getMchListData($submit = false)
    {
        \Yii::warning($this->use_coupon_id.'==接收外部传递优惠券=='.$this->use_sub_price,'info');
        foreach ($this->mch_list as $i => &$mch) {
            if ($mch['mch_id'] == 0) {
                $mch['name'] = '平台自营';
                if ($submit == false) {
                    $mch['form'] = $this->getFormData();

                    // 获取上次提交的自提订单
                    $lastOffline = Order::find()->select(['name', 'mobile'])
                        ->where(['mch_id' => $mch['mch_id'], 'is_offline' => 1, 'user_id' => $this->user->id, 'store_id' => $this->store_id])->orderBy(['id' => SORT_DESC])->one();
                    $mch['offline_name'] = $lastOffline['name'];
                    $mch['offline_mobile'] = $lastOffline['mobile'];
                } else {
                    $mch['form'] = $this->getForm($mch['form']);
                }
                $mch['send_type'] = $this->store->send_type;
                if ($this->store->send_type != 1) {
                    $shopArr = $this->getShopList();
                    $mch['is_shop'] = $shopArr['shop'];
                    $mch['shop_list'] = $shopArr['list'];
                } else {
                    $mch['shop_list'] = [];
                    $mch['is_shop'] = '';
                }
            } else {
                $_mch = Mch::findOne([
                    'store_id' => $this->store_id,
                    'id' => $mch['mch_id'],
                ]);
                if (!$_mch) {
                    unset($this->mch_list[$i]);
                    continue;
                }
                $mch['name'] = $_mch->name;
                $mch['form'] = null;
            }

            $this->getGoodsList($mch['goods_list']);
            if (empty($mch['goods_list'])) {
                throw new \Exception('商品不存在', 1);
            }
            $total_price = 0;
            $level_price = 0;
            $integral = [
                'forehead' => 0,
                'forehead_integral' => 0
            ];
            $mch['plugin_type'] = 0;
            $max_price = 0;//默认值
            foreach ($mch['goods_list'] as $_goods) {
                $total_price += doubleval($_goods['price']);
                $level_price += doubleval($_goods['level_price']) > 0 ? doubleval($_goods['level_price']) : doubleval($_goods['price']);
                $integral['forehead'] += doubleval($_goods['resIntegral']['forehead']);
                $integral['forehead_integral'] += doubleval($_goods['resIntegral']['forehead_integral']);
                if (isset($_goods['bargain_order_id'])) {
                    $mch['plugin_type'] = 2;
                }
            }

            $mch['total_price'] = sprintf('%.2f', $total_price);
            $mch['level_price'] = sprintf('%.2f', $level_price);
            $mch['integral'] = $integral;
            $this->getCouponList($mch);
            $mch['express_price'] = $this->getExpressPrice($mch);



            $mch['offer_rule'] = $this->getOfferRule($mch);
            $mch['is_area'] = $this->getTerritorialLimitation($mch);
            $max_price+=$mch['total_price'] ;//TODO 累计订单金额1  2019年8月28日15:45:03

//============================================================================================================================
//            TODO 进行不同规格价格计算、找出符合满减价格的梯度  2019年10月10日09:17:49
            //计算单商品不同规格总价
            $new_goods_list = array();
//            $mch['goods_off_list_test']= $this->object_array($mch['goods_list']);
            foreach($mch['goods_list'] as $_goods) {
//                if(isset($new_goods_list[$_goods['goods_id']])) $new_goods_list[$_goods['goods_id']]['price'] += $_goods['price'];
//                else $new_goods_list[$_goods['goods_id']] = $_goods;
                $new_goods_list[$_goods['goods_id']]['price'] += $_goods['price'];
                $new_goods_list[$_goods['goods_id']]['goods_id'] = $_goods['goods_id'];
            }
            $new_goods_list = array_values($new_goods_list);
            $new_goods_off_list=[];
            foreach ($new_goods_list as $new_goods) {
                $new_goods=$this->getGoodsOffList($new_goods['goods_id'], $new_goods['price']);
                array_push($new_goods_off_list,$new_goods);

            }


            $mch['goods_off_list'] = $new_goods_list;
            $mch['new_goods_off_list'] = $new_goods_off_list;
//============================================================================================================================
        }
        $mch['order_total_price'] = $max_price;

        return $this->mch_list;
    }



    /*
  * 数组转成对象
  * */
    function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        } if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }
    protected function getGoodsList(&$goods_list)
    {
        $goodsIds = [];
        foreach ($goods_list as $i => &$item) {
            if ($item['cart_id']) {
                $cart = Cart::findOne([
                    'store_id' => $this->store_id,
                    'id' => $item['cart_id'],
                    'is_delete' => 0
                ]);
                if (!$cart) {
                    unset($goods_list[$i]);
                    continue;
                }
                $item['num'] = $cart->num;
                $attr_id_list = (array)\Yii::$app->serializer->decode($cart->attr);
                $goods = Goods::findOne($cart->goods_id);
            } elseif ($item['bargain_order_id']) {
                $bargainOrder = BargainOrder::findOne(['id' => $item['bargain_order_id'], 'status' => 0]);
                if (!$bargainOrder) {
                    throw new \Exception('该砍价已购买或失败', 1);
                }
                $attr_id_list = [];
                $attr = \Yii::$app->serializer->decode($bargainOrder->attr);
                foreach ($attr as $_a) {
                    array_push($attr_id_list, $_a['attr_id']);
                }
                /* @var \app\models\Goods $goods */
                $goods = $bargainOrder->goods;
                $item['num'] = 1;
                $item['attr'] = $attr;
                $bargainPrice = BargainUserOrder::getPriceCount($this->store->id, $bargainOrder->id);
                $price = sprintf('%.2f', $bargainOrder->original_price - $bargainPrice);
                $item['bargain_price'] = $price < $bargainOrder->min_price ? $bargainOrder->min_price : $price;
            } elseif ($item['goods_id']) {
                $attr_id_list = [];
                foreach ($item['attr'] as $_a) {
                    array_push($attr_id_list, $_a['attr_id']);
                }
                $goods = Goods::findOne([
                    'store_id' => $this->store_id,
                    'id' => $item['goods_id'],
                ]);
            } else {
                unset($goods_list[$i]);
                continue;
            }
            if (!$goods) {
                unset($goods_list[$i]);
                continue;
            }
            if (($goods->confine_count && $goods->confine_count > 0)) {
                $goodsNum = Goods::getBuyNum($this->user, $goods->id);
                if ($goodsNum) {

                } else {
                    $goodsNum = 0;
                }
                $goodsTotalNum = intval($goodsNum + $item['num']);
                if ($goodsTotalNum > $goods->confine_count) {
                    throw new \Exception('商品：' . $goods->name . ' 超出购买数量', 1);
                }
            }
            $attr_info = $goods->getAttrInfo($attr_id_list);
            if (($goods->type != 2 && $item['num'] > $attr_info['num']) || $item['num'] <= 0) { //库存不足
                unset($goods_list[$i]);
                continue;
            }
            $attr_list = Attr::find()->alias('a')
                ->select('ag.id AS attr_group_id,ag.attr_group_name,a.id AS attr_id,a.attr_name')
                ->leftJoin(['ag' => AttrGroup::tableName()], 'a.attr_group_id=ag.id')
                ->where(['a.id' => $attr_id_list, 'ag.store_id' => $this->store_id,])
                ->asArray()->all();
            $item['attr_list'] = $attr_list;
            $item['goods_id'] = $goods->id;
            $item['mch_id'] = $goods->mch_id;
            $item['goods_name'] = $goods->name;
            $item['goods_pic'] = $goods->cover_pic;
            // bargain_price 砍价
            if (isset($item['bargain_price'])) {
                $item['price'] = sprintf('%.2f', ($item['bargain_price'] * $item['num']));
                $item['single_price'] = sprintf('%.2f', $item['bargain_price']);
            } else {
                $item['price'] = sprintf('%.2f', ($attr_info['price'] * $item['num']));
                $item['single_price'] = sprintf('%.2f', $attr_info['price']);
            }
            $item['weight'] = $goods->weight;
            $item['integral'] = $goods->integral ? $goods->integral : 0;
            $item['freight'] = $goods->freight;
            $item['full_cut'] = $goods->full_cut;
            $item['goods_cat_id'] = $goods->cat_id;
            $item['id'] = $goods->id;

            // 当前选择的规格
            $attrIdArr = [];
            foreach ($item['attr_list'] as $attrListItem) {
                $attrIdArr[] = $attrListItem['attr_id'];
            }

            $res = CommonGoods::currentGoodsAttr([
                'attr' => $goods['attr'],
                'price' => $goods['price'],
                'is_level' => $goods['is_level'],
                'mch_id' => $goods['mch_id'],
            ], $attrIdArr);

//            if ($goods->is_level == 1 && $this->level && $this->level['discount'] < 10 && $goods->mch_id == 0) {
//
//                // 当前选择的规格
//                $attrIdArr = [];
//                foreach ($item['attr_list'] as $attrListItem) {
//                    $attrIdArr[] = $attrListItem['attr_id'];
//                }
//
//                $res = CommonGoods::currentGoodsAttr([
//                    'attr' => $goods['attr'],
//                    'price' => $goods['price'],
//                    'is_level' => $goods['is_level'],
//                ], $attrIdArr);


//                if ($res['is_member_price'] === true) {
//                    $item['level_price'] = $res['price'];
//                } else {
//                    $item['level_price'] = sprintf('%.2f', ($item['price'] * floatval($this->level['discount']) / 10));
//                }


                $item['level_price'] = sprintf('%.2f', ($res['level_price'] * $item['num']));
//                if ($res['level_price'] > 0 && $res['level_price'] < 0.01) {
//                    $item['level_price'] = sprintf('%.2f', 0.01);
//                }
                $item['is_level'] = $res['is_level'];

            // 砍价不享受会员折扣
            if(isset($item['bargain_price'])) {
                $item['level_price'] = $item['price'];
                $item['is_level'] = 0;
            }
//            } else {
//                $item['level_price'] = sprintf('%.2f', ($item['single_price'] * $item['num']));
//                $item['is_level'] = 0;
//            }

            $integralArr = $this->getIntegral((object)$item, $this->store->integral, $goodsIds);
            $item['give'] = $integralArr['give'];
            $item['resIntegral'] = $integralArr['resIntegral'];
            $goodsIds[] = $goods->id;
            $item['goods_card_list'] = Goods::getGoodsCard($goods->id);





//           =================================满减多活动逻辑输出===============================================
//            //TODO 处理满减活动存在时  提示选择满减活动的问题 2019年9月11日16:59:32
            $discount_activities = DiscountActivities::findOne([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'is_join' => 2,
            ]);
//
//            $full_reduction=0;//满减金额
//            $discount=0;//折扣金额
            $goods_id_list = json_decode($discount_activities->goods_id_list);//解码json串
//            //TODO 判断该商品是否参加满减活动  并记录参加商品的单价  2019年9月16日15:12:50
            if (in_array($goods->id, $goods_id_list)) {
                $item['activity_off_list'] =0;
            }

            $item['goods_off_list'] =$this->getGoodsOffList($goods->id,$item['price']);
            $item['activity_off_list'] =$this->getGoodsOffList($goods->id,$item['price']);
//           ===================================End============================================
        }
    }


    //计算满减剃度  2019年10月9日10:55:48
    protected function getGoodsOffList($goods_id,$price)
    {
        //TODO 处理满减活动存在时  提示选择满减活动的问题 2019年9月11日16:59:32
        $discount_activities = DiscountActivities::findOne([
            'store_id' => $this->store_id,
            'is_delete' => 0,
            'is_join' => 2,
        ]);

        $discount_activities_list = DiscountActivities::find()->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'is_join' => 2,
            ])->all();





        $full_reduction=0;//满减金额
        $discount=0;//折扣金额
        $full_cut_list=[];
       $goods_id_list = json_decode($discount_activities->goods_id_list);//解码json串
        //TODO 判断该商品是否参加满减活动  并记录参加商品的单价  2019年9月16日15:12:50
        if (in_array($goods_id, $goods_id_list)) {
            $full_reduction+=$price;//叠加满减金额 2019年9月16日15:11:24
//            \Yii::warning($goods_id.'=============测试数据AAAAAAAAAAAAAAAAAAA================','info');
        }
        else{
//            \Yii::warning($goods_id.'=============没有进来AAAAAAAAAAAAAAAAAA================','info');
            return '' ;
        }


        $activitiesArr = [];
        foreach ( $discount_activities_list as $discount_activities_v  )
        {
            $goods_id_list_v = json_decode($discount_activities_v['goods_id_list']);//解码json串
            if (in_array($goods_id, $goods_id_list_v)) {

                \Yii::warning($goods_id.'=============测试数据BBBBBBBBBBBBBBBBBBBB================','info');
            }
            else{
                \Yii::warning($goods_id.'=============没有进来BBBBBBBBBBBBBBBBBB================','info');
                return '' ;
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
            $full_cut_list = [$obj];

        }
        if ($full_reduction >= floatval($discount_activities->min_price2)&&floatval($discount_activities->min_price2)>0) {
            $discount = $discount_activities->sub_price2;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price2), 'sub_price' => floatval($discount_activities->sub_price2));
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price3)&&floatval($discount_activities->min_price3)>0) {
            $discount = $discount_activities->sub_price3;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price3), 'sub_price' => floatval($discount_activities->sub_price3));
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price4)&&floatval($discount_activities->min_price4)>0) {
            $discount = $discount_activities->sub_price4;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price4), 'sub_price' => floatval($discount_activities->sub_price4));
            $full_cut_list = [$obj];
        }
        if ($full_reduction >= floatval($discount_activities->min_price5)&&floatval($discount_activities->min_price5)>0) {
            $discount = $discount_activities->sub_price5;
            $obj = (object)array('min_price' => floatval($discount_activities->min_price5), 'sub_price' => floatval($discount_activities->sub_price5));
            $full_cut_list = [$obj];
        }

        if(time() < $discount_activities->begin_time || time() > $discount_activities->end_time){
            $discount_activities=[];
            $full_cut_list=[];
            //return new ApiResponse(1, '满减活动暂未开始', []);
        }

        return $full_cut_list;
    }


    //自定义表单
    protected function getFormData()
    {
        $new_list = [];
        $new_list['is_form'] = Option::get('is_form', $this->store_id, 'admin', 0);
        $form_list = [];
        if ($new_list['is_form'] == 1) {
            $new_list['name'] = Option::get('form_name', $this->store_id, 'admin', '表单信息');
            $form_list = Form::find()->where([
                'store_id' => $this->store_id, 'is_delete' => 0,
            ])->orderBy(['sort' => SORT_ASC])->asArray()->all();
            foreach ($form_list as $index => $value) {
                if (in_array($value['type'], ['radio', 'checkbox'])) {
                    $default = str_replace("，", ",", $value['default']);
                    $list = explode(',', $default);
                    $default_list = [];
                    foreach ($list as $k => $v) {
                        $default_list[$k]['name'] = $v;
                        if ($k == 0) {
                            $default_list[$k]['is_selected'] = 1;
                        } else {
                            $default_list[$k]['is_selected'] = 0;
                        }
                    }
                    $form_list[$index]['default_list'] = $default_list;
                }
            }
        }
        $new_list['list'] = $form_list;
        return $new_list;
    }

    protected function getAddress()
    {
        if (!$this->address) {
            if ($this->address_id) {
                $this->address = Address::findOne(['id' => $this->address_id, 'user_id' => $this->user_id]);
            } else {
                $this->address = Address::find()->where([
                    'user_id' => $this->user_id,
                    'is_default' => 1,
                    'is_delete' => 0,
                ])->limit(1)->one();
            }
        }
        return $this->address;
    }

    //获取收货地址，有address_id优先获取，没有则获取默认地址
    protected function getAddressData()
    {
        $address = $this->getAddress();
        if ($address) {
            return [
                'id' => $address->id,
                'name' => $address->name,
                'mobile' => $address->mobile,
                'province_id' => $address->province_id,
                'province' => $address->province,
                'city_id' => $address->city_id,
                'city' => $address->city,
                'district_id' => $address->district_id,
                'district' => $address->district,
                'detail' => $address->detail,
                'is_default' => $address->is_default,
            ];
        } else {
            return null;
        }
    }

    //获取支付方式
    protected function getPayTypeList()
    {
        $pay_type_list_json = Option::get('payment', $this->store_id, 'admin', '{"wechat":"1"}');
        $pay_type_list = \Yii::$app->serializer->decode($pay_type_list_json);
        if (!(is_array($pay_type_list) || $pay_type_list instanceof \ArrayObject)) {
            return [];
        }
        $new_list = [];
        $ok = true;
        foreach ($this->mch_list as $mch) {
            if ($mch['mch_id'] == 0) {
                continue;
            } else {
                $ok = false;
                break;
            }
        }
        foreach ($pay_type_list as $index => $value) {
            if ($index == 'wechat' && $value == 1) {
                $new_list[] = [
                    'name' => '线上支付',
                    'payment' => 0,
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/recharge/icon-online.png'
                ];
            }
            if ($index == 'huodao' && $value == 1 && $ok) {
                $new_list[] = [
                    'name' => '货到付款',
                    'payment' => 2,
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/recharge/icon-huodao.png'
                ];
            }
            if ($index == 'balance' && $value == 1) {
                $balance = Option::get('re_setting', $this->store_id, 'app');
                $balance = json_decode($balance, true);
                if ($balance && $balance['status'] == 1) {
                    $new_list[] = [
                        'name' => '账户余额支付',
                        'payment' => 3,
                        'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/recharge/icon-balance.png'
                    ];
                }
            }
        }
        return $new_list;
    }

    protected function getCouponList(&$mch)
    {
        \Yii::warning('测试是否进入订单确认'.$mch['mch_id'],'info');
//        if ($mch['mch_id'] != 0) {
//            $mch['coupon_list'] = [];
//            return;
//        }
        $goods_total_price = $mch['total_price'];
        $cat_ids = $this->getCatIdList($mch['goods_list']);
        $coupon_goods_id = $this->getGoodsIdList($mch['goods_list']);
        $max_price = 0;
        foreach ($mch['goods_list'] as $v) {
            $max_price += $v['price'];
        }

        \Yii::warning($goods_total_price.'====最大金额==='.$max_price,'info');
        $list = UserCoupon::find()->alias('uc')
            ->leftJoin(['c' => Coupon::tableName()], 'uc.coupon_id=c.id')
            ->leftJoin(['cas' => CouponAutoSend::tableName()], 'uc.coupon_auto_send_id=cas.id')
            ->where([
                'AND',
                ['uc.is_delete' => 0],
                ['uc.is_use' => 0],
                ['uc.is_expire' => 0],
                ['uc.user_id' => $this->user_id],
               // ['<=', 'c.min_price', $goods_total_price],//TODO 将优惠券金额调整成总订单金额过滤、不考虑多店铺 2019年8月28日11:32:29
               // ['<=', 'c.min_price', $goods_total_price],
            ])
            ->select('c.name as coupon_name ,uc.id user_coupon_id,c.sub_price,c.min_price,cas.event,uc.begin_time,uc.end_time,uc.type,c.appoint_type,c.cat_id_list,c.goods_id_list')->orderBy('c.sub_price DESC,uc.end_time ASC')
            ->asArray()->all();
        $events = [
            0 => '平台发放',
            1 => '分享红包',
            2 => '购物返券',
            3 => '领券中心',
        ];


        \Yii::warning('===总订单金额==='.$max_price,'info');
        $new_list = [];
        foreach ($list as $i => $item) {
            if ($item['begin_time'] > (strtotime(date('Y-M-d')) + 86400) || $item['end_time'] < time()) {
                continue;
            }
            $list[$i]['status'] = 0;
            if ($item['is_use']) {
                $list[$i]['status'] = 1;
            }

            if ($item['is_expire']) {
                $list[$i]['status'] = 2;
            }

            $list[$i]['min_price_desc'] = $item['min_price'] == 0 ? '无门槛' : '满' . $item['min_price'] . '元可用';
            $list[$i]['begin_time'] = date('Y.m.d H:i', $item['begin_time']);
            $list[$i]['end_time'] = date('Y.m.d H:i', $item['end_time']);
            if (!$item['event']) {
                if ($item['type'] == 2) {
                    $list[$i]['event'] = $item['event'] = 3;
                } else {
                    $list[$i]['event'] = $item['event'] = 0;
                }
            }
            $list[$i]['event_desc'] = $events[$item['event']];
            $list[$i]['min_price'] = doubleval($item['min_price']);
            $list[$i]['sub_price'] = doubleval($item['sub_price']);

            if ($list[$i]['appoint_type'] == 1) {
                $list[$i]['cat_id_list'] = json_decode($list[$i]['cat_id_list']);
                if ($list[$i]['cat_id_list'] != null) {
                    $current = array_intersect($list[$i]['cat_id_list'], $cat_ids);
                    if ($current) {
                        $goodsAdd = [];
                        $price = 0;
                        foreach ($current as $v) {
                            foreach ($mch['goods_list'] as $v2) {
                                if (in_array($v, $v2['cat_id']) && !in_array($v2['goods_id'], $goodsAdd)) {
                                    $price += $v2['price'];
                                    array_push($goodsAdd, $v2['goods_id']);
                                }
                            };
                        }

                        if ($price < $list[$i]['min_price']) {
                            unset($list[$i]);
                            continue;
                        }

                    } else {
                        unset($list[$i]);
                        continue;
                    }
                }
            } elseif ($list[$i]['appoint_type'] == 2) {
                $list[$i]['goods_id_list'] = json_decode($list[$i]['goods_id_list']);
                if ($list[$i]['goods_id_list'] != null) {
                    $current = array_intersect($list[$i]['goods_id_list'], $coupon_goods_id);
                    if ($current) {
                        $goodsAdd = [];
                        $price = 0;
                        foreach ($current as $v) {
                            foreach ($mch['goods_list'] as $v2) {
                                if ($v == $v2['goods_id'] && !in_array($v2['goods_id'], $goodsAdd)) {
                                    $price += $v2['price'];
                                }
                            }
                        }
                        if ($price < $list[$i]['min_price']) {
                            unset($list[$i]);
                            continue;
                        }
                    } else {
                        unset($list[$i]);
                        continue;
                    }
                }
            } else {
                if ($max_price < $list[$i]['min_price']) {
                    unset($list[$i]);
                    continue;
                }
            }

            $new_list[] = $list[$i];
        }
        $mch['coupon_list'] = $new_list;
    }

    protected function getCatIdList(&$goods_list)
    {
        $cat_id_list = [];
        foreach ($goods_list as &$goods) {
            if ($goods['goods_cat_id'] == 0) {
                $goods_cat_list = GoodsCat::find()
                    ->select('cat_id')->where([
                        'goods_id' => $goods['goods_id'],
                        'is_delete' => 0,
                    ])->all();
                foreach ($goods_cat_list as $goods_cat) {
                    $cat_id_list[] = $goods_cat->cat_id;
                    $goods['cat_id'][] = $goods_cat->cat_id;
                }
            } else {
                $cat_id_list[] = $goods['goods_cat_id'];
                $goods['cat_id'][] = $goods['goods_cat_id'];
            }
            $cat_parent_list = Cat::find()->select('parent_id')
                ->andWhere(['id' => $goods['cat_id'], 'store_id' => $this->store_id, 'is_delete' => 0])->andWhere(['>', 'parent_id', 0])
                ->column();
            $cat_id_list = array_merge($cat_parent_list, $cat_id_list);
            $goods['cat_id'] = array_merge($cat_parent_list, $goods['cat_id']);
        }
        unset($goods);
        return array_unique($cat_id_list);
    }

    protected function getGoodsIdList($goods_list)
    {
        $goods_id_list = [];
        foreach ($goods_list as $goods) {
            $goods_id_list[] = $goods['goods_id'];
        }
        return $goods_id_list;
    }

    //TODO 计算商品总价格 2019年8月28日14:29:36
    protected function getGoodsCouponList($order_total_price)
    {
        $cat_ids = $this->getCatIdList($this->mch_list['goods_list']);
        $coupon_goods_id = $this->getGoodsIdList($this->mch_list['goods_list']);
        $list = UserCoupon::find()->alias('uc')
            ->leftJoin(['c' => Coupon::tableName()], 'uc.coupon_id=c.id')
            ->leftJoin(['cas' => CouponAutoSend::tableName()], 'uc.coupon_auto_send_id=cas.id')
            ->where([
                'AND',
                ['uc.is_delete' => 0],
                ['uc.is_use' => 0],
                ['uc.is_expire' => 0],
                ['uc.user_id' => $this->user_id],
                ['<=', 'c.min_price', $order_total_price],//TODO 将优惠券金额调整成总订单金额过滤、不考虑多店铺 2019年8月28日11:32:29
            ])
            ->select('c.name as coupon_name ,uc.id user_coupon_id,c.sub_price,c.min_price,cas.event,uc.begin_time,uc.end_time,uc.type,c.appoint_type,c.cat_id_list,c.goods_id_list')->orderBy('c.sub_price DESC,uc.end_time ASC')
            ->asArray()->all();
        $events = [
            0 => '平台发放',
            1 => '分享红包',
            2 => '购物返券',
            3 => '领券中心',
        ];

        $new_list = [];
        foreach ($list as $i => $item) {
            if ($item['begin_time'] > (strtotime(date('Y-M-d')) + 86400) || $item['end_time'] < time()) {
                continue;
            }
            $list[$i]['status'] = 0;
            if ($item['is_use']) {
                $list[$i]['status'] = 1;
            }

            if ($item['is_expire']) {
                $list[$i]['status'] = 2;
            }

            $list[$i]['min_price_desc'] = $item['min_price'] == 0 ? '无门槛' : '满' . $item['min_price'] . '元可用';
            $list[$i]['begin_time'] = date('Y.m.d H:i', $item['begin_time']);
            $list[$i]['end_time'] = date('Y.m.d H:i', $item['end_time']);
            if (!$item['event']) {
                if ($item['type'] == 2) {
                    $list[$i]['event'] = $item['event'] = 3;
                } else {
                    $list[$i]['event'] = $item['event'] = 0;
                }
            }
            $list[$i]['event_desc'] = $events[$item['event']];
            $list[$i]['min_price'] = doubleval($item['min_price']);
            $list[$i]['sub_price'] = doubleval($item['sub_price']);

            if ($list[$i]['appoint_type'] == 1) {
                $list[$i]['cat_id_list'] = json_decode($list[$i]['cat_id_list']);
                if ($list[$i]['cat_id_list'] != null) {
                    $current = array_intersect($list[$i]['cat_id_list'], $cat_ids);
                    if ($current) {
                        $goodsAdd = [];
                        $price = 0;
                        foreach ($current as $v) {
                            foreach ($this->mch_list['goods_list'] as $v2) {
                                if (in_array($v, $v2['cat_id']) && !in_array($v2['goods_id'], $goodsAdd)) {
                                    $price += $v2['price'];
                                    array_push($goodsAdd, $v2['goods_id']);
                                }
                            };
                        }

                        if ($price < $list[$i]['min_price']) {
                            unset($list[$i]);
                            continue;
                        }

                    } else {
                        unset($list[$i]);
                        continue;
                    }
                }
            } elseif ($list[$i]['appoint_type'] == 2) {
                $list[$i]['goods_id_list'] = json_decode($list[$i]['goods_id_list']);
                if ($list[$i]['goods_id_list'] != null) {
                    $current = array_intersect($list[$i]['goods_id_list'], $coupon_goods_id);
                    if ($current) {
                        $goodsAdd = [];
                        $price = 0;
                        foreach ($current as $v) {
                            foreach ($this->mch_list['goods_list'] as $v2) {
                                if ($v == $v2['goods_id'] && !in_array($v2['goods_id'], $goodsAdd)) {
                                    $price += $v2['price'];
                                }
                            }
                        }
                        if ($price < $list[$i]['min_price']) {
                            unset($list[$i]);
                            continue;
                        }
                    } else {
                        unset($list[$i]);
                        continue;
                    }
                }
            } else {
                if ($order_total_price < $list[$i]['min_price']) {
                    unset($list[$i]);
                    continue;
                }
            }

            $new_list[] = $list[$i];
        }
        $mch['coupon_list'] = $new_list;
    }

    protected function getLevelData()
    {
        $level = Level::find()->select([
            'name', 'level', 'discount',
        ])->where(['level' => $this->user->level, 'store_id' => $this->store_id, 'is_delete' => 0])
            ->asArray()->one();
        return $level;
    }

    //积分计算

    /**
     * @param $goods_item object 重新编写的goods_item
     * @param $store_integral int 商城设置的积分规则
     * @param $goods_id array 已设置积分的商品id数组
     * @return array
     */
    protected function getIntegral($goods_item, $store_integral, $goods_id = array())
    {
        $integral = json_decode($goods_item->integral, true);
        $resIntegral = [
            'forehead' => 0,
            'forehead_integral' => 0,
        ];
        if ($integral) {
            //赠送积分计算
            $give = $integral['give'];
            if (strpos($give, '%') !== false) {
                // 百分比
                $give = trim($give, '%');
                $goods_item->give = (int)($goods_item->price * ($give / 100));
            } else {
                // 固定积分
                $goods_item->give = (int)($give * $goods_item->num);
            }
            //抵扣积分计算
            $forehead = $integral['forehead'];
            if ($forehead) {
                if (strpos($forehead, '%') !== false) {//百分比积分抵扣计算
//                $forehead = (int)trim($forehead, '%');
                    if ($forehead >= 100) {
                        $forehead = 100;
                    }
                    if ($integral['more'] == '1') {//多件累计计算
                        $resIntegral['forehead_integral'] = (int)(($forehead / 100) * $goods_item->price * $store_integral);
                    } else {
                        if (!in_array($goods_item->id, $goods_id)) { //不允许多件累计   同id商品值计算一次积分抵扣
                            $resIntegral['forehead_integral'] = (int)(($forehead / 100) * $goods_item->single_price * $store_integral);
                        }
                    }
                } else {
//                $forehead = (int)$forehead;
                    if ($integral['more'] == '1') {
                        $resIntegral['forehead_integral'] = (int)($store_integral * $goods_item->price);
                        if ($goods_item->price > ($forehead * $goods_item->num)) {
                            $resIntegral['forehead_integral'] = (int)($forehead * $goods_item->num * $store_integral);
                        }
                    } else {
                        if (!in_array($goods_item->id, $goods_id)) {
                            $goodsPrice = $goods_item->single_price;
                            $resIntegral['forehead_integral'] = (int)($store_integral * $goodsPrice);
                            if ($goodsPrice > $forehead) {
                                $resIntegral['forehead_integral'] = (int)($forehead * $store_integral);
                            }
                        }
                    }
                }
            }
            if ($this->integral['forehead_integral'] < $this->user->integral) {
                $resetIntegral = $this->user->integral - $this->integral['forehead_integral'];
                $resIntegral['forehead_integral'] = $resIntegral['forehead_integral'] >= $resetIntegral ? $resetIntegral : $resIntegral['forehead_integral'];
                $resIntegral['forehead'] = sprintf("%.2f", ($resIntegral['forehead_integral'] / $store_integral));
                $this->integral['forehead_integral'] += $resIntegral['forehead_integral'];
                $this->integral['forehead'] += $resIntegral['forehead'];
            } else {
                $resIntegral['forehead_integral'] = 0;
                $resIntegral['forehead'] = 0;
            }
        }


        return [
            'resIntegral' => $resIntegral,
            'give' => $goods_item->give
        ];
    }

    protected function getExpressPrice($mch)
    {
        $expressPrice = 0;
        if ($this->address) {
            $address = $this->address;
            //先计算单品满件包邮和满额包邮
            $resGoodsList = Goods::cutFull($mch['goods_list']);
            //再通过运费规则计算运费
            $expressPrice = PostageRules::getExpressPriceMore($this->store_id, $address['city_id'], $resGoodsList, $address['province_id']);
        }
        $expressPrice = $this->getFreeDeliveryRules($mch, $expressPrice);
        return $expressPrice >= 0 ? $expressPrice : 0;
    }

    // 获取门店列表
    protected function getShopList()
    {
        $list = Shop::find()->select(['address', 'mobile', 'id', 'name', 'longitude', 'latitude', 'is_default'])
            ->where(['store_id' => $this->store_id, 'is_delete' => 0])->asArray()->all();
        $distance = array();
        $shop = null;
        foreach ($list as $index => $item) {
            $list[$index]['distance'] = -1;
            if ($item['longitude'] && $this->longitude) {
                $from = [$this->longitude, $this->latitude];
                $to = [$item['longitude'], $item['latitude']];
                $list[$index]['distance'] = $this->get_distance($from, $to, false, 2);
            }
            $distance[] = $list[$index]['distance'];
            if ($item['is_default'] == 1) {
                $shop = $item;
            }
        }
        array_multisort($distance, SORT_ASC, $list);
        $min = min(count($list), 30);
        $list_arr = array();
        foreach ($list as $index => $item) {
            if ($index <= $min) {
                $list[$index]['distance'] = $this->distance($item['distance']);
                array_push($list_arr, $list[$index]);
            }
        }
        return [
            'list' => $list,
            'shop' => $shop
        ];
    }

    private static function distance($distance)
    {
        if ($distance == -1) {
            return -1;
        }
        if ($distance > 1000) {
            $distance = round($distance / 1000, 2) . 'km';
        } else {
            $distance .= 'm';
        }
        return $distance;
    }

    /**
     * 根据起点坐标和终点坐标测距离
     * @param  [array]   $from  [起点坐标(经纬度),例如:array(118.012951,36.810024)]
     * @param  [array]   $to    [终点坐标(经纬度)]
     * @param  [bool]    $km        是否以公里为单位 false:米 true:公里(千米)
     * @param  [int]     $decimal   精度 保留小数位数
     * @return [string]  距离数值
     */
    public function get_distance($from, $to, $km = true, $decimal = 2)
    {
        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996; // 地球半径系数

        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;

        if ($km) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }


    // 获取起送规则
    protected function getOfferRule($mch)
    {
        $res = [
            'is_allowed' => 0,
            'total_price' => 0,
            'msg' => ''
        ];
        if ($mch['mch_id'] > 0) {
            $res['msg'] = '商户不支持起送规则';
            return $res;
        }
        if ($mch['plugin_type'] == 2) {
            $res['msg'] = '砍价不支持起送规则';
            return $res;
        }
        if (!$this->address) {
            $res['msg'] = '请选择收货地址';
            return $res;
        }
        $offerRule = Option::get('offer-price', $this->store_id, 'admin');
        if (!$offerRule) {
            $res['msg'] = '起送规则不存在';
            return $res;
        }
        if ($offerRule->is_enable == 0) {
            $res['msg'] = '起送规则未开启';
            return $res;
        }

        $ruleList = $offerRule->rule_list;

        $res['total_price'] = $offerRule->total_price;
        if (is_array($ruleList)) {
            foreach ($ruleList as $value) {
                foreach ($value['province_list'] as $item) {
                    if ($item['id'] == $this->address['city_id']) {
                        $res['total_price'] = $value['price'];
                    }
                }
            }
        }

        if ($mch['total_price'] >= $res['total_price']) {
            $res['is_allowed'] = 0;
        } else {
            $res['is_allowed'] = 1;
        }
        $value = round($res['total_price'] - $mch['total_price'], 2);
        $res['msg'] = "自营商品，还差{$value}元起送";

        return $res;
    }

    protected function getTerritorialLimitation($mch)
    {
        $isArea = 0;
        if ($mch['mch_id'] > 0) {
            return $isArea;
        }
        if ($this->address) {
            $area = TerritorialLimitation::findOne([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'is_enable' => 1,
            ]);
            if ($area) {
                $city_id = []; //限制的地区ID
                $detail = json_decode($area->detail);
                if (!is_array($detail)) {
                    $detail = [];
                }
                foreach ($detail as $key => $value) {
                    foreach ($value->province_list as $key2 => $value2) {
                        $city_id[] = $value2->id;
                    }
                }
                $addressArr = [
                    $this->address['province_id'],
                    $this->address['city_id'],
                    $this->address['district_id']
                ];
                $addressArray = array_intersect($addressArr, $city_id);
                if (empty($addressArray)) {
                    $isArea = 1;
                }
            }
        }
        return $isArea;
    }

    // 包邮规则
    protected function getFreeDeliveryRules($mch, $expressPrice)
    {
        if ($expressPrice == 0) {
            return $expressPrice;
        }
        if ($mch['mch_id'] == 0) {
            $free = FreeDeliveryRules::find()->where(['store_id' => $this->store_id])->asArray()->all();
            foreach ($free as $k => $v) {
                $city = json_decode($v['city'], true);
                foreach ($city as $v1) {
                    if ($this->address['city_id'] == $v1['id'] && $mch['total_price'] >= $v['price']) {
                        $expressPrice = 0;
                        break;
                    }
                }
            }
        } else {
            $model = MchOption::get('free-deliver-rules', $this->store->id, $mch['mch_id'], 'setting', null);
            if (!$model) {
                return $expressPrice;
            }
            if ($model->is_enable == 0) {
                return $expressPrice;
            }
            $ok = false;
            if($model->rule_list && is_array($model->rule_list)) {
                foreach ($model->rule_list as $value) {
                    foreach ($value['province_list'] as $item) {
                        if ($item['id'] == $this->address['city_id']) {
                            $ok = true;
                            if ($mch['total_price'] >= $value['price']) {
                                $expressPrice = 0;
                            }
                            break;
                        }
                    }
                    if ($ok) {
                        break;
                    }
                }
            }
            if ($ok == false && $model->total_price >= 0 && $mch['total_price'] >= $model->total_price) {
                $expressPrice = 0;
            }
        }
        return $expressPrice;
    }

    // 获取用户填写的自定义表单
    protected function getForm(&$form)
    {
        if ($form['is_form'] == 1) {
            $formList = &$form['list'];
            foreach ($formList as $index => $value) {
                if ($value['required'] == 1) {
                    if (in_array($value['type'], ['radio', 'checkbox'])) {
                        $is_true = false;
                        foreach ($value['default_list'] as $k => $v) {
                            if ($v['is_selected'] == 1) {
                                $is_true = true;
                            }
                        }
                        if (!$is_true) {
                            return [
                                'code' => 1,
                                'msg' => '请填写' . $form['name'] . '，加“*”为必填项',
                                'name' => $value['name']
                            ];
                        }
                    } else {
                        if (!$value['default'] && $value['default'] != 0) {
                            return [
                                'code' => 1,
                                'msg' => '请填写' . $form['name'] . '，加“*”为必填项',
                                'name' => $value['name']
                            ];
                        }
                    }
                }
                if (in_array($value['type'], ['radio', 'checkbox'])) {
                    $d = [];
                    foreach ($value['default_list'] as $k => $v) {
                        if ($v['is_selected'] == 1) {
                            $d[] = $v['name'];
                        }
                    }
                    $formList[$index]['default'] = implode(',', $d);
                }
            }
        }
        return $form;
    }

    protected function goodsCardList()
    {
        $list = [];
        foreach ($this->mch_list as $mch) {
            if($mch['mch_id'] == 0) {
                foreach ($mch['goods_list'] as $goods) {
                    if(!$goods['goods_card_list']) {
                        $goods['goods_card_list'] = [];
                    }
                    $list = array_merge($list, $goods['goods_card_list']);
                }
            }
        }
        return $list;
    }
}