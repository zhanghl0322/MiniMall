<!--<!DOCTYPE html>-->
<!--<html>-->
<!--<meta charset="utf-8">-->
<!--<head>-->
<!--    <title>抓取淘宝评论</title>-->
<!--</head>-->
<!--<style type="text/css">-->
<!--    .boss{-->
<!--        width:500px;-->
<!--        height:350px;-->
<!--        margin:0 auto;-->
<!--        margin-top: 10%;-->
<!--    }-->
<!--    img{-->
<!--        width:500px;-->
<!--        height:350px;-->
<!--        position: absolute;-->
<!--        z-index: -10;-->
<!--    }-->
<!--    .div1{-->
<!--        width: 200px;-->
<!--        height:200px;-->
<!--        margin:0 auto;-->
<!--        padding: 15px;-->
<!--    }-->
<!--    button{-->
<!--        width:180px;-->
<!--        height:30px;-->
<!--        background-color: skyblue;-->
<!--        margin-top: 10px;-->
<!--    }-->
<!--    #inp1{-->
<!--        width:115px;-->
<!--    }-->
<!--</style>-->
<!--<body>-->
<!--<div class="boss">-->
<!--    <img src="1.jpg">-->
<!--    <div class="div1">-->
<!--        <h1>抓取淘宝评论</h1>-->
<!--        <form action="pinglun.php" method="post">-->
<!--            请填写itemId:<br><input type="text" id="inp2" placeholder="请填写itemId" name="itemId" value=""><br>-->
<!--            请填写spuId:<br><input type="text" id="inp2" placeholder="请填写spuId" name="spuId" value=""><br>-->
<!--            请填写sellerId:<br><input type="text" id="inp3" placeholder="请填写sellerId" name="sellerId" value=""><br>-->
<!--            请填写生成文件夹名:<br><input type="text" id="inp3" placeholder="XXX.txt格式" name="sed" value=""><br>-->
<!--            <button>提交</button>-->
<!--        </form>-->
<!--    </div>-->
<!--</div>-->
<!--</body>-->
<!--</html>-->
<!---->
<!---->
<!---->
<!---->
<!--pinglun.php-->

<?php
//https://rate.tmall.com/list_detail_rate.htm?itemId=564954952507&spuId=933371447&sellerId=924448679&order=3&currentPage=1&append=0&content=1&tagId=&posi=&picture=1&groupId=&ua=098%23E1hvzQvXvw%2BvUvCkvvvvvjiPRF5ZzjnCRszZ0j3mPmPZljtEnLs9gjYRP25Wgj189phv8iMGdlquzYswMm8U7kdoE93ukbj2J1SemZ3usGQ2OO8k9I8ByeeBvIUkUFyCvvpvvvvvCQhvCYsw7DdNNdArvpvEvvAg9JfevC%2BpdphvmpvWKUj%2Bqv2mqFyCvvpvvvvv9phvHnQGJxzVzYswz2hl7%2FMVMbqwoliIdphvmpvpS9nxxpmRsT6CvCUyHH4mrqwvbZ0nrsXrmRjBAO7rvpvoECLHvYSfvnATFfw0fpF36weHdphvhkpZgvPPsQ2pJI0zne6UgajE34wCvvpvvUmmRphvCvvvvvvEvpCWhPKFvvwdD7zhs8TJwyNZeExreEIaWXxr1nkK5FtffwLWaB4AVAdWaNLUeiiev0zhljZ7%2B3%2Buljc6%2Ff8rwZEl%2BExreEyaUExr1nkKhgyCvvOUvvVvaZmivpvUvvmvn%2BnMfF0tvpvIvvvvvhCvvvvvvUnvphvWX9vv96CvpC29vvm2phCvhhvvvUnvphvppvhCvvOvCvvvphvPvpvhvv2MMgwCvvpvCvvvdphvhkpZOQWSbQ2v7g0zne6UgajE346CvvyvhWUmPL6vIekrvpvEvvQB948kv8d%2BdphvmpvWKQbj1Qm0LsyCvvpvvvvv&itemPropertyId=&itemPropertyIndex=&userPropertyId=&userPropertyIndex=&rateQuery=&location=&needFold=0&_ksTS=1565578350198_1019&callback=jsonp1020
header("Content-Type:text/html;charset=utf-8");
$itemId = '564954952507';
$spuId = '933371447';
$sellerId = '924448679';
$sed = 'TB.txt';
$url = "https://rate.tmall.com/list_detail_rate.htm?itemId=$itemId&spuId=$spuId&sellerId=$sellerId&order=3¤tPage=1&append=0&content=1&tagId=&posi=&picture=1";
$res = file_get_contents($url);
// 匹配评论部分  
preg_match_all("/Content\":\"((.|\n)*?)\"/", $res, $match);
$wt = array();
$arr = $match[1];
// 去除空评论  
foreach ($arr as $v) {
    if ($v != '') {
        // 转化成utf-8编码  
        $wt[] = iconv("GBK", "UTF-8", $v);
    }
}
$str = implode("\r\n", $wt);
$txt = file_put_contents($sed, $str);
if ($txt == 'false') {
//    history.go(-1);
//    echo "<script>alert('储存失败');</script>";
    echo "储存失败".print_r($match[1]);
} else {
    echo "储存成功";
}
?>
<!------------------------->
<!--版权声明：本文为CSDN博主「PHP_programmer」的原创文章，遵循CC 4.0 by-sa版权协议，转载请附上原文出处链接及本声明。-->
<!--原文链接：https://blog.csdn.net/PHP_programmer/article/details/80608881-->