<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/20
 * Time: 14:34
 */

namespace app\modules\mch\models;

use app\models\common\admin\order\CommonOrderSearch;
use app\models\common\CommonGoods;
use app\models\Goods;
use app\models\Mch;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderRefund;
use app\models\Share;
use app\models\Shop;
use app\models\User;
use app\modules\mch\extensions\Export;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use app\models\GoodsPic;

class OrderListForm extends MchModel
{
    public $store_id;
    public $user_id;
    public $keyword;
    public $status;
    public $is_recycle;
    public $page;
    public $limit;

    public $flag;//是否导出
    public $is_offline;
    public $clerk_id;
    public $parent_id;
    public $shop_id;

    public $date_start;
    public $date_end;
    public $express_type;
    public $keyword_1;
    public $seller_comments;

    public $fields;
    public $type;
    public $is_full;
    public $platform;//所属平台

    public function rules()
    {
        return [
            [['keyword',], 'trim'],
            [['status', 'is_recycle', 'page', 'limit', 'user_id', 'is_offline', 'clerk_id', 'shop_id', 'keyword_1', 'platform'], 'integer'],
            [['status',], 'default', 'value' => -1],
            [['page',], 'default', 'value' => 1],
            [['flag', 'date_start', 'date_end', 'express_type'], 'trim'],
            [['flag'], 'default', 'value' => 'no'],
            [['seller_comments'], 'string'],
            [['fields'], 'safe']
        ];
    }

    public function search()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        //'o.mch_id' => 0,  TODO 移除商户限制、直接在订单界面、导出全部订单
        $query = Order::find()->alias('o')->where([
            'o.store_id' => $this->store_id,

            'o.is_show' => 1
        ])->leftJoin(['u' => User::tableName()], 'u.id = o.user_id')
            ->leftJoin(['od' => OrderDetail::tableName()], 'od.order_id=o.id')
            ->leftJoin(['g' => Goods::tableName()], 'g.id=od.goods_id')
            ->leftJoin(['m' => Mch::tableName()], 'g.mch_id=m.id')
//            ->leftJoin(['p1' => User::tableName()], 'pu.id = o.parent_id')  //TODO 关联分销返佣信息 2019年11月18日11:19:52
//            ->leftJoin(['p2' => User::tableName()], 'p2.id = o.parent_id_1')  //TODO 关联分销返佣信息 2019年11月18日11:19:52
//            ->leftJoin(['p3' => User::tableName()], 'p3.id = o.parent_id_2')  //TODO 关联分销返佣信息 2019年11月18日11:19:52
            ->groupBy('o.id');

        switch ($this->status) {
            case 0:
                $query->andWhere(['o.is_pay' => 0]);
                break;
            case 1:
                $query->andWhere([
                    'o.is_send' => 0,
                ])->andWhere(['or', ['o.is_pay' => 1], ['o.pay_type' => 2]]);
                break;
            case 2:
                $query->andWhere([
                    'o.is_send' => 1,
                    'o.is_confirm' => 0,
                ])->andWhere(['or', ['o.is_pay' => 1], ['o.pay_type' => 2]]);
                break;
            case 3:
                $query->andWhere([
                    'o.is_send' => 1,
                    'o.is_confirm' => 1,
                ])->andWhere(['or', ['o.is_pay' => 1], ['o.pay_type' => 2]]);
                break;
            case 4:
                break;
            case 5:
                break;
            case 6:
                $query->andWhere(['o.apply_delete' => 1]);
                break;
            case 13:
                #13 默认归属满减活动订单 2019年11月12日10:57:55
                $query->andWhere(['>', 'o.full_reduction_price', 100]);
                break;
            default:
                break;
        }
        \Yii::warning($this->status.'**********满减进入**********','info');
        if ($this->status == 5) {//已取消订单
            $query->andWhere(['or', ['o.is_cancel' => 1], ['o.is_delete' => 1]]);
        } else {
            if ($this->is_recycle != 1) {
                $query->andWhere(['o.is_cancel' => 0, 'o.is_delete' => 0]);
            }
        }
        if ($this->is_full == 1) {//默认归属满减活动订单 2019年11月12日10:57:55
            \Yii::warning($this->is_full.'**********满减进入**********','info');
            $query->andWhere(['>', 'o.full_reduction_price', 0]);
        }
        //TODO 搜索 持续优化中...
        $commonOrderSearch = new CommonOrderSearch();
        $query = $commonOrderSearch->search($query, $this);
        $query = $commonOrderSearch->keyword($query, $this->keyword_1, $this->keyword);


