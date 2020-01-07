<?php
defined('YII_ENV') or exit('Access Denied');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29
 * Time: 9:50
 */

use yii\widgets\LinkPager;

/* @var \app\models\User $user */

$urlManager = Yii::$app->urlManager;
$statics = Yii::$app->request->baseUrl . '/statics';
$this->title = '订单管理';
$this->params['active_nav_group'] = 3;
$status = Yii::$app->request->get('status');
$is_recycle = Yii::$app->request->get('is_recycle');

$is_full = Yii::$app->request->get('is_full');//TODO 添加满减订单导出 2019年11月12日10:55:17

$user_id = Yii::$app->request->get('user_id');
$condition = ['user_id' => $user_id, 'clerk_id' => $_GET['clerk_id'], 'shop_id' => $_GET['shop_id']];
if ($status === '' || $status === null || $status == -1) {
    $status = -1;
}
if ($is_recycle == 1) {
    $status = 12;
}
if ($is_full == 1) {
    $status = 13;
}
$urlStr = get_plugin_url();
$urlPlatform = Yii::$app->requestedRoute;
?>
<style>
    .order-item {
        border: 1px solid transparent;
        margin-bottom: 1rem;
    }

    .order-item table {
        margin: 0;
    }

    .order-item:hover {
        border: 1px solid #3c8ee5;
    }

    .goods-item {
        /* margin-bottom: .75rem; */
        border: 1px solid #ECEEEF;
        padding: 10px;
        margin-top: -1px;
    }

    .goods-item:last-child {
        margin-bottom: 0;
    }

    .goods-pic {
        width: 5.5rem;
        height: 5.5rem;
        display: inline-block;
        background-color: #ddd;
        background-size: cover;
        background-position: center;
        margin-right: 1rem;
    }

    .table tbody tr td {
        vertical-align: middle;
    }

    .goods-name {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .titleColor {
        color: #888888;
    }

    .order-tab-1 {
        width: 30%;
    }

    .order-tab-2 {
        width: 20%;
        text-align: center;
    }

    .order-tab-3 {
        width: 10%;
        text-align: center;
    }

    .order-tab-4 {
        width: 10%;
        text-align: center;
    }

    .order-tab-5 {
        width: 10%;
        text-align: center;
    }
    .order-tab-6 {
        width: 10%;
        /*text-align: center;*/
    }

    .status-item.active {
        color: inherit;
    }
</style>
<script language="JavaScript" src="<?= $statics ?>/mch/js/LodopFuncs.js"></script>
<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0 style="display: none">
    <embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0></embed>
</object>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <?= $this->render('/layouts/order-search/order-search-all', [
            'urlPlatform' => $urlPlatform,
            'urlStr' => $urlStr
        ]) ?>
    </div>
    <div class="mb-4">
        <span class="text-danger mr-4">温馨提示：超出2件商品以上商品将不细节展示、只展示单号、金额、地址主体信息 、商品细节请移步订单管理预览核对！！！</span>
    </div>
    <div class="mb-4">
        <ul class="nav nav-tabs status">
            <li class="nav-item">
                <a class="status-item nav-link <?= $status == -1 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']])) ?>">全部</a>
            </li>
            <li class="nav-item">
                <a class="status-item nav-link <?= $status == 0 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 0])) ?>">未付款<?= $store_data['status_count']['status_0'] ? '(' . $store_data['status_count']['status_0'] . ')' : null ?></a>

            </li>
            <li class="nav-item">
                <a class="status-item nav-link <?= $status == 1 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 1])) ?>">待发货<?= $store_data['status_count']['status_1'] ? '(' . $store_data['status_count']['status_1'] . ')' : null ?></a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $status == 2 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 2])) ?>">待收货<?= $store_data['status_count']['status_2'] ? '(' . $store_data['status_count']['status_2'] . ')' : null ?></a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $status == 3 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 3])) ?>">已完成<?= $store_data['status_count']['status_3'] ? '(' . $store_data['status_count']['status_3'] . ')' : null ?></a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $status == 6 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 6])) ?>">待处理<?= $store_data['status_count']['status_6'] ? '(' . $store_data['status_count']['status_6'] . ')' : null ?></a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $status == 5 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => 5])) ?>">已取消<?= $store_data['status_count']['status_5'] ? '(' . $store_data['status_count']['status_5'] . ')' : null ?></a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $is_recycle == 1 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['is_recycle' => 1])) ?>">回收站</a>
            </li>
            <li class="nav-item">
                <a class="status-item  nav-link <?= $is_full == 1 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['is_full' => 1])) ?>">满减订单</a>
            </li>
        </ul>
    </div>
    <table class="table table-bordered bg-white">
        <tr>
            <th class="order-tab-1">商品信息</th>
            <th class="order-tab-2">金额</th>
            <th class="order-tab-3">实际付款</th>
            <th class="order-tab-3">分摊金额</th>
            <th class="order-tab-3">满减活动</th>
            <th class="order-tab-4">订单状态</th>
            <th class="order-tab-5">分销情况</th>
            <th class="order-tab-5">操作</th>
        </tr>
    </table>
    <?php foreach ($list as $k => $order_item) : ?>
        <div class="order-item" style="<?= $order_item['flag'] == 1 ? 'color:#ff4544' : '' ?>">
            <?php if ($order_item['flag'] == 1) : ?>
                <div class="text-danger">注：此订单数据异常，请谨慎发货，及时联系管理员处理</div>
            <?php endif; ?>
            <table class="table table-bordered bg-white">
                <tr>
                    <td colspan="6">
                            <span class="mr-3"><span
                                        class='titleColor'>下单时间：</span><?= date('Y-m-d H:i:s', $order_item['addtime']) ?></span>
                        <?php if ($order_item['seller_comments']) : ?>
                            <span class="badge badge-danger ellipsis mr-1" data-toggle="tooltip"
                                  data-placement="top"
                                  title="<?= $order_item['seller_comments'] ?>">有备注</span>
                        <?php endif; ?>
                        <sapn class="mr-1">
                            <?php if ($order_item['is_pay'] == 1) : ?>
                                <span class="badge badge-success">
                                        <?= $order_item['type'] == 1 ? '大转盘' : '' ?>
                                    <?= $order_item['type'] == 3 ? '刮刮卡' : '' ?>
                                    <?= $order_item['type'] == 4 ? '0元抽奖' : '' ?>
                                    已付款</span>
                            <?php else : ?>
                                <span class="badge badge-default">
                                        <?= $order_item['type'] == 1 ? '大转盘' : '' ?>
                                    <?= $order_item['type'] == 3 ? '刮刮卡' : '' ?>
                                    <?= $order_item['type'] == 4 ? '0元抽奖' : '' ?>
                                    未付款</span>
                            <?php endif; ?>
                        </sapn>
                        <?php if ($order_item['is_send'] == 1) : ?>
                            <span class="mr-1">
                                    <?php if ($order_item['is_confirm'] == 1) : ?>
                                        <span class="badge badge-success">已收货</span>
                                    <?php else : ?>
                                        <span class="badge badge-default">未收货</span>
                                    <?php endif; ?>
                                </span>
                        <?php else : ?>
                            <?php if ($order_item['is_pay'] == 1) : ?>
                                <span class="mr-1">
                                    <?php if ($order_item['is_send'] == 1) : ?>
                                        <span class="badge badge-success">已发货</span>
                                    <?php else : ?>
                                        <span class="badge badge-default">未发货</span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <span class="mr-5"><span class='titleColor'>订单号：</span><?= $order_item['order_no'] ?></span>
                        <span class="mr-5"><span class='titleColor'>商户名：</span><?= $order_item['mch_name'] ?></span>
                        <span class="mr-5"><span class='titleColor'>
                                用户名(ID)：</span><?= $order_item['nickname'] ?> <span
                                    class='titleColor'>(<?= $order_item['user_id'] ?>)</span>
                            <?php if (isset($order_item['platform']) && intval($order_item['platform']) === 0) : ?>
                                <span class="badge badge-success">微信</span>
                            <?php elseif (isset($order_item['platform']) && intval($order_item['platform']) === 1) : ?>
                                <span class="badge badge-primary">支付宝</span>
                            <?php else : ?>
                                <span class="badge badge-default">未知</span>
                            <?php endif; ?>


                        </span>
                        <span class="mr-3" >  <?php if (isset($order_item['order_type']) && $order_item['order_type'] =='zc') : ?>
                                订单类型： <span class="badge badge-success">普通订单</span>
                            <?php elseif (isset($order_item['order_type']) && $order_item['order_type'] =='ms') : ?>
                                订单类型： <span class="badge badge-primary">秒杀订单</span>
                            <?php else : ?>
                                订单类型： <span class="badge badge-default">拼团订单</span>
                            <?php endif; ?>
                        </span>
                        <?php if ($order_item['apply_delete'] == 1) : ?>
                            <span class="mr-1 titleColor">
                                    申请取消该订单：
                                <?php if ($order_item['is_delete'] == 0) : ?>
                                    <span class="badge badge-warning">申请中</span>
                                <?php else : ?>
                                    <span class="badge badge-warning">申请成功</span>
                                <?php endif; ?>
                                </span>
                        <?php endif; ?>

                        <?php if ($order_item['apply_delete'] == 1) : ?>
                            <?php if ($order_item['is_delete'] == 0) : ?>
                                <span>
                                        <a class="btn btn-sm btn-info apply-status-btn"
                                           href="<?= $urlManager->createUrl([$urlStr . '/apply-delete-status', 'id' => $order_item['id'], 'status' => 1]) ?>">同意取消</a>
                                    </span>
                                <span>
                                        <a class="btn btn-sm refuse btn-danger apply-status-btn"
                                           href="<?= $urlManager->createUrl([$urlStr . '/apply-delete-status', 'id' => $order_item['id'], 'status' => 0]) ?>">拒绝取消</a>
                                    </span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($order_item['refund']) : ?>
                            <span class="mr-1 titleColor">
                                    售后状态：
                                <?php if ($order_item['refund'] == 0) : ?>
                                    <span class="badge badge-warning">待商家处理</span>
                                <?php elseif ($order_item['refund'] == 1) : ?>
                                    <span class="badge badge-success">同意并已退款</span>
                                <?php elseif ($order_item['refund'] == 2) : ?>
                                    <span class="badge badge-success">已同意换货</span>
                                <?php elseif ($order_item['refund'] == 3) : ?>
                                    <span class="badge badge-danger">已拒绝退换货</span>
                                <?php endif; ?>
                                </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="order-tab-1">
                        <div class="goods-item" flex="dir:left box:first">
                            <div class="fs-0">
                                <div class="goods-pic"
                                     style="background-image: url('<?= $order_item['goods_pic'] ?>')"></div>
                            </div>
                            <div class="goods-info">
                                <div class="goods-name"><?= $order_item['goods_name'] ?></div>
                                <div class="mt-1">
                                        <span class="fs-sm">
                                            规格：
                                        <span class="text-danger">
                                            <?php $attr_list = json_decode($order_item['attr']); ?>
                                            <?php if (is_array($attr_list)) :
                                                foreach ($attr_list as $attr) : ?>
                                                    <span class="mr-3"><?= $attr->attr_group_name ?>
                                                        :<?= $attr->attr_name ?></span>
                                                <?php endforeach;;
                                            endif; ?>
                                        </span>
                                        </span>
                                    <span class="fs-sm">数量：
                                            <span class="text-danger"><?= $order_item['num'] . $order_item['unit'] ?></span>
                                        </span>
                                </div>
                                <div>
                                        <span class="fs-sm">小计：
                                            <span class="text-danger mr-4"><?= $order_item['total_price'] ?>元</span>
                                        </span>
                                </div>
                            </div>

                        </div>

                    </td>
                    <td class="order-tab-2">
                        <div class='titleColor'>总金额：<span style="color:blue;"><?= $order_item['total_price'] ?></span>元
                            <?php if ($order_item['express_price_1']) : ?>
                                (含运费：<span style="color:green;"><?= $order_item['express_price_1'] ?></span>元)</span>
                                <span class="text-danger">(包邮，运费减免)</span>
                            <?php else : ?>
                                (运费：<span style="color:green;"><?= $order_item['express_price'] ?></span>元)</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($order_item['user_coupon_id']) : ?>
                            <div class='titleColor'>优惠券：
                                <span style="color:red;"><?= $order_item['coupon_sub_price'] ?></span>元
                            </div>
                        <?php endif; ?>
                        <?php if ($order_item['before_update_price']) : ?>
                            <?php if ($order_item['pay_price'] > $order_item['before_update_price']) : ?>
                                <div class='titleColor'>后台加价：
                                    <span style="color:red;"><?= $order_item['pay_price'] - $order_item['before_update_price'] ?></span>元
                                </div>
                            <?php else : ?>
                                <div class='titleColor'>后台优惠：
                                    <span style="color:red;"><?= $order_item['before_update_price'] - $order_item['pay_price'] ?></span>元
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($order_item['integral'] && $order_item['integral']['forehead_integral']) : ?>
                            <span class='titleColor'>使用<?= $order_item['integral']['forehead_integral'] ?>积分抵扣：
                                    <span style="color:red;"><?= $order_item['integral']['forehead'] ?></span>元
                                </span>
                        <?php endif; ?>
                        <?php if ($order_item['discount'] && $order_item['discount'] != 10) : ?>
                            <!--                            <div class='titleColor'>会员折扣：<span style="color:red;">--><? //= $order_item['discount'] ?><!--</span>折</div>-->
                        <?php endif; ?>
                        <?php if ($order_item['is_pay'] == 0 && $order_item['is_cancel'] == 0 && $order_item['is_send'] == 0) : ?>
                            <div>
                                <a class="btn btn-sm btn-link update" href="javascript:" data-toggle="modal"
                                   data-target="#price"
                                   data-money="<?= $order_item['pay_price'] ?>"
                                   data-express_price='<?= $order_item['express_price'] ?>'
                                   data-id="<?= $order_item['id'] ?>">修改价格</a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="order-tab-3">
                        <div><span style="color:blue;"><?= $order_item['pay_price'] ?></span>元</div>
                    </td>
                    <td class="order-tab-3">
                        <div><span style="color:blue;"><?= $order_item['full_reduction_price'] ?></span>元</div>
                    </td>
                    <td class="order-tab-3">
                        <div><span style="color:blue;"><?= $order_item['pay_desc']?></span></div>
                    </td>
                    <td class="order-tab-4">

                        <?php if ($order_item['pay_type'] == 2) : ?>
                            <div>
                                支付方式：
                                <span class="badge badge-success">货到付款</span>
                            </div>
                        <?php elseif ($order_item['pay_type'] == 3) : ?>
                            <div>
                                支付方式：
                                <span class="badge badge-success">余额支付</span>
                            </div>
                        <?php else : ?>
                            <div>
                                支付方式：
                                <span class="badge badge-success">线上支付</span>
                            </div>
                        <?php endif; ?>

                        <div>
                            发货方式：
                            <?php if ($order_item['is_offline'] == 1) : ?>
                                <span class="badge badge-warning mt-1">到店自提</span>
                            <?php else : ?>
                                <span class="badge badge-warning mt-1">快递发送</span>
                            <?php endif; ?>
                        </div>

                    </td>
                    <td class="order-tab-6">
                        <div flex="dir:left">
                            <?php if ($order_item['share']): ?>
                                <div class="p-2 text-left">
                                    <span>昵称：<?= $order_item['share']['nickname'] ?></span>
                                    <span
                                            class="ml-3"><?= $order_item['share']['name'] ? "姓名：" . $order_item['share']['name'] : "" ?></span>
                                    <span
                                            class="ml-3"><?= $order_item['share']['mobile'] ? "电话：" . $order_item['share']['mobile'] : "" ?></span>
                                    <div class="titleColor">一级佣金：<span
                                                style="color:red;"><?= floatval($order_item['first_price']) ?></span>元
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($order_item['share_1']) : ?>
                                <div class="p-2 text-left">
                                    <span>昵称：<?= $order_item['share_1']['nickname'] ?></span>
                                    <span
                                            class="ml-3"><?= $order_item['share_1']['name'] ? "姓名：" . $order_item['share_1']['name'] : "" ?></span>
                                    <span
                                            class="ml-3"><?= $order_item['share_1']['mobile'] ? "电话：" . $order_item['share_1']['mobile'] : "" ?></span>
                                    <div class="titleColor">二级佣金：<span
                                                style="color:red;"><?= floatval($order_item['second_price']) ?></span>元
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($order_item['share_2']) : ?>
                                <div class="p-2 text-left">
                                    <span>昵称：<?= $order_item['share_2']['nickname'] ?></span>
                                    <span class="ml-3"
                                          class="ml-3"><?= $order_item['share_2']['name'] ? "姓名：" . $order_item['share_2']['name'] : "" ?></span>
                                    <span
                                            class="ml-3"><?= $order_item['share_2']['mobile'] ? "电话：" . $order_item['share_2']['mobile'] : "" ?></span>
                                    <div class="titleColor">三级佣金：<span
                                                style="color:red;"><?= floatval($order_item['third_price']) ?></span>元
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($order_item['rebate'] > 0) : ?>
                            <div class="p-1 text-left">
                                <div class="titleColor">自购返利：<span
                                            style="color:red;"><?= floatval($order_item['rebate']) ?></span>元
                                </div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="order-tab-5">
                        <div>
                            <?php if ($order_item['order_type'] == 'ms') : ?>
                                <a class="btn btn-sm btn-primary mt-2"
                                   href="index.php?r=mch%2Fmiaosha%2Forder%2Fdetail&order_id=<?= $order_item['id']?>">详情</a>
                            <?php elseif ($order_item['order_type'] == 'pt')  : ?>
                                <a class="btn btn-sm btn-primary mt-2"
                                   href="index.php?r=mch%2Fgroup%2Forder%2Fdetail&order_id=<?= $order_item['id']?>">详情</a>
                            <?php else : ?>
                                <a class="btn btn-sm btn-primary mt-2"
                                   href="<?= $urlManager->createUrl([$urlStr . '/detail', 'order_id' => $order_item['id']]) ?>">详情</a>
                            <?php endif; ?>

                        </div>
                    </td>

                </tr>
                <tr>
                    <td colspan="5">
                        <div>
                            <?php if ($order_item['is_offline'] == 0) : ?>
                                <span class="mr-2"><span
                                            class="titleColor">收货人：</span><?= $order_item['name'] ?></span>
                                <span class="mr-2"><span
                                            class="titleColor">电话：</span><?= $order_item['mobile'] ?></span>
                                <span class="mr-3"><span
                                            class="titleColor">地址：</span><?= $order_item['address'] ?></span>
                                <?php if ($order_item['order_type'] == 'ms') : ?>
                                    <a class="btn btn-sm btn-primary mt-2"
                                       href="index.php?r=mch%2Fmiaosha%2Forder%2Findex&date_start=&date_end=&keyword_1=1&keyword=<?= $order_item['order_no']?>">核对订单</a>
                                <?php elseif ($order_item['order_type'] == 'pt')  : ?>
                                    <a class="btn btn-sm btn-primary mt-2"
                                       href="index.php?r=mch%2Fgroup%2Forder%2Findex&date_start=&date_end=&keyword_1=1&keyword=<?= $order_item['order_no']?>">核对订单</a>
                                <?php else : ?>
                                    <a class="btn btn-sm btn-primary mt-2"
                                       href="index.php?r=mch%2Forder%2Findex&date_start=&date_end=&keyword_1=1&keyword=<?= $order_item['order_no']?>">核对订单</a>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="mr-3"><span
                                            class="titleColor">联系人：</span><?= $order_item['name'] ?></span>
                                <span class="mr-3"><span
                                            class="titleColor">联系电话：</span><?= $order_item['mobile'] ?></span>
                            <?php endif; ?>
                            <?php if ($order_item['is_send'] == 1) : ?>
                                <?php if ($order_item['is_offline'] == 0 || $order_item['express']) : ?>
                                    <?php if ($order_item['express_no'] != '') : ?>
                                        <span class=" badge badge-default"><?= $order_item['express'] ?></span>
                                        <span class="mr-3"><span class="titleColor">快递单号：</span><a
                                                    href="https://www.baidu.com/s?wd=<?= $order_item['express_no'] ?>"
                                                    target="_blank"><?= $order_item['express_no'] ?></a></span>
                                    <?php endif; ?>
                                <?php elseif ($order_item['is_offline'] == 1) : ?>
                                    <span><span class="titleColor">核销员：</span><?= $order_item['clerk_name'] ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div <?= $order_item['remark'] ? '' : 'hidden' ?>>
                            <span class="titleColor">用户备注：</span><?= $order_item['remark'] ?>
                        </div>
                        <?php if ($order_item['shop_id']) : ?>
                            <div>
                                <span class="mr-3"><span
                                            class="titleColor">门店名称：</span><?= $order_item['shop']['name'] ?></span>
                                <span class="mr-3"><span
                                            class="titleColor">门店地址：</span><?= $order_item['shop']['address'] ?></span>
                                <span class="mr-3"><span
                                            class="titleColor">电话：</span><?= $order_item['shop']['mobile'] ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order_item['content']) : ?>
                            <div><span><span class="titleColor">买家留言：</span><?= $order_item['content'] ?></span></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

        </div>
    <?php endforeach; ?>

    <!-- 发货 -->
    <div class="modal fade send-modal" data-backdrop="static">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 400px">
            <div class="modal-content">
                <div class="modal-header">
                    <b class="modal-title">物流信息</b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form class="send-form" method="post">
                        <div class="form-group row">
                            <div class="col-3 text-right">
                                <label class=" col-form-label">物流选择</label>
                            </div>
                            <div class="col-9">
                                <div class="pt-1">
                                    <label class="custom-control custom-radio">
                                        <input id="radio1" value="1" checked
                                               name="is_express" type="radio"
                                               class="custom-control-input is-express">
                                        <span class="custom-control-indicator"></span>
                                        <span class="custom-control-description">快递</span>
                                    </label>
                                    <label class="custom-control custom-radio">
                                        <input id="radio2" value="0" name="is_express" type="radio"
                                               class="custom-control-input is-express">
                                        <span class="custom-control-indicator"></span>
                                        <span class="custom-control-description">无需物流</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="is-true-express">
                            <input class="form-control" type="hidden" autocomplete="off" name="order_id">
                            <label>快递公司</label>
                            <div class="input-group mb-3">
                                <input class="form-control" placeholder="请输入快递公司" type="text" autocomplete="off"
                                       name="express" readonly>
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-secondary dropdown-toggle"
                                            data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right"
                                         style="max-height: 250px;overflow: auto">
                                        <?php if (count($express_list['private'])) : ?>
                                            <?php foreach ($express_list['private'] as $item) : ?>
                                                <a class="dropdown-item" href="javascript:"><?= $item ?></a>
                                            <?php endforeach; ?>
                                            <div class="dropdown-divider"></div>
                                        <?php endif; ?>
                                        <?php foreach ($express_list['public'] as $item) : ?>
                                            <a class="dropdown-item" href="javascript:"><?= $item ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <label>收件人邮编</label>
                            <input class="form-control" placeholder="请输入收件人邮编" type="text" autocomplete="off"
                                   name="post_code">
                            <label><a class="print" href="javascript:">获取面单</a></label>
                            <label><a href='http://www.c-lodop.com/download.html' target='_blank'
                                      hidden>下载插件</a></label>
                            <label>快递单号</label>
                            <input class="form-control" placeholder="请输入快递单号" type="text" autocomplete="off"
                                   name="express_no">
                            <div class="text-danger mt-3 form-error" style="display: none"></div>
                        </div>
                        <div class="mt-2">
                            <label>商家留言（选填）</label>
                            <textarea class="form-control" name="words"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary send-confirm-btn">提交</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="modalBox">
    <!-- 修改价格 -->
    <div class="modal fade" id="price" data-backdrop="static">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 400px">
            <div class="modal-content">
                <div class="modal-header">
                    <b class="modal-title">修改价格</b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input class="order-id" type="hidden">
                    <div class="form-group row" <?= in_array(get_plugin_type(), [0, 2]) ? '' : 'hidden' ?> >
                        <label class="col-5 text-right col-form-label">金额（不含运费）：</label>
                        <div class="col-7">
                            <input class="form-control printPay" id="printPay" type="number" v-model="pay"
                                   placeholder="请填写金额">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-5 text-right col-form-label">运费：</label>
                        <div class="col-7">
                            <input class="form-control printExpress" id="printExpress" type="number" v-model="express"
                                   placeholder="请填写运费">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-5 text-right col-form-label">修改后的金额：</label>
                        <div class="col-7">
                            <span>{{ (pay*1 + express*1).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-primary">一键包邮</button> -->
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary change-price">提交</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 添加备注 -->
    <div class="modal fade" data-backdrop="static" id="about">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 400px">
            <div class="modal-content">
                <div class="modal-header">
                    <b class="modal-title">备注</b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input class="order-id" type="hidden">
                    <input class="url" type="hidden">
                    <div class="form-group row">
                        <label class="col-4 text-right col-form-label">添加备注信息：</label>
                        <div class="col-11" style="margin-left: 1rem;">
                        <textarea id="seller_comments" name="seller_comments" cols="90"
                                  rows="5"
                                  style="width: 100%;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary remarks">提交</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 后台订单取消 -->
    <div class="modal fade" data-backdrop="static" id="adminOrderCancel">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 400px">
            <div class="modal-content">
                <div class="modal-header">
                    <b class="modal-title">订单取消</b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input class="order-id" type="hidden">
                    <input class="url" type="hidden">
                    <div class="form-group row">
                        <label class="col-4 text-right col-form-label">填写取消理由：</label>
                        <div class="col-11" style="margin-left: 1rem;">
                        <textarea id="order_cancel_remark-1" name="seller_comments" cols="90"
                                  rows="3"
                                  style="width: 100%;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary order-cancel">提交</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 订单取消 -->
    <div class="modal fade" data-backdrop="static" id="orderCancal">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 400px">
            <div class="modal-content">
                <div class="modal-header">
                    <b class="modal-title">{{modalTitle}}</b>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input class="order-id" type="hidden">
                    <input class="url" type="hidden">
                    <div class="form-group row">
                        <label class="col-4 text-right col-form-label">{{modalSubTitle}}：</label>
                        <div class="col-11" style="margin-left: 1rem;">
                        <textarea id="order_cancel_remark" name="seller_comments" cols="90"
                                  rows="3"
                                  style="width: 100%;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary order-cancel-btn">提交</button>
                </div>
            </div>
        </div>
    </div>
    <!--    查询翻页-->
    <div class="text-center">
        <nav aria-label="Page navigation example">
            <?php echo \yii\widgets\LinkPager::widget([
                'pagination' => $pagination,
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '尾页',
                'maxButtonCount' => 5,
                'options' => [
                    'class' => 'pagination',
                ],
                'prevPageCssClass' => 'page-item',
                'pageCssClass' => "page-item",
                'nextPageCssClass' => 'page-item',
                'firstPageCssClass' => 'page-item',
                'lastPageCssClass' => 'page-item',
                'linkOptions' => [
                    'class' => 'page-link',
                ],
                'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
            ])
            ?>
        </nav>
        <div class="text-muted">共<?= $row_count ?>条数据</div>
    </div>
</div>

<script>
    var app = new Vue({
        el: '#modalBox',
        data: {
            pay: '',
            express: '',
            modalTitle: '',
            modalSubTitle: '',
        },
    });
</script>

<script>
    $(document).on('click', '.del', function () {
        var a = $(this);
        $.myConfirm({
            content: a.data('content'),
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: a.data('url'),
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        if (res.code == 0) {
                            $.myAlert({
                                content: res.msg,
                                confirm: function (res) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            $.myAlert({
                                content: res.msg
                            });
                        }
                    },
                    complete: function (res) {
                        $.myLoadingHide();
                    }
                });
            }
        });
        return false;
    });

    var cancelUrl = '';
    $(document).on("click", ".order-cancel-btn", function () {
        var remark = $('#order_cancel_remark').val();
        $('.order-cancel-btn').btnLoading()
        $.ajax({
            url: cancelUrl,
            type: 'get',
            data: {
                remark: remark,
            },
            dataType: "json",
            success: function (res) {
                $.myAlert({
                    content: res.msg,
                    confirm: function () {
                        $('.order-cancel-btn').btnReset()
                        if (res.code == 0) {
                            location.reload();
                        }
                    }
                });
            }
        });
        return false;
    });

    $(document).on("click", ".refuse", ".apply-status-btn", function () {
        cancelUrl = $(this).attr("href");
        app.modalTitle = '拒绝取消';
        app.modalSubTitle = '填写拒绝理由';
        $('#orderCancal').modal('show');
        return false;
    });

    $(document).on("click", ".btn-info.apply-status-btn", function () {
        cancelUrl = $(this).attr("href");
        app.modalTitle = '同意取消';
        app.modalSubTitle = '填写同意理由';
        $('#orderCancal').modal('show');
        return false;
    });

    // 后台点击取消
    var cancelUrl = '';
    var cancelId = 0;
    $(document).on("click", ".admin-order-cancel", function () {
        var a = $(this);
        cancelId = a.data('id');
        cancelUrl = a.attr('href');
        $('#adminOrderCancel').modal('show');
        return false;
    });
    // 后台主动取消订单
    $(document).on("click", ".order-cancel", function () {
        var remark = $('#order_cancel_remark-1').val();
        var btn = $(this);
        btn.btnLoading(btn.text());
        $.ajax({
            url: cancelUrl,
            dataType: "json",
            data: {
                remark: remark,
                id: cancelId,
                status: 1,
                type: 1
            },
            success: function (res) {
                $.myLoadingHide();
                $.myAlert({
                    content: res.msg,
                    confirm: function () {
                        btn.btnReset();
                        if (res.code == 0)
                            location.reload();
                    }
                });
            }
        });
        return false;
    });

    $(document).on("click", ".send-btn", function () {
        var order_id = $(this).attr("data-order-id");
        $(".send-modal input[name=order_id]").val(order_id);
        var express_no = $(this).attr("data-express-no");
        $(".send-modal input[name=express_no]").val(express_no);
        var express = $(this).attr("data-express");
        $(".send-modal input[name=express]").val(express);
        $(".send-modal").modal("show");
    });

    $(document).on("click", ".send-confirm-btn", function () {
        var btn = $(this);
        var error = $(".send-form").find(".form-error");
        btn.btnLoading("正在提交");
        error.hide();
        console.log(error);
        $.ajax({
            url: "<?=$urlManager->createUrl([$urlStr . '/send'])?>",
            type: "post",
            data: $(".send-form").serialize(),
            dataType: "json",
            success: function (res) {
                if (res.code == 0) {
                    btn.text(res.msg);
                    location.reload();
                    $(".send-modal").modal("hide");
                }
                if (res.code == 1) {
                    btn.btnReset();
                    $.alert({
                        content: res.msg,
                        confirm: function () {
                        }
                    });
//                    error.html(res.msg).show();
                }
            }
        });
    });


