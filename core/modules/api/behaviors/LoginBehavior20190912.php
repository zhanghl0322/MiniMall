<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/27
 * Time: 1:05
 */

namespace app\modules\api\behaviors;

use app\hejiang\ApiResponse;
use yii\base\ActionFilter;
use yii\web\Controller;
use app\hejiang\BaseApiResponse;
use app\models\User;

class LoginBehavior extends ActionFilter
{
    public function beforeAction($e)
    {
        $access_token = \Yii::$app->request->get('access_token');
        if (!$access_token) {
            $access_token = \Yii::$app->request->post('access_token');
        }
        if (!$access_token) {
            \Yii::$app->response->data = new ApiResponse(-1, 'access_token 不能为空');
            return false;
        }
        if (\Yii::$app->user->loginByAccessToken($access_token)) {
            $userEnt = User::findOne(['access_token' => $access_token]);
            $this->logger('$access_token......'.$access_token);
            if (empty($userEnt->wechat_union_id)) {
            	 \Yii::$app->response->data = new ApiResponse(-1, 'wechat_union_id 不能为空');
            	  return false;
            }
            return true;
        } else {
            \Yii::$app->response->data = new ApiResponse(-1, '登录失败,');
            return false;
        }
    }
    public function logger($log_content)
    {
        $max_size = 100000;
        $log_filename = "raw.log";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }
}
