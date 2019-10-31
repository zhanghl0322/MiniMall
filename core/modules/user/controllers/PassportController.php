<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2018/3/9
 * Time: 10:26
 */

namespace app\modules\user\controllers;

use app\hejiang\ApiCode;
use app\models\Mch;
use app\models\Store;
use app\models\StorePermission;
use app\models\User;
use app\models\UserAuthLogin;
use app\utils\GenerateShareQrcode;
use yii\helpers\VarDumper;
use yii\web\Response;

class PassportController extends Controller
{
    public $layout = 'passport';

    public function actionLogin($_platform = null)
    {
        $m = new UserAuthLogin();
        $img_url = null;
        $error = null;
        if ($_platform) {
            $m->store_id = $this->store->id;
            if ($_platform == 'my') {
                $m->token = 'token=' . md5(uniqid());
            } else {
                $m->token = md5(uniqid());
            }
            $m->addtime = time();
            $m->save();
            $res = GenerateShareQrcode::getQrcode($this->store->id, $m->token, 430, 'pages/web/login/login');
            if ($res['code'] == 0) {
                $img_path = str_replace('\\', '/', $res['file_path']);
                $img_url = mb_substr(\Yii::$app->request->baseUrl, 0, -4) . '/' . mb_substr($img_path, mb_stripos($img_path, 'runtime/image/'));
            } else {
                $error = $res['msg'];
            }
        }
        \Yii::warning('登陆地址'.$img_url.md5(uniqid()),'info');
        $store = Store::findone(\Yii::$app->request->get('store_id'));

        $auth = StorePermission::getOpenPermissionList($store);
        $isAlipay = 0;
        foreach ($auth as $item) {
            if ($item === 'alipay') {
                $isAlipay = 1;
            }
        }

        return $this->render('login', [
            'error' => $error,
            'token' => $m->token,
            'img_url' => $img_url,
            '_platform' => $_platform,
            'isAlipay' => $isAlipay,
        ]);
    }

    public function actionCheckLogin($token)
    {
        \Yii::warning('验证登陆Token'.$token,'info');
        if (!$token) {
            return [
                'code' => 1,
                'msg' => 'token不能为空',
            ];
        }
      // $token='60f0df076d323449077d5eb95978a5c0'; //TODO 在这里切换用户Token  可实现 模拟登陆
        for ($i = 0; $i < 3; $i++) {
            $m = UserAuthLogin::findOne(['token' => $token]);
            if (!$m) {
                return [
                    'code' => 1,
                    'msg' => '错误的token',
                ];
            }
            if ($m->is_pass == 0) {
                sleep(3);
            }
            if ($m->is_pass == 1) {
                $user = User::findOne($m->user_id);
                \Yii::$app->user->login($user);
                $mch = Mch::find()->where(['store_id' => $this->store->id, 'user_id' => $m->user_id])->one();
                if ($mch->is_open === Mch::IS_OPEN_FALSE) {
                    return [
                        'code' => ApiCode::CODE_ERROR,
                        'msg' => '店铺已被关闭,请联系管理员'
                    ];
                }

                \Yii::$app->session->set('store_id', $this->store->id);
                return [
                    'code' => ApiCode::CODE_SUCCESS,
                    'msg' => '登录成功',
                ];
            }
        }
        return [
            'code' => -1,
            'msg' => '请扫描小程序码登录',
        ];
    }

    //TODO 新增普通用户登陆验证方式  Allon   2019-07-01  15点17分
    public function actionActionLogin()
    {
        $user_name= \Yii::$app->request->get('user_name');
        $password= \Yii::$app->request->get('password');
        if (!$user_name) {
            return [
                'code' => 1,
                'msg' => 'user_name不能为空',
            ];
        }
        if (!$password) {
            return [
                'code' => 1,
                'msg' => 'password不能为空',
            ];
        }
        //$mch_tel = Mch::find()->where(['store_id' => $this->store->id, 'tel' => $user_name])->one();
        $mch_tel = Mch::find()->where(['store_id' => $this->store->id, 'tel' => $user_name,'summary' => $password])->one();
        if (!$mch_tel) {
            return [
                'code' => 1,
                'msg' => '用户名跟密码不存在',
            ];
        }
        //TODO 在这里切换用户Token  可实现 模拟登陆
        for ($i = 0; $i < 3; $i++) {
            $m = UserAuthLogin::findOne(['user_id' => $mch_tel->user_id]);
            if (!$m) {
                return [
                    'code' => 1,
                    'msg' => '错误的token',
                ];
            }
            if ($m->is_pass == 0) {
                sleep(3);
            }
            if ($m->is_pass == 1) {
                $user = User::findOne($m->user_id);
                \Yii::$app->user->login($user);
                $mch = Mch::find()->where(['store_id' => $this->store->id, 'user_id' => $m->user_id])->one();
                if ($mch->is_open === Mch::IS_OPEN_FALSE) {
                    return [
                        'code' => ApiCode::CODE_ERROR,
                        'msg' => '店铺已被关闭,请联系管理员'
                    ];
                }

                \Yii::$app->session->set('store_id', $this->store->id);
                return [
                    'code' => ApiCode::CODE_SUCCESS,
                    'msg' => '登录成功',
                ];
            }
        }
        return [
            'code' => -1,
            'msg' => '请扫描小程序码登录',
        ];
    }

    public function actionLogout()
    {
        \Yii::$app->user->logout();
        \Yii::$app->response->redirect(\Yii::$app->urlManager->createUrl(['user']))->send();
    }
}
