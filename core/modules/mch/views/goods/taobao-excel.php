<?php
/**
 * @link:http://www.zjhejiang.com/
 * @copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 *
 * Created by PhpStorm.
 * User: 风哀伤
 * Date: 2018/9/4
 * Time: 16:15
 */
$urlManager = Yii::$app->urlManager;
$baseUrl = Yii::$app->request->baseUrl;
$this->title = "商品评论CSV导入";
?>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div style="background-color: #fce9e6;width: 100%;border-color: #edd7d4;color: #e55640;border-radius: 2px;padding: 15px;margin-bottom: 20px;">
            尽量在服务器空闲时间来操作，会占用大量内存与带宽，在获取过程中，请不要进行任何操作!
        </div>
        <div class="card mb-4">
            <div class="card-header">商品评论CSV导入助手</div>
            <div class="card-block">
                <div>功能介绍：可将Excel快速上传至商城,节约您的大量时间! </div>
                <div flex="dir:left box:first">
                    <div>使用方法：</div>
                    <div>
                        <span> 1. 将您获取到的CSV文件转存为Excel格式,否则将无法识别</span>
                        <br>
                        <span>2. 按照下载的模板文件、填充数据 </span>
                        <br>
                        <span>3. 确认上传即可</span>
                    </div>
                </div>
                <div flex="dir:left box:first">
                    <div>示例文件：</div>
                    <div>
                        <a href="<?= $baseUrl . '/chy-goods-pl.xlsx'?>">Excel示例文件</a>
                    </div>
                </div>
                <div class="text-danger">注意：导入的商品评论数量一次尽量控制在1000条以内、防止数据过多内存占用过高</div>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" id="tf">
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">EXCEL</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control" type="file" name="excel"
                           value="">
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                </div>
                <div class="col-sm-6">
                    <button type="submit" class="btn btn-primary btn-submit" href="javascript:">确定导入</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $(document).on('click', '.btn-submit', function() {
        var form = new FormData(document.getElementById("tf"));
        var btn = $(this);
        btn.btnLoading();
        $.ajax({
            type:"post",
            data:form,
            processData:false,
            contentType:false,
            success:function(res){
                btn.btnReset();
                $.myConfirm({
                    content:res.msg,
                    confirm:function(){
                        if(res.code == 0){
                            window.location.reload();
                        }
                    }
                });
            },
        });
        return false;
    });
</script>