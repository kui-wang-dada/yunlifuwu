$(function () {
    // 初始化数据
    window.init = {
        "type":level,
        level:{},
        "username": "",
        "idcard":"",
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
        window.location.href= $(this).data('url');
    })

    for (var i = number, len = $("#nav li").length; i < len; i++) {
        $("#nav li div ").eq(i).addClass("normal");
        $("#nav li div").eq(number).addClass("active");
        $("#nav li div").eq(number).trigger('click');
        $("#nav li div").eq(i).on("click",function () {
            var level = $(this).data('item');
            level = level ? level : {};
            level.start_time = getNowFormatDate();
            window.init.level = level;
            
            var index=$("#nav li div").index(this);
            $("#nav li div").removeClass("active")
            $(this).addClass("active")
            
            now=-(index-1)*sWidth
            $("#nav .nav_1").stop(true,false).animate({"left":now},200)
        });
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
            var level = $("#nav li div.active").data('item');
            var url = $(this).data('url');
            
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: {level: level.id, agree: 1},
                url: url,
                success: function (r) {
                    if (r.status === 1) {
                      window.location.href = r.url;
                    } else {
                      alertMsg(r.info);
                    }
                },
                error: function () {
                  alertMsg('网络错误，请稍后再试！');
                }
            });
        } else {
          alertMsg("请阅读并确认页面下方的条款")
        }
    })

//    输入身份证事件;第二个页面
//    小图标点击事件
    $(".message_2 .icon_x").on("click", function(){
        if($(this).hasClass('i-username')){
            init.username = '';
        }
        if($(this).hasClass('i-idcard')){
            init.idcard = '';
        }
        $(this).hide();
    });
    $(".message_2 input").on("keyup", function () {
      $(this).parent().parent().siblings('.msg-cell-value').find('.icon_x').show();
    });
  
    $(".nextstep").on("click", function () {
      if (!init.idcard || !init.username) {
          return false;
      }
      var url = $(this).data('url');
    
      $.ajax({
          type: 'POST',
          dataType: 'json',
          data: {
              username: init.username,
              idcard: init.idcard
          },
          url: url,
          success: function (r) {
              if (r.status === 1) {
                window.location.href = r.url
              } else {
                alertMsg(r.info)
              }
          },
          error: function () {
            alertMsg('网络错误，请稍后再试！');
          }
      });
    });



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
            var url = $(this).data('url');
            $.get(url, function (res) {
              if(res.status !== 1){
                alertMsg(res.info);
              }else{
                alertMsg('支付完成');
              }
              if(res.url) {
                window.location.href = res.url;
              }
            }, "json");
        } else {
            alertMsg("请选择支付方式！");
        }
    })


    //成功页面
    $(".content_5 input").click(function () {
        console.log(init.person_number)
        window.location.href= $(this).data('url');
    })
})

function getNowFormatDate(flag) {
  var date = new Date();
  var seperator1 = "-";
  var seperator2 = ":";
  var year = date.getFullYear();
  var month = date.getMonth() + 1;
  var strDate = date.getDate();
  if (month >= 1 && month <= 9) {
    month = "0" + month;
  }
  if (strDate >= 0 && strDate <= 9) {
    strDate = "0" + strDate;
  }
  if(flag){
    var currentdate = year + seperator1 + month + seperator1 + strDate
        + " " + date.getHours() + seperator2 + date.getMinutes()
        + seperator2 + date.getSeconds();
  }else{
    var currentdate = year + seperator1 + month + seperator1 + strDate;
  }
  return currentdate;
}

function alertMsg(msg) {
  layer.msg(msg, {
    area: ["300px", "50px"],
    offset: "60%",
  })
}
