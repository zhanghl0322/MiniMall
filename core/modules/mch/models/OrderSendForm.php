<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/24
 * Time: 18:42
 */

namespace app\modules\mch\models;

use app\models\common\api\CommonShoppingList;
use app\models\Express;
use app\models\FormId;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Store;
use app\models\User;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use app\utils\TaskCreate;

class OrderSendForm extends MchModel
{
    public $store_id;
    public $order_id;
    public $express;
    public $express_no;
    public $words;

    public function rules()
    {
        return [
            [['express', 'express_no', 'words'], 'trim'],
            [['express', 'express_no',], 'required', 'on' => 'EXPRESS'],
            [['order_id'], 'required'],
            [['express', 'express_no',], 'string',],
            [['express', 'express_no',], 'default', 'value' => ''],
        ];
    }

    public function batch($arrCSV)
    {

        $empty = [];  //是否存在
        $error = [];   //操作失败
        $cancel = [];  //是否取消
        $offline = []; //到店自提
        $send = [];  //是否发货
        $success = []; //是否成功

        foreach ($arrCSV as $v) {
            $order = Order::findOne([
                'is_delete' => 0,
                'store_id' => $this->store_id,
                'order_no' => $v[1],
                'mch_id' => 0,
            ]);
            if (!$order) {
                $empty[] = $v[1];
                continue;
            }
            if ($order->is_cancel) {
                $cancel[] = $v[1];
                continue;
            }
            if ($order->is_send) {
                $send[] = $v[1];
                continue;
            }
            if ($order->is_offline) {
                $offline[] = $v[1];
                continue;
            }
            if ($order->is_pay == 0 && $order->pay_type != 2) {
                $pay[] = $v[1];
            }

            $order->express_no = $v[2];
            $order->is_send = 1;
            $order->send_time = time();
            $order->express = $this->express;

            if (!$order->save()) {
                $error[] = $v[1];
            } else {
                $success[] = $v[1];
                try {
                    $wechat_tpl_meg_sender = new WechatTplMsgSender($this->store_id, $order->id, $this->getWechat());
                    $wechat_tpl_meg_sender->sendMsg();
                    TaskCreate::orderConfirm($order->id, 'STORE');
                } catch (\Exception $e) {
                    \Yii::warning($e->getMessage());
                }
            }
        };
        $data = [];
        $max = max(count($empty), count($error), count($cancel), count($send), count($offline), count($pay), count($success));
        for ($i = 0, $k = 0; $i < $max; $k++, $i++) {
            $data[$k][] = $empty[$k];
            $data[$k][] = $cancel[$k];
            $data[$k][] = $send[$k];
            $data[$k][] = $offline[$k];
            $data[$k][] = $pay[$k];
            $data[$k][] = $error[$k];
            $data[$k][] = $success[$k];
        }
        return $data;
    }

