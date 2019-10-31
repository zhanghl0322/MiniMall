<?php
/**
 * link: http://www.zjhejiang.com/
 * copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 * author: wxf
 */

namespace app\modules\mch\models;


use app\modules\mch\extensions\Export;

class CouponExportList
{
    public $fields;


    public function getList($type)
    {
        // $type 1.余额充值|2.会员购买|3.积分充值
        $list = [
            [
                'key' => 'couponname',
                'value' => '优惠券名称',
                'type' => [1]
            ],
            [
                'key' => 'sub_price',
                'value' => '金额',
                'type' => [1]
            ],
            [
                'key' => 'min_price',
                'value' => '使用门槛金额',
                'type' => [1]
            ],
            [
                'key' => 'nickname',
                'value' => '昵称',
                'type' => [1]
            ],
            [
                'key' => 'is_use',
                'value' => '使用状态',
                'type' => [1]
            ],
            [
                'key' => 'type',
                'value' => '领取类型',
                'type' => [1]
            ],
            [
                'key' => 'addtime',
                'value' => '领券时间',
                'type' => [1]
            ]
        ];

        $newArr = [];
        foreach ($list as $item) {
            if (in_array($type, $item['type'])) {
                $newArr[] = $item;
            }
        }

        return $newArr;
    }
    /**
     * 优惠券记录导出
     * Allon
     */
    public function couponListForm($query)
    {
        //筛选已选择的字段,并重新组合数据结构
        $newFields = [];
        foreach ($this->fields as $item) {
            if ($item['selected'] == 1) {
                $newFields[$item['key']] = $item['value'];
            }
        }

        $handle = fopen('php://temp', 'rwb');
        $EXCEL_OUT = Export::order_title($newFields);
        fwrite($handle, $EXCEL_OUT);
        $limit = 100;
        $count = $query->count();

        for ($i = 0; $i < $count; $i += $limit) {
           // $data = $query->limit($limit)->offset($i)->orderBy(['uc.addtime' => SORT_DESC])->all();
            $data = $query->orderBy('uc.id DESC')
                ->limit($limit)
                ->offset($i) ->select('uc.id user_coupon_id,c.sub_price,c.min_price,uc.begin_time,uc.end_time,uc.is_use,uc.is_expire,cas.event,uc.type,c.appoint_type,c.cat_id_list,c.goods_id_list,c.name as couponname,uc.addtime,us.nickname')->asArray()
                ->all();
            $newData = [];
            $status = ['平台发放', '自动发放', '领券中心领取'];//类型转换

            foreach ($data as $item) {
                $temporaryData = [];
                $temporaryData['platform'] =  '微信';
                $temporaryData['couponname'] = $item['couponname'];
                $temporaryData['sub_price'] = $item['sub_price'] ;
                $temporaryData['min_price'] = $item['min_price'];
                $temporaryData['nickname'] = $item['nickname'];
                $temporaryData['is_use'] = $item['is_use'] ? '已使用' : '未使用';
                $temporaryData['type'] =$status[$item['type']];
                $temporaryData['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                $newData[] = $temporaryData;
            }
            $EXCEL_OUT = Export::order_new($newData, $newFields);
            fwrite($handle, $EXCEL_OUT);
        }
        $name = date('YmdHis', time()) . rand(1000, 9999); //导出文件名称
        \Yii::$app->response->sendStreamAsFile($handle, $name . '.csv');
    }


}