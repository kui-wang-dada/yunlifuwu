var app = getApp();
var statusShow = require('../../utils/status');
Page({
  data: {
  },

  onShow() {
    this.showGetUserInfo();
  },

  showGetUserInfo() {
    var that = this;
    if (!wx.getSetting) {
      // 如果希望用户在最新版本的客户端上体验您的小程序，可以这样子提示
      wx.showModal({
        title: '提示',
        showCancel: false,
        content: '当前微信版本过低，请升级到最新微信版本后重试。',
          confirmColor:"#ffa825",
      })
      return;
    }
    wx.getSetting({
      success(res) {
        //  判断是否授权
        if (!res.authSetting['scope.userInfo']) {
          //  开启授权
          wx.authorize({
            scope: 'scope.userInfo',
            success() {
              // 已授权, 判断用户身份跳转选择门店界面
              
              that.getUserInfo()
            },
            fail() {
              //  未授权,提示
              wx.showModal({
                title: '提示',
                content: '闪购必须微信授权才可登录!',
                  confirmColor:"#ffa825",
                showCancel: false,
                success: function (res) {
                  if (res.confirm) {
                    //  跳转开启授权设置界面
                    wx.redirectTo({
                      url: '../login/index',
                    })
                  }
                }
              })
            }
          })
        } else {
          // 已授权, 判断用户身份跳转选择门店界面
        
          that.getUserInfo()
        }
      }
    })
  },

  //  获取用户信息
  getUserInfo() {
    statusShow.openLoading('登录中');
    var that = this;
    wx.login({
      success: function (res) {
        that.requestForUSerInfo(res.code);
      },
      fail: function (res) {
        console.log(res);
      }
    })
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
          app.globalData.openid = openid;
          var cardno = res.data.data.card_id;
          app.globalData.cardno = cardno;//会员卡号
          // 保存token
          try {
            wx.setStorageSync('token', res.data.data.token)
          } catch (e) {
          }
          that.chooseShop();
        } else if (res.data.code !== 0) {
          //  需要注册
          var openid = res.data.openid;
          app.globalData.openid = openid;
          wx.redirectTo({
            url: '../register/index',
          })
        } else {
          //显示查询失败
          console.log('useInfo_else: ', res);
          statusShow.openFail(res.data.msg)
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
