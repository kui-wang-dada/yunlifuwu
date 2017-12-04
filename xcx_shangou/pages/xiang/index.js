// 已核销的订单详情
var statusShow = require('../../utils/status'); //状态
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //时间
Page({
  data: {
    listInfo: [],//商品列表
    goodsNum: 0, //总数量
    amount: 0,  //折前总金额
    createDate: '', //时间
    orderId: '',   //订单编号
    discount: 0, //总折扣
    receivable: 0,//应付金额
    validateCode: '',//核检码
    storeName: ''//门店名称
  },

  onLoad(options) {
    var data = JSON.parse(options.data);
    console.log('xiang_data: ', data);
    this.requestForDetail(data);
  },

  //  发送请求获取订单详情
  requestForDetail(data) {
    console.log('发送请求获取数据')
    statusShow.openLoading('加载中');
    var that = this,
      date = util.formatTime(new Date), //日期
      store = getApp().globalData.storeId;   //门店
    wx.request({
      url: url + '/order/detail', //订单详情
      data: {
        "data": data,
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
        console.log('listAll_success: ', res);
        if (res.data.code == '0000') {
          wx.hideLoading();//隐藏加载框
          that.setData({
            listInfo: res.data.data.order.commods,//商品列表
            goodsNum: data.quantity, //总数量
            amount: data.amount,  //折前总金额
            createDate: res.data.data.order.brief.tradeDate, //时间
            orderId: data.orderId,   //订单编号
            discount: data.discount, //总折扣
            receivable: data.receivable,//应付金额
            validateCode: data.validateCode,//核检码
            storeName: getApp().globalData.shopName//门店名称
          })
        } else {
          //显示查询失败
          statusShow.openFail('网络较差');
        }
      },
      fail: function (res) {
        //显示查询失败
        statusShow.openFail('网络较差');
      }
    })
  },

});