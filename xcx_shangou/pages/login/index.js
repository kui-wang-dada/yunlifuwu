//  重新授权界面
var statusShow = require('../../utils/status');// 状态
var app = getApp();
Page({
  data:{
    helpCenter: '../help/index'
  },

  onLoad() {
  },

  //  打开授权设置界面
  setting() {
    var that = this;
    wx.openSetting({
      success: (res) => {
        if (res.authSetting['scope.userInfo']) {
          //  获取用户信息
          that.login();
        }
      }
    })
  },

  //  登录
  login() {
    statusShow.openLoading('登录中');
    var that = this;
    wx.login({
      success: function (res) {
        that.requestForUSerInfo(res.code);
      }
    });
  },

  //  判断用户身份
  requestForUSerInfo(js_code) {
    var that = this;
    var appid = getApp().globalData.appid;
    var url = getApp().API.Login;
    wx.request({
      url: url,
      data: {
        appid: appid,
        js_code: js_code
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('userInfo_success: ', res);
        wx.hideLoading();//隐藏加载框
        if (res.data.code === 0) {
          //  登录成功
          var openid = res.data.openid;
          app.globalData.openid = openid;//openid
          var cardno = res.data.data.cardno;
          app.globalData.cardno = cardno;//会员卡号
          // 保存token
          try {
            wx.setStorageSync('token', res.data.data.token)
          } catch (e) {
          }

          that.chooseShop();
        } else if (res.data.code !==0) {
          var openid = res.data.result_openid;
          app.globalData.openid = openid;
          //  需要注册
          wx.redirectTo({
            url: '../register/index',
          })
        } else {
          //显示查询失败
          console.log('useInfo_else: ', res);
          statusShow.openFail('网络较差')
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('useInfo_fail: ', res);
        statusShow.openFail('网络较差')
      }
    })
  },

  //  跳转到选择门店界面
  chooseShop() {
    wx.redirectTo({
      url: '../shop/index',
    })
  },
})