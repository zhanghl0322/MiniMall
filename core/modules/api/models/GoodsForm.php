<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/15
 * Time: 9:56
 */

namespace app\modules\api\models;

use app\models\common\CommonGoods;
use app\models\Coupon;
use app\models\DiscountActivities;
use app\models\Level;
use app\models\Option;
use app\modules\mch\models\LevelListForm;
use app\utils\GetInfo;
use app\hejiang\ApiResponse;
use app\models\Favorite;
use app\models\Goods;
use app\models\GoodsPic;
use app\models\Mch;
use app\models\MiaoshaGoods;
use app\modules\api\models\mch\ShopDataForm;

class GoodsForm extends ApiModel
{
    public $id;
    public $user_id;
    public $store_id;

    public function rules()
    {
        return [
            [['id'], 'required'],
            [['user_id'], 'safe'],
        ];
    }

    /**
     * 排序类型$sort   1--综合排序 2--销量排序
     */
    public function search()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        $goods = Goods::findOne([
            'id' => $this->id,
            'is_delete' => 0,
            'status' => 1,
            'store_id' => $this->store_id,
            'type' => get_plugin_type()
        ]);
        if (!$goods) {
            return new ApiResponse(1, '商品不存在或已下架');
        }
        //====================================记录用户点击的商品浏览数 2019年9月5日17:50:33===========================================

        \Yii::warning($this->user_id.'=================用户ID=====================','info');
        //TODO 由于授权9月1号之后 调整无痕浏览  2019年9月19日10:45:43
//        if (!empty($this->user_id)) {
//            //TODO 执行商品浏览记录 存储过程 2019年9月6日08:40:52
//            $command = \Yii::$app->db->createCommand("CALL sp_user_browsehistories('{$this->user_id}','{$goods->id}')");
//            $command->execute();
//        }
        //===============================================================================
        $mch = null;
        if ($goods->mch_id) {
            $mch = $this->getMch($goods);
            if (!$mch) {
                return new ApiResponse(1, '店铺已经打烊了哦~');
            }
        }
        \Yii::warning('====商品详情====','info');
        $pic_list = GoodsPic::find()->select('pic_url')->where(['goods_id' => $goods->id, 'is_delete' => 0])->asArray()->all();
        $is_favorite = 0;
        if ($this->user_id) {
            $exist_favorite = Favorite::find()->where(['user_id' => $this->user_id, 'goods_id' => $goods->id, 'is_delete' => 0])->exists();
            if ($exist_favorite) {
                $is_favorite = 1;
            }
        }
        $service_list = explode(',', $goods->service);
        // 默认商品服务
        if (!$goods->service) {
            $option = Option::get('good_services', $this->store->id, 'admin', []);
            foreach ($option as $item) {
                if ($item['is_default'] == 1) {
                    $service_list = explode(',', $item['service']);
                    break;
                }
            }
        }
        $new_service_list = [];
        if (is_array($service_list)) {
            foreach ($service_list as $item) {
                $item = trim($item);
                if ($item) {
                    $new_service_list[] = $item;
                }
            }
        }
        $price = [];
        foreach (json_decode($goods->attr) as $v) {
            if ($v->price > 0) {
                $price[] = $v->price;
            } else {
                $price[] = floatval($goods->price);
            }
        }

        $res_url = GetInfo::getVideoInfo($goods->video_url);
        $goods->video_url = $res_url['url'];

        if ($goods->is_negotiable) {
            $min_price = Goods::GOODS_NEGOTIABLE;
        } else {
            $min_price = sprintf('%.2f', min($price));
        }

        $res = CommonGoods::getMMPrice([
            'attr' => $goods['attr'],
            'attr_setting_type' => $goods['attr_setting_type'],
            'share_type' => $goods['share_type'],
            'share_commission_first' => $goods['share_commission_first'],
            'price' => $goods['price'],
            'individual_share' => $goods['individual_share'],
            'mch_id' => $goods['mch_id'],
            'is_level' => $goods['is_level'],
        ]);

        $attr = json_decode($goods->attr, true);
        $goodsPrice = $goods->price;
        $isMemberPrice = false;
        if ($res['user_is_member'] === true && count($attr) === 1 && $attr[0]['attr_list'][0]['attr_name'] == '默认') {
            $goodsPrice = $res['min_member_price'] ? $res['min_member_price'] : $goods->price;
            $isMemberPrice = true;
        }

        // 多商户商品无会员价
        if ($res['is_mch_goods'] === true) {
            $isMemberPrice = false;
        }

        //TODO 新增商品详情界面 店家商品推荐Arry 输出 Allon  2019年7月11日14:05:46
        $query_goods_list = Goods::find()->alias('g')->where(['g.store_id' => $this->store_id, 'g.is_delete' => 0,'g.is_best' => 1])->orderBy('g.mch_sort')->asArray()->all();

        //TODO 处理商品是否加入满减活动  2019年9月21日14:16:10
        $man_jian_list = DiscountActivities::find([
            'store_id' => $this->store_id,
            'is_delete' => 0,
            'is_join' => 2,
        ])->andWhere(['>=','end_time',time()])->andWhere(['<=','begin_time',time()])->all();