        if ($this->type) {
            $query->andWhere(['o.type' => $this->type]);
        } else {
            if (get_plugin_type() != 0) {
                $query->andWhere(['o.type' => get_plugin_type()]);
            } else {
                $query->andWhere(['o.type' => 0]);
            }
        }
        if ($this->is_recycle == 1) {
            $query->andWhere(['o.is_recycle' => 1]);
        } else {
            $query->andWhere(['o.is_recycle' => 0]);
        }

        if ($this->flag == "EXPORT") {
            $query_ex = clone $query;
            $list_ex = $query_ex;
            $export = new ExportList();
            $export->is_offline = $this->is_offline;
            $export->order_type = 0;
            $export->fields = $this->fields;
            $export->dataTransform_new($list_ex);
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1, 'route' => \Yii::$app->requestedRoute]);

        $clerkQuery = User::find()
            ->select('nickname')
            ->where(['store_id' => $this->store_id])
            ->andWhere('id = o.clerk_id');

        $refundQuery = OrderRefund::find()
            ->select('status')
            ->where(['store_id' => $this->store_id, 'is_delete' => 0])
            ->andWhere('order_id = o.id')
            ->orderBy(['addtime' => SORT_DESC])
            ->limit(1);

        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('o.addtime DESC')
            ->select(['o.*', 'u.nickname', 'IFNULL(m.name,"车海洋自营") as mch_name', 'u.platform', 'clerk_name' => $clerkQuery, 'refund' => $refundQuery])->asArray()->all();

        $listArray = ArrayHelper::toArray($list);
        foreach ($listArray as $i => &$item) {

            $item['goods_list'] = $this->getOrderGoodsList($item['id']);

            //此处考虑将 Order 和 Shop 模型使用 hasOne 关联，查询时使用 with 预查询 -- wi1dcard
            if ($item['shop_id'] && $item['shop_id'] != 0) {
                $shop = Shop::find()->where(['store_id' => $this->store_id, 'id' => $item['shop_id']])->asArray()->one();
                $item['shop'] = $shop;
            }
            $item['integral'] = json_decode($item['integral'], true);

            if (isset($item['address_data'])) {
                $item['address_data'] = \Yii::$app->serializer->decode($item['address_data']);
            }

            \Yii::warning('***订单分销ID***'.$item['parent_id'],'info');
            if ($item['parent_id'] != 0 && $item['parent_id'] != -1) {
                $share = User::find()->alias('u')->where(['u.id' => $item['parent_id'], 'u.store_id' => $this->store_id, 'u.is_delete' => 0])
                    ->leftJoin(Share::tableName() . ' s', 's.user_id = u.id and s.is_delete=0')->select([
                        'u.nickname','u.platform', 's.name', 's.mobile'
                    ])->asArray()->one();
                $listArray[$i]['share'] = $share;
            }
            if ($item['parent_id_1'] != 0 && $item['parent_id_1'] != -1) {
                $share_1 = User::find()->alias('u')->where(['u.id' => $item['parent_id_1'], 'u.store_id' => $this->store_id, 'u.is_delete' => 0])
                    ->leftJoin(Share::tableName() . ' s', 's.user_id = u.id and s.is_delete=0')->select([
                        'u.nickname','u.platform', 's.name', 's.mobile'
                    ])->asArray()->one();
                $listArray[$i]['share_1'] = $share_1;
            }
            if ($item['parent_id_2'] != 0 && $item['parent_id_1'] != -1) {
                $share_2 = User::find()->alias('u')->where(['u.id' => $item['parent_id_2'], 'u.store_id' => $this->store_id, 'u.is_delete' => 0])
                    ->leftJoin(Share::tableName() . ' s', 's.user_id = u.id and s.is_delete=0')->select([
                        'u.nickname','u.platform', 's.name', 's.mobile'
                    ])->asArray()->one();
                $listArray[$i]['share_2'] = $share_2;
            }
            $item['flag'] = 0;
        }

        return [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'pagination' => $pagination,
            'list' => $listArray,
        ];
    }

    /**
     * @param $data array 需要处理的数据
     */
    public function dataTransform($data)
    {
        //TODO 测试数据 需要换成真实的字段
        $newFields = [];
        foreach ($this->fields as &$item) {
            if ($this->is_offline == 1) {
                if (in_array($item['key'], ['clerk_name', 'shop_name'])) {
                    $item['selected'] = 1;
                }
            } else {
                if (in_array($item['key'], ['express_price', 'express_no', 'express'])) {
                    $item['selected'] = 1;
                }
            }
            if (isset($item['selected']) && $item['selected'] == 1) {
                $newFields[$item['key']] = $item['value'];
            }
        }

        $newList = [];
        foreach ($data as $datum) {
            $newItem = [];
            $newItem['order_no'] = $datum->order_no;
            $newItem['nickname'] = $datum->user->nickname;
            $newItem['name'] = $datum->name;
            $newItem['mobile'] = $datum->mobile;
            $newItem['address'] = $datum->address;
            $newItem['total_price'] = $datum->total_price;
            $newItem['pay_price'] = $datum->pay_price;
            $newItem['pay_time'] = $datum->pay_time ? date('Y-m-d H:i', $datum->pay_time) : '';
            $newItem['send_time'] = $datum->send_time ? date('Y-m-d H:i', $datum->send_time) : '';
            $newItem['confirm_time'] = $datum->confirm_time ? date('Y-m-d H:i', $datum->confirm_time) : '';
            $newItem['words'] = $datum->words;
            $newItem['goods_list'] = $this->getOrderGoodsList($datum['id']);
            $newItem['is_pay'] = $datum['is_pay'] == 1 ? "已付款" : "未付款";
            $newItem['apply_delete'] = ($datum['apply_delete'] == 1) ? "取消中" : "无";
            $newItem['is_send'] = ($datum['is_send'] == 1) ? "已发货" : "未发货";
            $newItem['is_confirm'] = ($datum['is_confirm'] == 1) ? "已收货" : "未收货";
            $newItem['addtime'] = date('Y-m-d H:i', $datum['addtime']);
            $newItem['express_price'] = $datum['express_price'] . "元";

            //是否到店自提 0--否 1--是
            if ($datum['is_offline']) {
                $newItem['clerk_name'] = $datum->clerk ? $datum->clerk->nickname : '';
                $newItem['shop_name'] = $datum->shop ? $datum->shop->name : '';
            } else {
                $newItem['express_price'] = $datum->express_price;
                $newItem['express_no'] = $datum->express_no;
                $newItem['express'] = $datum->express;
            }

            if ($datum->orderForm) {
                $str = '';
                foreach ($datum->orderForm as $key => $item) {
                    $str .= $item['key'] . ':' . $item['value'] . ',';
                }
                $newItem['content'] = rtrim($str, ',');
            } else {
                $newItem['content'] = $datum->content;
            }

            $newList[] = $newItem;
        }
        Export::order_3($newList, $newFields);
    }

    public function getOrderGoodsList($order_id)
    {
        $picQuery = GoodsPic::find()
            ->alias('gp')
            ->select('pic_url')
            ->andWhere('gp.goods_id = od.goods_id')
            ->andWhere(['is_delete' => 0])
            ->limit(1);

        //TODO 原执行语句、做了同商品不同规格组|规格值 去重   2019年8月8日10:21:13  Allon
        $orderDetailList = OrderDetail::find()->alias('od')
            ->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where([
                'od.is_delete' => 0,
                'od.order_id' => $order_id,
            ])->select(['od.num', 'od.total_price', 'od.attr', 'od.is_level', 'g.name', 'od.id as odId', 'g.unit', 'g.supplier_name','g.supplier_url','goods_pic' => $picQuery])->asArray()->all();

        foreach ($orderDetailList as &$item) {
            $od=   OrderDetail::find()->where(['id' => $item['odId']])->asArray()->one();//同步加上goods_id
            $item['id']=$od['goods_id'];
            $item['attr_list'] = json_decode($item['attr'], true);
        }

        return $orderDetailList;
    }

    public static function getCountData($store_id)
    {
        $form = new OrderListForm();
        $form->limit = 0;
        $form->store_id = $store_id;
        $data = [];

        $form->status = -1;
        $res = $form->search();
        $data['all'] = $res['row_count'];

        $form->status = 0;
        $res = $form->search();
        $data['status_0'] = $res['row_count'];

        $form->status = 1;
        $res = $form->search();
        $data['status_1'] = $res['row_count'];

        $form->status = 2;
        $res = $form->search();
        $data['status_2'] = $res['row_count'];

        $form->status = 3;
        $res = $form->search();
        $data['status_3'] = $res['row_count'];

        $form->status = 5;
        $res = $form->search();
        $data['status_5'] = $res['row_count'];

        return $data;
    }


    /**
     * @return array
     * @throws \yii\db\Exception
     * 新增的合并全部订单逻辑、在视图里面转化不灵活、存储过程里面不能使用UNION ALL 语句
     */
    public function orderAll()
    {
        $pageIndex = ($this->page - 1) * 10;
        $sqlWhere = 'where 1=1';
        //TODO 搜索 持续优化中...
        $commonOrderSearch = new CommonOrderSearch();
        $sqlWhere = $commonOrderSearch->order_all_keyword($sqlWhere, $this->keyword_1, $this->keyword);

        $count = $this->queryAllCount($sqlWhere);//计算订单数量

        if($this->date_start)
        {
            $date_start=strtotime( $this->date_start);
            $date_end=strtotime( $this->date_end);
            $sqlWhere="WHERE addtime BETWEEN {$date_start} AND {$date_end} ";
        }

        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
        $sql = "SELECT  * FROM (SELECT *
FROM 
    (SELECT 
         `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
         `o`.addtime,
         `o`.confirm_time,
         od.`attr` AS attr, od.`num` AS num, od.`pic` AS pic, g.`name` AS goods_name, `u`.`nickname`, IFNULL(m.name, \"车海洋自营\") AS mch_name, `u`.`platform`, 
        (SELECT `nickname`
        FROM `hjmall_user`
        WHERE (`store_id`={$this->store_id})
                AND (id = o.clerk_id)) AS `clerk_name`, 
            (SELECT `status`
            FROM `hjmall_order_refund`
            WHERE ((`store_id`={$this->store_id})
                    AND (`is_delete`=0))
                    AND (order_id = o.id)
            ORDER BY  `addtime` DESC LIMIT 1) AS `refund`, 'zc' AS order_type,
              g.cover_pic AS  goods_pic
            FROM `hjmall_order` `o`
            LEFT JOIN `hjmall_user` `u`
                ON u.id = o.user_id
            LEFT JOIN `hjmall_order_detail` `od`
                ON od.order_id=o.id
            LEFT JOIN `hjmall_goods` `g`
                ON g.id=od.goods_id
            LEFT JOIN `hjmall_mch` `m`
                ON g.mch_id=m.id
            WHERE ((`o`.`store_id`={$this->store_id})
                    AND (`o`.`is_show`=1))
                    AND ((`o`.`is_cancel`=0)
                    AND (`o`.`is_delete`=0)) 
                    #AND (`o`.`order_no` LIKE '%20190910094314757672%')
                    AND (`o`.`type`=0)
                    AND (`o`.`is_recycle`=0)
            GROUP BY  `o`.`id`
            ORDER BY  `o`.`addtime` DESC   ) AS order_list
            
        UNION ALL
        SELECT *
FROM 
    (SELECT  `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
         `o`.addtime,
         `o`.confirm_time,
         `o`.`attr`,
         `o`.`num`,
         `o`.`pic`,
         `g`.`name` AS `goods_name`,
         `u`.`nickname`,
         IFNULL(m.name,
         \"车海洋自营\") AS mch_name,
         `u`.`platform`,
         `u`.`nickname` AS `clerk_name`,
         '' AS refund, 'ms' AS order_type,
          g.cover_pic AS  goods_pic
         
    FROM `hjmall_ms_order` `o`
    LEFT JOIN `hjmall_user` `u`
        ON u.id=o.user_id
    LEFT JOIN `hjmall_ms_goods` `g`
        ON g.id=o.goods_id
    LEFT JOIN `hjmall_mch` `m`
        ON g.mch_id=m.id
    WHERE (`is_show`=1)
            AND (`o`.`store_id`={$this->store_id})
            AND (`o`.`is_recycle`=0)
            AND ((`o`.`is_cancel`=0)
            AND (`o`.`is_delete`=0)) #AND (`o`.`order_no` LIKE '%M20190719094615566418%')
    ORDER BY  `o`.`addtime` DESC  ) AS ms_order_list
UNION ALL
SELECT *
FROM 
    (SELECT  `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
          `o`.addtime,
         `o`.confirm_time,
         `od`.`attr`,
         `od`.`num`,
         `od`.`pic`,
         `g`.`name` AS `goods_name`,
         `u`.`nickname`,
         IFNULL(m.name,
         \"车海洋自营\") AS mch_name,
         `u`.`platform`,
         `c`.`nickname` AS `clerk_name`,
         '' AS refund, 'pt' AS order_type,
          g.cover_pic AS  goods_pic
    FROM `hjmall_pt_order` `o`
    LEFT JOIN `hjmall_user` `u`
        ON u.id=o.user_id
    LEFT JOIN `hjmall_user` `c`
        ON c.id=o.clerk_id
    INNER JOIN `hjmall_pt_order_detail` `od`
        ON od.order_id=o.id
    LEFT JOIN `hjmall_pt_goods` `g`
        ON g.id=od.goods_id
    LEFT JOIN `hjmall_mch` `m`
        ON g.mch_id=m.id
    WHERE (`is_show`=1)
            AND ((`o`.`is_delete`=0)
            AND (`o`.`store_id`={$this->store_id}))
            AND (`o`.`is_recycle`=0)
            AND (`o`.`is_cancel`=0) #AND (`o`.`order_no` LIKE '%20190516081044363479%')
    ORDER BY  `o`.`addtime` DESC ) AS pt_order_list ) AS all_order {$sqlWhere} LIMIT {$pageIndex},10  ";
        $order_list = \Yii::$app->db->createCommand($sql)->queryAll();

        return [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'pagination' => $pagination,
            'list' => $order_list,
        ];
    }

    /**
     * @param $sqlWhere
     * @return false|string|null
     * @throws \yii\db\Exception
     * 计算订单数量
     */
    public function queryAllCount($sqlWhere)
    {
        $order_count = "SELECT  COUNT(*)  AS 'order_count' FROM (SELECT *
FROM 
    (SELECT 
         `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
         `o`.addtime,
         `o`.confirm_time,
         od.`attr` AS attr, od.`num` AS num, od.`pic` AS pic, g.`name` AS goods_name, `u`.`nickname`, IFNULL(m.name, \"车海洋自营\") AS mch_name, `u`.`platform`, 
        (SELECT `nickname`
        FROM `hjmall_user`
        WHERE (`store_id`={$this->store_id})
                AND (id = o.clerk_id)) AS `clerk_name`, 
            (SELECT `status`
            FROM `hjmall_order_refund`
            WHERE ((`store_id`={$this->store_id})
                    AND (`is_delete`=0))
                    AND (order_id = o.id)
            ORDER BY  `addtime` DESC LIMIT 1) AS `refund`, 'zc' AS order_type,
              g.cover_pic AS  goods_pic
            FROM `hjmall_order` `o`
            LEFT JOIN `hjmall_user` `u`
                ON u.id = o.user_id
            LEFT JOIN `hjmall_order_detail` `od`
                ON od.order_id=o.id
            LEFT JOIN `hjmall_goods` `g`
                ON g.id=od.goods_id
            LEFT JOIN `hjmall_mch` `m`
                ON g.mch_id=m.id
            WHERE ((`o`.`store_id`={$this->store_id})
                    AND (`o`.`is_show`=1))
                    AND ((`o`.`is_cancel`=0)
                    AND (`o`.`is_delete`=0)) 
                    #AND (`o`.`order_no` LIKE '%20190910094314757672%')
                    AND (`o`.`type`=0)
                    AND (`o`.`is_recycle`=0)
            GROUP BY  `o`.`id`
            ORDER BY  `o`.`addtime` DESC   ) AS order_list
        UNION ALL
        SELECT *
FROM 
    (SELECT  `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
         `o`.addtime,
         `o`.confirm_time,
         `o`.`attr`,
         `o`.`num`,
         `o`.`pic`,
         `g`.`name` AS `goods_name`,
         `u`.`nickname`,
         IFNULL(m.name,
         \"车海洋自营\") AS mch_name,
         `u`.`platform`,
         `u`.`nickname` AS `clerk_name`,
         '' AS refund, 'ms' AS order_type,
          g.cover_pic AS  goods_pic
         
    FROM `hjmall_ms_order` `o`
    LEFT JOIN `hjmall_user` `u`
        ON u.id=o.user_id
    LEFT JOIN `hjmall_ms_goods` `g`
        ON g.id=o.goods_id
    LEFT JOIN `hjmall_mch` `m`
        ON g.mch_id=m.id
    WHERE (`is_show`=1)
            AND (`o`.`store_id`={$this->store_id})
            AND (`o`.`is_recycle`=0)
            AND ((`o`.`is_cancel`=0)
            AND (`o`.`is_delete`=0)) #AND (`o`.`order_no` LIKE '%M20190719094615566418%')
    ORDER BY  `o`.`addtime` DESC  ) AS ms_order_list
UNION ALL
SELECT *
FROM 
    (SELECT  `o`.id,
         `o`.store_id,
         `o`.user_id,
         `o`.order_no,
         `o`.total_price,
         `o`.pay_price,
         `o`.express_price,
         `o`.name,
         `o`.mobile,
         `o`.address,
         `o`.remark,
         `o`.is_pay,
         `o`.send_time,
         `o`.express,
         `o`.express_no,
         `o`.is_confirm,
          `o`.addtime,
         `o`.confirm_time,
         `od`.`attr`,
         `od`.`num`,
         `od`.`pic`,
         `g`.`name` AS `goods_name`,
         `u`.`nickname`,
         IFNULL(m.name,
         \"车海洋自营\") AS mch_name,
         `u`.`platform`,
         `c`.`nickname` AS `clerk_name`,
         '' AS refund, 'pt' AS order_type,
          g.cover_pic AS  goods_pic
    FROM `hjmall_pt_order` `o`
    LEFT JOIN `hjmall_user` `u`
        ON u.id=o.user_id
    LEFT JOIN `hjmall_user` `c`
        ON c.id=o.clerk_id
    INNER JOIN `hjmall_pt_order_detail` `od`
        ON od.order_id=o.id
    LEFT JOIN `hjmall_pt_goods` `g`
        ON g.id=od.goods_id
    LEFT JOIN `hjmall_mch` `m`
        ON g.mch_id=m.id
    WHERE (`is_show`=1)
            AND ((`o`.`is_delete`=0)
            AND (`o`.`store_id`={$this->store_id}))
            AND (`o`.`is_recycle`=0)
            AND (`o`.`is_cancel`=0) #AND (`o`.`order_no` LIKE '%20190516081044363479%')
    ORDER BY  `o`.`addtime` DESC ) AS pt_order_list ) AS all_order {$sqlWhere}  ";

        $count = \Yii::$app->db->createCommand($order_count)->queryScalar();//条数
        return $count;
    }

}
