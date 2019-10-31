<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/17
 * Time: 11:47
 */

namespace app\modules\api\controllers;

use app\hejiang\ApiResponse;
use app\hejiang\BaseApiResponse;
use app\models\ActionLog;
use app\models\FormId;
use app\models\Model;
use app\models\Order;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\ExpressDetailForm;
use app\modules\api\models\LocationForm;
use app\modules\api\models\OrderClerkForm;
use app\modules\api\models\OrderCommentForm;
use app\modules\api\models\OrderCommentPreview;
use app\modules\api\models\OrderConfirmForm;
use app\modules\api\models\OrderDetailForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\OrderPayDataForm;
use app\modules\api\models\OrderRefundDetailForm;
use app\modules\api\models\OrderRefundForm;
use app\modules\api\models\OrderRefundPreviewForm;
use app\modules\api\models\OrderRefundSendForm;
use app\modules\api\models\OrderRevokeForm;
use app\modules\api\models\OrderSubmitForm;
use app\modules\api\models\OrderSubmitPreviewForm;
use app\modules\api\models\QrcodeForm;

class OrderController extends Controller
{
    public function behaviors()
    {
        \Yii::warning("==每次都进入的地==" ,'info');
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    //订单提交前的预览页面
    public function actionSubmitPreview()
    {
        $form = new OrderSubmitPreviewForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //订单提交
    public function actionSubmit()
    {
        $form = new OrderSubmitForm();
        $model = \Yii::$app->request->post();
        if ($model['offline'] == 0) {
            $form->scenario = "EXPRESS";
        } else {
            $form->scenario = "OFFLINE";
        }
        $form->attributes = $model;
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $form->version = hj_core_version();
        return new BaseApiResponse($form->save());
    }

    //新-订单提交前的预览页面
    public function actionNewSubmitPreview()
    {
        \Yii::warning("==新订单提交预览界面==" ,'info');
        $form = new \app\modules\api\models\order\OrderSubmitPreviewForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->store = $this->store;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //新-订单提交
    public function actionNewSubmit()
    {
        //TODO 微信提单走的流程 Allon  2019年8月19日11:17:10
        \Yii::warning("==新订单提交界面==" ,'info');
        $form = new \app\modules\api\models\order\OrderSubmitForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store = $this->store;
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $form->user = \Yii::$app->user->identity;
        return new BaseApiResponse($form->save());
    }


    //订单支付数据
    public function actionPayData()
    {
        $form = new OrderPayDataForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user = \Yii::$app->user->identity;
        return new BaseApiResponse($form->search());
    }

    //订单列表
    public function actionList()
    {
        //warning
        //TODO 订单列表取参动作  2019.06.19 09点42分
        \Yii::warning('进入订单列表','info');
        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }
    //TODO 新增全部单
    //全部订单列表（拼团 秒杀  正常）
    public function actionAllList()
    {
        //warning
        //TODO 订单列表取参动作  2019.06.19 09点42分
        \Yii::warning('进入订单列表All全部订单','info');
        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->searchAll());
    }

    //TODO 订单取消、原路返回账户余额、对该笔订单使用的账户余额进行解冻  Allon 2019年8月20日09:20:37
    //订单取消
    public function actionRevoke()
    {
        $form = new OrderRevokeForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //订单确认收货
    public function actionConfirm()
    {
        $form = new OrderConfirmForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //订单各个状态数量
    public function actionCountData()
    {
        $res = OrderListForm::getCountData($this->store->id, \Yii::$app->user->id);
        return new BaseApiResponse([
            'code' => 0,
            'msg' => 'success',
            'data' => $res,
        ]);
    }

    //订单详情
    public function actionDetail()
    {
        $form = new OrderDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //售后页面
    public function actionRefundPreview()
    {
        $form = new OrderRefundPreviewForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //售后提交
    public function actionRefund()
    {
        $form = new OrderRefundForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //售后订单详情
    public function actionRefundDetail()
    {
        $form = new OrderRefundDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //评论预览页面
    public function actionCommentPreview()
    {
        $form = new OrderCommentPreview();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //评论提交
    public function actionComment()
    {
        $form = new OrderCommentForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //订单物流信息
    public function actionExpressDetail()
    {
        $form = new ExpressDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->search());
    }

    //核销订单
    public function actionClerk()
    {
        $form = new OrderClerkForm();
        $form->order_id = \Yii::$app->request->get('order_id');
        $form->order_no = \Yii::$app->request->get('order_no');
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }

    //核销订单详情
    public function actionClerkDetail()
    {
        if (\Yii::$app->user->identity->is_clerk != 1) {
            return new BaseApiResponse([
                'code' => 1,
                'msg' => '不是核销员禁止核销'
            ]);
        }
        $form = new OrderDetailForm();
        $form->order_no = \Yii::$app->request->get('order_no');
        $form->store_id = $this->store->id;
//        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->clerk());
    }

    public function actionGetQrcode()
    {
        $order_no = \Yii::$app->request->get('order_no');
        $form = new QrcodeForm();
        $form->page = "pages/clerk/clerk";
        $form->width = 100;
        if (\Yii::$app->fromAlipayApp()) {
            $form->scene = "order_no={$order_no}";
        } else {
            $form->scene = "{$order_no}";
        }
        $form->store = $this->store;
        $res = $form->getQrcode();
        return new BaseApiResponse($res);
    }

    public function actionLocation()
    {
        $form = new LocationForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        return new BaseApiResponse($form->search());
    }

    //售后订单用户发货
    public function actionRefundSend()
    {
        $form = new OrderRefundSendForm();
        $form->attributes = \Yii::$app->request->post();
        $form->user_id = \Yii::$app->user->id;
        return new BaseApiResponse($form->save());
    }
}
