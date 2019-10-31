
<?php
/**
 * 获取国内随机IP地址
 * 注：适用于32位操作系统
 */
function randomIp(){
//    $cache = \YII::$app-->cache;
//    $cache->set('user','aaa',300);

    $ip_long = array(
        array('607649792', '608174079'), //36.56.0.0-36.63.255.255
        array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
        array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
        array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
        array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
        array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
        array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
        array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
        array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
        array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
    );
    $rand_key = mt_rand(0, 9);
    $ip  = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));

    return $ip;
}
$temp = mt_rand() / mt_getrandmax();
$logistic_num='73114562740254';
//www.kuaidi100.com/query?type=zhongtong&postid=73114134040652
$ReqUrl = 'http://www.kuaidi100.com/query?type=zhongtong&postid='.$logistic_num.'&id=1&valicode=&temp='.$temp;
//初始化curl
$ch = curl_init();
//设置超时
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_URL, $ReqUrl);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/".randomIp()." Safari/536.11");
$res = curl_exec($ch);
curl_close($ch);
$result = json_decode($res,true);

echo randomIp().'状态码==>'.$result['state'].$ReqUrl;











