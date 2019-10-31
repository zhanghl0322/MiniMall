<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/21
 * Time: 9:58
 */

namespace app\models;

use Curl\Curl;
use Hejiang\Express\Exceptions\TrackingException;
use Hejiang\Express\Trackers\TrackerInterface;
use Hejiang\Express\Waybill;
use yii\helpers\VarDumper;

class ExpressDetailForm extends Model
{
    public $express_no;
    public $express;
    public $store_id;

    public $status_text = [
        1 => '?',
        2 => '运输中',
        3 => '已签收',
        4 => '问题件',
    ];

    public function rules()
    {
        return [
            [['express', 'express_no'], 'trim'],
            [['express_no', 'express', 'store_id'], 'required'],
        ];
    }

    public function search()
    {
        if (!$this->validate()) {
            return $this->errorResponse;
        }
        return $this->getData();
    }

    private function transExpressName($name)
    {
        if (!$name) {
            return false;
        }
        $append_list = [
            '快递',
            '快运',
            '物流',
            '速运',
            '速递',
        ];
        foreach ($append_list as $append) {
            $name = str_replace($append, '', $name);
        }

        $name_map_list = [
            '邮政快递包裹' => '邮政',
            '邮政包裹信件' => '邮政',
        ];
        if (isset($name_map_list[$name])) {
            $name = $name_map_list[$name];
        }
        return $name;
    }

    private function getData()
    {
        /**@var array $status_map 定义在 Hejiang\Express\Status */
        //TODO：调整签收为发货处理  2019-05-31 16点27分
        $status_map = [
            -1 => '已揽件',
            0 => '已揽件',
            1 => '已发出',
            2 => '在途中',
            3 => '派件中',
            4 => '已发货',
            5 => '已自取',
            6 => '问题件',
            7 => '已退回',
            8 => '已退签',
        ];
        //TODO 新增物流编Arry 采用最新版同步快递鸟官网  2019.06.19
        $expressname_map_list=$this->getSupportedExpresses();
        $ShipperCodeNmae = $expressname_map_list[$this->transExpressName($this->express)];
        $logisticResult= $this->getOrderTracesByJson($ShipperCodeNmae,$this->express_no);//调用物流查询
        $data = json_decode($logisticResult,true);

        if (isset($status_map[$data['State']])) {
            $status_text = $status_map[$data['State']];
        } else {
            $status_text = '状态未知';
        }
        if ($data['Success']) {
            return [
                'code' => 0,
                'data' => [
                    'list' => $data['Traces'],
                    'status' => $data['State'],
                    'status_text' => $status_text
                ],
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '未查询到物流信息。',
            ];
        }
    }

    private function getKuaidiniaoConfig()
    {
        $store = Store::findOne($this->store_id);
        if (!$store || !$store->kdniao_mch_id || !$store->kdniao_api_key) {
            return ['', ''];
        }
        $mch_id = $store->kdniao_mch_id;
        $api_key = $store->kdniao_api_key;
        return [$mch_id, $api_key];
    }

