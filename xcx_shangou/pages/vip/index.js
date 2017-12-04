// 会员信息
var url = getApp().globalData.url;
var util = require('../../utils/util');
var statusShow = require('../../utils/status');
Page({
  data: {
    cardno: '',  //会员卡号
  },
  onLoad: function (options) {
    //  会员卡号
    // var cardno = app.globalData.cardno;
    // this.setData({
    //   cardno: cardno
    // })
  },

  //  手机号输入框触发事件
  bindVipCard(e) {
    //  会员卡号
    var cardno = e.detail.value;
    this.setData({
      cardno: cardno
    })
  },

  //  绑定事件
  button() {
    var cardno = this.data.cardno,
      that = this;
    if (cardno.length == 0) {
      wx.showModal({
        title: '提示',
        content: '请输入会员卡号',
        showCancel: false
      })
      return;
    }
    wx.showModal({
      title: '提示',
      content: '请确认您的会员卡号:' + cardno,
        confirmColor:"#ffa825",
      success: function (res) {
        if (res.confirm) {
          that.requestForBind(cardno);
        }
      }
    })
  },

  //  发送请求,绑定会员卡号
  requestForBind(cardno) {
    statusShow.openLoading('提交中');
    //  调用后台 获取验证码
    var appid = getApp().globalData.appid,
      openid = getApp().globalData.openid,
      url = "";
    wx.request({
      url: url,
      data: {
        appid: appid,
        openid: openid,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('bind_success: ', res);
        wx.hideLoading();//隐藏加载框
        if (res.data.result_code === 0) {
          wx.navigateBack();
        } else {
          console.log('bind_else: ', res);
          statusShow.openFail('网络较差')
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('bind_fail: ', res);
        statusShow.openFail('网络较差')
      }
    })
  }
})