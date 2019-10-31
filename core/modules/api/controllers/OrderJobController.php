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

class OrderJobController extends Controller
{
    public function behaviors()
    {
        \Yii::warning("==订单定时Job作业文件==" ,'info');
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }
}