</script>
<!--打印函数-->
<script>
    var LODOP; //声明为全局变量

    $(document).on('click', '.print', function () {
        var id = $(".send-modal input[name=order_id]").val();
        var express = $(".send-modal input[name=express]").val();
        var post_code = $(".send-modal input[name=post_code]").val();
        $.ajax({
            url: "<?=$urlManager->createUrl([$urlStr . '/print'])?>",
            type: 'get',
            dataType: 'json',
            data: {
                id: id,
                express: express,
                post_code: post_code
            },
            success: function (res) {
                if (res.code == 0) {
                    $(".send-modal input[name=express_no]").val(res.data.Order.LogisticCode);
                    LODOP.PRINT_INIT("");
                    LODOP.ADD_PRINT_HTM(10, 50, '100%', '100%', res.data.PrintTemplate);
                    LODOP.PRINT_DESIGN();
                } else {
                    $.myAlert({
                        content: res.msg
                    });
                }
            }
        });
    });
</script>

<script>
    $(document).on('click', '.about', function () {
        var order_id = $(this).data('id');
        $('.order-id').val(order_id);
        var url = $(this).data('url');
        $('.url').val(url);
        var remarks = $(this).data('remarks');
        $('#seller_comments').val(remarks);
    });

    $(document).on('click', '.remarks', function () {
        var seller_comments = $("#seller_comments").val();
        var btn = $(this);
        var order_id = $('.order-id').val();
        var url = $('.url').val();
        btn.btnLoading("正在提交");
        $.ajax({
            url: url,
            type: "get",
            data: {
                seller_comments: seller_comments,
            },
            dataType: "json",
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    error.html(res.msg).show()
                }
            }
        });
    });

