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
            [['order_id', 'parent_user_id', 'condition'], 'integer'],
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

            $commonOrder = CommonOrder::saveParentId($this->parent_user_id);

            $goods_names = '';
            $goods_list = OrderDetail::find()->alias('od')->leftJoin(['g' => Goods::tableName()], 'g.id=od.goods_id')->where([
                'od.order_id' => $this->order->id,
                'od.is_delete' => 0,
            ])->select('g.name')->asArray()->all();
            foreach ($goods_list as $goods) {
                $goods_names .= $goods['name'] . ';';
            }
            $goods_names = mb_substr($goods_names, 0, 32, 'utf-8');

            $this->setReturnData($this->order);
            $this->order->order_union_id = 0;
            $this->order->save();
            if ($this->pay_type == 'WECHAT_PAY') {
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
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => (object)$pay_data,
                    'res' => $res,
                    'body' => $goods_names,
                ];
            }
            //货到付款和余额支付数据处理
            //TODO 2019年8月12日09:18:07  基于 货到付款和余额支付数据处理 改造、优先使用账户余额、如果余额不足、就使用微信支付支付余下部分
            if ($this->pay_type == 'HUODAO_PAY' || $this->pay_type == 'BALANCE_PAY') {
                $order = $this->order;
                //余额支付  用户余额变动

                if ($this->pay_type == 'BALANCE_PAY') {
                    $user = User::findOne(['id' => $order->user_id]);
                    
                    \Yii::warning("==账户余额支付==".$user->money ,'info');
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

                $this->setReturnData($order);
            }
            //微信支付
            if ($this->pay_type == 'WECHAT_PAY') {
                $res = $this->unifiedUnionOrder($order_list, $total_pay_price);

//                //TODO:1.0 调用微信支付记录商品信息 2019-05-15 17点39分
//                $user = User::findOne($order->user_id);
//                if (!$user) {
//                    return;
//                }
//                $this->payWriteBack_add($user->wechat_open_id,$user->wechat_union_id,$order->order_no,$order->id,$order->pay_price);
//                $this->payWriteBack_add($this->order, $this->user->id);
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
        $form = new ShareMoneyForm();
        $form->order = $order;
        $form->order_type = 0;
        return $form->setData();
    }

    //单个订单微信支付下单
    private function unifiedOrder($goods_names)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $goods_names,
            'out_trade_no' => $this->order->order_no,
            'total_fee' => $this->order->pay_price * 100,
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
    /**
     * 支付信息回写 新增
     * 2018年5月3日11:37:08
     */
    public function payWriteBack_add($openId,$unionId,$myOrderNO,$orderId,$surplusMoney)
    {
        \Yii::warning('----COUPON1 BEHAVIOR----');
        //1.0 使用订单ID拉取订单与商品中间表信息
        // $cost_price = $this->order->pay_price * 100;//商品价格-商品成本价*商品下单量
        $model = array('username' => 'CrmApi', 'password' => '4008366899', 'grant_type' => 'password');
        $dataJson = $this->send_post('http://139.199.88.207:16124/oauth2/token', $model);
        $tokenarr = json_decode($dataJson, true); //解码
        $token = $tokenarr['access_token'];//拿出数组中的Token

        //2.0 数据定义
//        $openId = $this->user->wechat_open_id;
//        $unionId = $this->user->wechat_union_id;
//        $myOrderNO = $this->order->order_no;
//        $orderId = $this->order->id;
//        $surplusMoney = $this->order->pay_price;
        $orderType = '小程序商城';
        \Yii::warning('----COUPON BEHAVIOR----');
        $url = 'http://139.199.88.207:16124/v1/MiniMall/AddOrder?openId='.$openId.'&unionId='.$unionId.'&myOrderNO='.$myOrderNO.'&orderId='.$orderId.'&surplusMoney='.$surplusMoney.'&orderType=小程序商城';
        //支付信息填充
        $arr_header[] = "Content-Type:application/json";
        $arr_header[] = "Authorization: Bearer " . $token;
        // $arr_header[] = "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxIiwidXNlcm5hbWUiOiJDcm1BcGkiLCJtaWNyb1NlcnZpY2VUb2tlbiI6IkZjbGc0eVZFWi9xb0dTODdCajM4WkRMNWhCTmMzY3Vtb1RoemtTKzlmQXZnQWxoZzFDN216QUY2S0dZM3NuRENtWVZOQlZLc052NCswUWlLbHZhdTNGbXFoY3djcmRXTmFhMHlRc3V6R0loMytEK2w2eE1Wb0tRTjExS21hZlJkMmF2SXZ5cjk0RUE9IiwidXNlciI6IntcIlVzZXJJZFwiOjEsXCJVc2VyTmFtZVwiOlwiQ3JtQXBpXCIsXCJGcm9tXCI6XCJDcm1fQXBpXCIsXCJGcm9tRGV0YWlsXCI6XCJcIixcIkZyb21EZXRhaWxJZFwiOlwiXCIsXCJGcm9tVHlwZVwiOm51bGx9IiwiaXNzIjoidGVzdCIsImF1ZCI6IkFueSIsImV4cCI6MTUyNzkwNzQ1NiwibmJmIjoxNTI1MzE1NDU2fQ.lITKpPIZPAXnQ__ML4QKfAv4188g6iS1vt0CL_bcmVQ"; //添加头，在name和pass处填写对应账号密码
        $this->http_request_xml($url, json_encode($orderType), $arr_header);//发送数据请求，回写订单信息
    }

    /**
     * 订单支付成功调用接口回写支付信息
     * 日期：2018年5月3日11:01:32
     */
    public function http_request_xml($url, $data = null, $arr_header = null)
    {
        $curl = curl_init();// 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);// 要访问的地址
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if (!empty($arr_header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $arr_header);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);// 执行操作
        // echo curl_getinfo($curl);
        curl_close($curl);// 关键CURL会话
        unset($curl);
        return $output;// 返回数据
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
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }
}
