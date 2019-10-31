<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10
 * Time: 10:19
 */
defined('YII_ENV') or exit('Access Denied');
$urlManager = Yii::$app->urlManager;
$this->title = '商品详情';
$staticBaseUrl = Yii::$app->request->baseUrl . '/statics';
?>
<style>

    .step-block {
        position: relative;
        transition: 200ms;
    }

    form .body {
        padding-top: 45px;
    }

    .step-block > div {
        padding: 20px;
        border: 1px solid #eee;
        transition: 200ms;
    }

    .step-block:hover {
        box-shadow: 0 1px 8px rgba(0, 0, 0, .15);
        z-index: 2;
    }

    .step-block:hover > div {
        border-color: #e3e3e3;
    }

    .step-block > div:first-child {
        padding: 20px;
        width: 120px;
        font-weight: bold;
        text-align: center;
        border-right: none;
    }

    .step-block .step-location {
        position: absolute;
        top: -122px;
        left: 0;
    }

    .step-block:first-child .step-location {
        top: -140px;
    }

    .edui-editor,
    #edui1_toolbarbox {
        z-index: 2 !important;
    }

    form .short-row {
        width: 380px;
    }

    form .form-group .col-3 {
        -webkit-box-flex: 0;
        -webkit-flex: 0 0 160px;
        -ms-flex: 0 0 160px;
        flex: 0 0 160px;
        max-width: 160px;
        width: 160px;
    }

    .cat-list .cat-item {
        max-width: 380px;
        background: #f5f7f9;
        padding: .35rem .7rem;
        margin-bottom: .5rem;
        border: 1px solid #f0f2f4;
    }

    .select-cat-list > div {
        margin-bottom: 1rem;
    }

    .select-cat-list .cat-item {
        display: inline-block;
        background: #f5f7f9;
        padding: .35rem .7rem;
        cursor: pointer;
        border: 1px solid #f5f7f9;
        transition: 150ms;
        float: left;
        margin-right: .5rem;
    }

    .select-cat-list .cat-item:hover {
        border: 1px solid #0275d8;
    }

    .select-cat-list .cat-item.checked {
        background: #0275d8;
        color: #fff;
        border: 1px solid #0275d8;
    }

    .publish-bar {
        position: fixed;
        bottom: 0;
        right: 0;
        z-index: 10;
        border: 1px solid #ccd0d4;
        left: 240px;
        text-align: center;
        padding: .5rem;
        background: #dde2e6;
    }

    .main-body {
        padding-bottom: 3.2rem !important;
    }

    .attr-group-list .attr-group-item:after {
        display: block;
        content: " ";
        height: 0;
        width: calc(100% + 2rem);
        margin-left: -1rem;
        border-bottom: 1px solid #eee;
    }

    .attr-group-list .attr-group-item {
        margin-bottom: 1rem;
    }

    .attr-group-list .attr-group-item:last-child {
        margin-bottom: 0;
    }

    .attr-group-list .attr-group-item:last-child:after {
        display: none;
    }

    .attr-item {
        display: inline-block;
        position: relative;
        background: #fff;
        padding: .25rem .5rem;
        margin-right: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #eee;
    }

    .attr-group-delete,
    .attr-item .attr-item-delete,
    .attr-row-delete-pic {
        display: inline-block;
        background: #fff;
        border: 1px solid #979797;
        color: #725755 !important;
        text-decoration: none !important;
        width: 1rem;
        height: 1rem;
        line-height: .75rem;
        text-align: center;
        transition: 150ms;
        transform: translateY(-.08rem);
    }

    .attr-group-delete:hover,
    .attr-item .attr-item-delete:hover,
    .attr-row-delete-pic:hover {
        border: 1px solid #ff4544;
        color: #fff !important;
        background: #ff4544;
    }

    td {
        cursor: default;
    }

    .input-td {
        padding: 0 .5rem !important;
        width: 8rem;
        vertical-align: middle;
    }

    .input-td input {
        display: inline-block;
        margin: 0;
        width: 100%;
        border: none;
        color: inherit;
        text-align: center;
        cursor: text;
        height: 100%;
    }

    .input-td input:focus {
        outline: none;
    }

</style>

