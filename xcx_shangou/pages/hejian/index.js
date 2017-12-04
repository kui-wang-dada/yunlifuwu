// 自己核检
var statusShow = require('../../utils/status'); //状态
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //时间

// 当前时间  
function countdown(that) {
  var time = setTimeout(function () {
    that.setData({
      // date: util.formatTime(new Date).substr(0, 10),
      time: util.formatTime(new Date).substr(11, 8)
    });
    countdown(that);
  }
    , 1000)
}

Page({
  data: {
    slider: [
      { picUrl: '../../images/lun_one.jpg' },
      { picUrl: '../../images/lun_two.jpg' },
      { picUrl: '../../images/lun_three.jpg' },
      
  ],
    swiperCurrent: 0,
   //  date: '', //当前时间年月日 
    time: '', //当前时间时分秒 
    listInfo: [],   //商品信息
    storeName: '',  //门店名称
    createDate: '',   //交易时间
    receivable: 0,//应付金额
    quantity: '',   //总数量
    sctCode: '',    //sctCode
    checkNum: 0,    //计数
  },

  onLoad(options) {
    this.setData({
      time: util.formatTime(new Date).substr(11, 8)
    })
    countdown(this);
    let data = JSON.parse(options.data);
    var route = options.route;
    //  更改上个页面属性
    var pages = getCurrentPages();
    var prevPage = pages[pages.length - 2];  //上一个页面
    prevPage.setData({
      flag: true     // 可以点击
    })
    //  赋值
    if (route === 'common') {
      this.setData({
        storeName: getApp().globalData.shopName,//门店名称
        createDate: data.brief.tradeDate, //时间
        quantity: data.brief.quantity, //总数量
        receivable: data.brief.receivable,//应付金额
        sctCode: data.brief.sctCode,   //sctCode
        listInfo: data.commods
      })
    } else {
      //  发送请求获取数据
      this.requestForDetail(data);
    }
    //  计数
    try {
      var value = wx.getStorageSync('checkNum')
      if (value) {
        var num = value;
        num++;
        this.setData({
          checkNum: num
        })
      } else {
        this.setData({
          checkNum: 1
        })
      }
    } catch (e) {
    };
    console.log('checkNum: ', this.data.checkNum)
    //  存入本地
    wx.setStorage({
      key: 'checkNum',
      data: this.data.checkNum
    })
  },

  //  发送请求获取数据
  requestForDetail(data) {
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
        if (res.data.code === '0000') {
          wx.hideLoading();//隐藏加载框
          var thisBrief = res.data.data.order.brief;
          that.setData({
            listInfo: res.data.data.order.commods,//商品列表
            quantity: thisBrief.quantity, //总数量
            sctCode: thisBrief.sctCode,//sctCode
            createDate: thisBrief.tradeDate, //时间
            receivable: thisBrief.receivable,//应付金额
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

  swiperChange: function (e) {
    this.setData({
      swiperCurrent: e.detail.current
    })
  }
})
