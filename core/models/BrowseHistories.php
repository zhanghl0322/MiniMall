<?php

namespace app\models;

use app\models\common\admin\log\CommonActionLog;
use Yii;

/**
 * This is the model class for table "{{%discount_activities}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $goods_id
 * @property integer $times
 * @property integer $addtime
 * @property integer $updatetime
 */
class BrowseHistories extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public $num;
    public $type;
    public static function tableName()
    {
        return '{{%browsehistories}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'goods_id'], 'required'],
            [['addtime', 'updatetime'], 'integer', 'max' => 2000000000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户ID',
            'goods_id'=>'商品ID',
            'times' => '浏览次数',
            'addtime' => 'Addtime',
            'updatetime' => '最后浏览时间',

        ];
    }

    public function getAddTime()
    {
        return date('Y-m-d H:i', $this->addtime);
    }
    public function getUpdateTime()
    {
        return date('Y-m-d H:i', $this->updatetime);
    }
}