</script>

<script>
    $(document).on('click', '.update', function () {
        var order_id = $(this).data('id');
        $('.order-id').val(order_id);
        var pay_price = $(this).data('money');
        var express_price = $(this).data('express_price');
        app.pay = (pay_price - express_price).toFixed(2);
        app.express = +express_price;
        app.price = parseFloat(app.pay + app.express);
        app.price = app.price.toFixed(2);
    });

    $(document).on('click', '.printPay', function () {
        let pay_price = app.pay;
        let firstPay = app.pay;
        if (+firstPay === pay_price) {
            app.pay = '';
        }
    });

    $(document).on('click', '.printExpress', function () {
        let express_price = app.express;
        let firstExpress = app.express;
        if (+firstExpress === express_price) {
            app.express = '';
        }
    });

    $(document).on('click', '.change-price', function () {
        var btn = $(this);
        var order_id = $('.order-id').val();
        var express_price = app.express;
        var pay_price = +app.pay + +app.express;
        var error = $('.form-error');
        error.hide();
        btn.btnLoading(btn.text());
        var url = "<?=$urlManager->createUrl([$urlStr . '/update-price'])?>"
        $.ajax({
            url: url,
            type: 'get',
            dataType: 'json',
            data: {
                pay_price: pay_price,
                order_id: order_id,
                express_price: express_price,
            },
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    alert(res.msg)
                }
            },
            complete: function (res) {
                btn.btnReset();
            }
        });
    });

    $(document).on('click', '.is-express', function () {
        if ($(this).val() == 0) {
            $('.is-true-express').prop('hidden', true);
        } else {
            $('.is-true-express').prop('hidden', false);
        }
    });

    $(document).on('click', '.clerk-btn', function () {
        $('.order_id').val($(this).data('order-id'));
        $('.clerk-modal').modal('show');
    });
</script>