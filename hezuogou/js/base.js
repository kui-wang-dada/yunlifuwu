$(function () {
    // 初始化数据
    window.init = {
        "type":1,
        "money_jiben":{
            "money0_1": 1000,
            "money0_2": 5000,
            "money0_3": 2000,
            "money1_1": 4000,
            "money1_2": 20000,
            "money1_3": 8000,
            "money2_1": 10000,
            "money2_2": 50000,
            "money2_3": 50000,
            "money3_1": 10000,
            "money3_2": 50000,
            "money3_3": 100000,
            "money4_1": 10000,
            "money4_2": 50000,
            "money4_3": 150000
        },
        "money_moren":{
            "money_1":"",
            "money_2":"",
            "money_3":""
        },
        "xieyi_time": 1,
        "shengxiao_time": "2017-11-15",
        "jiezhi_time":"2018-11-14",
        "xuanchuan": 2,
        "name": "王逵",
        "person_number":"",
        "tuikuan":"a"
    }
    // vue.js数据化绑定
    var vm1 = new Vue({
        el: "#wrap",
        data: init
    })
    // 会员等级判定;第一个页面
    //初始化
    var number = init.type;
    var sWidth=$("#nav li").width();
    var now =-(number-1)*sWidth;
    $("#nav .nav_1").stop(true,false).animate({"left":now},200)

    $(".is-left .mint-button").click(function () {
        history.go(-1);
    })
    $(".is-right .mint-button").click(function () {
        window.location.href="xieyi.html"
    })
    $("#wrap .message").hide();
    $("#wrap .message").eq(number).show()
    $(".money_1").hide();
    $(".money_1").eq(number).show();
    $(".money_2").hide();
    $(".money_2").eq(number).show();
    $(".money_3").hide();
    $(".money_3").eq(number).show();
    $(".money_4").hide();
    $(".money_4").eq(number).show();
    for (var i = number; i < 5; i++) {
        $("#nav li div ").eq(i).addClass("normal")
        $("#nav li div").eq(number).addClass("active")
        $("#nav li div").eq(i).on("click",function () {
            var index=$("#nav li div").index(this);
            $("#nav li div").removeClass("active")
            $(this).addClass("active")
            $("#wrap .message").hide();
            $("#wrap .message").eq(index).show()
            $(".money_1").hide();
            $(".money_1").eq(index).show();
            $(".money_2").hide();
            $(".money_2").eq(index).show();
            $(".money_3").hide();
            $(".money_3").eq(index).show();
            $(".money_4").hide();
            $(".money_4").eq(index).show();
            now=-(index-1)*sWidth
            $("#nav .nav_1").stop(true,false).animate({"left":now},200)
        })
    }
// 拖动nav事件
    $("#nav .nav_1").on("touchstart",function(e){
        // e.preventDefault();
    startX = e.originalEvent.changedTouches[0].pageX,
            startY = e.originalEvent.changedTouches[0].pageY;
    });
    $("#nav .nav_1").on("touchend", function(e) {
        // e.preventDefault();
        moveEndX = e.originalEvent.changedTouches[0].pageX,
            moveEndY = e.originalEvent.changedTouches[0].pageY,
            X = moveEndX - startX,
            Y = moveEndY - startY;

        if ( X > 0 ) {
            now+=sWidth
            if(now>=100){now=100}
            $(this).stop(true,false).animate({"left":now},200)
            console.log("右边")
        }
        else if ( X < 0 ) {
            now-=sWidth
            if(now<=-210){now=-210}
            $(this).stop(true,false).animate({"left":now},200)
            console.log("左边")
        }

    });


    // 立即签约绑定事件;
    $(".footer_1 input").click(function () {
        if ($(".choose input").prop("checked") == true){
            var index=$("#nav li div").index($(".active"));
            switch(index){
                case 0:
                    init.money_moren.money_1=init.money_jiben.money0_1;
                    init.money_moren.money_2=init.money_jiben.money0_1;
                    init.money_moren.money_3=init.money_jiben.money0_1;
                    break;
                case 1:
                    init.money_moren.money_1=init.money_jiben.money1_1;
                    init.money_moren.money_2=init.money_jiben.money1_1;
                    init.money_moren.money_3=init.money_jiben.money1_1;
                    break;
                case 2:
                    init.money_moren.money_1=init.money_jiben.money2_1;
                    init.money_moren.money_2=init.money_jiben.money2_1;
                    init.money_moren.money_3=init.money_jiben.money2_1;
                    break;
                case 3:
                    init.money_moren.money_1=init.money_jiben.money3_1;
                    init.money_moren.money_2=init.money_jiben.money3_1;
                    init.money_moren.money_3=init.money_jiben.money3_1;
                    break;
                case 4:
                    init.money_moren.money_1=init.money_jiben.money4_1;
                    init.money_moren.money_2=init.money_jiben.money4_1;
                    init.money_moren.money_3=init.money_jiben.money4_1;
                    break;
            }
            // $.ajax({
            //     type: 'POST',
            //     dataType: 'json',
            //     data: init.money_moren,
            //     url: '',
            //     success: function (r) {
            //         if (r.status == 'error') {
            //             layer.msg("您输入的身份证号码有误")
            //         } else if (r.status == 'success') {
            //             window.location.href = "step3.html"
            //         }
            //     },
            //     error: function () {
            //         console.log('系统繁忙,请用电话与我们联系!');
            //     }
            // });
            window.location.href = "step2.html"
        } else {
            layer.msg("请阅读并确认页面下方的条款", {
                area: ["300px", "50px"],
                offset: "60%",
            })
        }
    })

//    输入身份证事件;第二个页面
//    小图标点击事件

    $(".message_2 .icon_x").eq(0).click(function(){
        $(this).hide();
        init.name=""
    })
    $(".message_2 .icon_x").eq(1).hide();
    $(".message_2 .icon_x").eq(1).click(function(){
        $(this).hide();
        init.person_number=""
    })
    $(".message_2 .name").keyup(function () {
        $(".message_2 .icon_x").eq(0).show();
    })
    $(".message_2 .shenfen").keyup(function () {
        $(".message_2 .icon_x").eq(1).show();
        $(this).focus()
        var data = {
            name: init.name,
            person_number: init.person_number
        }
        if (init.person_number) {
            $(".nextstep").css("background-color", "#FF2832")
            $(".nextstep").click(function () {
                window.location.href="step3.html"
                // $.ajax({
                //     type: 'POST',
                //     dataType: 'json',
                //     data: data,
                //     url: '',
                //     success: function (r) {
                //         if (r.status == 'error') {
                //             layer.msg("您输入的身份证号码有误")
                //         } else if (r.status == 'success') {
                //             window.location.href = "step3.html"
                //         }
                //     },
                //     error: function () {
                //         console.log('系统繁忙,请用电话与我们联系!');
                //     }
                // });
            });
        } else{
            $(".nextstep").css("background-color", "#b3b3b3")
        }
    })


    // 样式调整;第三个页面
    var height = $(".message_3 .msg-cell-title").eq(1).css("height");
    $(".message_3 .msg-cell-title span").css("line-height", height);
    //输入框点击事件
    $(".message_3 input").click(function () {
        if ($(this).prop("checked") == true) {
            $(".message_3 .msg-cell-value span").removeClass("active");
            $(this).parent().addClass("active")
        } else {
            $(this).parent().removeClass("active")
        }
    })
    //立即支付事件
    $(".footer_3 input").click(function () {
        if ($(".message_3 input").prop("checked") == true){
            window.location.href = "success.html"
        } else {
            layer.msg("请选择支付方式！", {
                area: ["200px", "50px"],
                offset: "60%",

            })
        }
    })

    // 第四个页面


    //成功页面
    $(".content_5 input").click(function () {
        console.log(init.person_number)
        window.location.href="xieyi.html"
    })
})


