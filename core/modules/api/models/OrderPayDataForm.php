<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 12:11
 */

namespace app\modules\api\models;

use Alipay\AlipayRequestFactory;
use app\hejiang\ApiCode;
use app\hejiang\ApiResponse;
use app\models\common\api\CommonOrder;
use app\models\FormId;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderUnion;
use app\models\OrderWarn;
use app\models\Store;
use app\models\User;
use luweiss\wechat\Wechat;

/**
 * @property User $user
 * @property Order $order
 */
class OrderPayDataForm extends ApiModel
{
    public $store_id;
    public $order_id;
    public $order_id_list;
    public $pay_type;
    public $user;
    public $form_id;
    public $parent_user_id;
    public $share_parent_user_id;
    public $condition;

    /** @var  Wechat $wechat */
    private $wechat;
    private $order;

    public function rules()
    {
        return [
            [['pay_type'], 'required'],
            [['pay_type'], 'in', 'range' => ['ALIPAY', 'WECHAT_PAY', 'HUODAO_PAY', 'BALANCE_PAY']],
            [['form_id', 'order_id_list'], 'string'],
            [['order_id', 'parent_user_id', 'condition','share_parent_user_id'], 'integer'],
        ];
    }

    public function search()
    {
        $this->wechat = $this->getWechat();
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $this->user->money = doubleval($this->user->money);
        if ($this->order_id_list) {
            $order_id_list = json_decode($this->order_id_list, true);
            if (is_array($order_id_list) && count($order_id_list) == 1) {
                $this->order_id = $order_id_list[0];
                $this->order_id_list = '';
            }
        }


        if ($this->order_id) { //单个订单付款
            $this->order = Order::findOne([
                'store_id' => $this->store_id,
                'id' => $this->order_id,
            ]);

            if (!$this->order) {
                return [
                    'code' => 1,
                    'msg' => '订单不存在',
                ];
            }
            if ($this->order->is_delete == 1 || $this->order->is_cancel == 1) {
                return [
                    'code' => 1,
                    'msg' => '订单已取消',
                ];
            }
            try {
                $this->checkGoodsConfine($this->order);
            } catch (\Exception $e) {
                return [
                    'code' => ApiCode::CODE_ERROR,
                    'msg' => $e->getMessage()
                ];
            }


            \Yii::warning($this->parent_user_id.'***********saveParentIdsaveParentIdsaveParentIdsaveParentIdsaveParentId************'.$this->user->id,'info');
            //原分销关系绑定、支付成功 绑定、不更改模式
            $commonOrder = CommonOrder::saveParentId($this->parent_user_id);
            \Yii::warning($this->share_parent_user_id.'***********111share_parent_user_idshare_parent_user_id*************','info');
            //新分销关系、谁分销就归属于谁的下级
            $changeParentId = CommonOrder::changeParentId($this->share_parent_user_id);

            \Yii::warning($this->parent_user_id.'***********changeParentId************'.$this->user->id,'info');

            $goods_names = '';
            $goods_list = OrderDetail::find()->alias('od')->leftJoin(['g' => Goods::tableName()], 'g.id=od.goods_id')->where([
                'od.order_id' => $this->order->id,
                'od.is_delete' => 0,
            ])->select('g.name')->asArray()->all();
            foreach ($goods_list as $goods) {
                $goods_names .= $goods['name'] . ';';
            }
            $goods_names = mb_substr($goods_names, 0, 32, 'utf-8');

            //TODO 账户余额是否大于订单总金额 Allon  2019年8月19日09:59:51
            $user_copy = User::findOne(['id' =>$this->order->user_id]);



            $store1 = Store::findOne([
                'id' => $this->store_id
            ]);

            //TODO 如果超过有效保护期、将无佣金  2019年11月22日11:49:18
            if (time() < ($user_copy->parent_binding_validity + ($store1->share_validity_time * 86400))) {
                //如果存在设置返佣最低消费金额
                if ($store1->share_min_price == 0) {
                    //验证最低消费金额是否无限制
                    $this->setReturnData($this->order);
                } else {
                    if ($this->order->pay_price > $store1->share_min_price) {
                        //验证是否最低消费金额小于订单支付金额
                        $this->setReturnData($this->order);
                    }
                }
            }

            $this->order->order_union_id = 0;
            $this->order->save();


            $is_balance=true;
            if ($user_copy->money <$this->order->pay_price) {
//                return [
//                    'code' => 1,
//                    'msg' => '支付失败，余额不足',
//                ];
                $is_balance=false;
            }

            //=============================CRM单据传送 单个订单付款========================================
            \Yii::warning("CRM传递信息====wechat_open_id=>{$user_copy->wechat_open_id},wechat_union_id=>{$user_copy->wechat_union_id},order_no=>{$this->order->order_no},id=>{$this->order->id},pay_price=>{$this->order->pay_price}",'info');
            $this->payWriteBack_add($user_copy->wechat_open_id,$user_copy->wechat_union_id,$this->order->order_no,$this->order->id,$this->order->pay_price);
            // $this->logger($this->order->pay_price);
            //=====================================================================

            \Yii::warning("==订单总金额是否大于账户总金额==".$is_balance ,'info');
            //TODO 如果 BALANCE_PAY 账户余额支付、并且账户余额不足支付该笔订单金额、常用微信支付余款  pay_price  =订单无扣减情况下全额
            if ($this->pay_type == 'WECHAT_PAY'||($this->pay_type == 'BALANCE_PAY'&&$user_copy->money <$this->order->pay_price)) {

                \Yii::warning($this->order->pay_price."==金额|账户余额==".$user_copy->money ,'info');
                \Yii::warning($this->order->pay_price."==金额不够哦!!!==".$this->pay_type ,'info');
                if ($this->order->pay_price == 0) {

                    $this->order->is_pay = 1;
                    $this->order->pay_type = 1;
                    $this->order->pay_time = time();
                    $this->order->save();

                    //支付完成后，相关操作
                    $form = new OrderWarn();
                    $form->order_id = $this->order->id;
                    $form->order_type = 0;
                    $form->notify();

                    return [
                        'code' => 0,
                        'msg' => '0元支付',
                        'data' => [
                            'price' => 0
                        ]
                    ];
                }

                // 支付宝
                if (\Yii::$app->fromAlipayApp()) {
                    return $this->alipayUnifiedOrder($goods_names);
                }

                $res = $this->unifiedOrder($goods_names);
                if (isset($res['code']) && $res['code'] == 1) {
                    return $res;
                }

                //记录prepay_id发送模板消息用到
                FormId::addFormId([
                    'store_id' => $this->store_id,
                    'user_id' => $this->user->id,
                    'wechat_open_id' => $this->user->wechat_open_id,
                    'form_id' => $res['prepay_id'],
                    'type' => 'prepay_id',
                    'order_no' => $this->order->order_no,
                ]);

                $pay_data = [
                    'appId' => $this->wechat->appId,
                    'timeStamp' => '' . time(),
                    'nonceStr' => md5(uniqid()),
                    'package' => 'prepay_id=' . $res['prepay_id'],
                    'signType' => 'MD5',
                ];
                $pay_data['paySign'] = $this->wechat->pay->makeSign($pay_data);

//                //TODO 冻结账户余额  Allon 2019年8月19日18:26:55
//                $user_copy->money=0;
//                $user_copy->save();

                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => (object)$pay_data,
                    'res' => $res,
                    'body' => $goods_names,
                    'pay_type'=>$this->pay_type,
                    'is_balance'=>$is_balance//TODO 订单金额是否大于余额
                ];
            }
            //货到付款和余额支付数据处理
            if ($this->pay_type == 'HUODAO_PAY' || $this->pay_type == 'BALANCE_PAY') {
                $order = $this->order;
                //余额支付  用户余额变动
                if ($this->pay_type == 'BALANCE_PAY') {
                    $user = User::findOne(['id' => $order->user_id]);
                    if ($user->money < $order->pay_price) {
                        return [
                            'code' => 1,
                            'msg' => '支付失败，余额不足',
                        ];
                    }
                    $user->money -= floatval($order->pay_price);
                    $user->save();
                    $order->is_pay = 1;
                    $order->pay_type = 3;
                    $order->pay_time = time();
                    $order->save();
                }
                //支付完成后，相关操作
                $form = new OrderWarn();
                $form->order_id = $order->id;
                $form->order_type = 0;
                $form->notify();

                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => '',
                ];
            }
        } elseif ($this->order_id_list) { //多个订单合并付款
            $order_id_list = json_decode($this->order_id_list, true);
            if (!$order_id_list) {
                return [
                    'code' => 1,
                    'msg' => '数据错误：订单格式不正确。',
                ];
            }
            $order_list = [];
            $total_pay_price = 0;
            foreach ($order_id_list as $order_id) {
                $order = Order::findOne([
                    'store_id' => $this->store_id,
                    'id' => $order_id,
                    'is_delete' => 0,
                ]);
                if (!$order) {
                    return [
                        'code' => 1,
                        'msg' => '订单不存在',
                    ];
                }
                if ($order->is_pay == 1) {
                    return [
                        'code' => 1,
                        'msg' => '存在已付款的订单，订单合并支付失败，请到我的订单重新支付。',
                    ];
                }
                try {
                    $this->checkGoodsConfine($order);
                } catch (\Exception $e) {
                    return [
                        'code' => ApiCode::CODE_ERROR,
                        'msg' => $e->getMessage()
                    ];
                }
                $order_list[] = $order;
                $total_pay_price += doubleval($order->pay_price);

                //$this->setReturnData($order);
                $user1 = User::findOne(['id' =>$this->order->user_id]);
                $store1 = Store::findOne([
                    'id' => $this->store_id
                ]);

                //TODO 如果超过有效保护期、将无佣金  2019年11月22日11:49:18
                if (time() < ($user1->parent_binding_validity + ($store1->share_validity_time * 86400))) {
                    $this->setReturnData($this->order);
//                    //如果存在设置返佣最低消费金额
//                    if ($store1->share_min_price == 0) {
//                        //验证最低消费金额是否无限制
//                        $this->setReturnData($this->order);
//                    } else {
//                        if ($this->order->pay_price > $store1->share_min_price) {
//                            //验证是否最低消费金额小于订单支付金额
//                            $this->setReturnData($this->order);
//                        }
//                    }
                }

            }
            //微信支付
            //TODO:1.0 调用微信支付记录商品信息 2019-05-15 17点39分
            $user = User::findOne($order->user_id);
            if (!$user) {
                return;
            }

            if ($this->pay_type == 'WECHAT_PAY'||($this->pay_type == 'BALANCE_PAY'&&$user->money <$total_pay_price)) {

                \Yii::warning($total_pay_price."==记录支付==",'info');
                $res = $this->unifiedUnionOrder($order_list,$total_pay_price );

                if (isset($res['code']) && $res['code'] == 1) {
                    return $res;
                }
                //记录prepay_id发送模板消息用到
                FormId::addFormId([
                    'store_id' => $this->store_id,
                    'user_id' => $this->user->id,
                    'wechat_open_id' => $this->user->wechat_open_id,
                    'form_id' => $res['prepay_id'],
                    'type' => 'prepay_id',
                    'order_no' => $res['order_no'],
                ]);

                $pay_data = [
                    'appId' => $this->wechat->appId,
                    'timeStamp' => '' . time(),
                    'nonceStr' => md5(uniqid()),
                    'package' => 'prepay_id=' . $res['prepay_id'],
                    'signType' => 'MD5',
                ];
                $pay_data['paySign'] = $this->wechat->pay->makeSign($pay_data);

//                //TODO 冻结账户余额  Allon 2019年8月19日18:26:55
//                $user->money=0;
//                $user->save();
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => (object)$pay_data,
                    'res' => $res,
                    'body' => $res['body'],
                ];
            }
            //货到付款和余额支付数据处理
            if ($this->pay_type == 'HUODAO_PAY' || $this->pay_type == 'BALANCE_PAY') {
                //余额支付  用户余额变动
                if ($this->pay_type == 'BALANCE_PAY') {
                    if ($this->user->money < $total_pay_price) {
                        return [
                            'code' => 1,
                            'msg' => '支付失败，余额不足',
                        ];
                    }
                    $this->user->money = $this->user->money - $total_pay_price;
                    $this->user->save();
                    foreach ($order_list as $order) {
                        $order->is_pay = 1;
                        $order->pay_type = 3;
                        $order->pay_time = time();
                        $order->save();
                    }
                }
                foreach ($order_list as $order) {
                    //支付完成后，相关操作
                    $form = new OrderWarn();
                    $form->order_id = $order->id;
                    $form->order_type = 0;
                    $form->notify();
                }
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => '',
                ];
            }
        }
    }

    /**
     * 购买成功首页提示
     */
    private function buyData($order_no, $store_id, $type)
    {
        $order = Order::find()->select(['u.nickname', 'g.name', 'u.avatar_url', 'od.goods_id'])->alias('c')
            ->where('c.order_no=:order', [':order' => $order_no])
            ->andwhere('c.store_id=:store_id', [':store_id' => $store_id])
            ->leftJoin(['u' => User::tableName()], 'u.id=c.user_id')
            ->leftJoin(['od' => OrderDetail::tableName()], 'od.order_id=c.id')
            ->leftJoin(['g' => Goods::tableName()], 'od.goods_id = g.id')
            ->asArray()->one();

        $key = "buy_data";
        $data = (object)null;
        $data->type = $type;
        $data->store_id = $store_id;
        $data->order_no = $order_no;
        $data->user = $order['nickname'];
        $data->goods = $order['goods_id'];
        $data->address = $order['name'];
        $data->avatar_url = $order['avatar_url'];
        $data->time = time();
        $new = json_encode($data);
        $cache = \Yii::$app->cache;
        $cache->set($key, $new, 300);
    }

    /**
     * 设置佣金
     * @param Order $order
     */
    private function setReturnData($order)
    {
        \Yii::warning('*************验证是否进入佣金发放*************','info');
        $form = new ShareMoneyForm();
        $form->order = $order;
        $form->order_type = 0;
        return $form->setData();
    }

    //单个订单微信支付下单
    private function unifiedOrder($goods_names)
    {
        if ($this->pay_type == 'BALANCE_PAY') {
            $total_pay_price =round(( $this->order->pay_price - $this->user->money),2);
        } else {
            $total_pay_price =round( $this->order->pay_price,2);
        }

        \Yii::warning(round($total_pay_price,2)."==unifiedOrderunifiedOrder==".$total_pay_price ,'info');
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $goods_names,
            'out_trade_no' => $this->order->order_no,
            'total_fee' =>$total_pay_price*100,
            'notify_url' => pay_notify_url('/pay-notify.php'),
            'trade_type' => 'JSAPI',
            'openid' => $this->user->wechat_open_id,
        ]);

        if (!$res) {
            return [
                'code' => 1,
                'msg' => '支付失败',
            ];
        }
        if ($res['return_code'] != 'SUCCESS') {
            return [
                'code' => 1,
                'msg' => '支付失败，' . (isset($res['return_msg']) ? $res['return_msg'] : ''),
                'res' => $res,
            ];
        }
        if ($res['result_code'] != 'SUCCESS') {
            if ($res['err_code'] == 'INVALID_REQUEST') { //商户订单号重复
                $this->order->order_no = (new OrderSubmitForm())->getOrderNo();
                $this->order->save();
                return $this->unifiedOrder($goods_names);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . (isset($res['err_code_des']) ? $res['err_code_des'] : ''),
                    'res' => $res,
                ];
            }
        }
        return $res;
    }

    //合并订单微信支付下单
    private function unifiedUnionOrder($order_list, $total_pay_price)
    {
        \Yii::warning($this->pay_type."==合单支付类型12==".$total_pay_price,'info');
        \Yii::warning($this->pay_type."==合单支付账户余额==".$this->user->money,'info');
        if ($this->pay_type == "BALANCE_PAY") {
            //账户+微信 支付  应对账户+微信支付场景  2019年8月21日17:29:06
            $price=($total_pay_price*100-$this->user->money*100)/100;
            $total_pay_price =round($price,2);
        } else {
            if($total_pay_price<=0)
            {
                $total_pay_price=0.01;
            }
            $total_pay_price =round( $total_pay_price,2);
        }

        $total_pay_price= round( $total_pay_price,2);
        // 支付宝
        if (\Yii::$app->fromAlipayApp()) {
            $data = [
                'body' => count($order_list) . '笔订单合并支付', // 对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加
                'subject' => count($order_list) . '笔订单合并支付', // 商品的标题 / 交易标题 / 订单标题 / 订单关键字等
                'out_trade_no' => $this->getOrderUnionNo(), // 商户网站唯一订单号
                'total_amount' => $total_pay_price, // 订单总金额，单位为元，精确到小数点后两位，取值范围 [0.01,100000000]
                'buyer_id' => $this->user->wechat_open_id, // 购买人的支付宝用户 ID

            ];

            $request = AlipayRequestFactory::create('alipay.trade.create', [
                'notify_url' => pay_notify_url('/alipay-notify.php'),
                'biz_content' => $data,
            ]);

            try {
                $aop = $this->getAlipay();
                $res = $aop->execute($request)->getData();
            } catch (\Exception $e) {
                if ($e->getCode() == 40004 || $e->getCode() == 'ACQ.CONTEXT_INCONSISTENT') {
                    return $this->unifiedUnionOrder($order_list, $total_pay_price);
                } else {
                    return [
                        'code' => 1,
                        'msg' => '支付失败，' . $e->getMessage()
                    ];
                }
            }

            $order_union = new OrderUnion();
            $order_union->store_id = $this->store_id;
            $order_union->user_id = $this->user->id;
            $order_union->order_no = $data['out_trade_no'];
            $order_union->price = $total_pay_price;
            $order_union->is_pay = 0;
            $order_union->addtime = time();
            $order_union->is_delete = 0;
            $order_id_list = [];
            foreach ($order_list as $order) {
                $order_id_list[] = $order->id;
            }
            $order_union->order_id_list = json_encode($order_id_list);
            if (!$order_union->save()) {
                return $this->getErrorResponse($order_union);
            }
            foreach ($order_list as $order) {
                $order->order_union_id = $order_union->id;
                $order->save();
            }

            return new ApiResponse(0, '成功', $res);
        }
        \Yii::warning('计算金额'.$total_pay_price,'info');
        $data = [
            'body' => count($order_list) . '笔订单合并支付',
            'out_trade_no' => $this->getOrderUnionNo(),
            'total_fee' => $total_pay_price * 100,
            'notify_url' => pay_notify_url('/pay-notify.php'),
            'trade_type' => 'JSAPI',
            'openid' => $this->user->wechat_open_id,
        ];
        $order_union = new OrderUnion();
        $order_union->store_id = $this->store_id;
        $order_union->user_id = $this->user->id;
        $order_union->order_no = $data['out_trade_no'];
        $order_union->price = $total_pay_price;
        $order_union->is_pay = 0;
        $order_union->addtime = time();
        $order_union->is_delete = 0;
        $order_id_list = [];
        foreach ($order_list as $order) {
            $order_id_list[] = $order->id;
        }
        $order_union->order_id_list = json_encode($order_id_list);
        if (!$order_union->save()) {
            return $this->getErrorResponse($order_union);
        }
        $res = $this->wechat->pay->unifiedOrder($data);
        if (!$res) {
            return [
                'code' => 1,
                'msg' => '支付失败',
            ];
        }
        if ($res['return_code'] != 'SUCCESS') {
            return [
                'code' => 1,
                'msg' => '支付失败，' . (isset($res['return_msg']) ? $res['return_msg'] : ''),
                'res' => $res,
            ];
        }
        if ($res['result_code'] != 'SUCCESS') {
            if ($res['err_code'] == 'INVALID_REQUEST') { //商户订单号重复
                return $this->unifiedUnionOrder($order_list, $total_pay_price);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . (isset($res['err_code_des']) ? $res['err_code_des'] : ''),
                    'res' => $res,
                ];
            }
        }
        foreach ($order_list as $order) {
            $order->order_union_id = $order_union->id;
            $order->save();
        }
        $res['order_no'] = $data['out_trade_no'];
        $res['body'] = $data['body'];
        \Yii::warning($data['out_trade_no']."==账户余额支付微信请求==".$data['body'] ,'info');
        return $res;
    }

    public function getOrderUnionNo()
    {
        $order_no = null;
        while (true) {
            $order_no = 'U' . date('YmdHis') . mt_rand(10000, 99999);
            $exist_order_no = OrderUnion::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no) {
                break;
            }
        }
        return $order_no;
    }

    // 单个支付宝下单
    private function alipayUnifiedOrder($goods_names)
    {
        $request = AlipayRequestFactory::create('alipay.trade.create', [
            'notify_url' => pay_notify_url('/alipay-notify.php'),
            'biz_content' => [
                'body' => $goods_names, // 对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加
                'subject' => $goods_names, // 商品的标题 / 交易标题 / 订单标题 / 订单关键字等
                'out_trade_no' => $this->order->order_no, // 商户网站唯一订单号
                'total_amount' => $this->order->pay_price, // 订单总金额，单位为元，精确到小数点后两位，取值范围 [0.01,100000000]
                'buyer_id' => $this->user->wechat_open_id, // 购买人的支付宝用户 ID

            ],
        ]);

        try {
            $aop = $this->getAlipay();
            $res = $aop->execute($request)->getData();
        } catch (\Exception $e) {
            if ($e->getCode() == 'ACQ.CONTEXT_INCONSISTENT') { //订单号重复
                $this->order->order_no = (new OrderSubmitForm())->getOrderNo();
                $this->order->save();
                return $this->alipayUnifiedOrder($goods_names);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . $e->getMessage()
                ];
            }
        }
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => $res,
            'res' => $res,
            'body' => $goods_names,
        ];
    }

    /**
     * @param Order $order
     * @throws \Exception
     */
    private function checkGoodsConfine($order)
    {
        foreach ($order->detail as $detail) {
            /* @var Goods $goods*/
            /* @var OrderDetail $detail*/
            $goods = $detail->goods;
            if ($goods->confine_count && $goods->confine_count > 0) {
                $goodsNum = Goods::getBuyNum($this->user, $goods->id);
                if ($goodsNum) {

                } else {
                    $goodsNum = 0;
                }
                $goodsTotalNum = intval($goodsNum + $detail->num);
                if ($goodsTotalNum > $goods->confine_count) {
                    throw new \Exception('商品：' . $goods->name . ' 超出购买数量', 1);
                }
            }
        }
    }

    //========================================================CRM业务逻辑==========================================================
    /**
     * 支付信息回写 新增
     * 2018年5月3日11:37:08
     */
    public function payWriteBack_add($openId,$unionId,$myOrderNO,$orderId,$surplusMoney)
    {
        //1.0 使用订单ID拉取订单与商品中间表信息
        // $cost_price = $this->order->pay_price * 100;//商品价格-商品成本价*商品下单量
        $model = array('username' => 'CrmApi', 'password' => '4008366899', 'grant_type' => 'password');
        $dataJson = $this->send_post('http://139.199.88.207:16124/oauth2/token', $model);
        $tokenarr = json_decode($dataJson, true); //解码
        $token = $tokenarr['access_token'];//拿出数组中的Token
        $this->logger($token);
        //2.0 数据定义
        $url = 'http://139.199.88.207:16124/v1/MiniMall/AddOrder?openId='.$openId.'&unionId='.$unionId.'&myOrderNO='.$myOrderNO.'&orderId='.$orderId.'&surplusMoney='.$surplusMoney.'&orderType=小程序商城';
        //支付信息填充
        $arr_header[] = "Content-Type:application/json";
        $arr_header[] = "Authorization: Bearer " . $token;
        // $arr_header[] = "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxIiwidXNlcm5hbWUiOiJDcm1BcGkiLCJtaWNyb1NlcnZpY2VUb2tlbiI6IkZjbGc0eVZFWi9xb0dTODdCajM4WkRMNWhCTmMzY3Vtb1RoemtTKzlmQXZnQWxoZzFDN216QUY2S0dZM3NuRENtWVZOQlZLc052NCswUWlLbHZhdTNGbXFoY3djcmRXTmFhMHlRc3V6R0loMytEK2w2eE1Wb0tRTjExS21hZlJkMmF2SXZ5cjk0RUE9IiwidXNlciI6IntcIlVzZXJJZFwiOjEsXCJVc2VyTmFtZVwiOlwiQ3JtQXBpXCIsXCJGcm9tXCI6XCJDcm1fQXBpXCIsXCJGcm9tRGV0YWlsXCI6XCJcIixcIkZyb21EZXRhaWxJZFwiOlwiXCIsXCJGcm9tVHlwZVwiOm51bGx9IiwiaXNzIjoidGVzdCIsImF1ZCI6IkFueSIsImV4cCI6MTUyNzkwNzQ1NiwibmJmIjoxNTI1MzE1NDU2fQ.lITKpPIZPAXnQ__ML4QKfAv4188g6iS1vt0CL_bcmVQ"; //添加头，在name和pass处填写对应账号密码
        $this-> curl_post($url, null, $arr_header);//发送数据请求，回写订单信息
    }

    /**
     * 处理请求，返回授权编码
     * 日期：2018年5月3日11:01:32
     */
    public function send_post($url, $post_data)
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/json',
                'Authorization'=> 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxIiwidXNlcm5hbWUiOiJDcm1BcGkiLCJtaWNyb1NlcnZpY2VUb2tlbiI6IkZjbGc0eVZFWi9xb0dTODdCajM4WkRMNWhCTmMzY3Vtb1RoemtTKzlmQXZnQWxoZzFDN216QUY2S0dZM3NuRENtWVZOQlZLc052NCswUWlLbHZhdTNGbXFoY3djcmRXTmFhMHlRc3V6R0loMytEK2w2eE1Wb0tRTjExS21hZlJkMmF2SXZ5cjk0RUE9IiwidXNlciI6IntcIlVzZXJJZFwiOjEsXCJVc2VyTmFtZVwiOlwiQ3JtQXBpXCIsXCJGcm9tXCI6XCJDcm1fQXBpXCIsXCJGcm9tRGV0YWlsXCI6XCJcIixcIkZyb21EZXRhaWxJZFwiOlwiXCIsXCJGcm9tVHlwZVwiOm51bGx9IiwiaXNzIjoidGVzdCIsImF1ZCI6IkFueSIsImV4cCI6MTU2MDY1NTY1MiwibmJmIjoxNTU4MDYzNjUyfQ.lNFaI0sp9wQqym3zZG5kSLBDqPyGWEzyXzRe-jdg0BM',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $this->logger(Helloworld);
        return $result;
    }

    public function curl_post($url,$array,$headers){

        $curl = curl_init();
        //设置提交的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回。而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        $post_data = $array;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //运行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //获得数据并返回
        $this->logger($data);
        return $data;
    }
    //日志记录
    public function logger($log_content)
    {
        $max_size = 100000;
        $log_filename = "raw.log";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }

}
