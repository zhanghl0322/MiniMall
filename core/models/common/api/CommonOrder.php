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
            \Yii::warning('验证是否无分销归属','info');
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
            if(\Yii::$app->user->id==$parentId)
            {
                //默认自购买 归属21
                $form->parent_id = 21;//车海洋总部
                $user->parent_id =21;
            }
            $form->condition = 1;
            $form->save();

            $user->parent_user_id = $parentId; //该父级ID实实支付变化
            //保护期=设置有效期天数+当前系统时间
//            $user->parent_binding_validity=time(); //设置分销保护期时间  2019年11月5日15:12:33
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
        $_user=User::findOne(\Yii::$app->user->id);//查询用户信息
        if (!$_user) {
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

        //***************分销归属********************
        //2019年11月9日09:32:11
        $parent = User::findOne(\Yii::$app->user->id); //上级
        if ($parent->parent_id) {
            \Yii::warning('*******本级父级ID有效用户存在*******' . $parent->nickname, 'info');
            $binding_pernet_id = $parent->parent_id;
            $parent_1 = User::findOne($parent->parent_id); //上上级
            \Yii::warning('*******上级父级ID有效用户存在*******' . $parent_1->nickname, 'info');
            if ($parent_1->parent_id) {

                $parent_2 = User::findOne($parent_1->parent_id); //上上级
                \Yii::warning($parent_2->parent_id.'*******上上级父级ID有效用户存在*******' . $parent_2->nickname, 'info');
                \Yii::warning($parentId.'系统设定'.$parent_2->parent_id, 'info');
                if ($parent_2->parent_id==0||$parent_2->parent_id>0) {

                    //1.0 应陈总、周总【2019年12月23日11点】会议、分销关系调整
                    //2.0 会员自扫加盟商分销码进入购买、默认(含分销上级)依旧重新依码归属、不在进行包含周期有效期限制、
                    //3.0 21为车海洋设置的代理分销商、实际正式id以线上为准、
                    if ($parentId>0&&$parentId!=21) {
                        $user->parent_id = $parentId;
                        $user->parent_binding_validity = time();//重新绑定时间
                        \Yii::warning($parentId.'变更系统设定人员归属'.$user->parent_binding_validity, 'info');
                    }
                    //验证是否超出系统设定保护期  2019年11月6日14:15:48
                    //if (time() > ($user->parent_binding_validity +  + ($store->share_validity_time * 86400))) {
                     //PS       总店
                     //一级    泡泡龙先生
                     //二级   亮        余
                    //会员(变动层) Allon
                    //如果是总级、那么说明该用户是处于分销最底层的弱关系层可以变更关系
                    \Yii::warning($parent_2->parent_id.'是不是总级哦'.$user->parent_binding_validity, 'info');
                }
                else
                {
                    //总店->加盟商A->加盟商员工A
                    \Yii::warning('*******总店->加盟商A->加盟商员工A*******' . $parent->nickname, 'info');
                }
                \Yii::warning('*******上上级父级ID有效用户存在*******' . $parent->nickname, 'info');

            } else {
                //总店->加盟商A->
                \Yii::warning('*******总店->加盟商A->*******' . $parent->parent_id, 'info');
            }
        } else {
            //归属总店
            \Yii::warning('*******低层无效用户*******' . $parentId, 'info');
        }
        //********************End***********************

        $user->save();
        return $user;
    }

    /**
     * @param $parentId
     * 变更上级绑定id
     */
    public static function changeParentUserId($parentId)
    {
        $parent_user = User::find()->select(['u.nickname as name', 'IFNULL(u1.nickname ,\'无归属\') AS \'parent_id_1_name\'', 'IFNULL(u2.nickname ,\'无归属\') AS \'parent_id_2_name\'', 'u.id AS \'user_id\', IFNULL(u1.id,0) AS \'parent_id_1\'','IFNULL(u2.id,0) AS \'parent_id_2\''])->alias('u')
            ->leftJoin(['u1' => User::tableName()], 'u.parent_id=u1.id')
            ->leftJoin(['u2' => User::tableName()], 'u1.parent_id=u2.id')
            ->where('u.is_distributor=:is_distributor', [':is_distributor' => 1])
            ->andwhere(['or' , ['u1.parent_id' => 0] , ['u1.parent_id' =>null]])
            ->andwhere(['or' , ['u2.parent_id' => 0] , ['u2.parent_id' =>null]])
            ->andwhere('u.id=:id', [':id' => $parentId])
            ->asArray()->one();
        $current_user= User::findOne(\Yii::$app->user->id); //当前用户id
        $share_mark=CommonOrder::isShare($current_user->id);
        \Yii::warning('console'.$share_mark,'info');
        if($share_mark=='parent_id_3')
        {
            //验证是否是加盟商+加盟商员工
                $current_user->parent_id = $parentId;
                $current_user->parent_binding_validity = time();//重新绑定时间
                $current_user->save();
//            if(!empty($parent_user))
//            {
//                //验证是否是加盟商+加盟商员工
//                $current_user->parent_id = $parentId;
//                $current_user->parent_binding_validity = time();//重新绑定时间
//                $current_user->save();
//            }
        }

    }

    public static function changeParentUserId_1($parentId)
    {
        $user_list = User::find()->select(['u.nickname as name', 'IFNULL(u1.nickname ,\'无归属\') AS \'parent_id_1_name\'', 'IFNULL(u2.nickname ,\'无归属\') AS \'parent_id_2_name\'', 'u.id AS \'user_id\', IFNULL(u1.id,0) AS \'parent_id_1\'','IFNULL(u2.id,0) AS \'parent_id_2\''])->alias('u')
            ->leftJoin(['u1' => User::tableName()], 'u.parent_id=u1.id')
            ->leftJoin(['u2' => User::tableName()], 'u1.parent_id=u2.id')
            ->where('u.is_distributor=:is_distributor', [':is_distributor' => 1])
            ->andwhere(['or' , ['u1.parent_id' => 0] , ['u1.parent_id' =>null]])
            ->andwhere(['or' , ['u2.parent_id' => 0] , ['u2.parent_id' =>null]])
            ->asArray()->all();
        $current_user= User::findOne(\Yii::$app->user->id); //当前用户id
        foreach ($user_list as $item) {
            $item['name'];
            $item['parent_id_1_name'];
            $item['parent_id_2_name'];
            if(['u.id']!=$parentId)
            {
                //验证是否是加盟商+加盟商员工
                $current_user->parent_id = $parentId;
                $current_user->parent_binding_validity = time();//重新绑定时间
                $current_user->save();
            }
            \Yii::warning($item['name'].'测试分销语句'.$item['parent_id_1_name'].'===<parent_id_1_name====parent_id_2_name>=='.$item['parent_id_2_name'],'info');
        }
    }


    /**
     * @param $user_id
     * 验证是否是分销商
     * PS:是否是总店下面加盟商
     */
    public static function isShare($user_id)
    {
        $user = User::find()->select(['u.nickname as name', 'IFNULL(u1.nickname ,\'无归属\') AS \'parent_id_1_name\'', 'IFNULL(u2.nickname ,\'无归属\') AS \'parent_id_2_name\'', 'u.id AS \'user_id\', IFNULL(u1.id,0) AS \'parent_id_1\'','IFNULL(u2.id,0) AS \'parent_id_2\''])->alias('u')
            ->leftJoin(['u1' => User::tableName()], 'u.parent_id=u1.id')
            ->leftJoin(['u2' => User::tableName()], 'u1.parent_id=u2.id')
            ->where('u.is_distributor=:is_distributor', [':is_distributor' => 1])
            ->andwhere(['or' , ['u1.parent_id' => 0] , ['u1.parent_id' =>null]])
            ->andwhere(['or' , ['u2.parent_id' => 0] , ['u2.parent_id' =>null]])
            ->andwhere('u.id=:id', [':id' => $user_id])
            ->asArray()->one();

        //普通
        if(empty($user))
        {
            return 'parent_id_3';
        }else
        {
            if($user['parent_id_1']==0&&$user['parent_id_2']==0)
            {
                //总店下加盟商标识
                return 'parent_id_1';
            }
            if ($user['parent_id_1']>0&&$user['parent_id_2']==0)
            {
                //总店下加盟商员工标识
                return 'parent_id_2';
            }
        }
    }

}