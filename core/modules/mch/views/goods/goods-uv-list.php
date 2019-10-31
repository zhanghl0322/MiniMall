<?php
defined('YII_ENV') or exit('Access Denied');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29
 * Time: 9:50
 */

use yii\widgets\LinkPager;

$urlManager = Yii::$app->urlManager;
$imgurl = Yii::$app->request->baseUrl;
$this->title = '商品浏览UV列表';
$this->params['active_nav_group'] = 2;
$urlStr = get_plugin_url();
$show = true;
if (in_array(get_plugin_type(), [0])) {
    $show = true;
} else {
    $show = false;
}
?>
<style>
    table {
        table-layout: fixed;
    }

    th {
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td {
        text-align: center;
        line-height: 30px;
    }

    .ellipsis {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    td.nowrap {
        white-space: nowrap;
        overflow: hidden;
    }

    .goods-pic {
        margin: 0 auto;
        width: 3rem;
        height: 3rem;
        background-color: #ddd;
        background-size: cover;
        background-position: center;
    }
</style>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>

    <div class="panel-body">
        <div class="mb-3 clearfix">
            <form method="get">
                <?php $_s = ['keyword', 'date_start', 'date_end', 'page', 'per-page'] ?>
                <?php foreach ($_GET as $_gi => $_gv) :
                    if (in_array($_gi, $_s)) {
                        continue;
                    } ?>
                    <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                <?php endforeach; ?>
                <div flex="dir:left">
                    <div class="mr-3 ml-3">
                        <div class="form-group row">
                            <div>
                                <label class="col-form-label">日期：</label>
                            </div>
                            <div>
                                <div class="input-group">
                                    <input class="form-control" id="date_start" name="date_start"
                                           autocomplete="off"
                                           value="<?= isset($_GET['date_start']) ? trim($_GET['date_start']) : '' ?>">
                                    <span class="input-group-btn">
                                        <a class="btn btn-secondary" id="show_date_start" href="javascript:">
                                            <span class="iconfont icon-daterange"></span>
                                        </a>
                                    </span>
                                    <span class="middle-center input-group-addon" style="padding:0 4px">至</span>
                                    <input class="form-control" id="date_end" name="date_end"
                                           autocomplete="off"
                                           value="<?= isset($_GET['date_end']) ? trim($_GET['date_end']) : '' ?>">
                                    <span class="input-group-btn">
                                        <a class="btn btn-secondary" id="show_date_end" href="javascript:">
                                            <span class="iconfont icon-daterange"></span>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row ml-1">
                    <div class="form-group">
                        <button style="margin-left: 3px;" class="btn btn-primary mr-4">筛选</button>
                    </div>
                </div>
            </form>
        </div>
        <table class="table table-bordered bg-white table-hover">
            <thead>
            <tr>
                <th style="text-align: center;text-overflow:clip;">
                    <label class="checkbox-label" style="margin-right: 0px;">
                        <input type="checkbox" class="goods-all">
                        <span class="label-icon"></span>
                    </label>
                </th>
                <th>
                    <span class="label-text">商品ID</span>
                </th>

                <th>商品名称</th>
                <th>商品UV</th>


            </tr>
            </thead>
            <col style="width: 2.5%">

            <col style="width: 12%">
            <col style="width: 6%">
            <col style="width: 3%">

            <tbody>
            <?php foreach ($list as $index => $goods) : ?>
                <tr>
                    <td class="nowrap" style="text-align: center;">
                        <label class="checkbox-label" style="margin-right: 0px;">
                            <input data-num="<?= $index?>" type="checkbox"
                                   class="goods-one"
                                   value="<?= $index?>">
                            <span class="label-icon"></span>
                        </label>
                    </td>
                    <td data-toggle="tooltip"
                        data-placement="top" title="<?= $goods['goods_id'] ?>" >
                        <span class="label-text"><?= $goods['goods_id']  ?></span>
                    </td>
                    <td data-toggle="tooltip"
                        data-placement="top" title="<?= $goods['name'] ?>" >
                        <span class="label-text"><?= $goods['name']  ?></span>
                    </td>
                    <td data-toggle="tooltip"
                              data-placement="top" title="<?= $goods['times'] ?>" >
                        <span class="label-text"><?= $goods['times']  ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
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
</div>

<script>
    $("#myModal").modal({backdrop: "static", keyboard: false});

    $("#closeModel").click(function () {
        $("#goods_qrcode_wx").attr("src", '').hide();
        $("#goods_qrcode_my").attr("src", '').hide();
    });

    var GoodsQrcodeUrl = "<?= $urlManager->createUrl([$urlStr . '/goods-qrcode']) ?>";

    function getGoodsQrcode(id) {
        $("#goods_qrcode_wx").attr("src", '').hide();
        $("#goods_qrcode_my").attr("src", '').hide();
        $.ajax({
            url: GoodsQrcodeUrl,
            type: 'get',
            dataType: 'json',
            data: {
                goods_id: id
            },
            success: function (res) {
                if (res.code == 0) {console.log(res);
                    if(res.data.wx){
                        $("#goods_qrcode_wx").attr("src", res.data.wx).show();
                    }
                    if(res.data.my){
                        $("#goods_qrcode_my").attr("src", res.data.my).show();
                    }
                } else {
                    alert('获取小程序码失败');
                }
            }
        });
    }

    $(document).on('click', '.del', function () {
        if (confirm("是否删除？")) {
            $.ajax({
                url: $(this).attr('href'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    alert(res.msg);
                    if (res.code == 0) {
                        window.location.reload();
                    }
                }
            });
        }
        return false;
    });

    function upDown(id, type) {
        var text = '';
        if (type == 'up') {
            text = "上架";
        } else if (type == 'start') {
            text = "加入快速购买";
        } else if (type == 'close') {
            text = "关闭快速购买";
        } else {
            text = '下架';
        }

        var url = "<?= $urlManager->createUrl([$urlStr . '/goods-up-down']) ?>";
        layer.confirm("是否" + text + "？", {
            btn: [text, '取消'] //按钮
        }, function () {
            layer.msg('加载中', {
                icon: 16
                , shade: 0.01
            });
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'json',
                data: {id: id, type: type},
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    }
                    if (res.code == 1) {
                        layer.alert(res.msg, {
                            skin: 'layui-layer-molv'
                            , closeBtn: 0
                            , anim: 4 //动画类型
                        });
                        if (res.return_url) {
                            location.href = res.return_url;
                        }
                    }
                }
            });
        });
        return false;
    }

    $(document).on('click', '.goods-all', function () {
        var checked = $(this).prop('checked');
        $('.goods-one').prop('checked', checked);
        if (checked) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.goods-one', function () {
        var checked = $(this).prop('checked');
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        var is_use = false;//只要有一个选中，批量按妞就可以使用
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_use = true;
            } else {
                is_all = false;
            }
        });
        if (is_all) {
            $('.goods-all').prop('checked', true);
        } else {
            $('.goods-all').prop('checked', false);
        }
        if (is_use) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.batch', function () {
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_all = false;
            }
        });
        if (is_all) {
            $.myAlert({
                content: "请先勾选商品"
            });
        }
    });
    // 批量设置积分
    $(document).on('click', '.save-attr-btn', function () {
        var give = $('input[name^="integral[give]"]').val();
        var forehead = $('input[name^="integral[forehead]"]').val();
//        var more = $('input[name^="integral[more]"]').val();
        if ($('input[name^="integral[more]"]').is(':checked')) {
            var more = 1;
        } else {
            var more = '';
        }
        console.log(more);
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_all = false;
            }
        });
        if (is_all) {
            $.myAlert({
                content: "请先勾选商品"
            });
            return;
        }
        var a = $(this);
        var goods_group = [];
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                var goods = {};
                goods_group.push($(all[i]).val());
            }
        });

        $.ajax({
            url: "<?= Yii::$app->urlManager->createUrl([$urlStr . '/batch-integral']) ?>",
            type: 'get',
            dataType: 'json',
            data: {
                goods_group: goods_group,
                give: give,
                forehead: forehead,
                more: more,
            },
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    $('.modelError').text(res.msg);
                    $('.modelError').css('display', 'block');
                }
            },
