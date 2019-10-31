<?php
// 淘宝评论的js,itemId和sellerId在采用的天猫产品页面看源代码能找到，spuId不用管
$url ='https://rate.tmall.com/list_detail_rate.htm?itemId=527365688650&spuId=511956162&sellerId=2259699799&order=3&currentPage=1&append=0&content=1&tagId=620&posi=1&picture=0&groupId=&ua=098%23E1hv6QvRvLhvUvCkvvvvvjiPRF5Ztji2RszZ0jthPmPOzjtEPFMp0jY8RFMZ1j1PRphvCvvvphmrvpvO3g2rTfQvvveNRoHxDaVQ%2Bt%2BXak5OACkQdphvVp9C7xlEvvmj7sy3xXcB%2BbRfVcOi1RvXVLV5vpvhvvCCBvGCvvpvvPMMmphvLUHdu89afXkK5FGDN%2B1lHd8reB691EkAdBeKfvDrA8TJPqUf8j7Q%2Bu0Xd56OfwoKhq8rwk%2FQF46Xe8tYvtxre4g7%2B3%2BiaNoAdBAKNymEvpvVvpCmpYLZuphvmvvvpo6onDPzKphv8vvvphvvvvvvvvChB9vv9wQvvhNjvvvmjvvvBGwvvvUUvvCj1Qvvv99CvpvVvUCvpvvvRphvCvvvphmjvpvhvUCvp86Cvvyv9nSNh9vv3eVCvpvF4x%2BDMGjw7Di4fLj5MRrfcDugzg9umWTMsd%2BLyo8kKXmWkKU%3D&itemPropertyId=&itemPropertyIndex=&userPropertyId=&userPropertyIndex=&rateQuery=&location=&needFold=0&_ksTS=1565591976631_1015&callback=jsonp1016';

$cookie = 'cna=4LIBE7wnyVwCAT2MfC6hmsCs; x=__ll%3D-1%26_ato%3D0; UM_distinctid=16ae893bd6c137-0f5492ff2b69d2-5d4e211f-1fa400-16ae893bd7129; otherx=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; OZ_1U_2061=vid=vc3556010fbea0.0&ctime=1561621045&ltime=1546999402; hng=CN%7Czh-CN%7CCNY%7C156; uc3=nk2=0IE%2BXNKjqU%2BaRhC3&vt3=F8dBy3KzvObqNMGCKjQ%3D&lg2=U%2BGCWk%2F75gdr5Q%3D%3D&id2=UoYagkEGeNm47A%3D%3D; t=144416c3b5ce62a6c59add450f5f167f; tracknick=%5Cu6F6E%5Cu6D41%5Cu8BBE%5Cu8BA1%5Cu4E2D%5Cu56FD; lid=%E6%BD%AE%E6%B5%81%E8%AE%BE%E8%AE%A1%E4%B8%AD%E5%9B%BD; uc4=id4=0%40UO6TGh%2BTJ9EPqIsgEeeNh4fSCAYJ&nk4=0%400gs8bbzAPmKVk7wHKq5UVsJ1Zn9G0DQ%3D; lgc=%5Cu6F6E%5Cu6D41%5Cu8BBE%5Cu8BA1%5Cu4E2D%5Cu56FD; enc=neg6%2BWcynE2B3KmvGDrunA%2BM%2FEVP1JnRLFhGzqwgx1QbvpIkrsT1h%2B8kwb116E4AK4QqSL1WEJ7U5CAIeigLHw%3D%3D; _tb_token_=5e55eeeee1e58; cookie2=119743f0ec4f8e60a1415a3edd93babc; x5sec=7b22726174656d616e616765723b32223a226235643936633537356162646536323033636534373762353565613234353461434a4b54784f6f46454d6d302f4b66452b3650437441453d227d; l=cB_Yd_7PvrbmWYrjBOfiGuIRh6_O3QdfCsPzw4wgwICP9V1v7E2VWZebB0TJCnGVL6uHR3WHB0HYB88Ufy4EhZXRFJXn9MpO.; isg=BODgUBN2qXIfFRQzfNf1uolise5yQc40hIoLSFryMvugVYR_Avg9Qrkj7b3wZXyL';
$opts = array('http' => array('header'=> 'Cookie:'.@$cookie.''));
$context = stream_context_create($opts);

//$html = file_get_contents($url);
$res= file_get_contents($url, false, $context);

//$res =  file_get_contents($url);
$txt = file_put_contents('TB测试文件.txt', $res);
// 匹配评论部分
preg_match_all("/Content\":\"((.|\n)*?)\"/",$res, $match);

$wt = array();

$arr = $match[1];
// 去除空评论
foreach ($arr as $v) {
    if($v != ''){
        // 转化成utf-8编码
        $wt[] = iconv("GBK","UTF-8", $v);
    }
}
echo "储存失败".print_r($wt[0]);
?>