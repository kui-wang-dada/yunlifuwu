// 我的订单,订单详情 no 0 2
var statusShow = require('../../utils/status'); //状态
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //时间
var totalTime = 180;  //超时时间
var timeOut = 900;
var signUtil = require('../../utils/signUtil');
Page({
  data: {
    currentTab: 0,  // tab切换
    windowH: 0, //列表高度
    status: 0,
    //  数据
    allList: [], //所有订单
    payList: [], //待付款
    hejianList: [],  //待核检
    storeName: '',
    flag: true
  },

  onLoad(options) {
    //  默认显示
    var index = options.index,
      listData = JSON.parse(options.listData);
    wx.setStorageSync("listData", listData);
    this.setData({
      currentTab: index,
      storeName: getApp().globalData.shopName//门店名称
    })
    //  设置待付款和待核检数据
    this.sethejianData(listData);
    this.setpayData(listData);
    //  设置列表的高度
    var res = wx.getSystemInfoSync(),
      windowH = res.windowHeight - 44;
    this.setData({
      windowH: windowH
    })
    //  设置status状态
    if(options.index === '0'){
      //  获取商品所有数据
      this.requestForDataAll()
    }
  },
 
  onShow(options) {
   //  console.log('detail_show: ');
    this.requestForDataAll()
  },

  //  设置待核检数据
  sethejianData(listData) {
    if (JSON.stringify(listData) == '{}'){
      this.setData({
        status: 0
      })
    } else {
      // var pay = [];
      var hejian = [];
       for(var i in listData.list){
          var status = listData.list[i].order_status;
         //  if (status == '0') {
         //     //待付款
         //     pay.push(listData.list[i]);
         //     this.setData({
         //        status: 1,
         //        payList: pay,
         //     })
         //  } 
          if (status == '1') {
             //待核检
             hejian.push(listData.list[i]);
             this.setData({
                status: 2,
                trade_no: listData.pay_id,
                hejianList: hejian,
             })
          } 
       }
       console.log("hejianList:", this.data.hejianList)
      //  console.log("payList:", this.data.payList)
    }
  },
//设置待付款
  setpayData(listData) {
     if (JSON.stringify(listData) == '{}') {
        this.setData({
           status: 0
        })
     } else {
        var pay = [];
      //   var hejian = [];
        for (var i in listData.list) {
           var status = listData.list[i].order_status;
           if (status == '0') {
              //待付款
              pay.push(listData.list[i]);
              this.setData({
                 status: 1,
                 payList: pay,
              })
           } 
         //   else if (status == '1') {
         //      //待核检
         //      hejian.push(listData.list[i]);
         //      this.setData({
         //         status: 2,
         //         trade_no: listData.pay_id,
         //         hejianList: hejian,
         //      })
         //   }
        }
      //   console.log("hejianList:", this.data.hejianList)
        console.log("payList:", this.data.payList)
     }
  },
  //  获取商品所有数据
  requestForDataAll() {
    statusShow.openLoading('加载中');
    var that = this,
      date = util.formatTime(new Date), //日期
      store = getApp().globalData.storeId;   //门店
    wx.request({
       url: getApp().API.OrderList, //订单列表
      data: {
         "token": wx.getStorageSync("token"),
         //   "status": "1",
         "p": 1,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('listAll_success: ', res);
        if (res.data.code == '0') {
          var data = res.data.data.list;
          that.setData({
            allList: data
          })
        } else if (res.data.code == '8001') {
          that.setData({
            allList: []
          })
        } else {
          //显示查询失败
          console.log('listAll_else:', res);
        }
      },
      fail: function (res) {
        //显示查询失败
        wx.hideLoading();//隐藏加载框
        console.log('listAll_fail:', res);
      }
    })
  },

  swichNav: function (e) {
    var that = this;
    if (this.data.currentTab === e.target.dataset.current) {
      return false;
    } else {
      that.setData({
        currentTab: e.target.dataset.current,
      })
    }
  },
  swiperChange: function (e) {
    this.setData({
      currentTab: e.detail.current,
    })
    //  切换状态,发送请求
    var status = '';
    switch (e.detail.current) {
      case 0:
        break;
      case 1:
        status = '0';//待付款
        break;
      case 2:
        status = '2';//待核检
        break;
      default:
    }
    if(status == 0) {
       var listData = wx.getStorageSync("listData");
      //  console.log("list:",listData)
      this.setpayData(listData);
    } else if(status == 2){
       var listData = wx.getStorageSync("listData");
       //  console.log("list:",listData)
       this.sethejianData(listData);
    }
    else {
      //  全部
      this.requestForDataAll();
    }
  },

  //  取消订单
  cancelOrder(e) {
    var orderData = {},
      index = e.currentTarget.dataset.index;
    if( index == 1) {
      orderData = e.currentTarget.dataset.item
    } else {
      orderData = e.currentTarget.dataset.item.brief
    }
    console.log('cancel_orderData: ', orderData)
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确认取消订单吗?',
        confirmColor:"#ffa825",
      success: function (res) {
        if (res.confirm) {
          // that.updateStatus();
          that.requestForCancel(orderData); 
        }
      }
    })
  },

  //  取消订单,发送网络请求
  requestForCancel(orderData) {
    statusShow.openLoading('取消中');
    var that = this,
      date = util.formatTime(new Date), //日期
      store = getApp().globalData.storeId;   //门店
    var orderId = orderData.order_no;
    wx.request({
      url: getApp().API.OrderCancel, //订单列表
      data: {
        "store": store,
        "order_no": orderId,
        "status": "cancel",
        "token": wx.getStorageSync('token')
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('cancel_success: ', res);
        if (res.data.code === 0) {
          //  清除缓存
          try {
            wx.removeStorageSync('OLPayRes')
          } catch (e) {
          }
          that.updateStatus();
        } else {
          statusShow.openFail('订单取消失败');
        }
      },
      fail: function (res) {
        //显示取消失败
        statusShow.openFail('订单取消失败');
        console.log('cancel_fail:', res);
      }
    })
  },

  //  立即付款
  confirmToPay(e) {
    var confirmData = {},
      index = e.currentTarget.dataset.index;
    if (index == 1) {
      confirmData = e.currentTarget.dataset.item
    } else {
      confirmData = e.currentTarget.dataset.item.brief
    }
    console.log('点击付款');
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确定支付?',
      confirmColor:"#ffa825",
      success: function (res) {
        if (res.confirm) {
          //判断订单是否超时
          that.judgeTime(confirmData);
        }
      }
    })
  },

  //  确认支付
  confirm(confirmData, trade_no) {
    confirmData.trade_no = trade_no;
    var orderno = confirmData.order_no;
    var data = JSON.stringify(confirmData);
    console.log("data:",data)
    wx.navigateTo({
      url: '../inspection/index?data=' + data + '&orderno' + orderno,
    })
  },

  //  判断订单是否超时
  judgeTime(confirmData) {
    var that = this;
   //    date = confirmData.tradeDate.substring(0, 19);
   //  date = date.replace(/-/g, '/'); //下单时间
   //  var payTime = new Date(date).getTime();//下单时间戳
   //  var currentTime = new Date().getTime();//当前时间
   //  var timeDef = currentTime - payTime; //时间差
   //  timeDef = parseInt(timeDef / 1000);

   //  if (timeDef > timeOut) {
   //    //  取消订单
   //    that.requestForCancel(confirmData);
   //    //通知后台
   //    wx.showModal({
   //      title: '提示',
   //      content: '支付已超时,请重新下单',
   //      showCancel: false
   //    })
   //  } else {
      //未超时 可支付
      // that.confirm(confirmData);
      try {
        that.requestForOLPay(confirmData);
        // var value = wx.getStorageSync('OLPayRes')
        // if (value) {
        //   console.log('有值: ', value)
        //   that.againPay(value, confirmData);
        // } else {
        //   that.requestForOLPay(confirmData);
        //   console.log('没有值')
        // }
      } catch (e) {
      };
   //  }
  },

  //  再次付款
  againPay(value, confirmData) {
    var that = this;
    var data = value.msg;
    var trade_no = value.order_id;
    wx.requestPayment({
      'timeStamp': data.timeStamp,
      'nonceStr': data.nonceStr,
      'package': data.package,
      'signType': data.signType,
      'paySign': data.paySign,
      'success': function (res) {
        console.log('res_success_api: ', res);
        if (res.errMsg === "requestPayment:ok") {
          //  清除缓存
          try {
            wx.removeStorageSync('OLPayRes')
          } catch (e) {
          }
          that.confirm(confirmData, trade_no);
        }
      },
      'fail': function (res) {
        console.log('res_fail_api: ', res)
      },
      complete: function (res) {
        console.log('res_complete_api: ', res)
      },
    })
  },

  //  发送支付请求
  requestForOLPay(confirmData) {
    statusShow.openLoading('提交中');
    var that = this;
    console.log(this.data)
    console.log(confirmData)
    wx.request({
       url: getApp().API.OrderPay,
      data: {
         "openid": getApp().globalData.openid,
         "token": wx.getStorageSync("token"),
         "order_no": confirmData.order_no,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('res_success: ', res.data)
        var data = res.data
        console.log('支付数据: ', data)
        if (res.data.code === 0) {
          var trade_no = data.order_id;
          console.log('trade_no: ', trade_no)
          //  存入本地
          wx.setStorage({
            key: 'OLPayRes',
            data: data
          })
          console.log('-----')
          console.log(data)
          wx.requestPayment({
            'timeStamp': data.data.timeStamp,
            'nonceStr': data.data.nonceStr,
            'package': data.data.package,
            'signType': data.data.signType,
            'paySign': data.data.paySign,
            'success': function (res) {
              console.log('res_success: ', res)
              if (res.errMsg === "requestPayment:ok") {
                //  清除缓存
                try {
                  wx.removeStorageSync('OLPayRes')
                } catch (e) {
                }
                that.confirm(confirmData, trade_no);
              }
            },
            'fail': function (res) {
              console.log('res_success: ', res)
            }
          })
        } else {
          console.log('pay_else: ', res);
          statusShow.openFail('支付失败');
        }
      },
      fail: function (res) {
        console.log('pay_fail: ', res);
        statusShow.openFail('支付失败');
      }
    })
  },

  //  申请退款
  applyRefund(e) {
    var orderData = {},
      index = e.currentTarget.dataset.index;
    console.log('refund_data: ', e.currentTarget.dataset.item)
    if (index == 1) {
      orderData = e.currentTarget.dataset.item
    } else {
      orderData = e.currentTarget.dataset.item.brief
    }
    console.log('refund_orderData: ', orderData)
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确认申请退款吗?',
        confirmColor:"#ffa825",
      success: function (res) {
        if (res.confirm) {
          //  确认,更新状态
          console.log('确认退款')
          that.requestForRefund(orderData);
        }
      }
    })
  },

  //  发送请求,申请退款
  requestForRefund(data) {
    statusShow.openLoading('');
    var that = this,
      validateCode = data.validateCode,
      date = util.formatTime(new Date), //日期
      orderId = data.orderId,  //订单identity
      store = getApp().globalData.storeId;   //门店
    wx.request({
      url: url + '/order/refundment',
      data: {
        "data": {
          "orderId": orderId,
          "validateCode": validateCode
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
        console.log('detail_refund: ', res);
        if (res.data.code == '0000') {
          // that.updateStatus();
          that.requestForOLPayRefund();
          console.log('detail_refund_success: ', res);
        } else if (res.data.code == '2000' || res.data.code == '4000') {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
              confirmColor:"#ffa825",
            success: function (res) {
            }
          })
        } else {
          console.log('detail_refund_else: ', res);
          statusShow.openFail('网络异常,请重试');
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('detail_refund_fail: ', res);
        statusShow.openFail('网络异常,请重试');
      }
    })
  },

  //  更新状态
  updateStatus() {
    var that = this;
    
    //  改变交易状态
    that.setData({
      status: 3
    })
    this.requestForDataAll();
  },
 //查看核检码
 checkHejian(e){
    var data = e.currentTarget.dataset.item;
    var orderId = data.order_no;
    data = JSON.stringify(data);
    wx.navigateTo({
       url: '../inspection/index?data=' + data,
    })
 },
  //跳转订单详情
  clickPayDetail(e) {
    var status = e.currentTarget.dataset.status;
    var  data = e.currentTarget.dataset.item;
    var  orderId = data.order_no;
   //  console.log("orderId:", orderId)
   //  console.log("status:", status)
    if (status == 2){
       var orderId = data.order_no;
    }else if(status == 3){
       var orderId = data[0].order_no;
    }else{
       var orderId = data[0].order_no;
    }
     var that = this;
     wx.request({
        url: getApp().API.OrderInfo, //订单列表
        data: {
           "token": wx.getStorageSync("token"),
           "order_no": orderId,
        },
        header: {
           'content-type': 'application/json'
        },
        method: 'POST',
        success: function (res) {
           wx.hideLoading();//隐藏加载框
           console.log('listAll_success: ', res);
           if (res.data.code == '0') {
              var route = "mine"
              var data = res.data.data;
              var status = data.order_status;
              data = JSON.stringify(data);
              wx.navigateTo({
                  url: '../paydetail/index?status=' + status + '&data=' + data + '&route=' + route,
               })
           } else {
              statusShow.openFail('网络较差');
           }
        },
        fail: function (res) {
           //显示查询失败
           wx.hideLoading();//隐藏加载框
           statusShow.openFail('网络较差');
           console.log('listAll_fail:', res);
        }
     })
   //  console.log('e: ', e);
   //  var status = e.currentTarget.dataset.status,
   //    data = e.currentTarget.dataset.item,
   //    index = e.currentTarget.dataset.index;
   //  var route = '';
   //  var currentStatus = data.status;
   //  var currentData = data;
   //  if (status == 1 || status == 3 || status == 8){
   //    route = 'mine';
   //  }
   //  console.log('data: ', data);
   //  console.log('status: ', data.order_status)
   //  data = JSON.stringify(data);
   //  if (currentStatus === '9') {
   //    //  判断时间
   //    var tradeDate = currentData.tradeDate.substring(0, 19);
   //    tradeDate = tradeDate.replace(/-/g, '/'); //下单时间
   //    var payTime = parseInt(new Date(tradeDate).getTime() / 1000);//下单时间戳
   //    var currentTime = parseInt(new Date().getTime() / 1000);//当前时间戳
   //    var timeDef = payTime - currentTime + totalTime; //时间差
   //    console.log('时间差: ', timeDef);
   //    if (timeDef > 0 && index == 0) {
   //    // if (index == 0) {
   //      route = 'detail';
   //      wx.navigateTo({
   //        url: '../hejian/index?data=' + data + '&route=' + route,
   //      })
   //    } else {
   //      wx.navigateTo({
   //        url: '../xiang/index?data=' + data,
   //      })
   //    }
   //  } else {
   //    wx.navigateTo({
   //      url: '../paydetail/index?status=' + status + '&data=' + data + '&route=' + route,
   //    })
   //  }
  },

  //  开始退款 REFUND
  requestForOLPayRefund() {
    statusShow.openLoading('提交中');
    var that = this;
    //  金额,trade_no
    var trade_no = this.data.trade_no;
    console.log('trade_no: ', trade_no)
    wx.request({
      url: getApp().globalData.loginUrl + '/refundsq',
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
        if (res.data.result_code === 0) {
          //  退款成功
          wx.showModal({
            title: '提示',
            content: '退款成功!',
            showCancel: false,
              confirmColor:"#ffa825",
            success: function (res) {
              if (res.confirm) {
                //  确认,更新状态
                that.updateStatus();
              }
            }
          })
        } else {
          console.log('refund_else: ', res);
          statusShow.openFail('退款失败');
        }
      },
      fail: function (res) {
        console.log('refund_fail: ', res);
        statusShow.openFail('退款失败');
      }
    })
  }

})