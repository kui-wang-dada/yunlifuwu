//  订单详情
var statusShow = require('../../utils/status'); //状态
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //时间
var timeOut = 900;  //超时时间
var signUtil = require('../../utils/signUtil');
var app = getApp();
Page({
  data: {
    listInfo: [],//订单详情
    status: 0,  //订单状态
    titleStatus: '',  //状态标题
    goodsNum: 0,  //总数量
    amount: 0,  //折前总金额
    createDate: '',  // 时间
    orderId: '',  //订单编号
    discount: 0,  //总折扣
    receivable: 0,   //应付金额
    validateCode: '',  //核检码
    cardno: "",//会员卡号
    storeName: ''  //门店名称
  },

  onLoad(e) {
    let data = JSON.parse(e.data);
    let identity = (e.identity);
    let totalprice = (e.totalprice);
    let disprice = (e.totalprice);
   //  console.log('e_data: ', data);
   //  console.log('e_identity: ', identity);
   //  console.log('e.totalprice: ', e.totalprice);
   //  订单状态
    let status = parseInt(e.status);
    //  判断路由
    if(e.route === 'cart') { 
       var totalnum = 0;
       for (var i in data) {
          totalnum += data[i].sl;
       }
       var discount = parseFloat(totalprice - disprice);
      this.setData({
        listInfo: data,//商品列表
        goodsNum: totalnum, //总数量
        amount: totalprice,  //折前总金额
        createDate: util.formatTime(new Date), //时间
        orderId: identity,   //订单编号
        discount: discount, //总折扣
        receivable: disprice,//应付金额
        titleStatus: '待付款',
        status: status,
      //   validateCode: data.orderBrief.validateCode,
      //   cardno: getApp().globalData.cardno,
        storeName: getApp().globalData.shopName//门店名称
      })
    } else if (e.route === 'payConfirm' || e.route === 'mine'){
      // var thisStatus = parseInt(data.order_status),
      //   thisTitle = '';
      // if (thisStatus == 0) {
      //   thisTitle = '待付款'
      // } else if (thisStatus == 1) {
      //   thisTitle = '待核检'
      // } else {
      //   thisTitle = '已核检'
      // }
      var totalnum = 0;
      for (var i in data.detail) {
         totalnum += parseInt(data.detail[i].sl);
      }
      this.setData({
        listInfo: data.detail,//商品列表
        goodsNum: totalnum, //总数量
        amount: data.order_amt,  //折前总金额
        createDate: data.add_time, //时间
        orderId: data.order_no,   //订单编号
        discount: data.dis_amt, //总折扣
        receivable: data.yf_amt,//应付金额
        titleStatus: data.status_str,
        status: parseInt(data.order_status),
      //   validateCode: data.brief.validateCode,
      //   cardno: getApp().globalData.cardno,
        storeName: getApp().globalData.shopName//门店名称
      })
      if (data.pay_id) {
        this.setData({
          trade_no: data.pay_id  //trade_no
        })
      }
    } else {
      //  获取数据
      this.requestForDetail(data);
      //  标题状态
       var  newStatus = data.status_str;
      this.setData({
         titleStatus: newStatus,
      })
    }
  },

  //  发送请求获取订单详情
  requestForDetail(data) {
    console.log('发送请求获取数据')
    statusShow.openLoading('加载中');
    var that = this,
      date = util.formatTime(new Date), //日期
      store = getApp().globalData.storeId;   //门店
    wx.request({
       url: getApp().API.OrderList, //订单详情
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
        console.log('listAll_success: ', res);
        if (res.data.code == 0) {
          wx.hideLoading();//隐藏加载框
          that.setData({
            listInfo: res.data.data.order.commods,//商品列表
            goodsNum: data.quantity, //总数量
            amount: data.amount,  //折前总金额
            createDate: res.data.data.order.brief.tradeDate, //时间
            orderId: data.orderId,   //订单编号
            discount: data.discount, //总折扣
            receivable: data.receivable,//应付金额
            validateCode: data.validateCode,
            storeName: getApp().globalData.shopName//门店名称
          })
          if (res.data.data.order.payments) {
            var trade_no = res.data.data.order.payments[0].payer;
            that.setData({
              trade_no: trade_no
            })
          }
        } else {
          //显示查询失败
          statusShow.openFail('获取信息失败');
          console.log('listAll_else:', res);
        }
      },
      fail: function (res) {
        //显示查询失败
        statusShow.openFail('获取信息失败');
        console.log('listAll_fail:', res);
      }
    })
  },

  //  立即付款的点击事件
  confirm() {
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确定支付?',
      success: function (res) {
        if (res.confirm) {
          that.judgeTime();
        }
      }
    })
  },

  //  判断订单是否超时
  judgeTime() {
    var that = this;
   //    date = this.data.createDate.substring(0, 19);
   //  date = date.replace(/-/g, '/'); //下单时间
   //  var payTime = new Date(date).getTime();//下单时间戳
   //  var currentTime = new Date().getTime();//当前时间
   //  var timeDef = currentTime - payTime; //时间差
   //  timeDef = parseInt(timeDef / 1000);

   //  if (timeDef > timeOut) {
   //    //取消订单
   //    that.requestForCancel();
   //    //通知后台
   //    wx.showModal({
   //      title: '提示',
   //      content: '支付已超时,请重新下单',
   //      showCancel: false
   //    })
   //  } else {
      //未超时 可支付
      // this.confirmToPay();
      try {
      //   var value = wx.getStorageSync('OLPayRes')
      //   if (value) {
      //     console.log('有值: ', value)
      //     that.againPay(value);
      //   } else {
          that.requestForOLPay();
          console.log('没有值')
      //   }
      } catch (e) {
      };
   //  }
  },

  //  再次付款
  againPay(value) {
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
          that.confirmToPay(trade_no);
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
  requestForOLPay() {
    statusShow.openLoading('提交中');
    var that = this;
    wx.request({
      url: getApp().API.OrderPay,
      data: {
        "openid": getApp().globalData.openid,
        "token":wx.getStorageSync("token"),
        "order_no": that.data.orderId,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('res_success: ',  res.data)
        var data = res.data
      //   data = JSON.parse(data)
        console.log('支付数据: ', data)
        if (data.code === 0) {
          var trade_no = data.data.appId;
          console.log('trade_no: ', trade_no)
          //  存入本地
          wx.setStorage({
            key: 'OLPayRes',
            data: data
          })
          wx.requestPayment({
            'timeStamp': data.data.timeStamp,
            'nonceStr': data.data.nonceStr,
            'package': data.data.package,
            'signType': data.data.signType,
            'paySign': data.data.paySign,
            'success': function (res) {
              console.log('res_success_api: ', res);

              if (res.errMsg === "requestPayment:ok") {
                //  清除缓存
                try {
                  wx.removeStorageSync('OLPayRes'); 
                } catch (e) {
                }
                that.confirmToPay(trade_no);
              }
            },
            'fail': function (res) {
              console.log('res_fail_api: ', res)
            },
            complete: function (res) {
              console.log('res_complete_api: ', res)
            },
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

  //  确认支付 
  confirmToPay(trade_no) {
        var storeNum = app.globalData.storeId;
        //  获取本地数据
        try {
           var value = wx.getStorageSync(app.globalData.storeId)
           if (value) {
              wx.removeStorageSync(app.globalData.storeId)
           }
        } catch (e) {
        };
    var data = {};
      data.receivable = this.data.receivable,//金额
      data.orderId = this.data.orderId,//orderId
      data.quantity = this.data.goodsNum,//数量
      data.trade_no = trade_no;  //trade_no
      data = JSON.stringify(data);
    wx.redirectTo({
      url: '../inspection/index?data=' + data,
    })
    //改变上一页的交易状态
   //  var pages = getCurrentPages();
   //  var prevPage = pages[pages.length - 2];
   //  prevPage.setData({
   //    status: 3
   //  })
  },

  //  取消订单
  cancelOrder() {
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确认取消订单吗?',
      success: function (res) {
        if (res.confirm) {
          //  确认取消,更新状态
          that.requestForCancel();
        }
      }
    })
  },

  //  发送请求,取消订单
  requestForCancel() {
    statusShow.openLoading('取消中');
    var that=this;
    wx.request({
       url: getApp().API.OrderCancel,
      data: {
         "order_no": this.data.orderId,
         "token":wx.getStorageSync("token"),
         "status": "cancel",
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('detail_cancel: ', res);
        if (res.data.code == '0') {
          //  清除缓存
          try {
            wx.removeStorageSync('OLPayRes')
          } catch (e) {
          }
          that.updateStatus();
          var storeNum = app.globalData.storeId;
          //  获取本地数据
          try {
             var value = wx.getStorageSync(app.globalData.storeId)
             if (value) {
                wx.removeStorageSync(app.globalData.storeId)
             }
          } catch (e) {
          };
        } else {
          statusShow.openFail('订单取消失败');
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('account_fail: ', res);
        statusShow.openFail('订单取消失败');
      }
    })
  },
   //删除订单
  deleteOrder(){
     var that = this;
      wx.request({
         url: getApp().API.OrderDelete,
         data:{
            "token":wx.getStorageSync("token"),
            "order_no":that.data.orderId,
         },
         header: {
            'content-type': 'application/json'
         },
         method: 'POST',
         success: function (res) {
            wx.hideLoading();//隐藏加载框
            console.log('delete_res: ', res);
            if (res.data.code == '0') {
                  console.log("删除订单成功");
                  wx.switchTab({
                     url: '../cart/index',
                  })
            } else {
               statusShow.openFail('订单删除失败');
            }
         },
         fail: function (res) {
            //显示查询失败
            console.log('account_fail: ', res);
            statusShow.openFail('订单取消失败');
         }
      })
  },
  //  更新状态
  updateStatus() {
    //  改变交易状态
    this.setData({
      status: 2,
      titleStatus: '交易关闭',
    });
    
   //  var pages = getCurrentPages();
   //  var prevPage = pages[pages.length - 2];
   //  prevPage.setData({
   //    status: 3
   //  })
  },

  //  申请退款
  applyRefund() {
    var that = this;
    wx.showModal({
      title: '提示',
      content: '确认申请退款吗?',
      success: function (res) {
        if (res.confirm) {
          //  确认,发送请求
          that.requestForRefund();
        }
      }
    })
  },
  
  //  发送请求,申请退款
  requestForRefund() {
    statusShow.openLoading('');
    var that = this,
      validateCode = this.data.validateCode,
      date = util.formatTime(new Date), //日期
      orderId = this.data.orderId,  //订单identity
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
        if (res.data.code == '0') {
          that.updateStatus();
          console.log('detail_refund_success: ', res);
          that.requestForOLPayRefund();
        } else if (res.data.code == '2000' || res.data.code == '4000') {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
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

  //  开始退款 REFUND
  requestForOLPayRefund() {
    statusShow.openLoading('提交中');
    var that = this;
    //  金额,trade_no
    var trade_no = this.data.trade_no;
    var fee = this.data.receivable;
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