<div class="panel" id="app">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <form class="auto-form" method="post" return="<?= $returnUrl ?>">
            <!-- 选择分类-->
            <div class="step-block" flex="dir:left box:first">
                <div>
                    <span>选择分类</span>
                </div>
                <div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">商品分类</label>
                        </div>
                        <div class="col-9">
                            <div class="cat-list">
                                <?php foreach ($cat_list as $value) : ?>
                                    <div class="cat-item" flex="dir:left box:last">
                                        <div><?= $value['name'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 基本信息-->
            <div class="step-block" flex="dir:left box:first">
                <div>
                    <span>基本信息</span>
                    <span class="step-location" id="step2"></span>
                </div>
                <div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label required">商品名称</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" type="text" name="model[name]"
                                   value="<?= str_replace("\"", "&quot", $goods['name']) ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">单位</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" type="text" name="model[unit]"
                                   value="<?= $goods['unit'] ? $goods['unit'] : '件' ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">商品排序</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" type="text" name="model[sort]"
                                   value="<?= $goods['sort'] ?>">
                            <div class="text-muted fs-sm">排序按升序排列</div>
                        </div>
                    </div>

                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="form-group row" >
                        <div class="col-3 text-right">
                            <label class=" col-form-label">已出售量</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" type="number"
                                   name="model[virtual_sales]"
                                   value="<?= $goods['virtual_sales'] ?>" min="0" max="999999">
                            <div class="text-muted fs-sm">前端展示的销量=实际销量+已出售量</div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">限购数量</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" type="number"
                                   name="model[confine_count]"
                                   value="<?= $goods['confine_count'] ?>" min="0" max="999999">
                            <div class="text-muted fs-sm">设置为0则不限购，大于0则等于对应的限购数量</div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">重量</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" step="0.01" class="form-control"
                                       name="model[weight]"
                                       value="<?= $goods['weight'] ? $goods['weight'] : 0 ?>">
                                <span class="input-group-addon">克<span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class="col-form-label required">商品缩略图</label>
                        </div>
                        <div class="col-9">
                            <div class="upload-group short-row">
                                <div class="input-group">
                                    <input class="form-control file-input" name="model[cover_pic]"
                                           value="<?= $goods->cover_pic ?>">
                                    <span class="input-group-btn">
                                        <a class="btn btn-secondary upload-file" href="javascript:"
                                           data-toggle="tooltip"
                                           data-placement="bottom" title="上传文件">
                                            <span class="iconfont icon-cloudupload"></span>
                                        </a>
                                    </span>
                                    <span class="input-group-btn">
                                        <a class="btn btn-secondary select-file" href="javascript:"
                                           data-toggle="tooltip"
                                           data-placement="bottom" title="从文件库选择">
                                            <span class="iconfont icon-viewmodule"></span>
                                        </a>
                                    </span>
                                    <span class="input-group-btn">
                                        <a class="btn btn-secondary delete-file" href="javascript:"
                                           data-toggle="tooltip"
                                           data-placement="bottom" title="删除文件">
                                            <span class="iconfont icon-close"></span>
                                        </a>
                                    </span>
                                </div>
                                <div class="upload-preview text-center upload-preview">
                                    <span class="upload-preview-tip">325&times;325</span>
                                    <img class="upload-preview-img" src="<?= $goods->cover_pic ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">商品视频</label>
                        </div>
                        <div class="col-9">
                            <div class="video-picker"
                                 data-url="<?= $urlManager->createUrl(['upload/video']) ?>">
                                <div class="input-group short-row">
                                    <input class="video-picker-input video form-control"
                                           name="model[video_url]"
                                           value="<?= $goods['video_url'] ?>"
                                           placeholder="请输入视频源地址或者选择上传视频">
                                    <a href="javascript:"
                                       class="btn btn-secondary video-picker-btn">选择视频</a>
                                </div>
                                <a class="video-check"
                                   href="<?= $goods['video_url'] ? $goods['video_url'] : "javascript:" ?>"
                                   target="_blank">视频预览</a>

                                <div class="video-preview"></div>
                                <div>
                                    <span
                                            class="text-danger fs-sm">支持格式mp4;支持编码H.264;视频大小不能超过<?= \app\models\UploadForm::getMaxUploadSize() ?>
                                        MB</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class="col-form-label required">商品图片</label>
                        </div>
                        <div class="col-9">
                            <?php if ($goods->goodsPicList) :
                                foreach ($goods->goodsPicList as $goods_pic) : ?>
                                    <?php $goods_pic_list[] = $goods_pic->pic_url ?>
                                <?php endforeach;
                            else :
                                $goods_pic_list = [];
                            endif; ?>

                            <div class="upload-group multiple short-row">
                                <div class="input-group">
                                    <input class="form-control file-input" readonly>
                                    <span class="input-group-btn">
                                                        <a class="btn btn-secondary upload-file" href="javascript:"
                                                           data-toggle="tooltip"
                                                           data-placement="bottom" title="上传文件">
                                                            <span class="iconfont icon-cloudupload"></span>
                                                        </a>
                                                    </span>
                                    <span class="input-group-btn">
                                                        <a class="btn btn-secondary select-file" href="javascript:"
                                                           data-toggle="tooltip"
                                                           data-placement="bottom" title="从文件库选择">
                                                            <span class="iconfont icon-viewmodule"></span>
                                                        </a>
                                                    </span>
                                </div>
                                <div class="upload-preview-list" id="sortList">
                                    <?php if (count($goods_pic_list) > 0) : ?>
                                        <?php foreach ($goods_pic_list as $item) : ?>
                                            <div class="upload-preview text-center" flex="cross:center">
                                                <input type="hidden" class="file-item-input"
                                                       name="model[goods_pic_list][]"
                                                       value="<?= $item ?>">
                                                <span class="file-item-delete">&times;</span>
                                                <span class="upload-preview-tip">750&times;750</span>
                                                <img class="upload-preview-img" src="<?= $item ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="upload-preview text-center">
                                            <input type="hidden" class="file-item-input"
                                                   name="model[goods_pic_list][]">
                                            <span class="file-item-delete">&times;</span>
                                            <span class="upload-preview-tip">750&times;750</span>
                                            <img class="upload-preview-img" src="">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label required">售价</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" step="0.01" class="form-control"
                                       name="model[price]" min="0.01"
                                       value="<?= $goods['price'] ? $goods['price'] : 1 ?>">
                                <span <?= in_array(get_plugin_type(), [0,2]) ? 'hidden' : '' ?> class="input-group-addon">活力币</span>
                                <span <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="input-group-addon">元</span>
                            </div>
                        </div>
                    </div>

                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">成本价</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" step="0.01" class="form-control"
                                       name="model[cost_price]" min="0.01"
                                       value="<?= $goods['cost_price'] ? $goods['cost_price'] : 1 ?>">
                                <span class="input-group-addon">元</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label required">原价</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" step="0.01" class="form-control short-row"
                                       name="model[original_price]" min="0"
                                       value="<?= $goods['original_price'] ? $goods['original_price'] : 1 ?>">
                                <span class="input-group-addon">元</span>
                            </div>
                        </div>
                    </div>


                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">服务内容</label>
                        </div>
                        <div class="col-9">
                            <input class="form-control short-row" name="model[service]"
                                   value="<?= $goods['service'] ?>">
                            <div class="fs-sm text-muted">例子：正品保障,极速发货,7天退换货。多个请使用英文逗号<kbd>,</kbd>分隔
                            </div>
                        </div>
                    </div>

                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden_x' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">运费设置</label>
                        </div>
                        <div class="col-9">
                            <select class="form-control short-row" name="model[freight]">
                                <option value="0">默认模板</option>
                                <?php foreach ($postageRiles as $p) : ?>
                                    <option
                                            value="<?= $p->id ?>" <?= $p->id == $goods['freight'] ? 'selected' : '' ?>><?= $p->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">单品满件包邮</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" class="form-control short-row"
                                       name="full_cut[pieces]"
                                       value="<?= $goods['full_cut']['pieces'] ?>">
                                <span class="input-group-addon">件</span>
                            </div>
                            <div class="fs-sm text-muted">如果设置0或空，则不支持满件包邮</div>
                        </div>
                    </div>

                    <div <?= in_array(get_plugin_type(), [5]) ? 'hidden' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label">单品满额包邮</label>
                        </div>
                        <div class="col-9">
                            <div class="input-group short-row">
                                <input type="number" step="0.01" class="form-control short-row"
                                       name="full_cut[forehead]"
                                       value="<?= $goods['full_cut']['forehead'] ?>">
                                <span class="input-group-addon">元</span>
                            </div>
                            <div class="fs-sm text-muted">如果设置0或空，则不支持满额包邮</div>
                        </div>
                    </div>
                    <div <?= in_array(get_plugin_type(), [2,5]) ? 'hidden' : '' ?> class="form-group row">
                        <div class="col-3 text-right">
                            <label class="col-form-label">是否开启面议</label>
                        </div>
                        <div class="col-9">
                            <label class="radio-label">
                                <input <?= $goods['is_negotiable'] == 0 ? 'checked' : null ?>
                                        value="0" name="model[is_negotiable]" type="radio"
                                        class="custom-control-input">
                                <span class="label-icon"></span>
                                <span class="label-text">关闭</span>
                            </label>
                            <label class="radio-label">
                                <input <?= $goods['is_negotiable'] == 1 ? 'checked' : null ?>
                                        value="1" name="model[is_negotiable]" type="radio"
                                        class="custom-control-input">
                                <span class="label-icon"></span>
                                <span class="label-text">开启</span>
                            </label>

                            <div class="fs-sm text-danger">如果开启面议，则商品无法在线支付</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 规格库存-->
            <div class="step-block" flex="dir:left box:first">
                <div>
                    <span>规格库存</span>
                    <span class="step-location" id="step3"></span>
                </div>
                <div>
                    <?php if ($goods['use_attr'] == 0) : ?>
                        <div class="form-group row">
                            <div class="col-3 text-right">
                                <label class=" col-form-label">商品库存</label>
                            </div>
                            <div class="col-9">
                                <div class="input-group short-row">
                                    <label class=" col-form-label"><?= $goods['goods_num'] ?>件</label>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="form-group row">
                            <div class="col-3 text-right">
                                <label class=" col-form-label">规格库存设置</label>
                            </div>
                            <div class="col-9">
                                <table class="table table-bordered table-sm mb-0 bg-white">
                                    <tr>
                                        <?php foreach ($attr_group_list as $group) : ?>
                                            <th class="text-center"><?= $group['attr_group_name'] ?></th>
                                        <?php endforeach; ?>
                                        <th class="text-center">价格</th>
                                        <th class="text-center">库存</th>
                                        <th class="text-center">编号</th>
                                        <th class="text-center">图片</th>
                                    </tr>
                                    <?php foreach ($attr_row_list as $row_index => $attr_row) : ?>
                                        <tr class="text-center">
                                            <?php foreach ($attr_row['attr_list'] as $attr_index => $attr) : ?>
                                                <td><?= $attr['attr_name'] ?></td>
                                            <?php endforeach; ?>
                                            <td><?= $attr_row['price'] ?></td>
                                            <td><?= $attr_row['num'] ?></td>
                                            <td><?= $attr_row['no'] ?></td>
                                            <td>
                                                <?php if ($attr_row['pic']) : ?>
                                                    <img src="<?= $attr_row['pic'] ?>"
                                                         style="height: 1.5rem;width: 1.5rem;border-radius: .15rem">
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
            <!--图文详情-->
            <div class="step-block" flex="dir:left box:first">
                <div>
                    <span>图文详情</span>
                    <span class="step-location" id="step4"></span>
                </div>
                <div>
                    <div class="form-group row">
                        <div class="col-3 text-right">
                            <label class=" col-form-label required">图文详情</label>
                        </div>
                        <div class="col-9">
                            <textarea class="short-row" id="editor"
                                      name="model[detail]"><?= $goods['detail'] ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!--底部保存按钮-->
            <div style="margin-left: 0;" class="form-group row text-center">
                <a class="btn btn-primary auto-form-btn" href="javascript:">保存</a>
                <input type="button" class="btn btn-default ml-4"
                       name="Submit" onclick="javascript:history.back(-1);" value="返回">
            </div>
        </form>
    </div>
</div>


<script src="<?= Yii::$app->request->baseUrl ?>/statics/ueditor/ueditor.config.js?v=1.9.6"></script>
<script src="<?= Yii::$app->request->baseUrl ?>/statics/ueditor/ueditor.all.min.js?v=1.9.6"></script>
<script>

    var ue = UE.getEditor('editor', {
        serverUrl: "<?=$urlManager->createUrl(['upload/ue'])?>",
        enableAutoSave: false,
        saveInterval: 1000 * 3600,
        enableContextMenu: false,
        autoHeightEnabled: false,
        toolbars: []
    });
</script>