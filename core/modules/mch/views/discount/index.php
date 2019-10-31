<?php
defined('YII_ENV') or exit('Access Denied');

/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/24
 * Time: 10:18
 */

use yii\widgets\LinkPager;

/* @var \app\models\DiscountActivities[] $list */

$urlManager = Yii::$app->urlManager;
$this->title = '满减活动管理';
$this->params['active_nav_group'] = 7;
?>

<style>
    .table tbody tr td{
        vertical-align: middle;
    }
</style>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <a class="btn btn-primary mb-3" href="<?= $urlManager->createUrl(['mch/discount/edit']) ?>">新建满减活动</a>
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>ID</th>
                <th>满减活动名称</th>
                <th>满金额一（元）</th>
                <th>减金额一（元）</th>
                <th>满金额二（元）</th>
                <th>减金额二（元）</th>
                <th>满金额三（元）</th>
                <th>减金额三（元）</th>
                <th>满金额四（元）</th>
                <th>减金额四（元）</th>
                <th>满金额五（元）</th>
                <th>减金额五（元）</th>
                <th>活动时间</th>
                <th>活动说明</th>
                <th>活动状态</th>
                <th>排序</th>
                <th>操作</th>
            </tr>
            </thead>
            <?php foreach ($list as $item) : ?>
                <tr>
                    <td><?= $item->id ?></td>
                    <td><?= $item->name ?></td>
                    <td><?= $item->min_price1 ?></td>
                    <td><?= $item->sub_price1 ?></td>
                    <td><?= $item->min_price2 ?></td>
                    <td><?= $item->sub_price2 ?></td>
                    <td><?= $item->min_price3 ?></td>
                    <td><?= $item->sub_price3 ?></td>
                    <td><?= $item->min_price4 ?></td>
                    <td><?= $item->sub_price4 ?></td>
                    <td><?= $item->min_price5 ?></td>
                    <td><?= $item->sub_price5 ?></td>

                    <td>
                        <span><?= date('Y-m-d', $item->begin_time) ?>-<?= date('Y-m-d', $item->end_time) ?></span>
                    </td>
                    <td>
                        <?= $item->rule ?>
                    </td>
                    <td><?= ($item->is_join == 1) ? "禁用" : "启用" ?></td>
                    <td><?= $item->sort ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary"
                           href="<?= $urlManager->createUrl(['mch/discount/edit', 'id' => $item->id]) ?>">编辑</a>
                        <a class="btn btn-sm btn-danger delete-confirm"
                           href="<?= $urlManager->createUrl(['mch/discount/delete', 'id' => $item->id]) ?>">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<script>
    $(document).on("click", ".delete-confirm", function () {
        var url = $(this).attr("href");
        $.myConfirm({
            content: "确认删除？",
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: url,
                    dataType: "json",
                    success: function (res) {
                        if (res.code == 0) {
                            location.reload();
                        } else {
                            $.myLoadingHide();
                            $.myAlert({
                                content: res.msg,
                            });
                        }
                    }
                });
            },
        });
        return false;
    });
</script>