<?php
/**
 * link: http://www.zjhejiang.com/
 * copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 * author: wxf
 */

namespace app\models\common\api;

use app\models\Goods;
use app\models\Model;
use app\models\MsGoods;
use app\models\Option;
use app\models\OrderDetail;
use app\models\PtOrderDetail;
use app\models\Store;
use app\models\User;
use app\models\YyGoods;
use app\modules\api\models\BindForm;
use Curl\Curl;

class CommonOrder
{
    /**
     * 持续更新...
     * 下单前的检测
     */
    public static function checkOrder($other = [])
    {
        $user = \Yii::$app->user->identity;

        if ($user->blacklist) {
            return [
                'code' => 1,
                'msg' => '无法下单'
            ];
        }

        if (isset($other['mobile']) && $other['mobile']) {
            $option = Option::getList('mobile_verify', \Yii::$app->controller->store->id, 'admin', 1);
            if ($option['mobile_verify']) {
                if (!preg_match(Model::MOBILE_VERIFY, $other['mobile'])) {
                    return [
                        'code' => 1,
                        'msg' => '请输入正确的手机号'
                    ];
                }
            }
        }
    }

    //TODO 存储分销关系  2019年8月1日14:37:39  Allon
    /**
     * 分销 保存上级的ID(用于先成为上下级，再成为分销商)
     * @param $parentId
     * @return static
     */
    public static function saveParentId($parentId)
    {
        if (!$parentId) {
            return;
        }
        $store=Store::findOne(\Yii::$app->store->id);//查询店铺设置有效期
        //店铺不存在
        if (!$store) {
            return;
        }
        // 父级用户不存在
        $parentUser = User::findOne($parentId);
        if (!$parentUser) {
            return;
        }

        $user = \Yii::$app->user->identity;

        if ($user) {
            $form = new BindForm();
            $form->store_id = \Yii::$app->store->id;
            $form->user_id = \Yii::$app->user->id;
            $form->parent_id = $parentId;
            $form->condition = 1;
            $form->save();

            $user->parent_user_id = $parentId; //该父级ID实实支付变化
            //保护期=设置有效期天数+当前系统时间
            $user->parent_binding_validity=time(); //设置分销保护期时间  2019年11月5日15:12:33
            $user->save();
        }

        return $user;
    }

    /**
     * 支付订单、变更分销归属
     * @param $parentId
     * @return void|\yii\web\IdentityInterface|null
     */
    public static function changeParentId($parentId)
    {
        //PS：这里可能造成一个回环分销
        \Yii::warning($parentId.'***********changeParentId************','info');
        if (!$parentId) {
            return;
        }
        $store=Store::findOne(\Yii::$app->store->id);//查询店铺设置有效期
        //店铺不存在
        if (!$store) {
            return;
        }
        // 父级用户不存在
        $parentUser = User::findOne($parentId);
        if (!$parentUser) {
            return;
        }

        \Yii::warning('***********changeParentIdV22222***************','info');
        $user = \Yii::$app->user->identity;
        //验证是否超出系统设定保护期  2019年11月6日14:15:48
        if(time()>($parentUser->parent_binding_validity+($store->share_validity_time*86400)))
        {
            $user->parent_id = $parentId;
            \Yii::warning('超出系统设定保护期、可变更系统设定人员归属','info');
        }
//        $user->parent_user_id = $parentId;
        $user->save();
        return $user;
    }
}