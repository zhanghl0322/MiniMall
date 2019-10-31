<?php
//解析网页
function jsonp_decode($jsonp, $assoc = false) {
    $jsonp = trim($jsonp);
    if (isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
        $begin = strpos($jsonp, '(');
        if (false !== $begin) {
            $end = strrpos($jsonp, ')');
            if (false !== $end) {
                $jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
            }
        }
    }
    return json_decode($jsonp, $assoc);
}
//获取图片信息，写入文件当前采集的页码
function getdata() {
//    $url = 'https://rate.tmall.com/list_detail_rate.htm?itemId=40771336334&spuId=293134690&sellerId=2098049097&order=3&currentPage=4&append=0&content=1&tagId=&posi=&picture=&ua=098%23E1hv%2FpvUvbpvUpCkvvvvvjiPPsShgjnjRszZtjthPmPUtj1bRFMp6jrERs5UQjDvRuwCvvpvvhHhRphvCvvvphvPvpvhMMGvvvhCvvOvChCvvvmtvpvIvvCvpvvvvvvvvhNjvvmvfvvvBGwvvvUwvvCj1Qvvv99vvhNjvvvmm8yCvv9vvhhy56dBHIyCvvOCvhE20RvEvpCWv2O1WB0xdBkKHkx%2F1j7JhXk7OyTxfBkK5dUf857gKL90r2pwkUkZHd8raAd6D46Od3wAxYexRfeAHVDHD70OdiTAVA1lK27zr2aIo9%3D%3D&needFold=0&_ksTS=1537188472682_863&callback=jsonp864';

    $url = 'https://rate.tmall.com/list_detail_rate.htm?itemId=527365688650&spuId=511956162&sellerId=2259699799&order=3&currentPage=1&append=0&content=1&tagId=620&posi=1&picture=0&groupId=&ua=098%23E1hv6QvRvLhvUvCkvvvvvjiPRF5Ztji2RszZ0jthPmPOzjtEPFMp0jY8RFMZ1j1PRphvCvvvphmrvpvO3g2rTfQvvveNRoHxDaVQ%2Bt%2BXak5OACkQdphvVp9C7xlEvvmj7sy3xXcB%2BbRfVcOi1RvXVLV5vpvhvvCCBvGCvvpvvPMMmphvLUHdu89afXkK5FGDN%2B1lHd8reB691EkAdBeKfvDrA8TJPqUf8j7Q%2Bu0Xd56OfwoKhq8rwk%2FQF46Xe8tYvtxre4g7%2B3%2BiaNoAdBAKNymEvpvVvpCmpYLZuphvmvvvpo6onDPzKphv8vvvphvvvvvvvvChB9vv9wQvvhNjvvvmjvvvBGwvvvUUvvCj1Qvvv99CvpvVvUCvpvvvRphvCvvvphmjvpvhvUCvp86Cvvyv9nSNh9vv3eVCvpvF4x%2BDMGjw7Di4fLj5MRrfcDugzg9umWTMsd%2BLyo8kKXmWkKU%3D&itemPropertyId=&itemPropertyIndex=&userPropertyId=&userPropertyIndex=&rateQuery=&location=&needFold=0&_ksTS=1565591976631_1015&callback=jsonp1016';
    //获取网页
//
//    //新增带cookie  淘宝处理
//    $cookie = 'cna=4LIBE7wnyVwCAT2MfC6hmsCs; x=__ll%3D-1%26_ato%3D0; UM_distinctid=16ae893bd6c137-0f5492ff2b69d2-5d4e211f-1fa400-16ae893bd7129; otherx=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0; OZ_1U_2061=vid=vc3556010fbea0.0&ctime=1561621045&ltime=1546999402; hng=CN%7Czh-CN%7CCNY%7C156; uc3=nk2=0IE%2BXNKjqU%2BaRhC3&vt3=F8dBy3KzvObqNMGCKjQ%3D&lg2=U%2BGCWk%2F75gdr5Q%3D%3D&id2=UoYagkEGeNm47A%3D%3D; t=144416c3b5ce62a6c59add450f5f167f; tracknick=%5Cu6F6E%5Cu6D41%5Cu8BBE%5Cu8BA1%5Cu4E2D%5Cu56FD; lid=%E6%BD%AE%E6%B5%81%E8%AE%BE%E8%AE%A1%E4%B8%AD%E5%9B%BD; uc4=id4=0%40UO6TGh%2BTJ9EPqIsgEeeNh4fSCAYJ&nk4=0%400gs8bbzAPmKVk7wHKq5UVsJ1Zn9G0DQ%3D; lgc=%5Cu6F6E%5Cu6D41%5Cu8BBE%5Cu8BA1%5Cu4E2D%5Cu56FD; enc=neg6%2BWcynE2B3KmvGDrunA%2BM%2FEVP1JnRLFhGzqwgx1QbvpIkrsT1h%2B8kwb116E4AK4QqSL1WEJ7U5CAIeigLHw%3D%3D; _tb_token_=5e55eeeee1e58; cookie2=119743f0ec4f8e60a1415a3edd93babc; l=cB_Yd_7PvrbmW7bfBOfixuIRh6_OfIdf1sPzw4wgwICP935vjnLcWZebEWLJCnGVL6lyJ3WHB0HYB58Edy4EhZXRFJXn9MpO.; isg=BKys6edd3S4EOMhXwOvBlh0mfYoezVqIsE53LAbsVtfDEU4bL3SVntnnMZkMmYhn';
//    $opts = array('http' => array('header'=> 'Cookie:'.@$cookie.''));
//    $context = stream_context_create($opts);

    //$html = file_get_contents($url);
    $html= file_get_contents($url);
    //运行网页,获取数据
    $data = jsonp_decode($html);

    //获取总页数
    $allpage = $data->rateDetail->paginator->lastPage;
    //当前页面
    $page = $data->rateDetail->paginator->page;
    //写入文件信息
    //创建目录
    $myfile = fopen("log.txt", "a");
    fwrite($myfile, '当前情况：' . $html. '\\n');
    $len = fwrite($myfile, '当前采集第：' . $page . '页,一共有：' . $allpage . '页' . '\\n');

    //获取数据图片
    $webdata = $data->rateDetail->rateList;
    //遍历数据
    foreach ($webdata as $key => $value) {
        //var_dump($value);
        //文件夹ID
        $uid = $value->id;
        //图片
        $pics = $value->pics;
        //追加的图片
        $zpics = $value->appendComment;
        if (!empty($zpics)) {
            $zzpics = $value->appendComment->pics;
            //有追加评论的图片
            if (!empty($zzpics) and count($zzpics >= 3)) {
                $allarr = array_merge($pics, $zzpics);
                savefile($allarr, $uid);
                sleep(1);
            } else {
                continue;
            }
        } else {
            if (count($pics >= 3) and !empty($pics)) {
                savefile($pics, $uid);
                sleep(1);
            } else {
                continue;
            }
        }
    }
}

//保存图片
function savefile($file, $finleid) {
    //判断目录
    foreach ($file as $key => $value) {
        $path = $finleid . '/';
        if (!is_dir($path)) {
            mkdir($path);
        }
        $img = file_get_contents('http:' . $value);
        # 网络显示图片扩展名不是必须的，只不过在windows中无法识别
        ;
        file_put_contents($path . time() . '.jpg', $img);
    }
}

getdata();
?>