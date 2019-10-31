<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/20
 * Time: 10:25
 */

namespace app\modules\api\models;

use app\models\common\api\CommonShoppingList;
use app\models\Goods;
use app\models\OrderDetail;
use app\utils\PinterOrder;
use app\models\Level;
use app\models\Order;
use app\models\PrinterSetting;
use app\models\User;
use app\utils\TaskCreate;

class OrderConfirmForm extends ApiModel
{

    public $store_id;
    public $user_id;
    public $order_id;

    public function rules()
    {
        return [
            [['order_id'], 'required'],
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $order = Order::findOne([
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'id' => $this->order_id,
            'is_send' => 1,
            'is_delete' => 0,
        ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在'
            ];
        }
        /*************************************************************/
        //1.0 确认收货设置分润写入
        $user = User::findOne(['id' => $order->user_id, 'store_id' => $this->store_id]);//拉取用户信息

        //2.0 拉取订单信息List提取商品成本价格
        $order_detail_list = OrderDetail::find()->alias('od')->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where(['od.is_delete' => 0, 'od.order_id' => $order->id])
            ->asArray()
            ->select('g.individual_share,g.share_commission_first,g.share_commission_second,g.share_commission_third,g.original_price,od.total_price,od.num,g.share_type')
            ->all();

        //拉取订单与商品中间表信息
        foreach ($order_detail_list as $OrderDetail) {
            //3.0 调用分润写入
            $this->profitWriteBack_add($order,$OrderDetail,$user->wechat_union_id);
        }
        /*************************************************************/
        $order->is_confirm = 1;
        $order->confirm_time = time();
        if ($order->pay_type == 2) {
            $order->is_pay = 1;
            $order->pay_time = time();
        }
/*
        $user = User::findOne(['id' => $order->user_id, 'store_id' => $this->store_id]);
        $order_money = Order::find()->where(['store_id' => $this->store_id, 'user_id' => $user->id, 'is_delete' => 0])
            ->andWhere(['is_pay' => 1, 'is_confirm' => 1])->select([
                'sum(pay_price)'
            ])->scalar();
        $next_level = Level::find()->where(['store_id' => $this->store_id, 'is_delete' => 0,'status'=>1])
            ->andWhere(['<', 'money', $order_money])->orderBy(['level' => SORT_DESC])->asArray()->one();
        if ($user->level < $next_level['level']) {
            $user->level = $next_level['level'];
            $user->save();
        }
*/

        if ($order->save()) {
            $printer_order = new PinterOrder($this->store_id, $order->id, 'confirm', 0);
            $res = $printer_order->print_order();
            $wechatAccessToken = $this->getWechat()->getAccessToken();
            $res = CommonShoppingList::updateBuyGood($wechatAccessToken, $order, 0, 100);
            return [
                'code' => 0,
                'msg' => '已确认收货'
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '确认收货失败'
            ];
        }
    }

    /**
     * 确认收货分润回写 新增
     * 2018年5月3日11:37:08
     *  @param Order $order
     *  @param Goods $goods
     *  @param OrderDetail $OrderDetail
     */
    public function profitWriteBack_add($order, $OrderDetail,$wechat_union_id)
    {
        \Yii::warning('----COUPON1 BEHAVIOR----');
        //1.0 使用订单ID拉取订单与商品中间表信息

        $model = array('username' => 'CrmApi', 'password' => '4008366899', 'grant_type' => 'password');
        $dataJson = $this->send_post('http://139.199.88.207:16124/oauth2/token', $model);
        $tokenarr = json_decode($dataJson, true); //解码
        $token = $tokenarr['access_token'];//拿出数组中的Token

        //2.0 数据定义
        $unionId = $wechat_union_id;

        $orderId = $this->order_id;
        $cost_price = ($OrderDetail['original_price']*0.1) * $OrderDetail['num'];//(商品成本价格*10%)*商品下单量  PS:2018年5月24日11:34:07
        $orderType = '小程序商城';
        \Yii::warning('----COUPON BEHAVIOR----');
        $url = 'http://139.199.88.207:16124/v1/MiniMall/DistributionProfits?unionId=' . $unionId . '&amount=' . $cost_price . '&orderId=' . $orderId . '';
        //信息填充
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