        $is_join_full_reduction = false;
        foreach ($man_jian_list as $key => $man_jian_item) {
            if (in_array($goods->id, json_decode($man_jian_item['goods_id_list']))||count(json_decode($man_jian_item['goods_id_list']))==0) {
                $is_join_full_reduction = true;//是否满减商品
            }
        }
        //========================满减组装业务逻辑 2019年9月16日15:35:40=================================


        //$is_join_full_reduction=false; //关闭前端满减icon按钮 图标  2019年12月18日15:39:18

        //新增商品底部优惠券领取展示 过滤未过期  2019年12月9日14:37:09
        //*****************************************************************************
        $coupon_list = Coupon::find()->where([ 'is_delete' => 0])->andWhere(['<>','coupon_bg_url',''])->andWhere(['is not','coupon_bg_url',null]) ->orderBy('sort ASC')->all();
        //->andWhere(['>=','end_time',time()])->andWhere(['<=','begin_time',time()])
        $new_coupon_list=[];
        foreach ($coupon_list as $key => $coupon_item) {
            if (in_array($goods->id, json_decode($coupon_item['goods_id_list']))||count(json_decode($coupon_item['goods_id_list']))==0) {
                $new_coupon_list[$key] = $coupon_item;
            }
        }
        //*********************************End********************************************
        $data = [
            'id' => $goods->id,
            'pic_list' => $pic_list,
            'attr' => $goods->attr,
            'is_negotiable' => $goods->is_negotiable,
            'max_price' => sprintf('%.2f', max($price)),
            'min_price' => $min_price,
            'name' => $goods->name,
            'cat_id' => $goods->cat_id,
            'price' => sprintf('%.2f', $goodsPrice),
            'detail' => $goods->detail,
            'sales' => $goods->getSalesVolume() + $goods->virtual_sales,
            'attr_group_list' => $goods->getAttrGroupList(),
            'num' => $goods->getNum(),
            'is_favorite' => $is_favorite,
            'service_list' => $new_service_list,
            'original_price' => sprintf('%.2f', $goods->original_price),
            'video_url' => $goods->video_url,
            'unit' => $goods->unit,
            'use_attr' => intval($goods->use_attr),
            'mch' => $mch,
            'max_share_price' => sprintf('%.2f', $res['max_share_price']),
            'min_member_price' => sprintf('%.2f', $res['min_member_price']),
            'is_share' => $res['is_share'],
            'is_level' => $res['is_level'],
            'is_member_price' => $isMemberPrice,
            'best_good_list' => $query_goods_list,
            'is_join_full_reduction'=>$is_join_full_reduction,//是否加入满减
            'new_coupon_list' => $new_coupon_list,
        ];

        return new ApiResponse(0, 'success', $data);
    }

    //获取商品秒杀数据
    public function getMiaoshaData($goods_id)
    {
        $miaosha_goods = MiaoshaGoods::findOne([
            'goods_id' => $goods_id,
            'is_delete' => 0,
            'start_time' => intval(date('H')),
            'open_date' => date('Y-m-d'),
        ]);
        if (!$miaosha_goods) {
            return null;
        }
        $attr_data = json_decode($miaosha_goods->attr, true);
        $total_miaosha_num = 0;
        $total_sell_num = 0;
        $miaosha_price = 0.00;
        foreach ($attr_data as $i => $attr_data_item) {
            $total_miaosha_num += $attr_data_item['miaosha_num'];
            $total_sell_num += $attr_data_item['sell_num'];
            if ($miaosha_price == 0) {
                $miaosha_price = $attr_data_item['miaosha_price'];
            } else {
                $miaosha_price = min($miaosha_price, $attr_data_item['miaosha_price']);
            }
        }
        return [
            'miaosha_num' => $total_miaosha_num,
            'sell_num' => $total_sell_num,
            'miaosha_price' => (float)$miaosha_price,
            'begin_time' => strtotime($miaosha_goods->open_date . ' ' . $miaosha_goods->start_time . ':00:00'),
            'end_time' => strtotime($miaosha_goods->open_date . ' ' . $miaosha_goods->start_time . ':59:59'),
            'now_time' => time(),
        ];
    }


    // 快速给购买商品
    public function quickGoods($twocatid)
    {
        $goods = Goods::find()
            ->where([
                'store_id' => $this->store_id,
                'is_delete' => 0,
                'status' => 1,
                'quick_purchase' => 1
            ])
            ->andWhere([

                'in', 'cat_id', $twocatid
            ])->asArray()
            ->all();
        foreach ($goods as $key => &$value) {
            $value['attr'] = json_decode($value['attr']);
            foreach ($value['attr'] as $key2 => $value2) {
                foreach ($value2->attr_list as $key3 => $value3) {
                    $value['attr_name'] = $value3->attr_name;
                }
                // $value['attr_num'][] = $value2->num;
                // $value['attr_price'][] = $value2->price;
                // $value['attr_no'][] = $value2->no;
                // $value['attr_pic'][] = $value2->pic;
                $value['num'] = 0;
            }
            // unset($value['attr']);
        }
        return [
            'code' => 0,
            'data' => [
                'list' => $goods,
            ],
        ];
    }

    /**
     * @param Goods $goods
     */
    public function getMch($goods)
    {
        $f = new ShopDataForm();
        $f->mch_id = $goods->mch_id;
        $shop = $f->getShop();
        if (isset($shop['code']) && $shop['code'] == 1) {
            return null;
        }
        return $shop;
    }
}
