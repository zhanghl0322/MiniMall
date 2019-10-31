<?php
/**
 * link: http://www.zjhejiang.com/
 * copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 * author: wxf
 */

namespace app\modules\mch\models;


use app\modules\mch\extensions\Export;

class GoodsExportList
{
    public $fields;
    //TODO 添加商品Excel 导出列头 2019年9月26日14:47:36
    public function excelFields()
    {
        $list = [
            [
                'key' => 'id',
                'value' => '商品ID',
                'selected' => 0,
            ],
            [
                'key' => 'supplier_name',
                'value' => '供应商',
                'selected' => 0,
            ],
            [
                'key' => 'mch_id',
                'value' => '商户名称',
                'selected' => 0,
            ],
            [
                'key' => 'mch_id',
                'value' => '商品类型',
                'selected' => 0,
            ],
            [
                'key' => 'name',
                'value' => '商品名称',
                'selected' => 0,
            ],
            [
                'key' => 'pic_url',
                'value' => '商品图片',
                'selected' => 0,
            ],
            [
                'key' => 'price',
                'value' => '售价',
                'selected' => 0,
            ],
//            [
//                'key' => 'num',
//                'value' => '库存',
//                'selected' => 0,
//            ],
            [
                'key' => 'quick_purchase',
                'value' => '是否快速购买',
                'selected' => 0,
            ],
            [
                'key' => 'is_best',
                'value' => '是否店长推荐',
                'selected' => 0,
            ],
            [
                'key' => 'is_show',
                'value' => '是否展示商品',
                'selected' => 0,
            ],
            [
                'key' => 'virtual_sales',
                'value' => '已出售量',
                'selected' => 0,
            ],
            [
                'key' => 'sort',
                'value' => '排序',
                'selected' => 0,
            ]
        ];

        return $list;
    }

    /**
     * 商品列表导出
     */
    public function GoodsForm($data)
    {
        //筛选已选择的字段,并重新组合数据结构
        $newFields = [];
        foreach ($this->fields as $item) {
            if ($item['selected'] == 1) {
                $newFields[$item['key']] = $item['value'];
            }
        }
//        $status = ['商户A', '商户B'];//类型转换
        $handle = fopen('php://temp', 'rwb');
        $EXCEL_OUT = Export::order_title($newFields);
        fwrite($handle, $EXCEL_OUT);
        $newData = [];
        foreach ($data as $item) {
            $temporaryData = [];
            $temporaryData['platform'] =  '微信';
            $temporaryData['id'] = $item['id'];
            $temporaryData['name'] = $item['name'];
            $temporaryData['supplier_name'] = $item['supplier_name'];
            $temporaryData['mch_id'] = $item['mch_id'];

            $temporaryData['price'] = $item['price'];
//            $temporaryData['num'] = $item['num'];
            $temporaryData['quick_purchase'] = $item['quick_purchase']==1?"加入":"不加入";

            $temporaryData['is_best'] = $item['is_best']==1?"推荐":"不推荐";
            $temporaryData['is_show'] = $item['is_show']==1?"显示":"不显示";
            $temporaryData['virtual_sales'] = $item['virtual_sales'];

            $temporaryData['sort'] = $item['sort'];
            $newData[] = $temporaryData;
        }
        $EXCEL_OUT = Export::order_new($newData, $newFields);
        fwrite($handle, $EXCEL_OUT);
        $name = date('YmdHis', time()) . rand(1000, 9999); //导出文件名称
        \Yii::$app->response->sendStreamAsFile($handle, $name . '.csv');
    }

}