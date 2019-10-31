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

namespace app\modules\mch\models\goods;


use app\models\Goods;
use app\models\GoodsPic;
use app\modules\mch\models\MchModel;
use yii\web\UploadedFile;

//TODO 新增Excel 评论导入 Allon  2019年8月12日16:01:54
class Taobaoexcel extends MchModel
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
                        'score' => $rowValue[$colIndex['score']],
                        'goods_id' => $rowValue[$colIndex['goods_id']],
                        'content' => $rowValue[$colIndex['content']],
                        'pic_list' => $rowValue[$colIndex['pic_list']],
                    ];
                   // \Yii::warning("==随机时间==".$rdate,'info');
                    //echo date("Y-m-d h:i:s",$rdate);
                   // \Yii::warning($rowValue[$colIndex['content']]."==Excel导入值==".$rowValue[$colIndex['addtime']],'info');
                    $res = $this->save_comment($newItem);
                    if($res){
                        $arr[] = $res;
                    }
                }
            }
            ++$row;
        }
        $count = count($arr);
        unlink($uploadfile);
        return [
            'code' => 0,
            'msg' => "共导入{$count}条数据"
        ];
    }

    //TODO 新增评论信息  Allon  2019年8月13日09:14:17
    private function save_comment($list = [])
    {
        if(count($list) == 0){
            return false;
        }
        //TODO 读取临时虚拟用户表随机取一条用户 Allon  2019年8月13日11:11:37
        $sql="SELECT *
                FROM  hjmall_temp_user AS t1
                JOIN 
                (
                    SELECT ROUND(RAND() * 
                    (
                    (SELECT MAX(id) FROM hjmall_temp_user) 
                    - 
                    (SELECT MIN(id) FROM hjmall_temp_user)
                    ) 
                    + 
                    (SELECT MIN(id) FROM hjmall_temp_user )
                    ) AS id
                ) AS t2 
                WHERE t1.id >= t2.id 
                ORDER BY t1.id 
                LIMIT 1";
        $temp_user = \Yii::$app->db->createCommand($sql)->queryAll();
        $rdate = mt_rand(time()-3600*24*7,time());//生成随机时间 7天内
        $order_comment = new \app\models\OrderComment();
        $order_comment->store_id = 1;
        $order_comment->order_detail_id = 0;
        $order_comment->user_id = 0;
        $order_comment->order_id = 0;
        $order_comment->goods_id = $list['goods_id'];
        $order_comment->score = 3;
        $order_comment->content = $list['content'];
        $order_comment->virtual_user=$temp_user[0]['nickname'];
        $order_comment->virtual_avatar=$temp_user[0]['headimgurl'];
        $order_comment->is_virtual=1;//虚拟用户设置
        if (!empty($list['pic_list'])) {
            $img_arr= explode(',', $list['pic_list']) ;//解析逗号分隔数组
            $order_comment->pic_list = json_encode($img_arr, JSON_UNESCAPED_UNICODE);
        }
        else{
            $order_comment->pic_list = [];//无用户图片置空
        }
        $order_comment->addtime = $rdate;
        if ($order_comment->save()) {
            return true;
        } else {
            return false;
        }
    }
}