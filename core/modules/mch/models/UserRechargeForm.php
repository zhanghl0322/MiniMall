<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/8
 * Time: 16:30
 */

namespace app\modules\mch\models;

use app\models\IntegralLog;
use app\models\User;
use app\models\UserAccountLog;

class UserRechargeForm extends MchModel
{
    public $store_id;
    public $admin;

    public $user_id;
    public $type;
    public $rechargeType;
    public $money;
    public $pic_url;
    public $explain;

    public function rules()
    {
        return [
            [['user_id', 'type', 'rechargeType','money'], 'integer'],//TODO 由于字段类型不兼容 所有统一 使用integer
            //[['money'], 'number','min'=>0, 'max'=>99999999.99],
            [['pic_url', 'explain'], 'trim'],
            [['pic_url', 'explain'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'money' => '输入金额',
            'pic_url' => '图片',
            'explain' => '说明',
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $user = User::findOne($this->user_id);
        if (!$user) {
            return [
                'code' => 1,
                'msg' => '用户不存在'
            ];
        }
        $this->money = floatval($this->money);
        if ($this->money < 0) {
            return [
                'code' => 1,
                'msg' => '输入数值不能小于0'
            ];
        }

        $integralLog = new IntegralLog();
        $integralLog->store_id = $this->store_id;
        $integralLog->user_id = $this->user_id;
        $integralLog->pic_url = $this->pic_url;
        $integralLog->explain = $this->explain;

        $userAccountLog = new UserAccountLog();
        $userAccountLog->user_id = $user->id;

        switch ($this->rechargeType) {
            case 1:
                $user->money += $this->money;
                $integralLog->content = "管理员： " . $this->admin->username . " 后台操作账号：" . $user->nickname . " 余额充值：" . $this->money . " 元";
                $userAccountLog->desc = "管理员： " . $this->admin->username . " 后台操作账号：" . $user->nickname . " 余额充值：" . $this->money . " 元";
                $userAccountLog->type = 1;
                break;
            case 2:
                if ($user->money < $this->money) {
                    return [
                        'code' => 1,
                        'msg' => '扣除数值大于当前用户余额'
                    ];
                }
                $user->money -= $this->money;
                $integralLog->content = "管理员： " . $this->admin->username . " 后台操作账号：" . $user->nickname . " 余额扣除：" . $this->money . " 元";
                $userAccountLog->desc = "管理员： " . $this->admin->username . " 后台操作账号：" . $user->nickname . " 余额扣除：" . $this->money . " 元";
                $userAccountLog->type = 2;
                break;
            default:
                return [
                    'code' => 1,
                    'msg' => '网络异常，请刷新重试'
                ];
        }
        $t = \Yii::$app->db->beginTransaction();
        if ($user->save()) {
            $integralLog->integral = $this->money;
           // $integralLog->integral = 1; //TODO 类型不兼容 2019年8月24日09:23:18
             $integralLog->username = trim($user->nickname) ? $user->nickname : '未知';
            $integralLog->operator = $this->admin->username;
            $integralLog->operator_id = $this->admin->id;
            $integralLog->addtime = time();
            $integralLog->type = 1;
            if (!$integralLog->save()) {
                $t->rollBack();
                return $this->getErrorResponse($integralLog);
            }
            $userAccountLog->price = $this->money;
            $userAccountLog->order_type = 7;
            $userAccountLog->order_id = $integralLog->id;
            $userAccountLog->addtime = time();
            if (!$userAccountLog->save()) {
                $t->rollBack();
                return $this->getErrorResponse($userAccountLog);
            } else {
                $t->commit();
                return [
                    'code' => 0,
                    'msg' => '操作成功'
                ];
            }
        } else {
            $t->rollBack();
            return $this->getErrorResponse($user);
        }
    }

    public function ExchangeMoney()
    {
//        $requestData= "{'OrderCode':'','ShipperCode':'ZTO','LogisticCode':'75156353341708'}";
//
//        $datas = array(
//            'EBusinessID' => '1539698',
//            'RequestType' => '1002',
//            'RequestData' => urlencode($requestData) ,
//            'DataType' => '2',
//        );
//        $datas['DataSign'] = $this->encrypt($requestData, '8e11491a-2c80-4ae6-ac1c-e32d90d99e13');
//        $result=$this->sendPost('http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx', $datas);

        $url = "http://139.199.88.207:16124/oauth2/token";
        $post_data = array('username' => 'CrmApi', 'password' => '4008366899', 'grant_type' => 'password');
        // post的变量
        $result=$this->sendPost($url,$post_data);
        //根据公司业务处理返回的信息......

        \Yii::warning('*****************sendPost*******************'.$result , 'info');
    }

    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }
    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
}
