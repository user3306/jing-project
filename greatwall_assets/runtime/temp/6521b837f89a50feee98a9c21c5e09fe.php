<?php /*a:1:{s:81:"D:\WWW\greatwall_assets\application\officeaccount\view\finance\companydetail.html";i:1558435884;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <!-- 浏览器中页面将以原始大小显示，并不允许缩放 -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <!-- 网站开启对web app程序的支持 -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>公司简介</title>
    <link rel="stylesheet" href="/greatwall_assets/public/static/home/officeaccount/css/public.css">
    <link rel="stylesheet" href="/greatwall_assets/public/static/home/officeaccount/css/style.css?v=111">
    <link rel="stylesheet" href="/greatwall_assets/public/static/home/officeaccount/css/swiper.min.css">
    <script src="/greatwall_assets/public/static/home/officeaccount/js/jquery.min.js"></script>
    <script src="/greatwall_assets/public/static/home/officeaccount/js/swiper.min.js"></script>
    <!--<script src="/greatwall_assets/public/static/home/officeaccount/js/tab.js"></script>-->
</head>

<body>

<div class="content">
    <div class="topbg">
        <img src="/greatwall_assets/public/static/home/officeaccount/img/logo.png" alt="" class="logo" style="margin:0.5rem 0 0 17%;">
        <div class="toptit"><a href="#"><span></span></a><span class="tit-one" style="margin-left:0px">联系我们</span></div>
        <div class="swiper-container clear">
            <div class="swiper-wrapper">
                <div class="swiper-slide clear">
                    <img class="left" src="/greatwall_assets/public/static/home/officeaccount/img/banner-03.jpg" alt="">
                </div>
                <div class="swiper-slide clear">
                    <img class="left" src="/greatwall_assets/public/static/home/officeaccount/img/banner-04.jpg" alt="">
                </div>
                <div class="swiper-slide clear">
                    <img class="left" src="/greatwall_assets/public/static/home/officeaccount/img/banner-01.jpg" alt="">
                </div>
            </div>
            <!-- 如果需要分页器 -->
            <div class="swiper-pagination"></div>
        </div>
        <div class="pics-bot"></div>
        <!-- Start Tabs !-->
        <div class="zongg clear">
            <ul class="zong_left left">
                <?php if(is_array($companyclass) || $companyclass instanceof \think\Collection || $companyclass instanceof \think\Paginator): $i = 0; $__LIST__ = $companyclass;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                <li><button onclick="getarticle(<?php echo htmlspecialchars($vo['id']); ?>)"><?php echo htmlspecialchars($vo['typename']); ?></button></li>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </ul>
            <div class="zong_right right">
                <?php echo $content; ?>
            </div>
        </div>
        <!-- Tab Container !-->
    </div>
</div>
<script>
    !(function(doc, win) {
        var docEle = doc.documentElement,
            fn = function() {
                var width = docEle.clientWidth;
                var height = docEle.clientHeight;
                if (640 / 1000 > width / height) {
                    (docEle.style.fontSize = 16 * (width / 320) + "px");
                } else {
                    (docEle.style.fontSize=16 * (height / 500) + "px");
                }
            };
        fn();
        win.addEventListener("resize", fn, false);
        doc.addEventListener("DOMContentLoaded", fn, false);
    }(document, window));
    var mySwiper = new Swiper(".swiper-container",{
        autoplay:1500,
        loop:true,
        // 如果需要分页器，即下面的小圆点
        pagination: '.swiper-pagination',
        autoplayDisableOnInteraction:false
    })

    window.onload = function(){
        var lia=$('.zong_left li');
        var lib=$('.zong_right .zong_a');
        //速度
        var speed=30;
        $(".zong_left li:first-child").addClass("aa active");
        for(var i=0;i<lia.length;i++){
            lia[i].index=i;
            lia[i].onclick=function(){
                for(var j=0;j<lia.length;j++){
                    lia[j].className="aa";
                    console.log(lia[j]);
                }
                this.className ="aa active";

            }
        }
    }
    function getarticle(id) {
        $.ajax({
            url: "<?php echo url('officeaccount/Finance/companyprofile'); ?>",
            type : 'post',
            dataType : 'json',
            data:{typeid:id},
            success: function(result){
                $('.zong_a ul').html(result);
            }
        });
    }

</script>
</body>
</html>