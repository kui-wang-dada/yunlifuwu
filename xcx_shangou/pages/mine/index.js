// pages/mine/index.js
//我的界面
var app = getApp();
var url = getApp().globalData.url;
var util = require('../../utils/util');
var statusShow = require('../../utils/status');
var timeOut = 1200;  //超时时间
Page({
  data: {
    userInfo: '',
    allData: {},  //所有信息
    showID: '100', //显示核检码
    cardno: '',   //会员卡号
    centerInfos: [
      {
        index: 0,
        text: '全部订单',
        url:'../../images/mine_one.png',
        status: 0,
      },
      {
        index: 1,
        text: '待付款',
        url: '../../images/mine_two.png',
        status: 0,
      },
      {
        index: 2,
        text: '待核检',
        url: '../../images/mine_three.png',
        status: 0,
      }
    ],
    listInfos: [
      // {
      //   text: '待核检',
      //   url: '../idCheck/index',
      //   status: 0,
      // },
      {
        text: '切换门店',
        url: '../shop/index',
        status: 0,
      },
      {
        text: '帮助',
        url: '../help/index',
        status: 0,
      }
    ]
  },

  onLoad(options) {
    var that = this;
    //调用应用实例的方法获取全局数据
    app.getUserInfo(function (userInfo) {
      //更新数据
      that.setData({
        userInfo: userInfo
      })
    })  

  },

  onShow() {
    //  获取数据
    this.requestForLatest();
    statusShow.openLoading('加载中');
    //  会员卡号
    var cardno = app.globalData.cardno;
    this.setData({
      cardno: cardno
    })
  },

  //  获取数据
  requestForLatest() {
    var that = this;
    wx.request({
      url: getApp().API.OrderList,
      data: {
           "token":wx.getStorageSync("token"),
         //   "status": "1",
           "p":1,
        },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('mine_request: ', res);
        if (res.data.code == '0') {
          console.log('mine_success: ', res.data.data);
          wx.hideLoading();//隐藏加载框
          var data = res.data.data;
          that.setData({
            allData: data
          })
          console.log("data:", res.data.data)
          //  获取支付信息
         //  try {
         //    var value = wx.getStorageSync('saveData')
         //    if (value) {
         //      //  本地有,判断是否是待付款
         //      console.log('saveData_value: ', value)
         //      that.checkData(value, data);
         //    } else {
         //      //  本地没有
         //      that.judgeTime(data);
         //    }
         //  } catch (e) {
         //  }
        } else if (res.data.code == '2000') {
          wx.hideLoading();//隐藏加载框
          that.showNum("100");
          that.setData({
            allData: {},
            showID: '100'
          })
        } else {
          //显示失败
          // wx.hideLoading();//隐藏加载框
          console.log('mine_else: ', res);
          statusShow.openFail('加载失败');
        }
      },
      fail: function (res) {
        //显示失败
        wx.hideLoading();//隐藏加载框
        console.log('mine_fail: ', res);
      },
    })
  },

  //  跳转detail
  clickToDetail(e) {
     var data = e.currentTarget.dataset.item;
     var index = data.index;
     var listData = JSON.stringify(this.data.allData);
     wx.navigateTo({
        url: '../detail/index?index=' + index + '&listData=' + listData,
     })
  },

  //  判断订单状态
//   checkData(value,newData) {
//     var data = newData;
//     if (data.status === "0" && value.receivable === data.receivable && value.orderId === data.orderId && value.quantity === data.quantity){
//       //待付款, 发起确认订单请求
//       this.requestForConfirm(value.receivable, value.orderId, value.trade_no)
//     } else {
//       //待核检
//       this.showNum("2");
//       //  移除支付信息
//       try {
//         wx.removeStorageSync('saveData')
//       } catch (e) {
//       }
//     }
//   },

  //  确认订单信息
//   requestForConfirm(receivable, orderId, trade_no) {
//     var that = this;
//     var receivableValue = parseFloat(receivable);
//     //  交易金额
//     wx.request({
//        url: getApp().API.OrderConfirm,
//       data: {
//           "orderId": orderId,
//           "token": wx.getStorageSync("token"),
//       },
//       header: {
//         'content-type': 'application/json'
//       },
//       method: 'POST',
//       success: function (res) {
//         console.log('mine_confirm_success: ', res);
//         if (res.data.code == '0') {
//           wx.hideLoading();//隐藏加载框
//           that.showNum("2");
//           var data = res.data.data.order;
//           that.setData({
//             allData: data
//           })
//           //  移除支付信息
//           try {
//             wx.removeStorageSync('saveData')
//           } catch (e) {
//           }
//           //  清除缓存
//           try {
//             wx.removeStorageSync('checkNum')
//           } catch (e) {
//           }
//           //  清除缓存
//           try {
//             wx.removeStorageSync('OLPayRes')
//           } catch (e) {
//           }
//         } else {
//           //确认失败
//           console.log('mine_confirm_else: ', res);
//         }
//       },
//       fail: function (res) {
//         //确认失败
//         console.log('mine_confirm_fail: ', res);
//       }
//     })
//   },

  //  显示提示
//   showNum(statusTitle) {
//     var newData = this.data.centerInfos;
//     var newListData = this.data.listInfos;
//     //  全部不显示
//     for (var i = 0; i < newData.length;i++) {
//       newData[i].status = 0;
//     };
//     newListData[0].status = 0;
//     //  设置显示项
//     if (statusTitle === "2" || statusTitle === "8"){
//       newData[2].status = 1;
//       newListData[0].status = 1;
//       this.setData({
//         showID: statusTitle
//       })
//     } else if (statusTitle === "0"){
//       newData[1].status = 1;
//       this.setData({
//         showID: '100'
//       })
//     }
//     this.setData({
//       centerInfos: newData,
//       listInfos: newListData
//     })
//   },

  //  判断订单是否超时
//   judgeTime(data) {
//     console.log('data: ', data);
//     var that = this;
//       date = data.brief.tradeDate.substring(0, 19);
//     date = date.replace(/-/g, '/'); //下单时间
//     var payTime = new Date(date).getTime();//下单时间戳
//     var currentTime = new Date().getTime();//当前时间
//     var timeDef = currentTime - payTime; //时间差
//     timeDef = parseInt(timeDef / 1000);
    
//     if (timeDef < timeOut && data.brief.status === "0"){
//       that.requestForOLPaySearch(data.brief);
//     } else if (timeDef > timeOut && data.brief.status === "0") {
//        取消订单
//       that.requestForCancel(data);
//       通知后台
//       wx.showModal({
//         title: '提示',
//         content: '支付已超时,请重新下单',
//         showCancel: false
//       })
//     } else {
//       that.showNum(data.brief.status);
//     }
//   },

  //  发送请求,取消订单
//   requestForCancel(data) {
//     var that = this,
//       date = util.formatTime(new Date), //日期
//       orderId = data.brief.orderId,  //orderId
//       store = getApp().globalData.storeId;   //门店
//     wx.request({
//       url: url + '/order/cancel',
//       data: {
//         "data": {
//           "orderId": orderId
//         },
//         "session": {
//           "customer": {
//             "openId": getApp().globalData.openid
//           },
//           "datetime": date,
//           "store": store
//         }
//       },
//       header: {
//         'content-type': 'application/json'
//       },
//       method: 'POST',
//       success: function (res) {
//         console.log('mine_cancel_success: ', res);
//         if (res.data.code == '0000') {
//           that.showNum("100");
//           //  清除缓存
//           try {
//             wx.removeStorageSync('OLPayRes')
//           } catch (e) {
//           }
//         }
//       },
//       fail: function (res) {
//         //取消订单失败
//         console.log('mine_cancel_fail: ', res);
//       }
//     })
//   },


  vipInfo() {
    wx.navigateTo({
      url: '../vip/index'
    })
  },

  //  开始查询 findorder
  requestForOLPaySearch(data) {
    console.log('OLPayRes: ', data)
    statusShow.openLoading('提交中');
    var that = this;
    //  金额,trade_no
    var trade_no = '';
    try {
      var value = wx.getStorageSync('OLPayRes')
      if (value) {
        trade_no = value.order_id;
        console.log('value: ', value)
      }
    } catch (e) {
    };
    
    console.log('trade_no: ', trade_no)
    wx.request({
      url: getApp().globalData.loginUrl + '/findorder',
      data: {
        "openid": getApp().globalData.openid,
        "order_id": trade_no
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('refund_success: ', res.data)
        if (res.data.status === 0) {
          console.log('有交易')
          //  有订单 确认订单
          that.requestForConfirm(data.receivable, data.orderId, trade_no)
        } else {
          console.log('没有交易:', data.status)
          that.showNum(data.status)
        }
      },
      fail: function (res) {
        console.log('refund_fail: ', res);
      }
    })
  }

})