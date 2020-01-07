<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/1
 * Time: 16:52
 */

namespace app\modules\api\models;

use Alipay\AlipayRequestFactory;
use app\hejiang\ApiResponse;
use app\models\alipay\MpConfig;
use app\models\common\api\CommonOrder;
use app\models\Coupon;
use app\models\IntegralLog;
use app\models\LotteryUser;
use app\models\Share;
use app\models\User;
use app\models\UserCouponSendLog;
use app\models\UserIntegralSendLog;
use app\modules\api\models\wxbdc\WXBizDataCrypt;
use Curl\Curl;
use Alipay\Exception\AlipayException;

class LoginForm extends ApiModel
{
    public $wechat_app;

    public $code;
    public $user_info;
    public $encrypted_data;
    public $iv;
    public $signature;

    public $store_id;

    public $share_user_id;//分销加盟商id

    public function rules()
    {
        return [
            [['wechat_app', 'code', 'user_info', 'encrypted_data', 'iv', 'signature'], 'required'],
        ];
    }

    public function loginAlipay()
    {
        try {
            $aop = $this->getAlipay();
        } catch (\InvalidArgumentException $ex) {
            return new ApiResponse(1, $ex->getMessage());
        }
        try {
            $request = AlipayRequestFactory::create('alipay.system.oauth.token', [
                'grant_type' => 'authorization_code',
                'code' => $this->code,
            ]);
            $response = $aop->execute($request);
            $dataToken = $response->getData();

            $request = AlipayRequestFactory::create('alipay.user.info.share', [
                'auth_token' => $dataToken['access_token'],
            ]);
            $response = $aop->execute($request);
            $dataInfo = $response->getData();

            $data = array_merge($dataToken, $dataInfo);
        } catch (AlipayException $ex) {
            return new ApiResponse(2, $ex->getMessage());
        }

        $user = User::findOne(['wechat_open_id' => $data['user_id'], 'store_id' => $this->store_id]);
        if (!$user) {
            $user = new User();
            $user->type = 1;
            $user->username = $data['user_id'];
            $user->password = \Yii::$app->security->generatePasswordHash(\Yii::$app->security->generateRandomString(), 5);
            $user->auth_key = \Yii::$app->security->generateRandomString();
            $user->access_token = \Yii::$app->security->generateRandomString();
            $user->addtime = time();
            $user->is_delete = 0;
            $user->wechat_open_id = $data['user_id'];
             $user->wechat_union_id = isset($data['unionId']) ? $data['unionId'] : '';//TODO:补充微信unionId  2019-05-15 17点39分
            $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nick_name']);
            $user->avatar_url = $data['avatar'] ? $data['avatar'] : \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/avatar.png';
            $user->store_id = $this->store_id;
            $user->platform = 1; // 支付宝
            $user->save();
        } else {
            $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nick_name']);
            $user->avatar_url = $data['avatar'];
            $user->wechat_union_id = isset($data['unionId']) ? $data['unionId'] : '';//TODO:补充微信unionId  2019-05-15 17点39分
            $user->save();
        }
        $share = Share::findOne(['user_id' => $user->parent_id]);
        $share_user = User::findOne(['id' => $share->user_id]);
        $data = [
            'access_token' => $user->access_token,
            'nickname' => $user->nickname,
            'avatar_url' => $user->avatar_url,
            'is_distributor' => $user->is_distributor ? $user->is_distributor : 0,
            'parent' => $share->id ? ($share->name ? $share->name : $share_user->nickname) : '总店',
            'id' => $user->id,
            'is_clerk' => $user->is_clerk === null ? 0 : $user->is_clerk,
            'integral' => $user->integral === null ? 0 : $user->integral,
            'money' => $user->money === null ? 0 : $user->money,
            'level' => $user->level,
            'blacklist' => $user->blacklist,
        ];
        return new ApiResponse(0, 'success', $data);
    }

