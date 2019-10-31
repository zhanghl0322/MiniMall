<?php
/**
 * link: http://www.zjhejiang.com/
 * copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 * author: wxf
 */

namespace app\models\task\order;

use app\hejiang\task\TaskRunnable;
use app\models\ActionLog;
use app\models\common\CommonGoodsAttr;
use app\models\Goods;
use app\models\MiaoshaGoods;
use app\models\Model;
use app\models\MsGoods;
use app\models\MsOrder;
use app\models\Order;
use app\models\OrderDetail;
use app\models\PtGoods;
use app\models\PtOrder;
use app\models\PtOrderDetail;
use app\models\Register;
use app\models\Store;
use app\models\User;

//TODO 新增Order自动推送服务通知  Allon  2019年7月15日14:52:10
class OrderAutoSendMsg extends TaskRunnable
{
    public $store;
    public $time;
    public $params = [];
    public $orderTypeName;

    public function run($params = [])
    {
        $this->store = Store::findOne($params['store_id']);
        $this->time = time();
        $this->params = $params;
        $res = $this->storeOrder();
        $this->orderTypeName = '商城';
        return $res;
    }

    /**
     * 商城订单自动逾期15分钟自动推送服务通知
     * @return bool
     * @throws \Exception
     */
    public function storeOrder()
    {
        \Yii::warning("==任务执行中............==".$this->time ,'info');
    }

}