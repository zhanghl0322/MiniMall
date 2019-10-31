<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2018/3/9
 * Time: 10:28
 */
Yii::warning('扫码授权Token','info');
?>
<style>
    body {
        background: #f7f6f1;
    }

    .main-box {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }

    .main-content {
        max-width: 260px;
    }

    .title {
        text-align: center;
        padding: 16px;
        font-size: 1.35rem;
    }

    .qrcode {
        max-width: 260px;
        border-radius: 0;
        border: 1px solid #eee;
        margin-bottom: 20px;
        padding: 1rem;
        background-color: #fff;
    }

    .desc {
        background: #eee;
        max-width: 100%;
        text-align: center;
        padding: 12px;
        border-radius: 999px;
        box-shadow: inset 1px 1px 3px 0px rgba(0, 0, 0, .2), 1px 1px 1px #fff;
    }

    .login-success {
        color: #1f9832;
        display: none;
    }

    .platform-item {
        text-decoration: none;
        text-align: center;
        padding: .5rem;
        margin: 0 1rem;
    }
  /*登陆区域CSS*/
    .login-body {
        display: inline-block;
        vertical-align: middle;
        width: 400px;
        padding: 30px 30px;
        margin: 75px 0;
        background: #fff;
        border-radius: 4px;
        padding: 50px 40px 40px;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }
    .login-content {
    }
    .brand .brand-text {
        margin-top: 20px;
        margin-bottom: 11px;
        font-size: 20px !important;
        font-family: "Microsoft YaHei";
        text-shadow: rgba(0, 0, 0, .15) 0 0 1px;
        font-weight: 400;
        color: #37474f;
        text-align: center;
    }
    .login-form {
        margin: 45px 0 30px;
    }
    .login-form .form-group {
        margin: 30px 0;
    }
    .login-form input {
        display: block;
        border: 0;
        border-radius: 0;
        -webkit-box-shadow: none;
        box-shadow: none;
        width: 100%;
        border-bottom: 1px solid #E4EAEC;
        font-size: 14px;
        height: 42px;
        line-height: 1.5;
        outline: none;
        padding: 0 5px;
        color: #a3afb7;
        font: 14px/1.5 "Segoe UI", "Lucida Grande", Helvetica, Arial, "Microsoft YaHei", FreeSans, Arimo, "Droid Sans", "wenquanyi micro hei", "Hiragino Sans GB", "Hiragino Sans GB W3", Roboto, Arial, sans-serif;

        transition: all 0.3s ease-in-out;
        -moz-transition: all 0.3s ease-in-out;
        -webkit-transition: all 0.3s ease-in-out;
        -o-transition: all 0.3s ease-in-out;

    }

    .login-form input:focus {
        border-bottom: 1px solid #62a8ea;
    }

    .login-form button {
        width: 100%;
        margin-top: 40px;
        padding: 10px 18px;
        font-size: 18px;
        line-height: 1.3333333;
        border-radius: 4px;
        white-space: normal;
        -webkit-transition: border .2s linear, color .2s linear, width .2s linear, background-color .2s linear;
        -o-transition: border .2s linear, color .2s linear, width .2s linear, background-color .2s linear;
        transition: border .2s linear, color .2s linear, width .2s linear, background-color .2s linear;
        -webkit-font-smoothing: subpixel-antialiased;

        color: #fff;
        background-color: #62a8ea;
        border-color: #62a8ea;
        background-image: none;
        border: 1px solid transparent;
        cursor: pointer;
        font: 18px/1.5 "Segoe UI", "Lucida Grande", Helvetica, Arial, "Microsoft YaHei", FreeSans, Arimo, "Droid Sans", "wenquanyi micro hei", "Hiragino Sans GB", "Hiragino Sans GB W3", Roboto, Arial, sans-serif;
    }


    .login-form button[disabled], .login-form button[disabled]:hover {
        color: #fff;
        background-color: #a2caee;
        border-color: #a2caee;
        cursor: not-allowed;
        opacity: .65;
    }


    .login-form button:hover {
        background-color: #89bceb;
        border-color: #89bceb;
    }

