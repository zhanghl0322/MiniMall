<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/9
 * Time: 16:18
 */

namespace app\modules\mch\models;

use app\models\Mch;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderForm;
use app\models\OrderRefund;
use app\models\User;
use app\models\UserAccountLog;

class OrderDetailForm extends MchModel
{
    public $store_id;
    public $order_id;

    public function search()
    {
        $order = Order::find()->where(['store_id' => $this->store_id, 'id' => $this->order_id])->asArray()->one();
        if (!$order) {
            return [
                'code'=>1,
                'msg'=>'fail'
            ];
        }
        $order['integral_arr'] = json_decode($order['integral'], true);

        $order['get_integral'] = OrderDetail::find()
            ->andWhere(['order_id' => $order['id'], 'is_delete' => 0])
            ->select([
                'sum(integral)'
            ])->scalar();

        $form = new OrderListForm();
        $goods_list = $form->getOrderGoodsList($order['id']);
        $user = User::find()->where(['id' => $order['user_id'], 'store_id' => $this->store_id])->asArray()->one();
        $order_form = OrderForm::find()->where(['order_id' => $order['id'], 'is_delete' => 0, 'store_id' => $this->store_id])->asArray()->all();
        $order_refund = OrderRefund::findOne(['store_id' => $this->store_id, 'order_id' => $order['id'], 'is_delete' => 0]);

        //pay_type
        $order['deduct_from'] = 0;//默认余额抵扣0元
        $order['pay_money'] = 0;//实付金额
        //账户 或者  账户+微信  支付模式
        if($order['pay_type']==4)
        {
            //TODO 余额抵扣 全扣 记录 2019年8月24日09:59:44
            $order['deduct_from'] = $order['pay_price'] ;//抵扣金额
        }
        if($order['pay_type']==3)
        {
            $user_accoen_log = UserAccountLog::findOne(['user_id' => $order['user_id'], 'type' => 2,'order_id'=>$order['id'],'order_type'=>1]);//余额抵扣记录
            //TODO 余额抵扣记录 2019年8月24日09:59:44
            $order['deduct_from'] =$user_accoen_log->price ;//抵扣金额
        }
        $order['pay_money'] =$order['pay_price']-$order['deduct_from'];
        if ($order_refund) {
            $order['refund'] = $order_refund->status;
        }
        if ($order['mch_id'] > 0) {
            $mch = Mch::findOne(['store_id' => $this->store_id, 'id' => $order['mch_id']]);
        }
        \Yii::warning('测试商品长度'.count($goods_list),'info');
        return [
            'order' => $order,
            'goods_list' => $goods_list,
            'user' => $user,
            'order_form' => $order_form,
            'mch' => $mch
        ];
    }
}
