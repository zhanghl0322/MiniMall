<?php

/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/20
 * Time: 14:27
 */

namespace app\modules\mch\controllers;

use app\models\common\admin\order\CommonUpdateAddress;
use app\models\common\api\CommonShoppingList;
use app\models\Express;
use app\models\Order;
use app\models\Shop;
use app\models\User;
use app\models\WechatTplMsgSender;
use app\modules\api\models\OrderRevokeForm;
use app\modules\mch\models\ExportList;
use app\modules\mch\models\order\OrderClerkForm;
use app\modules\mch\models\order\OrderDeleteForm;
use app\modules\mch\models\OrderDetailForm;
use app\modules\mch\models\OrderListForm;
use app\modules\mch\models\OrderPriceForm;
use app\modules\mch\models\OrderRefundForm;
use app\modules\mch\models\OrderRefundListForm;
use app\modules\mch\models\OrderSendForm;
use app\modules\mch\models\PrintForm;
use app\modules\mch\models\StoreDataForm;
use app\modules\mch\extensions\Export;
use app\utils\PinterOrder;
use app\utils\TaskCreate;
use yii\web\UploadedFile;
use app\models\RefundAddress;
use yii\helpers\ArrayHelper;

class OrderController extends Controller
{
    public function actionIndex($is_offline = null)
    {
        \Yii::warning('----三生三世1111==》》》》》》》----','info');
        // 获取可导出数据
        $f = new ExportList();
        $exportList = $f->getList();

        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $data = $form->search();

        $store_data_form = new StoreDataForm();
        $store_data_form->store_id = $this->store->id;
        $store_data_form->is_offline = \Yii::$app->request->get('is_offline');
        $user_id = \Yii::$app->request->get('user_id');
        $clerk_id = \Yii::$app->request->get('clerk_id');
        $shop_id = \Yii::$app->request->get('shop_id');
        $store_data_form->user_id = $user_id;
        $store_data_form->clerk_id = $clerk_id;
        $store_data_form->shop_id = $shop_id;
        if ($user_id) {
            $user = User::findOne(['store_id' => $this->store->id, 'id' => $user_id]);
        }
        if ($clerk_id) {
            $clerk = User::findOne(['store_id' => $this->store->id, 'id' => $clerk_id]);
        }
        if ($shop_id) {
            $shop = Shop::findOne(['store_id' => $this->store->id, 'id' => $shop_id]);
        }

        return $this->render('index', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            'store_data' => $store_data_form->search(),
            'express_list' => $this->getExpressList(),
            'user' => $user,
            'clerk' => $clerk,
            'shop' => $shop,
            'exportList' => \Yii::$app->serializer->encode($exportList),
        ]);
    }


    //TODO 订单集合列表  （秒杀  拼团  正常） 2019-06-25  16点24分
    public function actionOrderList()
    {

        //搜索栏参数
        $type="". \Yii::$app->request->get('platform')."";//订单类型
        $status="". \Yii::$app->request->get('status')."";//订单状态
        $order_no="". \Yii::$app->request->get('keyword')."";//订单号
        $export_order="". \Yii::$app->request->get('export_order')."";//导出订单

        $pageindex=\Yii::$app->request->get('page')-1;//MYSQL 默认 0起
        \Yii::warning($status.'----进入订单状态值----'.\Yii::$app->request->baseUrl,'info');
        // 获取可导出数据
        $f = new ExportList();
        $exportList = $f->getAllList();//自定义列名导出

        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $data = $form->search();

        $store_data_form = new StoreDataForm();
        $store_data_form->store_id = $this->store->id;
        $store_data_form->is_offline = \Yii::$app->request->get('is_offline');
        $user_id = \Yii::$app->request->get('user_id');
        $clerk_id = \Yii::$app->request->get('clerk_id');
        $shop_id = \Yii::$app->request->get('shop_id');
        $store_data_form->user_id = $user_id;
        $store_data_form->clerk_id = $clerk_id;
        $store_data_form->shop_id = $shop_id;
        if ($user_id) {
            $user = User::findOne(['store_id' => $this->store->id, 'id' => $user_id]);
        }
        if ($clerk_id) {
            $clerk = User::findOne(['store_id' => $this->store->id, 'id' => $clerk_id]);
        }
        if ($shop_id) {
            $shop = Shop::findOne(['store_id' => $this->store->id, 'id' => $shop_id]);
        }
        //TODO 新增视图并单查询输出

        $page=0;
        if($pageindex>0)
        {
            $page=$pageindex*10;
        }
        //TODO 订单搜索项 条件过滤器  2019-06-25  16点05分
        $sql_where='1=1';
        if($type==1)
        {
            $sql_where="ordertype='zc'";
        }
        if($type==2)
        {
            $sql_where="ordertype='ms'";
        }
        if($type==3)
        {
            $sql_where="ordertype='pt'";
        }
        if($order_no)
        {
            $sql_where="order_no='{$order_no}' and ".$sql_where;
        }
        \Yii::warning($order_no.'----查询条件----'.$sql_where,'info');
        //状态查询
        if(isset($status))//检测变量值是否存在
        {
            $status=-1;
        }
        switch ($status) {
            case -1:
                $sql_where='1=1  and '.$sql_where;//全部
                break;
            case 0:
                $sql_where='is_pay=0 and '.$sql_where;//待付款订单
                break;
            case 1:
                $sql_where='is_send=0  and  is_pay=1 and '.$sql_where;//待发货   已支付订单
                break;
            case 2:
                $sql_where='is_send=1  and  is_pay=1  and is_confirm=0 and '.$sql_where;//待收货 已支付  已发货
                break;
            case 3:
                $sql_where='is_send=1  and  is_pay=1  and is_confirm=1 and '.$sql_where;//已完成 已支付  已发货  已收货
                break;
            case 4:
                break;
            case 5:
                break;
            case 6:
                $sql_where='apply_delete=1 and  '.$sql_where;//待处理 申请取消单
                break;
            default:
                $sql_where='1=1  and '.$sql_where;//全部
                break;
        }

//        if ($this->status == 5) {//已取消订单
//            $query_where->andWhere(['or', ['o.is_cancel' => 1], ['o.is_delete' => 1]]);
//        } else {
//            if ($this->is_recycle != 1) {
//                $query_where->andWhere(['o.is_cancel' => 0, 'o.is_delete' => 0]);
//            }
//        }

        $sql="SELECT  * FROM (SELECT   * FROM v_order_list WHERE {$sql_where}  ORDER BY id DESC  ) AS hj_order  LIMIT 10 OFFSET  ".$page;
        $order_list = \Yii::$app->db->createCommand($sql)->queryAll();
        $list_count= \Yii::$app->db->createCommand('SELECT  COUNT(id) list_count FROM v_order_list')->queryAll();//计算订单数

        $new_all_list = [];
        for ($i = 0; $i < count($order_list); $i++) {
            $order_id_query = $order_list[$i]['id'];
            $goods_all_list = [];
//            \Yii::warning('循环数据单据=====>' . $order_id_query, 'info');
            $order_type="'".$order_list[$i]['ordertype']."'";
            \Yii::warning('----订单列表商品详情ID标==》》》》》》》----'.$order_id_query,'info');
            $order_detail_all_list_Arry = \Yii::$app->db->createCommand('SELECT  * FROM v_order_detail_list  where order_id=' . $order_id_query.' and ordertype='.$order_type)->queryAll();
            // TODO 输出参数定义
            foreach ($order_detail_all_list_Arry as $order_detail_all) {
                //TODO 输出视图拼单
                if (!$order_detail_all) {
                    continue;
                }
                \Yii::warning('----订单列表商品详情==》》》》》》》----'.$order_detail_all['goods_name'],'info');

                $goods_all_list[] = (object)[
                    'goods_id' => $order_detail_all['goods_id'],
                    'goods_pic' => $order_detail_all['cover_pic'],
                    'goods_name' => $order_detail_all['goods_name'],
                    'num' => $order_detail_all['num'],
                    'price' => $order_detail_all['total_price'],
                    'attr_list' => json_decode($order_detail_all['attr']),
                ];
            }
            //TODO 查询用户名称
            if ($order_list[$i]['user_id']) {
                $user = User::findOne(['store_id' => $this->store->id, 'id' =>$order_list[$i]['user_id']]);
            }
            $new_all_list[] = (object)[
                'order_id' => $order_list[$i]['id'],
                'user_id'=> $order_list[$i]['user_id'],
                'nickname'=> $user->nickname,
                'platform'=>0,
                'order_no' => $order_list[$i]['order_no'],
                'addtime' => date('Y-m-d H:i', $order_list[$i]['ADDTIME']),
                'goods_list' => $goods_all_list,
                'total_price' =>$order_list[$i]['total_price'],
                'pay_price' =>$order_list[$i]['pay_price'],
                'is_pay' => intval($order_list[$i]['is_pay']),
                'is_send' => intval($order_list[$i]['is_send']),
                'is_confirm' =>intval($order_list[$i]['is_confirm']),
                'is_comment' => $order_list[$i]['is_comment'],
                'apply_delete' => intval($order_list[$i]['apply_delete']),
                'is_offline' => 0,
                'order_type' => $order_list[$i]['ordertype'],
                'express' =>$order_list[$i]['express'],
                'pay_type' =>intval($order_list[$i]['pay_type']),
                'name' =>$order_list[$i]['name'],//收货人  TODO 新增2019-07-02  09点24分
                'mobile' =>$order_list[$i]['mobile'],//手机号
                'address' =>$order_list[$i]['address'],//地址
                'express_no' =>$order_list[$i]['express_no'],//快递单号
            ];
            \Yii::warning((int)$order_list[$i]['is_send'].'----测试类型转换----'.intval($order_list[$i]['is_send']),'info');
        }
        $new_all_list_Array = ArrayHelper::toArray($new_all_list);
        arsort($new_all_list_Array);
        return $this->render('order-list', [
            'row_count' => $list_count[0]['list_count'],
            'pagination' => $data['pagination'],
           // 'list' => $data['list'],
            'user' => $user,
            'clerk' => $clerk,
            'shop' => $shop,
            'exportAllList' => \Yii::$app->serializer->encode($exportList),
            'order_list'=>$new_all_list_Array//TODO 返回全部订单 含（秒杀-拼团-正常单 合并）
        ]);
    }

    //TODO 新增补充导出订单
    public function actionExport()
    {
        $pageindex=\Yii::$app->request->get('page');//MYSQL 默认 0起
        if($pageindex>0)
        {
            $page=$pageindex*10;
        }
        \Yii::warning('----页码----'.$pageindex,'info');

        $sql_where='ORDER BY ADDTIME DESC ';//按最新下单时间导出
        $sql="SELECT  * FROM (SELECT   * FROM v_order_list  {$sql_where} ) AS hj_order  LIMIT   ".$page;
        $order_list = \Yii::$app->db->createCommand($sql)->queryAll();
        $list_count= \Yii::$app->db->createCommand('SELECT  COUNT(id) list_count FROM v_order_list')->queryAll();//计算订单数

        $new_all_list = [];
        for ($i = 0; $i < count($order_list); $i++) {
            $order_id_query = $order_list[$i]['id'];
            $goods_all_list = [];
//            \Yii::warning('循环数据单据=====>' . $order_id_query, 'info');
            $order_type="'".$order_list[$i]['ordertype']."'";
            \Yii::warning('----订单列表商品详情ID标==》》》》》》》----'.$order_id_query,'info');
            $order_detail_all_list_Arry = \Yii::$app->db->createCommand('SELECT  * FROM v_order_detail_list  where order_id=' . $order_id_query.' and ordertype='.$order_type)->queryAll();
            // TODO 输出参数定义
            foreach ($order_detail_all_list_Arry as $order_detail_all) {
                //TODO 输出视图拼单
                if (!$order_detail_all) {
                    continue;
                }
                \Yii::warning('----订单列表商品详情==》》》》》》》----'.$order_detail_all['goods_name'],'info');

                $goods_all_list[] = (object)[
                    'goods_id' => $order_detail_all['goods_id'],
                    'goods_pic' => $order_detail_all['cover_pic'],
                    'goods_name' => $order_detail_all['goods_name'],
                    'num' => $order_detail_all['num'],
                    'price' => $order_detail_all['total_price'],
                    'attr_list' => json_decode($order_detail_all['attr']),
                ];
            }
            //TODO 查询用户名称
            if ($order_list[$i]['user_id']) {
                $user = User::findOne(['store_id' => $this->store->id, 'id' =>$order_list[$i]['user_id']]);
            }
            $new_all_list[] = (object)[
                'order_id' => $order_list[$i]['id'],
                'user_id'=> $order_list[$i]['user_id'],
                'nickname'=> $user->nickname,
                'platform'=>0,
                'order_no' => $order_list[$i]['order_no'],
                'addtime' => date('Y-m-d H:i', $order_list[$i]['ADDTIME']),
                'goods_list' => $goods_all_list,
                'total_price' =>$order_list[$i]['total_price'],
                'pay_price' =>$order_list[$i]['pay_price'],
                'is_pay' => $order_list[$i]['is_pay'],
                'is_send' => $order_list[$i]['is_send'],
                'is_confirm' =>$order_list[$i]['is_confirm'],
                'is_comment' => $order_list[$i]['is_comment'],
                'apply_delete' => $order_list[$i]['apply_delete'],
                'is_offline' => 0,
                'order_type' => $order_list[$i]['ordertype'],
                'express' =>$order_list[$i]['express'],
                'pay_type' =>$order_list[$i]['pay_type'],
            ];
        }
        $new_all_list_Array = ArrayHelper::toArray($new_all_list);
        Export::expOrderList($new_all_list_Array); //Excel 导出
    }
    //移入回收站
    public function actionEdit()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $is_recycle = \Yii::$app->request->get('is_recycle');
        //TODO 解除管理员操作其他商户限制 2019年10月18日11:50:22 'mch_id' => 0
        if ($is_recycle == 0 || $is_recycle == 1) {
            $form = Order::find()->where(['store_id' => $this->store->id])
                ->andWhere('id = :order_id', [':order_id' => $order_id])->one();
            $form->is_recycle = $is_recycle;
            if ($form && $form->save()) {
                return [
                    'code' => 0,
                    'msg' => '操作成功',
                ];
            }
        };
        return [
            'code' => 1,
            'msg' => '操作失败',
        ];
    }

    //添加备注
    public function actionSellerComments()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $seller_comments = \Yii::$app->request->get('seller_comments');
        //TODO 权限操作处理、移除限制管理员操作其他商户订单 2019年10月18日09:33:50 mch_id' => 0
        $form = Order::find()->where(['store_id' => $this->store->id, 'id' => $order_id])->one();
        $form->seller_comments = $seller_comments;
        if ($form->save()) {
            return [
                'code' => 0,
                'msg' => '操作成功',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '操作失败',
            ];
        }
    }

    //订单发货
    public function actionSend()
    {
        $form = new OrderSendForm();
        $post = \Yii::$app->request->post();
        if ($post['is_express'] == 1) {
            $form->scenario = 'EXPRESS';
        }
        $form->attributes = $post;
        $form->store_id = $this->store->id;
        return $form->save();
    }

    private function getExpressList()
    {
        $storeExpressList = Order::find()
            ->select('express')
            ->where([
                'AND',
                ['store_id' => $this->store->id],
                ['is_send' => 1],
                ['!=', 'express', ''],
            ])->groupBy('express')->orderBy('send_time DESC')->limit(5)->asArray()->all();
        $expressLst = Express::getExpressList();
        $newStoreExpressList = [];
        foreach ($storeExpressList as $i => $item) {
            foreach ($expressLst as $value) {
                if ($value['name'] == $item['express']) {
                    $newStoreExpressList[] = $item['express'];
                    break;
                }
            }
        }

        $newPublicExpressList = [];
        foreach ($expressLst as $i => $item) {
            $newPublicExpressList[] = $item['name'];
        }

        return [
            'private' => $newStoreExpressList,
            'public' => $newPublicExpressList,
        ];
    }

    //售后订单列表
    public function actionRefund()
    {
        // 获取可导出数据
        $f = new ExportList();
        $f->type = 1;
        $exportList = $f->getList();
        $form = new OrderRefundListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $data = $form->search();

        $address = RefundAddress::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->all();
        foreach ($address as &$v) {
            if (mb_strlen($v->address) > 20) {
                $v->address = mb_substr($v->address, 0, 20) . '···';
            }
        }
        unset($v);

        return $this->render('refund', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            'address' => $address,
            'exportList' => \Yii::$app->serializer->encode($exportList)
        ]);
    }

    //TODO 订单申请取消  首先测试 余额取消流程  Allon  2019年8月21日15:28:48
    //订单取消申请处理
    public function actionApplyDeleteStatus($id, $status, $type = 0)
    {
        $where = [
            'id' => $id,
            'is_delete' => 0,
            'store_id' => $this->store->id,
//            'mch_id' => 0,
        ];
        // type=1 后台主要取消订单， type=0 用户发起订单取消申请
        if ($type == 0) {
            $where['apply_delete'] = 1;
        }
        $order = Order::findOne($where);

        if (!$order || $order->mch_id > 0) {
            return [
                'code' => 1,
                'msg' => '订单不存在，请刷新页面后重试',
            ];
        }

        \Yii::warning('==订单申请取消=='.$status,'info');
        $remark = \Yii::$app->request->get('remark');
        if ($status == 1) { //同意
            $form = new OrderRevokeForm();
            $form->order_id = $order->id;
            $form->delete_pass = true;
            $form->user_id = $order->user_id;
            $form->store_id = $order->store_id;
            $res = $form->save();
            if ($res['code'] == 0) {
                $msg_sender = new WechatTplMsgSender($this->store->id, $order->id, $this->wechat);
                $msg_sender->revokeMsg($remark ? $remark : '商家同意了您的订单取消请求');
                $wechatAccessToken = $this->wechat->getAccessToken();
                $res = CommonShoppingList::updateBuyGood($wechatAccessToken, $order, 0, 12);
                return [
                    'code' => 0,
                    'msg' => '操作成功',
                ];
            } else {
                return $res;
            }
        } else { //拒绝
            $order->apply_delete = 0;
            $order->save();
            $msg_sender = new WechatTplMsgSender($this->store->id, $order->id, $this->wechat);
            $msg_sender->revokeMsg($remark ? $remark : '您的取消申请已被拒绝');
            return [
                'code' => 0,
                'msg' => '操作成功',
            ];
        }
    }

    public function actionPrint()
    {
        $id = \Yii::$app->request->get('id');
        $express = \Yii::$app->request->get('express');
        $post_code = \Yii::$app->request->get('post_code');
        $form = new PrintForm();
        $form->store_id = $this->store->id;
        $form->order_id = $id;
        $form->express = $express;
        $form->post_code = $post_code;
        return $form->send();
    }

    public function actionAddPrice()
    {
        $form = new OrderPriceForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        return $form->update();
    }

    public function actionDetail($order_id = null)
    {
        $form = new OrderDetailForm();
        $form->store_id = $this->store->id;
        $form->order_id = $order_id;
        $arr = $form->search();
        $arr['is_update'] = true;
        return $this->render('detail', $arr);
    }

    public function actionOffline()
    {
        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->attributes = \Yii::$app->request->post();
        $form->is_offline = 1;
        $form->store_id = $this->store->id;
        $form->platform = \Yii::$app->request->get('platform');
        $form->limit = 10;
        $data = $form->search();

        $store_data_form = new StoreDataForm();
        $store_data_form->store_id = $this->store->id;
        $store_data_form->is_offline = 1;
        $user_id = \Yii::$app->request->get('user_id');
        $clerk_id = \Yii::$app->request->get('clerk_id');
        $shop_id = \Yii::$app->request->get('shop_id');
        $store_data_form->user_id = $user_id;
        $store_data_form->clerk_id = $clerk_id;
        $store_data_form->shop_id = $shop_id;
        if ($user_id) {
            $user = User::findOne(['store_id' => $this->store->id, 'id' => $user_id]);
        }
        if ($clerk_id) {
            $clerk = User::findOne(['store_id' => $this->store->id, 'id' => $clerk_id]);
        }
        if ($shop_id) {
            $shop = Shop::findOne(['store_id' => $this->store->id, 'id' => $shop_id]);
        }
        // 获取可导出数据
        $f = new ExportList();
        $exportList = $f->getList();
        return $this->render('index', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            //'count_data' => OrderListForm::getCountData($this->store->id),
            'store_data' => $store_data_form->search(),
            'express_list' => $this->getExpressList(),
            'user' => $user,
            'clerk' => $clerk,
            'shop' => $shop,
            'exportList' => \Yii::$app->serializer->encode($exportList)
        ]);
    }

    //批量发货
    public function actionBatchShip()
    {
        if (\Yii::$app->request->isPost) {
            $file = \Yii::$app->request->post();
            if (!$file['url']) {
                return [
                    'code' => 1,
                    'msg' => '请输入模板地址'
                ];
            }
            if (!$file['express']) {
                return [
                    'code' => 1,
                    'msg' => '请输入快递公司'
                ];
            }
            $arrCSV = array();
            if (($handle = fopen($file['url'], "r")) !== false) {
                $key = 0;
                while (($data = fgetcsv($handle, 0, ",")) !== false) {
                    $c = count($data);
                    for ($x = 0; $x < $c; $x++) {
                        $arrCSV[$key][$x] = trim($data[$x]);
                    }
                    $key++;
                }
                fclose($handle);
            }
            unset($arrCSV[0]);
            $form = new OrderSendForm();
            $form->store_id = $this->store->id;
            $form->express = \Yii::$app->request->post('express');
            $info = $form->batch($arrCSV);

            return [
                'code' => 0,
                'msg' => '操作成功',
                'data' => $info,
            ];
        }
        return $this->render('batch-ship', [
            'express_list' => $this->getExpressList(),
        ]);
    }

    public function actionShipModel()
    {
        Export::shipModel();
    }

    //货到付款，确认收货
    public function actionConfirm()
    {
        $order_id = \Yii::$app->request->get('order_id');
        $order = Order::findOne([
            'id' => $order_id,
//            'mch_id' => 0,
        ]);
        if (!$order) {
            return [
                'code' => 1,
                'msg' => '订单不存在，请刷新重试',
            ];
        }
        if ($order->pay_type != 2) {
            return [
                'code' => 1,
                'msg' => '订单支付方式不是货到付款，无法确认收货',
            ];
        }
        if ($order->is_send == 0) {
            return [
                'code' => 1,
                'msg' => '订单未发货',
            ];
        }
        $order->is_confirm = 1;
        $order->confirm_time = time();
        $order->is_pay = 1;
        $order->pay_time = time();
        if ($order->save()) {
            return [
                'code' => 0,
                'msg' => '成功',
            ];
        } else {
            foreach ($order->errors as $error) {
                return [
                    'code' => 1,
                    'msg' => $error,
                ];
            }
        }
    }

    ///TODO addons/zjhj_mall/core/web/index.php?r=mch%2Forder%2Frefund-handle  退款
    // 处理售后订单
    public function actionRefundHandle()
    {
        $form = new OrderRefundForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        return $form->save();
    }

    // 删除订单（软删除）
    public function actionDelete($order_id = null)
    {
        $orderDeleteForm = new OrderDeleteForm();
        $orderDeleteForm->order_model = 'app\models\Order';
        $orderDeleteForm->order_id = $order_id;
        $orderDeleteForm->store = $this->store;
        return $orderDeleteForm->delete();
    }

    // 清空回收站
    public function actionDeleteAll()
    {
        $orderDeleteForm = new OrderDeleteForm();
        $orderDeleteForm->order_model = 'app\models\Order';
        $orderDeleteForm->store = $this->store;
        $orderDeleteForm->type = get_plugin_type();
        $orderDeleteForm->mch_id = 0;
        return $orderDeleteForm->deleteAll();
    }

    // 修改价格
    public function actionUpdatePrice()
    {
        $form = new \app\modules\mch\models\order\OrderPriceForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->order_type = 's';
        return $form->save();
    }

    // 核销订单
    public function actionClerk()
    {
        $form = new OrderClerkForm();
        $form->attributes = \Yii::$app->request->get();
        $form->order_model = 'app\models\Order';
        $orderType = get_plugin_type();
        if ($orderType == 2) {
            $form->order_type = 7;
        } else {
            $form->order_type = 0;
        }
        $form->store = $this->store;
        return $form->clerk();
    }

    // 更新订单地址
    public function actionUpdateOrderAddress()
    {
        $commonUpdateAddress = new CommonUpdateAddress();
        $commonUpdateAddress->data = \Yii::$app->request->post();
        $updateAddress = $commonUpdateAddress->updateAddress();

        return $updateAddress;

    }

    public function actionPrintOrder()
    {
        $get = \Yii::$app->request->get();
        $print = new PinterOrder($this->store->id, $get['order_id'], 'reprint', $get['order_type']);
        return $print->print_order();
    }
}