    public function attributeLabels()
    {
        return [
            'express' => '快递公司',
            'express_no' => '快递单号',
            'words' => '商家留言',
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $order = Order::findOne([
            'is_delete' => 0,
            'store_id' => $this->store_id,
            'id' => $this->order_id,
//            'mch_id' => 0, TODO 移除平台管理员 平台操作限制  2019年10月18日11:48:38
         ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在或已删除',
            ];
        }
        if ($order->is_pay == 0 && $order->pay_type != 2) {
            return [
                'code' => 1,
                'msg' => '订单未支付'
            ];
        }

        if ($order->apply_delete == 1) {
            return [
                'code' => 1,
                'msg' => '该订单正在申请取消操作，请先处理'
            ];
        }

        $expressList = Express::getExpressList();
        $ok = false;
        foreach ($expressList as $value) {
            if ($value['name'] == $this->express) {
                $ok = true;
                break;
            }
        }
        if (!$ok && $this->scenario == "EXPRESS") {
            return [
                'code' => 1,
                'msg' => '快递公司不正确'
            ];
        }

        $order->express = $this->express;
        $order->express_no = $this->express_no;
        $order->words = $this->words;
        $order->is_send = 1;
        $order->send_time = time();
        if ($order->save()) {
            try {
                $wechat_tpl_meg_sender = new WechatTplMsgSender($this->store_id, $order->id, $this->getWechat());
                $wechat_tpl_meg_sender->sendMsg();
            } catch (\Exception $e) {
                \Yii::warning($e->getMessage());
            }
            // 创建订单自动收货定时任务
            TaskCreate::orderConfirm($order->id, 'STORE');

            //TODO 创建订单自动收货定时任务
            //TaskCreate::orderConfirm($order->id, 'STORE');

            $wechatAccessToken = $this->getWechat()->getAccessToken();
            $res = CommonShoppingList::updateBuyGood($wechatAccessToken, $order, 0, 4);
            return [
                'code' => 0,
                'msg' => '发货成功',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '操作失败',
            ];
        }
    }

    /**
     * @deprecated 已废弃
     */
    private function sendMessage($order)
    {
        $user = User::findOne($order->user_id);
        if (!$user) {
            return;
        }
        /* @var FormId $form_id */
        $form_id = FormId::find()->where(['order_no' => $order->order_no])->orderBy('addtime DESC')->one();
        $wechat = $this->getWechat();
        if (!$wechat) {
            return;
        }
        if (!$form_id) {
            return;
        }
        $store = Store::findOne($this->store_id);
        if (!$store || !$store->order_send_tpl) {
            return;
        }

        $goods_list = OrderDetail::find()
            ->select('g.name,od.num')
            ->alias('od')->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where(['od.order_id' => $order->id, 'od.is_delete' => 0])->asArray()->all();

        $msg_title = '';
        foreach ($goods_list as $goods) {
            $msg_title .= $goods['name'];
        }


        //TODO  获取用户Token  2019年7月11日10:41:41
        $access_token = $this->wechat->getAccessToken();
        $api = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        $data = (object)[
            'touser' => $user->wechat_open_id,
            'template_id' => $store->order_send_tpl,
            'form_id' => $form_id->form_id,
            'page' => 'pages/order/order?status=2',
            'data' => (object)[
                'keyword1' => (object)[
                    'value' => $msg_title,
                    'color' => '#333333',
                ],
                'keyword2' => (object)[
                    'value' => $order->express,
                    'color' => '#333333',
                ],
                'keyword3' => (object)[
                    'value' => $order->express_no,
                    'color' => '#333333',
                ],
            ],
        ];
        $data = \Yii::$app->serializer->encode($data);
        $wechat->curl->post($api, $data);
        $res = json_decode($wechat->curl->response, true);
        if (!empty($res['errcode']) && $res['errcode'] != 0) {
            \Yii::warning("模板消息发送失败：\r\ndata=>{$data}\r\nresponse=>" . \Yii::$app->serializer->encode($res));
        }
    }

//    public function sendTestMessage()
//    {
//        $this->payMsg();
//    }

    //TODO  form_id  通知成功  再次通知会出现  41029  41029 form_id id以使用
    public  function  sendTestMessage()
    {
        \Yii::warning("进入支付SendMsg发送" ,'info');
        $access_token = 'pPESDrn3ndh5F0y4umvNpHyep5NGzJkj';
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
        $key1 = '2019-7-12 18:07:00';//发送的消息
        $key2 = '12';
        $key3 = '测试支付商品';
        $key4 = '这里是温馨提示语你的订单待支付';
        //这里一定要按照微信给的格式
        $data = array(
            "touser"=>'ohXJd5RjpwBp8-QwLBnpg6CXB1-A',
            "template_id"=>'ydx-ep98BVniTp63G8YjWDUzREGa11kEXvKKrFVUmbQ',
            "page"=>'pages/order/order?status=2',
            "form_id"=>'6c5fe6e11984467f9d42fd0368f018cf',
            "data"=>array(
                "keyword1"=>array(
                    "value"=>$key1,
                    "color"=>"#173177"
                ),
                "keyword2"=>array(
                    "value"=>$key2,
                    "color"=>"#173177"
                ),
                "keyword3"=>array(
                    "value"=>$key3,
                    "color"=>"#173177"
                ),
                "keyword4"=>array(
                    "value"=>$key4,
                    "color"=>"#173177"
                )
            ),
            //"emphasis_keyword"=>"keyword1.DATA",//需要进行加大的消息
        );
        \Yii::warning("模板消息发送失败Json111" ,'info');
        $res = $this->postCurl($url,$data,'json');//将data数组转换为json数据
        //logger::info("结果是".print_r($res,1));

        if($res){
            \Yii::warning("模板消息发送失败" ,'info');
            echo json_encode(array('state'=>4,'msg'=>$res));
        }else{
            echo json_encode(array('state'=>5,'msg'=>$res));
            \Yii::warning("进入支付SendMsg发送嘻嘻嘻" ,'info');
        }

    }

    public function postCurl($url,$data,$type)
    {
        \Yii::warning("模板消息发送失败Json2222222222" ,'info');
        if($type == 'json'){
            $data = json_encode($data);//对数组进行json编码
            $header= array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");
        }
        \Yii::warning("模板消息发送失败Json" ,'info');
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
        if(!empty($data)){
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        $res = curl_exec($curl);

        if(curl_errno($curl)){
            logger::info('Error+'.curl_error($curl));
        }
        curl_close($curl);

        return $res;
    }



    function curl_get($url)
    {
        //logger::info("测试消息推送curl_get");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return json_decode($data);//对数据进行json解码
    }
    //小程序消息推送统一返回access_token ,这里的access_token是你微信平台账户的access_token,
    function returnAsskey()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=yourAppId&secret=yourappseceret';//将appid和appseceret换成你自己的
        $ass_key = $this->curl_get($url);
        $a1 = $ass_key->access_token;
        return $a1;
    }

}
