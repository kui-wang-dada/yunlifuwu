//  待核检
var url = getApp().globalData.url;//接口地址
var util = require('../../utils/util');//时间
var statusShow = require('../../utils/status');//状态
var wxbarcode = require('../../utils/index.js');//条码生成
var wxqrcode = require('../../utils/index.js');//二维码生成
Page({
  data: {
    validateCode: '',   //核检码
    orderId:"",//订单号
    allData: {},    //所有数据
    flag: true ,     //次数限制
    order_no:"",
  },

  onLoad(options) {
    //  数据 
    var data = JSON.parse(options.data);
    console.log('data_ins:', data);
    var receivable = data.receivable;
    var orderId = data.orderId;
    var order_no = data.order_no;
    var trade_no = data.trade_no;
    this.setData({
       orderId: orderId,
       order_no: order_no,
    })
    
    //  存储支付信息
    try {
      wx.setStorageSync('saveData', data)
    } catch (e) {
    }
    //  查询次数
    var flag = 0;
    //  获取核检码
    this.requestForConfirm(receivable, orderId, flag, trade_no);
    statusShow.openLoading('加载中');
  },

  //  确认订单信息
  requestForConfirm(receivable, orderId, flag, trade_no) {
   //  if(flag > 2){
   //    wx.hideLoading();//隐藏加载框
   //    wx.showModal({
   //      title: '提示',
   //      content: "当前网络较差,请在'我的'查看核检码",
   //      showCancel: false,
   //      success: function (res) {
   //        if (res.confirm) {
   //          wx.navigateBack()
   //        }
   //      }
   //    })
   //    return;
   //  }
   var that = this;
   var data = wx.getStorageSync("saveData");
   var orderId = data.orderId;
    console.log('orderId:', orderId)
    //  交易金额
    wx.request({
       url: getApp().API.OrderConfirm,
      data: {
          "orderId": orderId || this.data.order_no,
          "token":wx.getStorageSync("token"),
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        console.log('inspection_success: ', res);
        if (res.data.code == '0') {
          wx.hideLoading();//隐藏加载框
          // 获取信息
          var validateCode = res.data.data;
          that.setData({
            validateCode: validateCode,
          })
         //  wxbarcode.barcode('barcode', validateCode, 680, 200);
          wxqrcode.qrcode('barcode', validateCode, 1500, 500);
          //  移除支付信息
          try {
            wx.removeStorageSync('saveData')
          } catch (e) {
          }
          //  清除缓存
          try {
            wx.removeStorageSync('checkNum')
          } catch (e) {
          }
        } else {
          //显示查询失败
          console.log('inspection_else: ', res);
          flag++;
          that.requestForConfirm(receivable, orderId, flag, trade_no);
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('inspection_fail: ', res);
      }
    })
  },

//查看订单
  payDetail() {
    statusShow.openLoading('加载中');
    var that = this;
    wx.request({
       url: getApp().API.OrderInfo, //订单列表
      data: {
         "token": wx.getStorageSync("token"),
         "order_no": this.data.orderId || this.data.order_no,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();//隐藏加载框
        console.log('listAll_success: ', res);
        if (res.data.code == '0') {
          var data = res.data.data;
          var status = data.order_status;
          that.setData({
             allData: data
          })
          if (status == '9') {
            data = JSON.stringify(data);
            wx.navigateTo({
              url: '../xiang/index?data=' + data,
            })
          } else {
            that.toPayDetail();
          }
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
  },

  toPayDetail() {
    var dataAll = this.data.allData;
    var route = 'payConfirm';
    //判断是否有值
    if (JSON.stringify(dataAll) == '{}') {
      return;
    }
    var data = JSON.stringify(dataAll);
    //  支付成功 跳转 订单详情
    wx.redirectTo({
      url: '../paydetail/index?status=3&data=' + data + '&route=' + route,
    })
  },

  //  自己核检，测试用
//   hejian() {
//      var that = this;
//      wx.request({
//         url: getApp().API.OrderInfo, //订单列表
//         data: {
//            "token": wx.getStorageSync("token"),
//            "order_no": this.data.validateCode,
//         },
//         header: {
//            'content-type': 'application/json'
//         },
//         method: 'POST',
//         success: function (res) {
//            wx.hideLoading();//隐藏加载框
//            console.log('listAll_success: ', res);
//            if (res.data.code == '0') {
//               var data = res.data.data;
//               that.setData({
//                  allData: data
//               })
//            } else {
//               statusShow.openFail('网络较差');
//            }
//         },
//         fail: function (res) {
//            //显示查询失败
//            wx.hideLoading();//隐藏加载框
//            statusShow.openFail('网络较差');
//            console.log('listAll_fail:', res);
//         }
//      })
//     var dataAll = this.data.allData;
//     //判断是否有值
//     if (JSON.stringify(dataAll) == '{}') {
//       return;
//     }
//     console.log('dataAll: ', dataAll)
//     var data = JSON.stringify(dataAll);
//     wx.navigateTo({
//       url: '../hejian/index?status=3&data=' + data,
//     })
//     var that = this;
//     wx.scanCode({
//       onlyFromCamera: true,
//       success: (res) => {
//         console.log('res.result: ', res.result);
//         //  发送请求
//         this.confirm(res.result);
//       },
//       fail: (res) => {
//       }
//     })
//   },

//   //  确认核检
//   confirm(result) {
//     var flag = this.data.flag;
//     if (!flag) {
//       console.log('禁止点击')
//       return;
//     }
//     //  显示加载框 
//     this.setData({
//       flag: false
//     })
//     statusShow.openLoading('核检中');
//     var that = this,
//       validateCode = this.data.allData.order_no,//核检码
//       orderId = this.data.allData.order_no,//订单id
//       date = util.formatTime(new Date),  //时间
//       storeId = getApp().globalData.storeId;   //门店
//     wx.request({
//       url: url + '/order/validate',
//       data: {
//         "data": {
//           "validateCode": validateCode,
//           "orderId": orderId,
//           "qrCode": result
//         },
//         "session": {
//           "customer": {
//             "openId": getApp().globalData.openid
//           },
//           "store": storeId,
//           "datetime": date
//         }
//       },
//       header: {
//         'content-type': 'application/json'
//       },
//       method: 'POST',
//       success: function (res) {
//         //隐藏加载框
//         wx.hideLoading();
//         console.log('validate_success: ', res);
//         if (res.data.code == '0') {
//           var data = JSON.stringify(res.data.data);
//           var route = 'common';
//           wx.navigateTo({
//             url: '../hejian/index?status=3&data=' + data + '&route=' + route,
//           })
//         } else if (res.data.code == '4000' || res.data.code == '2000') {
//           wx.showModal({
//             title: '提示',
//             content: res.data.message,
//             showCancel: false,
//             success: function (res) {
//               that.setData({
//                 flag: true     // 可以点击
//               })
//             }
//           })
//         } else {
//           //显示查询失败
//           statusShow.openFail('网络异常');
//           console.log('validate_else_res:', res);
//           that.setData({
//             flag: true
//           })
//         }
//       },
//       fail: function (res) {
//         //核检失败
//         statusShow.openFail('网络异常');
//         console.log('validate_fail:', res);
//         that.setData({
//           flag: true
//         })
//       }
//     })
//   }

})