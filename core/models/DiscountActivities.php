<?php

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

/**
 * This is the model class for table "{{%discount_activities}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $name
 * @property string $desc
 * @property string $pic_url
 * @property integer $discount_type
 * @property string $min_price1
 * @property string $sub_price1
 *@property string $min_price2
 *@property string $sub_price2
 *@property string $min_price3
 *@property string $sub_price3
 *@property string $min_price4
 *@property string $sub_price4
 *@property string $min_price5
 *@property string $sub_price5
 * @property string $discount
 * @property integer $expire_type
 * @property integer $expire_day
 * @property integer $begin_time
 * @property integer $end_time
 * @property integer $addtime
 * @property integer $is_delete
 * @property integer $is_join
 * @property integer $sort
 * @property integer $cat_id_list
 * @property integer $appoint_type
 * @property integer $goods_id_list
 * @property integer $maximum_price
 * @property string $rule
 */
class DiscountActivities extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public $num;
    public $type;
    public static function tableName()
    {
        return '{{%discount_activities}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'name'], 'required'],
            [['store_id', 'discount_type', 'expire_type', 'expire_day', 'begin_time', 'end_time', 'addtime', 'type','is_delete',  'is_join', 'sort','appoint_type'], 'integer'],
            [['min_price1', 'sub_price1','min_price2', 'sub_price2','min_price3', 'sub_price3','min_price4', 'sub_price4','min_price5', 'sub_price5', 'discount','maximum_price'], 'number'],
            [['name','cat_id_list','goods_id_list'], 'string', 'max' => 2048],
            [['desc', 'pic_url'], 'string', 'max' => 2000],
            [['begin_time', 'end_time'], 'integer', 'max' => 2000000000],
            [['rule'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'name' => '满减活动名称',
            'desc' => 'Desc',//无启用
            'pic_url' => 'Pic Url',//无启用
            'discount_type' => '优惠券类型：1=折扣，2=满减',
            'min_price1' => '最低消费金额',//满金额
            'sub_price1' => '优惠金额',//减金额
            'min_price2' => '最低消费金额',//满金额
            'sub_price2' => '优惠金额',//减金额
            'min_price3' => '最低消费金额',//满金额
            'sub_price3' => '优惠金额',//减金额
            'min_price4' => '最低消费金额',//满金额
            'sub_price4' => '优惠金额',//减金额
            'min_price5' => '最低消费金额',//满金额
            'sub_price5' => '优惠金额',//减金额
            'discount' => '折扣率',//折扣 0
            'expire_type' => '到期类型：1=领取后N天过期，2=指定有效期',
            'expire_day' => '有效天数，expire_type=1时',
            'begin_time' => '有效期开始时间',
            'end_time' => '有效期结束时间',
            'addtime' => 'Addtime',
            'is_delete' => 'Is Delete',//是否删除  软删除
            'is_join' => '是否加入活动中心 1--不加入 2--加入',//是否启用
            'sort' => '排序按升序排列',
            'cat_id_list' => '商品分类id',
            'appoint_type' => '指定类别或商品',
            'goods_id_list' => '指定商品id',
            'maximum_price' => '最高减额',//无启用
            'rule' => '使用规则说明',
        ];
    }

    public function getBeginTime()
    {
        return date('Y-m-d H:i', $this->begin_time);
    }
    public function getEndTime()
    {
        return date('Y-m-d H:i', $this->end_time);
    }
}
