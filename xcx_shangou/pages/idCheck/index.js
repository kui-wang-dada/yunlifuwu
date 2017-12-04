// 身份核检码
var wxbarcode = require('../../utils/index.js');//条码生成
var statusShow = require('../../utils/status'); //状态
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //时间
Page({
  data: {
    isShow: false,
    code: '',
    allData: {},
    flag: true
  },

  onLoad(e) {
    if (e.show === "2" || e.show === "8") {
      this.setData({
        isShow: true
      })
      this.requestForCode(e.show);
    } else {
      this.setData({
        isShow: false
      })
    }
  },

  //  获取核检码
  requestForCode(show) {
    statusShow.openLoading('加载中');
    var that = this,
      date = util.formatTime(new Date), //日期
      store = getApp().globalData.storeId;   //门店
    wx.request({
      url: url + '/order/list', //订单列表
      data: {
        "data": {
          "status": show
        },
        "session": {
          "customer": {
            "openId": getApp().globalData.openid
          },
          "datetime": date,
          "store": store
        }
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('idCheck_success: ', res.data.data);
        if (res.data.code === '0000') {
          var data = res.data.data[0];
          that.setData({
            code: data.validateCode,
            allData: data
          })
          wxbarcode.barcode('barcode', data.validateCode, 680, 200);
        } else {
          //显示查询失败
          console.log('idCheck_else:', res);
        }
      },
      fail: function (res) {
        //显示查询失败
        wx.hideLoading();//隐藏加载框
        console.log('idCheck_fail:', res);
      }
    })
  },

  //  自己核检
  hejian() {
    var dataAll = this.data.allData;
    //判断是否有值
    if (JSON.stringify(dataAll) == '{}') {
      return;
    }
    console.log('data: ', dataAll)
    
    var that = this;
    wx.scanCode({
      onlyFromCamera: true,
      success: (res) => {
        console.log('res.result: ', res.result);
        //  发送请求
        this.confirm(res.result);
      },
      fail: (res) => {
      }
    })
  },

  //  确认核检
  confirm(result) {
    var flag = this.data.flag;
    if (!flag) {
      console.log('禁止点击')
      return;
    }
    //  显示加载框 
    this.setData({
      flag: false
    })
    statusShow.openLoading('核检中');
    var that = this,
      validateCode = this.data.allData.validateCode,//核检码
      orderId = this.data.allData.orderId,//订单id
      date = util.formatTime(new Date),  //时间
      storeId = getApp().globalData.storeId;   //门店
    wx.request({
      url: url + '/order/validate',
      data: {
        "data": {
          "validateCode": validateCode,
          "orderId": orderId,
          "qrCode": result
        },
        "session": {
          "customer": {
            "openId": getApp().globalData.openid
          },
          "store": storeId,
          "datetime": date
        }
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        //隐藏加载框
        wx.hideLoading();
        console.log('validate_success: ', res);
        if (res.data.code === '0000') {
          var data = JSON.stringify(res.data.data);
          var route = 'common';
          wx.redirectTo({
            url: '../hejian/index?status=3&data=' + data + '&route=' + route,
          })
        } else if (res.data.code === '4000' || res.data.code === '2000') {
          wx.showModal({
            title: '提示',
            content: res.data.message,
              confirmColor:"#ffa825",
            showCancel: false,
            success: function (res) {
              that.setData({
                flag: true     // 可以点击
              })
            }
          })
        } else {
          //显示查询失败
          statusShow.openFail('网络异常');
          console.log('validate_else_res:', res);
          that.setData({
            flag: true
          })
        }
      },
      fail: function (res) {
        //核检失败
        statusShow.openFail('网络异常');
        console.log('validate_fail:', res);
        that.setData({
          flag: true
        })
      }
    })
  }
})