    /**
     * Json方式 查询订单物流轨迹
     *@$shipperCode  string
     * @$logisticCode
     */
    function getOrderTracesByJson($shipperCode,$logisticCode){
        //$requestData= "{'OrderCode':'','ShipperCode':'ZTO','LogisticCode':'7515635338888841708'}";
        $requestData= "{'OrderCode':'','ShipperCode':'$shipperCode','LogisticCode':'$logisticCode'}";
        $datas = array(
            'EBusinessID' => '1539698',
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] =$this->encrypt($requestData, '8e11491a-2c80-4ae6-ac1c-e32d90d99e13');
        $result=$this->sendPost('http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx', $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }

    public  function getSupportedExpresses()
    {
        return [
            '京东' => 'JD',
            '顺丰' => 'SF',
            '申通' => 'STO',
            '韵达' => 'YD',
            '圆通' => 'YTO',
            '中通' => 'ZTO',
            '百世' => 'HTKY',
            'EMS' => 'EMS',
            '天天' => 'HHTT',
            '邮政' => 'YZPY',
            '宅急送' => 'ZJS',
            '国通' => 'GTO',
            '全峰' => 'QFKD',
            '优速' => 'UC',
            '中铁' => 'ZTKY',
            '中铁' => 'ZTWL',
            '亚马逊' => 'AMAZON',
            '城际' => 'CJKD',
            '德邦' => 'DBL',
            '汇丰' => 'HFWL',
            '百世' => 'BTWL',
            '安捷' => 'AJ',
            '安能' => 'ANE',
            '安信达' => 'AXD',
            '北青小红帽' => 'BQXHM',
            '百福东方' => 'BFDF',
            'CCES' => 'CCES',
            '城市100' => 'CITY100',
            'COE东方' => 'COE',
            '长沙创一' => 'CSCY',
            '成都善途' => 'CDSTKY',
            'D速' => 'DSWL',
            '大田' => 'DTWL',
            '快捷' => 'FAST',
            '联邦' => 'FEDEX',
            'FEDEX' => 'FEDEX_GJ',
            '飞康达' => 'FKD',
            '广东邮政' => 'GDEMS',
            '共速达' => 'GSD',
            '高铁' => 'GTSD',
            '恒路' => 'HLWL',
            '天地华宇' => 'HOAU',
            '华强' => 'hq568',
            '华夏龙' => 'HXLWL',
            '好来运' => 'HYLSD',
            '京广' => 'JGSD',
            '九曳供应链' => 'JIUYE',
            '佳吉' => 'JJKY',
            '嘉里' => 'JLDT',
            '捷特' => 'JTKD',
            '急先达' => 'JXD',
            '晋越' => 'JYKD',
            '加运美' => 'JYM',
            '佳怡' => 'JYWL',
            '跨越' => 'KYWL',
            '龙邦' => 'LB',
            '联昊通' => 'LHT',
            '民航' => 'MHKD',
            '明亮' => 'MLWL',
            '能达' => 'NEDA',
            '平安达腾飞' => 'PADTF',
            '全晨' => 'QCKD',
            '全日通' => 'QRT',
            '如风达' => 'RFD',
            '赛澳递' => 'SAD',
            '圣安' => 'SAWL',
            '盛邦' => 'SBWL',
            '上大' => 'SDWL',
            '盛丰' => 'SFWL',
            '盛辉' => 'SHWL',
            '速通' => 'ST',
            '速腾' => 'STWL',
            '速尔' => 'SURE',
            '唐山申通' => 'TSSTO',
            '全一' => 'UAPEX',
            '万家' => 'WJWL',
            '万象' => 'WXWL',
            '新邦' => 'XBWL',
            '信丰' => 'XFEX',
            '希优特' => 'XYT',
            '新杰' => 'XJ',
            '源安达' => 'YADEX',
            '远成' => 'YCWL',
            '义达' => 'YDH',
            '越丰' => 'YFEX',
            '原飞航' => 'YFHEX',
            '亚风' => 'YFSD',
            '运通' => 'YTKD',
            '亿翔' => 'YXKD',
            '增益' => 'ZENY',
            '汇强' => 'ZHQKD',
            '众通' => 'ZTE',
            '中邮' => 'ZYWL',
            '速必达' => 'SUBIDA',
            '瑞丰' => 'RFEX',
            '快客' => 'QUICK',
            'CNPEX中邮' => 'CNPEX',
            '鸿桥供应链' => 'HOTSCM',
            '海派通' => 'HPTEX',
            '澳邮专线' => 'AYCA',
            '泛捷' => 'PANEX',
            'PCA Express' => 'PCA',
            'UEQ Express' => 'UEQ',
            '程光' => 'CG',
            '富腾达' => 'FTD',
            '中通快运' => 'ZTOKY',
            '品骏快递' => 'PJ',
        ];
    }
}
