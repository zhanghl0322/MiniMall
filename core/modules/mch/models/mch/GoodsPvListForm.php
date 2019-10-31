<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/9
 * Time: 17:08
 */

namespace app\modules\mch\models\mch;

use app\models\Goods;
use app\models\BrowseHistories;//用户浏览商品PV
use app\models\Mch;
use app\models\MchCat;
use app\models\MchGoodsCat;
use app\models\GoodsCat;
use app\models\Cat;
use app\models\User;
use app\modules\mch\models\MchModel;
use yii\data\Pagination;
use app\modules\mch\models\GoodsSearchForm;

class GoodsPvListForm extends MchModel
{
    public $store_id;
    public $keyword;
    public $shop_name;
    public $status;

    public $limit;
    public $page;

    public $cat;

    public $cat_id;
    public $id;
    public $date_end;
    public $date_start;
    public function rules()
    {
//        return [
//            [['keyword', 'status', 'limit', 'page', 'shop_name'], 'trim'],
//            [['keyword','cat'], 'string'],
//            [['status'], 'in', 'range' => [1, 2]],
//            [['cat_id', 'id'], 'integer'],
//            [['limit'],'default','value'=>20]        ];
    }

    public function search()
    {
        $query = BrowseHistories::find()->alias('p')
            ->leftJoin(['uc' => User::tableName()], 'p.user_id=uc.id')
            ->leftJoin(['g' => Goods::tableName()], 'g.id=p.goods_id')
            ->where([
                'g.store_id' =>$this->store_id,
            ]);
        //TODO 查询时间范围限制
        if ($this->date_start) {
            $query->andWhere(['>', 'p.updatetime', strtotime($this->date_start)]);
        }

        if ($this->date_end) {
            $query->andWhere(['<', 'p.updatetime', strtotime($this->date_end)]);
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1,]);
        $list = $query->select('uc.nickname,p.times,p.updatetime,g.*')
            ->limit($pagination->limit)->offset($pagination->offset)
            ->orderBy('p.times DESC')
            ->asArray()->all();

        return [
            'list' => $list,
            'row_count'=>$count,
            'pagination' => $pagination,
        ];
    }

    public function searchUv()
    {
        $query = BrowseHistories::find()->alias('p')
            ->leftJoin(['g' => Goods::tableName()], 'g.id=p.goods_id')
            ->where([
                'g.store_id' =>$this->store_id,
            ]);
        //TODO 查询时间范围限制
        if ($this->date_start) {
            $query->andWhere(['>', 'p.updatetime', strtotime($this->date_start)]);
        }

        if ($this->date_end) {
            $query->andWhere(['<', 'p.updatetime', strtotime($this->date_end)]);
        }
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1,]);
        $list = $query->select('p.goods_id,g.name,SUM( p.times) as times')
            ->limit($pagination->limit)->offset($pagination->offset)->groupBy('p.goods_id,g.name')
            ->orderBy('SUM(p.times) DESC')
            ->asArray()->all();
        return [
            'list' => $list,
            'row_count'=>$count,
            'pagination' => $pagination,
        ];
    }
}
