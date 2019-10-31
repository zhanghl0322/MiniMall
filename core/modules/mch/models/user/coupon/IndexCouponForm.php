<?php
/**
 * link: http://www.zjhejiang.com/
 * copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 * author: wxf
 */

namespace app\modules\mch\models\user\coupon;

use app\models\Cat;
use app\models\Coupon;
use app\models\CouponAutoSend;
use app\models\User;
use app\models\UserCoupon;
use app\modules\mch\models\MchModel;
use app\modules\mch\models\UserExportList;
use app\modules\mch\models\CouponExportList;//TODO 券使用状态记录  2019年9月9日10:17:17

use yii\data\Pagination;

class IndexCouponForm extends MchModel
{
    public $userId = 0;
    public $type = '';
    public $fields;
    public $flag;
    public $date_start;
    public $date_end;
    public $is_use;
    public $coupon_type;
    public $coupon_name;

    public function rules()
    {
        return [
            [['flag', 'date_start', 'date_end'], 'trim'],
            [['fields'], 'safe']
        ];
    }

    //TODO 查询优惠券列表
    public function getCouponSendList()
    {
        $query = UserCoupon::find()->alias('uc')->leftJoin(['c' => Coupon::tableName()], 'uc.coupon_id=c.id')->leftJoin(['cas' => CouponAutoSend::tableName()], 'cas.id=uc.coupon_auto_send_id')
            ->leftJoin(['us' => User::tableName()], 'us.id=uc.user_id')
            ->where(['uc.store_id' => 1]);//查询平台店铺下的所有券

        //TODO 查询时间范围限制
        if ($this->date_start) {
            $query->andWhere(['>', 'uc.addtime', strtotime($this->date_start)]);
        }

        \Yii::warning($this->is_use.'===========1545454======'.$this->coupon_type,'info');
        if ($this->date_end) {
            $query->andWhere(['<', 'uc.addtime', strtotime($this->date_end)]);
        }
        //TODO 是否使用状态过滤
        if ($this->is_use!=''&&$this->is_use==0) {
            $query->andWhere([
                'uc.is_use' => 0
            ]);
        }
        if ($this->is_use!=''&&$this->is_use==1) {
            $query->andWhere([
                'uc.is_use' => 1
            ]);
        }
        if ($this->coupon_type!='') {
            $query->andWhere([
                'uc.type' => $this->coupon_type
            ]);
        }
        \Yii::warning($this->coupon_name.'模糊查询名称'.$this->coupon_type,'info');
        if ($this->coupon_name) {
            $query->andWhere(['LIKE', 'c.name', $this->coupon_name]);
        }

        \Yii::warning($this->coupon_type.'试一试导出'.$this->flag,'info');
//==========================新增优惠券EXPORT ===============================
        if ($this->flag == "EXPORT") {
            $userExport = new CouponExportList();
            $userExport->fields = $this->fields;
            $userExport->couponListForm($query);
        }
//=========================================================
        //TODO 优惠券查询语句
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => 20]);
        $list = $query->orderBy('uc.id DESC')
            ->limit($pagination->limit)
            ->offset($pagination->offset) ->select('uc.id user_coupon_id,c.sub_price,c.min_price,uc.begin_time,uc.end_time,uc.is_use,uc.is_expire,cas.event,uc.type,c.appoint_type,c.cat_id_list,c.goods_id_list,c.name as couponname,uc.addtime,us.nickname')->asArray()
            ->all();


        $events = [
            0 => '平台发放',
            1 => '分享红包',
            2 => '购物返券',
            3 => '领券中心',
            4 => '积分兑换',
        ];
        foreach ($list as $i => $item) {
            $list[$i]['status'] = 0;
            if ($item['is_use']) {
                $list[$i]['status'] = 1;
            }
            if ($item['is_expire']) {
                $list[$i]['status'] = 2;
            }
            $list[$i]['min_price_desc'] = $item['min_price'] == 0 ? '无门槛' : '满' . $item['min_price'] . '元可用';
            $list[$i]['begin_time'] = date('Y.m.d H:i', $item['begin_time']);
            $list[$i]['end_time'] = date('Y.m.d H:i', $item['end_time']);
            if (!$item['event']) {
                if ($item['type'] == 2) {
                    $list[$i]['event'] = $item['event'] = 3;
                } elseif ($item['type'] == 0) {
                    $list[$i]['event'] = $item['event'] = 0;
                } else {
                    $list[$i]['event'] = $item['event'] = 4;
                }
            }
            $list[$i]['event_desc'] = $events[$item['event']];

            if ($list[$i]['appoint_type'] == 1) {
                $list[$i]['cat'] = Cat::find()->select('name')->where(['store_id'=>1,'is_delete'=>0,'id'=>json_decode($item['cat_id_list'])])->asArray()->all();
                $list[$i]['goods'] = [];
            } elseif ($list[$i]['appoint_type'] == 2) {
                $list[$i]['goods'] = json_decode($list[$i]['goods_id_list']);
                $list[$i]['cat'] = [];
            } else {
                $list[$i]['goods'] = [];
                $list[$i]['cat'] = [];
            }
        }
        return [
            'list' => $list,
            'pagination' => $pagination
        ];
    }
}
