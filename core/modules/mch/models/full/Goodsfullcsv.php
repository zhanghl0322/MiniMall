<?php
/**
 * @link:http://www.zjhejiang.com/
 * @copyright: Copyright (c) 2018 浙江禾匠信息科技有限公司
 *
 * Created by PhpStorm.
 * User: 风哀伤
 * Date: 2018/9/3
 * Time: 11:47
 */

namespace app\modules\mch\models\full;


use app\models\DiscountActivities;
use app\models\Goods;
use app\models\GoodsPic;
use app\modules\mch\models\MchModel;
use yii\web\UploadedFile;

//TODO 新增满减商品导入 Allon  2019年9月27日10:55:56
class Goodsfullcsv extends MchModel
{
    public $store_id;

    public $excel;
    public $zip;
    public $mch_id;

    public function rules()
    {
        return [
            [['excel'], 'file', 'extensions' => ['excel']],
            [['zip'], 'file', 'extensions' => ['zip']],
        ];
    }

    public function search()
    {
        if (!$this->validate()) {
            return $this->getErrorResponse();
        }
        set_time_limit(0);
        $filename = $_FILES['excel']['name'];
        $tmpname = $_FILES['excel']['tmp_name'];
        $path = \Yii::$app->basePath . '/web/temp/';
        if(!is_dir($path)){
            mkdir($path);
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (($ext != 'xlsx') && ($ext != 'xls')) {
            return [
                'code' => 1,
                'msg' => '请上传excel文件'
            ];
        }
        $file = time() . $this->store_id . '.' . $ext;
        $uploadfile = $path . $file;
        $result = move_uploaded_file($tmpname, $uploadfile);

        // 读取Excel文件
        $reader = \PHPExcel_IOFactory::createReader(($ext == 'xls' ? 'Excel5' : 'Excel2007'));
        $excel = $reader->load($uploadfile);
        $sheet = $excel->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnCount = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $row = 1;
        $colIndex = [];
        $arr = [];
        $goods_arr=[];
        $str = '';
        $str_id = '';
        while ($row <= $highestRow) {
            $rowValue = array();
            $col = 0;
            while ($col < $highestColumnCount) {
                $rowValue[] = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
                ++$col;
            }
            if(count($rowValue) == 0){
                unlink($uploadfile);
                return [
                    'code' => 1,
                    'msg' => '上传文件内容不符合规范'
                ];
            }else{


                if($row == 1){

                }else if($row == 2){
                    $colIndex = array_flip($rowValue);
                }else if($row == 3){
                }else{
                    $newItem = [
                        'activity_id' => $rowValue[$colIndex['activity_id']],
                        'goods_id' => $rowValue[$colIndex['goods_id']]
                    ];
                    $res = $this->save_discount_activities($newItem);
                    $goods_arrt_list= array_push($goods_arr,array( 'activity_id' => $rowValue[$colIndex['activity_id']],
                        'goods_id' => $rowValue[$colIndex['goods_id']]));

                    $str .= ','.$rowValue[$colIndex['goods_id']];//拼接起来
                    $str_id=$rowValue[$colIndex['activity_id']];
                    \Yii::warning('**************看看有多少**************'.$str,'info');

                    if($res){
                        $arr[] = $res;
                    }
                }
            }
            ++$row;
        }

        $newItemGoods = [
            'activity_id' => $str_id,
            'goods_id' =>$str
        ];
        $this->save_discount_activities($newItemGoods);
        $count = count($arr);
        unlink($uploadfile);
        return [
            'code' => 0,
            'msg' => "共导入{$count}条数据"
        ];
    }

    //TODO 新增评论信息  Allon  2019年8月13日09:14:17
    private function save_discount_activities($list = [])
    {
        if(count($list) == 0){
            return false;
        }
        $this->logger(__LINE__ .'*******************save_discount_activities************************');
        $rdate = mt_rand(time()-3600*24*7,time());//生成随机时间 7天内
        $model = DiscountActivities::findOne([
            'id' => $list['activity_id']
        ]);
        if(!$model)
        {
            return false;
        }
        if (!empty($list['goods_id'])) {
            $goods_arr= explode(',', $list['goods_id']) ;//解析逗号分隔数组
            $model->goods_id_list = json_encode($goods_arr, JSON_UNESCAPED_UNICODE);
        }
        $model->addtime = $rdate;
        if ($model->save()) {
            return true;
        } else {
            return false;
        }
    }

    public function logger($log_content)
    {
        $max_size = 100000;
        $log_filename = "raw.log";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }
}