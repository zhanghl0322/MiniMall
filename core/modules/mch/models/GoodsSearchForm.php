<?php
/**
 * @link:http://www.zjhejiang.com/
 * @copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 *
 * Created by PhpStorm.
 * User: 风哀伤
 * Date: 2018/7/13
 * Time: 9:42
 */

namespace app\modules\mch\models;

use app\models\Card;
use app\models\Cat;
use app\models\Mch;
use app\models\common\CommonGoodsAttr;
use app\models\Goods;
use app\models\GoodsCat;
use app\models\Model;
use app\models\PostageRules;
use app\modules\mch\extensions\Export;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

/**
 * app\models\Goods $goods
 * app\models\Store $store
 */
class GoodsSearchForm extends MchModel
{
    public $goods;
    public $store;
    public $keyword;
    public $status;
    public $plugin;
    public $limit = 10;
    public $page = 1;

    // 编辑商品查询
    public function search()
    {
        $goods = $this->goods;
        $catList = Cat::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'parent_id' => 0])->all();
        $postageRiles = PostageRules::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->all();
        $cardList = Card::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->asArray()->all();
        if ($goods->full_cut) {
            $goods->full_cut = json_decode($goods->full_cut, true);
        } else {
            $goods->full_cut = [
                'pieces' => '',
                'forehead' => '',
            ];
        }
        if ($goods->integral) {
            $goods->integral = json_decode($goods->integral, true);
        } else {
            $goods->integral = [
                'give' => 0,
                'deduction' => 0,
                'more' => 0,
            ];
        }
        $goodsCardList = Goods::getGoodsCard($goods->id);
        $goodsCatList = Goods::getCatList($goods);
        foreach ($goods as $index => $value) {
            if (in_array($index, ['attr', 'full_cat', 'integral', 'payment', 'detail'])) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $goods[$index] = str_replace("\"", "&quot;", $value);
        }

        return [
            'goods' => $goods,
            'cat_list' => $catList,
            'postageRiles' => $postageRiles,
            'card_list' => $cardList,
            'goods_card_list' => $goodsCardList,
            'goods_cat_list' => $goodsCatList,
        ];
    }

    // 商品列表数据
    public function getList()
    {
        $keyword = $this->keyword;
        $status = $this->status;
//        $query = Goods::find()->alias('g')->where(['g.store_id' => $this->store->id, 'g.is_delete' => 0, 'g.mch_id' => 0]);
        //TODO 移除mch_id=0 条件过滤
        $query = Goods::find()->alias('g')->where(['g.store_id' => $this->store->id, 'g.is_delete' => 0]);
        if ($status != null) {
            $query->andWhere('g.status=:status', [':status' => $status]);
        }
        if ($this->plugin) {
            $query->andWhere(['g.type' => $this->plugin]);
        } else {
            $query->andWhere(['g.type' => 0]);
        }
        $query->leftJoin(['c' => Cat::tableName()], 'c.id=g.cat_id');
        $query->leftJoin(['gc' => GoodsCat::tableName()], 'gc.goods_id=g.id');
        $query->leftJoin(['c2' => Cat::tableName()], 'gc.cat_id=c2.id');
//        $query->leftJoin(['mc' => Mch::tableName()], 'g.mch_id=mc.id');
        $query->andWhere(['or', ['gc.is_delete' => 0, 'gc.store_id' => $this->store->id], 'isnull(gc.id)']);

        $cat_query = clone $query;

        //TODO 新增is_best 字段  2019-07-10 16点51分
        //TODO  新增 is_show 字段输出  2019年8月7日16:50:54
        $query->select('g.id,g.name,g.price,g.original_price,g.status,g.cover_pic,g.sort,g.attr,g.cat_id,g.virtual_sales,g.store_id,g.quick_purchase,g.mch_id,g.is_best,g.is_show,g.supplier_name,g.supplier_url');
        if (trim($keyword)) {
            $query->andWhere(['LIKE', 'g.name', $keyword]);
        }
        if (isset($_GET['cat'])) {
            $cat = trim($_GET['cat']);
            $query->andWhere([
                'or',
                ['c.name' => $cat],
                ['c2.name' => $cat],
            ]);
        }
        $cat_list = $cat_query->groupBy('name')->orderBy(['g.cat_id' => SORT_ASC])->select([
            '(case when g.cat_id=0 then c2.name else c.name end) name',
        ])->asArray()->column();
        $query->groupBy('g.id');
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'route' => \Yii::$app->requestedRoute]);
        $list = $query->orderBy('g.sort ASC,g.addtime DESC')
            ->limit($pagination->limit)
            ->offset($pagination->offset)
            ->with(['goodsPicList', 'catList1.cat', 'cat'])
            ->all();
        \Yii::warning($pagination->offset.'----商品列表查询条件----','info');
        $goodsList = ArrayHelper::toArray($list);
        $flag = \Yii::$app->request->post('flag');
        if ($flag === Export::EXPORT) {
            \Yii::warning($pagination->page.'----商品列表导出----'.$pagination->limit,'info');
            $list = $query->orderBy('g.sort ASC,g.addtime DESC')
                ->limit(($pagination->offset+$pagination->limit))
                ->offset(1)
                ->with(['goodsPicList', 'catList1.cat', 'cat'])
                ->all();
            $goodsExport = new GoodsExportList();
            $goodsExport->fields = \Yii::$app->request->post('fields');
            $goodsExport->GoodsForm($list);
            \Yii::warning('----商品Excel导出----','info');
        }
        return [
            'list' => $list,
            'goodsList' => $goodsList,
            'cat_list' => $cat_list,
            'pagination' => $pagination
        ];
    }

    public function goodsSearch()
    {
        $query = Goods::find()->where([
            'store_id' => $this->getCurrentStoreId(),
            'is_delete' => Model::IS_DELETE_FALSE,
        ]);

        if ($this->keyword) {
            $query->andWhere(['like', 'name', $this->keyword]);
        }

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        $list = $query->asArray()->limit($pagination->limit)->offset($pagination->offset)->orderBy('id DESC')->all();

        return [
            'code' => 0,
            'data' => [
                'row_count' => $count,
                'page_count' => $pagination->pageCount,
                'list' => $list,
            ],
        ];
    }

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
//            [
//                'key' => 'mch_id',
//                'value' => '商品类型',
//                'selected' => 0,
//            ],
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
}