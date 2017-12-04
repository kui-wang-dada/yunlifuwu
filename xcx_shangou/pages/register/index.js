// 手机号注册界面
// 时长
var secondTime = 60;
var statusShow = require('../../utils/status');
var app = getApp();
// 倒计时
function countdown(that) {
  var second = that.data.second
  if (second == 0) {
    that.setData({
      codeBtn: 2,
      second: secondTime
    });
    return;
  }
  var time = setTimeout(function () {
    that.setData({
      second: second - 1
    });
    countdown(that);
  }
    , 1000)
}

Page({
  data: {
    second: secondTime,
    phoneValue: '', //手机号
    codeValue: '',  //验证码
    codeBtn: 1,   //验证码按钮状态 1灰 2可点击 3读秒
    confirmBtn: false,  //登录按钮状态
    isAgree: true,   //勾选框状态
  },

  onLoad: function (options) {

  },

  //  手机号输入框触发事件
  bindPhone(e) {
    if (this.data.codeBtn == 3){
      return;
    }
    //  手机号
    var phoneNum = e.detail.value;
    if (phoneNum.length === 11){
        this.setData({
          codeBtn: 2,
          phoneValue: phoneNum
        })
      }else{
        this.setData({
          codeBtn: 1,
          phoneValue: phoneNum
        })
      }
  },

  //  验证码输入框触发事件
  bindCode(e) {
    //  验证码
    var code = e.detail.value;
    this.setData({
      codeValue: code
    })
    if (code.length === 6 && this.data.isAgree) {
      this.setData({
        confirmBtn: true
      })
    } else {
      this.setData({
        confirmBtn: false
      })
    }
  },

  //  勾选框
  bindAgreeChange(e) {
    var status = !!e.detail.value.length;
    this.setData({
      isAgree: status
    });
    if ((this.data.codeValue.length === 6) && status) {
      this.setData({
        confirmBtn: true
      });
    } else {
      this.setData({
        confirmBtn: false
      });
    }
  },

  //  获取验证码按钮
  getCode() {
    this.setData({
      codeBtn: 3
    })
    countdown(this);
    //  调用后台 获取验证码
    var appid = getApp().globalData.appid,
      openid = getApp().globalData.openid,
      tel = this.data.phoneValue,
      url = getApp().API.Sendsms;
    wx.request({
      url: url,
      data: {
        appid: appid,
        openid: openid,
        tel: tel,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('register_success: ', res);
        wx.hideLoading();//隐藏加载框
        if (res.data.code === 0) {
           wx.showToast({
              title: '短信已发到您的手机，请稍后重试!',
              // icon: 'loading',
              duration: 1000
           })
        } else {
          statusShow.openFail('网络较差')
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('register_fail: ', res);
        statusShow.openFail('网络较差')
      }
    })
  },

  //  登录按钮
  loginBtn() {
    var that = this;
    var phone = this.data.phoneValue,
      code = this.data.codeValue;
    if(phone.length < 11) {
      wx.showToast({
        title: '手机号不规范',
        image: '../../images/fail.png'
      });
      return;
    }
    if(code.length < 6) {
      wx.showToast({
        title: '验证码不规范',
        image: '../../images/fail.png'
      });
      return;
    }

    // 获取微信登录
    wx.login({
      success: function (res) {
        that.register(res.code);
      }
    });
  },

  // 调用注册
  register(js_code){
    var that = this;
    //  调用后台 验证登录
    var appid = getApp().globalData.appid,
      openid = getApp().globalData.openid,
      tel = this.data.phoneValue,
      codeValue = this.data.codeValue,
      url = getApp().API.Register;

    that.setData({
      confirmBtn: false
    });

    wx.request({
      url: url,
      data: {
        appid: appid,
        openid: openid,
        tel: tel,
        verify: codeValue,
        js_code: js_code,
        userInfo: app.globalData.userInfo
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('register_success: ', res);
        wx.hideLoading();//隐藏加载框
        console.log(typeof res.data.code)
        if (res.data.code === 0) {
          try {
            wx.setStorageSync('token', res.data.data.token)
          } catch (e) {
          }
          that.chooseShop();
        } else {
          statusShow.openFail(res.data.msg)
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('register_fail: ', res);
        statusShow.openFail(res.data.msg)
      },
      complete: function () {
        that.setData({
          confirmBtn: true
        });
      }
    })
  },

  //  跳转到选择门店界面
  chooseShop() {
    wx.redirectTo({
      url: '../shop/index',
    })
    wx.hideLoading();//隐藏加载框
  },
  onReady: function () {
    app.getUserInfo()
  }
})