<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/9
 * Time: 16:30
 */

namespace app\modules\mch\controllers\mch;

use app\modules\mch\controllers\Controller;
use app\modules\mch\models\mch\GoodsDetailForm;
use app\modules\mch\models\mch\GoodsListForm;
use app\modules\mch\models\mch\GoodsPvListForm;

class GoodsController extends Controller
{
    public function actionGoods()
    {
        $form = new GoodsListForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $arr = $form->search();
        return $this->render('goods', $arr);
    }

    public function actionDetail()
    {
        $form = new GoodsDetailForm();
        $form->store_id = $this->store->id;
        $form->goods_id = \Yii::$app->request->get('goods_id');
        $arr = $form->search();
        return $this->render('detail', $arr);
    }

    //TODO 新增商户列表界面、商品编辑入口以及相关业务  Allon  2019-07-01  09点15分
    public function actionEdit()
    {
        $form = new GoodsDetailForm();
        $form->store_id = $this->store->id;
        $form->goods_id = \Yii::$app->request->get('goods_id');
        $arr = $form->search();
        return $this->render('edit', $arr);
    }
    //TODO 新增购物车 2019-07-08 17点18分 Allon
    public function actionCat(){
        $form = new GoodsListForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        return $form->setCat();
    }
}