    public function login()
    {
        $this->logger('LoginFormLoginFormLoginFormLoginFormLoginFormLoginForm');
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $this->logger('loginloginloginloginloginloginloginlogin');
        $res = $this->getOpenid($this->code);
        $this->logger($this->code);
        if (!$res || empty($res['openid'])) {
            return new ApiResponse(1, '获取用户OpenId失败', $res);
        }
        $session_key = $res['session_key'];
        $pc = new WXBizDataCrypt($this->wechat_app->app_id, $session_key);
        $errCode = $pc->decryptData($this->encrypted_data, $this->iv, $data);

        $this->logger('$errCode$errCode$errCode$errCode$errCode$errCode'.$errCode);
        if ($errCode == 0 || $errCode == -41003) {
            if ($errCode == -41003) {
                $user_info = json_decode($this->user_info, true);
                $data = [
                    'openId' => $res['openid'],
                    'nickName' => $user_info['nickName'],
                    'gender' => $user_info['gender'],
                    'city' => $user_info['city'],
                    'province' => $user_info['province'],
                    'country' => $user_info['country'],
                    'avatarUrl' => $user_info['avatarUrl'],
                    'unionId' => isset($res['unionid']) ? $res['unionid'] : '',
                ];
            } else {
                $data = json_decode($data, true);
            }
            $user = User::findOne(['wechat_open_id' => $data['openId'], 'store_id' => $this->store_id]);
//            if($this->share_user_id>0)
//            {
//                $user
//            }
              \Yii::warning('测试分销数字是否进入'.$this->share_user_id,'info');
            if (!$user) {
                $user = new User();
                $user->type = 1;
                $user->username = $data['openId'];
                $user->password = \Yii::$app->security->generatePasswordHash(\Yii::$app->security->generateRandomString(), 5);
                $user->auth_key = \Yii::$app->security->generateRandomString();
                $user->access_token = \Yii::$app->security->generateRandomString();
                $user->addtime = time();
                $user->is_delete = 0;
                $user->wechat_open_id = $data['openId'];
                $user->wechat_union_id = isset($data['unionId']) ? $data['unionId'] : '';
                $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nickName']);
                $user->avatar_url = $data['avatarUrl'] ? $data['avatarUrl'] : \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/avatar.png';
                $user->store_id = $this->store_id;
                $user->platform = 0; // 微信
                $user->save();
                $same_user = User::find()->select('id')->where([
                    'AND',
                    [
                        'store_id' => $this->store_id,
                        'wechat_open_id' => $data['openId'],
                        'is_delete' => 0,
                    ],
                    ['<', 'id', $user->id],
                ])->one();
                if ($same_user) {
                    $user->delete();
                    $user = null;
                    $user = $same_user;
                }

            } else {
                $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nickName']);
                $user->avatar_url = $data['avatarUrl'];
                $user->wechat_union_id = isset($res['unionid']) ? $res['unionid'] : '';
                $user->save();

                \Yii::warning('账户信息同步','info');
                //TODO：查询CRM新用户流量分发进入、处理券同步自动补充业务校验   2019-06-05-09点37分
                $user_coupon_list = UserCouponSendLog::find()->select('coupon_id')->where(['wechat_union_id' => $data['unionId'], 'store_id' => 1])->all();
                foreach ($user_coupon_list as $u) {
                    Coupon::userAddCoupon($user->id, $u->coupon_id);
                    // $this->logger('新用户同步优惠券');
                }

                //TODO：查询CRM新用户流量分发进入、处理积分同步自动补充业务校验   2019-06-05-15点34分
                $user_integral_list = UserIntegralSendLog::find()->select('integral,content')->where(['wechat_union_id' => $data['unionId'], 'store_id' => 1])->asArray()->all();
                foreach ($user_integral_list as $index => $value) {
                   // \Yii::warning('账户信息同步Test'.$value['content'],'info');
                    IntegralLog::userAddIntegralLog($user->id, $value['integral'],$value['content']);
                    // $this->logger('新用户同步积分');
                }
            }
            $share = Share::findOne(['user_id' => $user->parent_id]);
            $share_user = User::findOne(['id' => $share->user_id]);
            $data = [
                'access_token' => $user->access_token,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'is_distributor' => $user->is_distributor ? $user->is_distributor : 0,
                'parent' => $share->id ? ($share->name ? $share->name : $share_user->nickname) : '总店',
                'id' => $user->id,
                'is_clerk' => $user->is_clerk === null ? 0 : $user->is_clerk,
                'integral' => $user->integral === null ? 0 : $user->integral,
                'money' => $user->money === null ? 0 : $user->money,
                'errCode' => $errCode,
                'binding' => $user->binding,
                'level' => $user->level,
                'blacklist' => $user->blacklist,
            ];
            return new ApiResponse(0, 'success', $data);
        } else {
            return new ApiResponse(1, '登录失败', $errCode);
        }
    }


    /**
     * 绑定分销信息
     */
    public function bindParentId()
    {

        $user_id= $this->getCurrentUserId();//这里如果是后台提供的分销码、将无法提取当前用户id
        //CommonOrder::changeParentUserId($this->share_user_id);
        \Yii::warning($user_id.'<==$user_id==Test-bindParentId====>share_user_id===>'.$this->share_user_id,'info');
         $this->logger($user_id.'<==$user_id==Test-bindParentId====>share_user_id===>'.$this->share_user_id);
         if(!empty($user_id))
         {
             \Yii::warning($user_id.'<==admin====share_user_id===>'.$this->share_user_id,'info');
             $this->logger($user_id.'<==admin====share_user_id===>'.$this->share_user_id);
             //后台分销码进入   自己不能分销自己
             if($this->share_user_id>0&&$user_id!=$this->share_user_id)
             {
                 CommonOrder::changeParentUserId($this->share_user_id);
             }
         }
//        if($user_id>0)
//        {
//            if($this->share_user_id>0)
//            {
//                CommonOrder::changeParentUserId($this->share_user_id);
//                // CommonOrder::changeParentId($this->share_user_id);
//                //$user->parent_id = $this->share_user_id;
//                //$user->parent_binding_validity = time();//重新绑定时间
//                //$user->save();
//            }
//
//        }
    }

    private function getOpenid($code)
    {
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->wechat_app->app_id}&secret={$this->wechat_app->app_secret}&js_code={$code}&grant_type=authorization_code";
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->get($api);
        $res = $curl->response;
        $res = json_decode($res, true);
        return $res;
    }

   public function logger($log_content)
    {
        $max_size = 100000;
        $log_filename = "raw.log";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }
}
