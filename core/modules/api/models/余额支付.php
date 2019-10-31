<?php

use app\models\FormId;
use app\models\OrderWarn;
use app\models\User;

$order_list[] = $order;
$total_pay_price_yu = 0;
$total_pay_price_yu += doubleval($order->pay_price);
\Yii::warning("==账户余额支付主逻辑==".$total_pay_price_yu ,'info');
$res = $this->unifiedUnionOrder($order_list, $total_pay_price_yu);

//TODO:1.0 调用微信支付记录商品信息 2019-05-15 17点39分
$user = User::findOne($order->user_id);
if (!$user) {
    return;
}
// $this->payWriteBack_add($user->wechat_open_id,$user->wechat_union_id,$order->order_no,$order->id,$order->pay_price);
if (isset($res['code']) && $res['code'] == 1) {
    return $res;
}
\Yii::warning("==账户余额支付主逻辑222==".$total_pay_price_yu ,'info');
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


//TODO 屏蔽原逻辑 余额支付、跟货到付款的 业务 将余额支付跟在线支付 合并 2019年8月14日09:49:12
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


$user->money -= floatval($order->pay_price);
$user->save();
$order->is_pay = 1;
$order->pay_type = 3;
$order->pay_time = time();
$order->save();