</style>
<div class="main-box" flex="dir:left main:center cross:center">
    <?php if ($_platform == 'wx'): ?>
        <div class="main-content">
            <div class="title">微信登陆</div>
            <img class="qrcode" src="<?= $img_url ?>">
            <div class="desc">
                <div class="login-tip">请使用微信扫描小程序码登录</div>
            </div>
        </div>
    <?php elseif ($_platform == 'my'): ?>
        <div class="main-content">
            <div class="title">支付宝登录</div>
            <img class="qrcode" src="<?= $img_url ?>">
            <div class="desc">
                <div class="login-tip">请使用支付宝扫描小程序码登录</div>
            </div>
        </div>
    <?php elseif ($_platform == 'sj'): ?>
        <!--商户登陆布局板块-->
        <div id="wrapper" class="login-body">
            <div class="login-content">
                <div class="brand">
                    <h2 class="brand-text">商户登陆</h2>
                </div>
                <div id="login-form" class="login-form">
                    <div class="form-group">
                        <input class="" name="user_name" placeholder="请输入用户名" type="text" required="">
                    </div>
                    <div class="form-group">
                        <input class="" name="password" placeholder="请输入密码" type="password" required="">
                    </div>
                    <div class="form-group">
                        <button id="btn-submit"  onclick="actionLogin()">
                            登录
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="main-content">
            <div class="title">请选择您的用户类型</div>
            <div flex="dir:left main:center">
                <a class="platform-item"
                   href="<?= Yii::$app->request->baseUrl ?>/mch.php?store_id=<?= Yii::$app->request->get('store_id') ?>&_platform=wx">
                    <div>
                        <img style="width: 100px;height: 100px"
                             src="https://open.weixin.qq.com/zh_CN/htmledition/res/assets/res-design-download/icon64_appwx_logo.png">
                    </div>
                    <div>微信用户</div>
                </a>
                <?php if ($isAlipay == 1): ?>
                    <a class="platform-item"
                       href="<?= Yii::$app->request->baseUrl ?>/mch.php?store_id=<?= Yii::$app->request->get('store_id') ?>&_platform=my">
                        <div>
                            <img style="width: 100px;height: 100px"
                                 src="<?= Yii::$app->request->baseUrl ?>/statics/images/alipay.png">
                        </div>
                        <div>支付宝用户</div>
                    </a>
                <?php endif; ?>
                <a class="platform-item"
                   href="<?= Yii::$app->request->baseUrl ?>/mch.php?store_id=<?= Yii::$app->request->get('store_id') ?>&_platform=sj">
                    <div>
                        <img style="width: 100px;height: 100px"
                             src="http://cloud.chehaiyang.com/Content/mobile/img/246.png">
                    </div>
                    <div>商家用户</div>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    var stop = false;
    var token = '<?=$token?>';

    function checkLogin() {
        if (stop)
            return;
        if (!token || token == '')
            return;
        $.ajax({
            url: '<?=Yii::$app->urlManager->createUrl(['user/passport/check-login',])?>',
            data: {
                token: token,
            },
            dataType: 'json',
            success: function (res) {
                $('.login-tip').text(res.msg);
                if (res.code == 1) {
                    stop = true;
                }
                if (res.code == 0) {
                    console.log('<?=Yii::$app->urlManager->createUrl(['user'])?>');
                    location.href = '<?=Yii::$app->urlManager->createUrl(['user'])?>';
                }
                if (res.code == -1) {
                    checkLogin();
                }
            },
            error: function () {
                stop = true;
            }
        });
    }
    checkLogin();
    function actionLogin() {
       var user_name= $(" input[ name='user_name' ] ").val();
       var password= $(" input[ name='password' ] ").val();
        if (!user_name || user_name == '')
            return;
        if (!password || password == '')
            return;
        $.ajax({
            url: '<?=Yii::$app->urlManager->createUrl(['user/passport/action-login',])?>',
            data: {
                user_name: user_name,
                password:password
            },
            dataType: 'json',
            success: function (res) {
                alert(res.msg);
                if (res.code == 1) {
                    stop = true;
                }
                if (res.code == 0) {
                    console.log('<?=Yii::$app->urlManager->createUrl(['user'])?>');
                    location.href = '<?=Yii::$app->urlManager->createUrl(['user'])?>';
                }
                if (res.code == -1) {
                    checkLogin();//重新检查登陆
                }
            },
            error: function () {
                stop = true;
            }
        });
    }

</script>