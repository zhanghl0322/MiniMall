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
$this->params['active_nav_group'] = 3;
$status = Yii::$app->request->get('status');
$is_recycle = Yii::$app->request->get('is_recycle');
$user_id = Yii::$app->request->get('user_id');
$condition = ['user_id' => $user_id, 'clerk_id' => $_GET['clerk_id'], 'shop_id' => $_GET['shop_id']];
if ($status === '' || $status === null || $status == -1) {
    $status = -1;
}
if ($is_recycle == 1) {
    $status = 12;
}
$urlStr = get_plugin_url();
$urlPlatform = Yii::$app->requestedRoute;
$this->title = '订单列表(全部)';
Yii::warning($urlManager->createUrl([$urlStr . '/edit', 'order_id' =>1, 'is_recycle' => 1]).'----测试测试','info');
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
        width: 40%;
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
        width: 20%;
        text-align: center;
    }

    .order-tab-5 {
        width: 10%;
        text-align: center;
    }

    .status-item.active {
        color: inherit;
    }
</style>
<div class="panel mb-3" id="app">
    <div class="panel-header"><?= $this->title ?></div>
<!--    搜索栏区域-->
    <div class="panel-body">
        <?= $this->render('/layouts/order-search/order-search-list', [
            'urlPlatform' => $urlPlatform,
            'urlStr' => $urlStr
        ]) ?>
    </div>
    <div class="mb-4">
        <ul class="nav nav-tabs status">
            <li class="nav-item">
                <a class="status-item nav-link <?= $status == -1 ? 'active' : null ?>"
                   href="<?= $urlManager->createUrl(array_merge([$_GET['r']], $condition, ['status' => -1], $condition, ['page' => 1])) ?>">全部</a>
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
        </ul>
    </div>
    <table class="table table-bordered bg-white">
        <tr>
            <th class="order-tab-1">商品信息</th>
            <th class="order-tab-2">金额</th>
<!--            <th class="order-tab-3">实际付款</th>-->
            <th class="order-tab-4">订单状态</th>
            <th class="order-tab-5">操作</th>
        </tr>
    </table>
    <?php foreach ($order_list as $k => $order_item) : ?>
        <div class="order-item" style="<?= 0 == 1 ? 'color:#ff4544' : '' ?>">
            <?php if (0 == 1) : ?>
                <div class="text-danger">注：此订单数据异常，请谨慎发货，及时联系管理员处理</div>
            <?php endif; ?>
            <table class="table table-bordered bg-white">
                <!--单头信息-->
                <tr>
                    <td colspan="5">
                            <span class="mr-3"><span
                                        class='titleColor'>下单时间：</span><?= $order_item['addtime'] ?></span>
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
                <!-- 中部商品-->
                <tr>
                    <td class="order-tab-1">
                        <?php foreach ($order_item['goods_list'] as $goods_item) : ?>
                            <div class="goods-item" flex="dir:left box:first">
                                <div class="fs-0">
                                    <div class="goods-pic"
                                         style="background-image: url('<?= $goods_item['goods_pic'] ?>')"></div>
                                </div>
                                <div class="goods-info">
                                    <div class="goods-name"><?= $goods_item['goods_name'] ?></div>
                                    <div class="mt-1">
                                        <span class="fs-sm">
                                            规格：
                                        <span class="text-danger">
                                            <?php $attr_list = json_decode($goods_item['attr']); ?>
                                            <?php if (is_array($attr_list)) :
                                                foreach ($attr_list as $attr) : ?>
                                                    <span class="mr-3"><?= $attr->attr_group_name ?>
                                                        :<?= $attr->attr_name ?></span>
                                                <?php endforeach;;
                                            endif; ?>
                                        </span>
                                        </span>
                                        <span class="fs-sm">数量：
                                            <span class="text-danger"><?= $goods_item['num'] ?></span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="fs-sm">小计：
                                            <span class="text-danger mr-4"><?= $goods_item['price'] ?>元</span>
                                        </span>
                                    </div>
                                    <!--TODO 补充商品核对  补充不同商品类型链接 -->
                                    <div>
                                        <?php if ($order_item['order_type']=='zc') :
                                            ?>  <a class="btn btn-sm btn-primary mt-2"
                                                   href="index.php?r=mch%2Fgoods%2Fgoods-edit&id=<?= $goods_item['goods_id']?>">商品核对</a>
                                        <?php endif; ?>
                                        <?php if ($order_item['order_type']=='pt') :
                                            ?>  <a class="btn btn-sm btn-primary mt-2"
                                                   href="index.php?r=mch%2Fgroup%2Fgoods%2Fgoods-edit&id=<?= $goods_item['goods_id']?>">商品核对</a>
                                        <?php endif; ?>
                                        <?php if ($order_item['order_type']=='ms') :
                                            ?> <a class="btn btn-sm btn-primary mt-2"
                                                  href="index.php?r=mch%2Fmiaosha%2Fgoods%2Fedit&id=<?= $goods_item['goods_id']?>">商品核对</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td class="order-tab-3">
                        <div><span style="color:blue;"><?= $order_item['pay_price'] ?></span>元</div>
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
                            <?php if (0 == 1) : ?>
                                <span class="badge badge-warning mt-1">到店自提</span>
                            <?php else : ?>
                                <span class="badge badge-warning mt-1">快递发送</span>
                            <?php endif; ?>
                        </div>

                    </td>
                    <td class="order-tab-5">
                        <div>
                            <?php if ($order_item['order_type']=='zc') :
                                ?> <a class="btn btn-sm btn-primary mt-2"
                                      href="<?= $urlManager->createUrl([$urlStr . '/index', 'keyword' => $order_item['order_no']]) ?>">（正常）预览订单</a>
                            <?php endif; ?>
                            <?php if ($order_item['order_type']=='pt') :
                                ?> <a class="btn btn-sm btn-primary mt-2"
                                      href="<?= $urlManager->createUrl(['mch/group/order/index', 'keyword' =>$order_item['order_no']]) ?>">（拼团）预览订单</a>
                            <?php endif; ?>
                            <?php if ($order_item['order_type']=='ms') :
                                ?><a class="btn btn-sm btn-primary mt-2"
                                      href="<?= $urlManager->createUrl(['mch/miaosha/order/index', 'keyword' =>$order_item['order_no']]) ?>">（秒杀）预览订单</a>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <!-- 底部信息-->
                <tr>
                    <td colspan="5">
                        <div>
                            <?php if (0 == 0) : ?>
                                <span class="mr-2"><span
                                            class="titleColor">收货人：</span><?= $order_item['name'] ?></span>
                                <span class="mr-2"><span
                                            class="titleColor">电话：</span><?= $order_item['mobile'] ?></span>
                                <span class="mr-3"><span
                                            class="titleColor">地址：</span><?= $order_item['address'] ?></span>
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
                                <?php elseif (1== 1) : ?>

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
<!--    查询翻页-->
    <div class="text-center">
        <nav aria-label="Page navigation example">
            <?php echo LinkPager::widget([
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
<!--样式Css区域-->
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
        width: 40%;
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
        width: 20%;
        text-align: center;
    }

    .order-tab-5 {
        width: 10%;
        text-align: center;
    }

    .status-item.active {
        color: inherit;
    }
</style>