//            complete: function () {
//                $.myLoadingHide();
//            }
        });


    });
    $(document).on('click', '.is_use', function () {
        var a = $(this);
        var goods_group = [];
        var all = $('.goods-one');
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                var goods = {};
                goods.id = $(all[i]).val();
                goods.num = $(all[i]).data('num');
                goods_group.push(goods);
            }
        });
        $.myConfirm({
            content: a.data('content'),
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: a.data('url'),
                    type: 'get',
                    dataType: 'json',
                    data: {
                        goods_group: goods_group,
                        type: a.data('type'),
                    },
                    success: function (res) {
                        if (res.code == 0) {
                            $.myAlert({
                                content: res.msg,
                                confirm: function () {
                                    window.location.reload();
                                }
                            });
                        } else {
                            $.myAlert({
                                content: res.msg
                            });
                        }
                    },
                    complete: function () {
                        $.myLoadingHide();
                    }
                });
            }
        })
    });
</script>
<script>
    $(document).ready(function () {
        var clipboard = new Clipboard('.copy');
        clipboard.on('success', function (e) {
            $.myAlert({
                title: '提示',
                content: '复制成功'
            });
        });
        clipboard.on('error', function (e) {
            $.myAlert({
                title: '提示',
                content: '复制失败，请手动复制。链接为：' + e.text
            });
        });
    })

    //TODO 新增监控is_switch  按钮 动作
    $(document).on('change', 'input[class=is_switch_best]', function () {
        var id = $(this).attr('data-id');
        var status = 0;
        if ($(this).prop('checked'))
            status = 1;
        else
            status = 0;
        $.loading();
        $.ajax({
            url: '<?= $urlManager->createUrl([$urlStr . '/set-best']) ?>',
            dataType: 'json',
            data: {
                id: id,
                status: status
            },
            success: function (res) {
                $.toast({
                    content: res.msg,
                });
            },
            complete: function () {
                $.loadingHide();
            }
        });
    });

    //TODO 新增监控is_switch  按钮 动作  界面操作控制 商品是否展示  2019年8月7日16:51:52
    $(document).on('change', 'input[class=is_switch_show]', function () {

        var id = $(this).attr('data-id');
        var status = 0;
        if ($(this).prop('checked'))
            status = 1;
        else
            status = 0;

        $.toast({
            content: status,
        });
        $.loading();
        $.ajax({
            url: '<?= $urlManager->createUrl([$urlStr . '/set-show']) ?>',
            dataType: 'json',
            data: {
                id: id,
                status: status
            },
            success: function (res) {
                $.toast({
                    content: res.msg,
                });
            },
            complete: function () {
                $.loadingHide();
            }
        });
    });

</script>