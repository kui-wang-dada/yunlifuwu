// 确认订单
var url = getApp().globalData.url;//接口地址
var util = require('../../utils/util');//时间
var statusShow = require('../../utils/status');//状态
Page({

  data: {
    listInfo:[],  //选中的商品信息
    newData: [],  //未选中的商品信息
    goodsNum: 0,  //总数
    goodsPrice: 0,  //总价
    cardInfo: '',   //选择的卡券
    identity: '',    //商品信息identity
    flag: true,  //跳转次数控制
    dateTime: '',   //时间
    storeName: ''  //门店名称
  },

  onLoad(options) {
    this.getStorageInfo(options);
    //  日期
    var date = util.formatTime(new Date); //日期
    this.setData({
      dateTime: date,
      storeName: getApp().globalData.shopName
    })
    //  更改上个页面属性,改变购物袋点击状态
   //  var pages = getCurrentPages();
   //  var prevPage = pages[pages.length - 2];  //上一个页面
   //  prevPage.setData({
   //    currentBag: 99,//  购物袋
   //    flag: true     // 可以点击
   //  })
 
  },

  //  获取本地数据
  getStorageInfo(options) {
    var that = this,
    listInfo = [],
    newData = [],
    totalPrice = 0;
    console.log(options.data)
    let dataInfo = JSON.parse(options.data);
    console.log('options.data: ', dataInfo);
    //  设置其他值
    for (var i = 0; i < dataInfo.length; i++) {
      totalPrice += dataInfo[i].receivable;
      totalPrice = parseFloat(totalPrice.toFixed(2));
    }
    //  获取商品信息identity
    this.setData({
      identity: options.identity,
      goodsNum: dataInfo.length,
      listInfo: dataInfo,
      goodsPrice: totalPrice
    })
    //  获取本地购物车数据
    wx.getStorage({
      key: 'goodsArr',
      success: function (res) {
        var data = res.data;
        for(var i=0; i<data.length; i++) {
          if (!data[i].goods.checked) {
            newData.push(data[i]);
          }
        }
        that.setData({
          newData: newData//位被选中的商品
        })
      }
    })
  },

  //  提交订单信息
  createOrder() {
    var flag = this.data.flag;
    if(!flag) {
      console.log('禁止点击');
      return;
    }
    statusShow.openLoading('提交中');
    this.setData({
      flag: false
    })
    var that = this,
      date = this.data.dateTime, //日期
      identity = this.data.identity,  //订单identity
      cardno = getApp().globalData.cardno, //cardid
      store = getApp().globalData.storeId;   //门店
    wx.request({
      url: url + '/order/create',
      data: {
        "data": {
          "identity": identity
        },
        "session": {
          "customer": {
            "id": cardno,
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
        console.log('account_success: ', res.data.data);
        if (res.data.code === '0000') {
          //  获取信息
          var allData = res.data.data;
          that.clickToPayDetail(allData);
          
        } else {
          statusShow.openFail('网络较差');
          console.log('account_success_else: ', res);
          that.setData({
            flag: true
          })
        }
      },
      fail: function (res) {
        //显示查询失败
        statusShow.openFail('网络较差');
        console.log('account_fail: ', res);
        that.setData({
          flag: true
        })
      }
    })
  },

  //  提交订单
  clickToPayDetail(allData) {
    var that = this;
    //  存入本地,更新本地购物车信息
    wx.setStorage({
      key: 'goodsArr',
      data: this.data.newData
    });
    //  获取订单详情
    var data = JSON.stringify(allData);
    //  来源
    var route = 'account';
    wx.hideLoading();//隐藏加载框
    wx.redirectTo({
      url: '../paydetail/index?status=1&data=' + data + '&route=' + route,
    })
    var time = setTimeout(function () {
      that.setData({
        flag: true     // 可以点击
      })
    }
      , 1000)
  },

  //  选择卡券
//   chooseCard() {
//     wx.navigateTo({
//       url: '../chooseCard/index',
//     })
//   }
})