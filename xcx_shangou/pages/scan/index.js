//  扫码界面
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //  时间
var status = require('../../utils/status');// 状态
var app = getApp();

Page({
  data: {
    inputValue: '',
    showModal: false,  //弹框默认不显示
    goodsNum: 1,       //商品默认数量
    currentGoods: {},  //当前商品信息
    goodsArr: [],  //商品信息数组
    flag: true,  //次数控制
    isNum: '',   //商品是否可加减
    totalType: 0, //商品种数
    totalNum: 0,  //商品总件数
    totalPrice: 0,//折前总金额
    totalAllowance: 0,//总折扣
    totalReceivable: 0,//总应付金额
    show: false,
    showData: {},
    showAllowance: false
  },

  onLoad(options) {
     this.getStorageInfo();
     wx.setStorageSync("goodsnum", 1);
  },

  onShow() {
    this.getStorageInfo();
    this.updateView();
  },

  //  获取本地数据
  getStorageInfo() {
    var storeNum = app.globalData.storeId;
    var that = this;
    //  获取本地数据
    wx.getStorage({
       key: app.globalData.storeId,
      success: function (res) {
         console.log("res.data:", res.data)
        if (res.data) {
          that.setData({
            goodsArr: res.data
          })
        }
      }
    })
  },

  //  扫码
  scan() {
    var flag = this.data.flag;
    if (!flag) {
      console.log('禁止点击');
      return;
    }
    var that = this;
    wx.scanCode({
      onlyFromCamera: true,
      success: (res) => {
        that.setData({
          inputValue: res.result
        })
        wx.setStorageSync("inputvalue", res.result);
        //  发送请求
        that.requestForData();  
      },
      fail: (res) => {
        status.openFail('请重新扫描');
      }
    })
  },

  //  获取input值
  bindSeachBar(e) {
    this.setData({
      inputValue: e.detail.value
    })
  },

  //  查找
  clickToSearch() {
    var flag = this.data.flag;
    if (!flag) {
      console.log('禁止点击');
      return;
    }
    //  判断是否输入了条形码
    if(!this.data.inputValue) {
      wx.showModal({
        title: '提示',
        content: '请输入商品条形码',
          confirmColor:"#ffa825",
        showCancel: false,
      })
      return;
    }
    this.requestForData();
    wx.setStorageSync("inputvalue", this.data.inputValue);
  },

  //  发送请求,查询商品   047400115507
  requestForData() {
    this.setData({
      flag: false
    })
    //  显示加载框
    status.openLoading('商品查询中');
    var that = this,
      storeId = getApp().globalData.storeId;   //门店
    wx.request({
      url: getApp().API.Scan,
      data: {
        "data": {
          "code": that.data.inputValue,
        },
        "token": wx.getStorageSync('token'),
        "session": {
          "store": storeId,
          "datetime": util.formatTime(new Date)
        }
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        //隐藏加载框 
        wx.hideLoading();
         console.log('scan_success: ', res);
        //  将数据添加到数组 message
        if (res.data.code == 0) {
          var newGoods = that.data.goodsArr;
          newGoods.push(res.data.data.commod);
          if (res.data.data.commod.goods.isdzc == "N") {
            that.setData({
              goodsArr: newGoods,
              currentGoods: res.data.data.commod,
            //   goodsNum: res.data.data.commod.quantity,
              goodsNum:1,
              flag: true,
              isNum: res.data.data.commod.goods.isdzc
            })
          } else {
            that.setData({
              goodsArr: newGoods,
              currentGoods: res.data.data.commod,
              goodsNum: 1,
              flag: true,
              isNum: res.data.data.commod.goods.isdzc
            })
          }
          try {
            wx.setStorageSync('testCurData', newGoods)
           var  news = wx.getStorageSync("testCurData")
            console.log("newGoods:", news)
          } catch (e) {
          }
          //  显示弹窗
          that.setData({
            showModal: true,
            currentGoods:{
               "goods":{
                  "name": res.data.data.commod.name,
               },
               "amount": res.data.data.commod.oldprice,
               "receivable": res.data.data.commod.price,
            }
          })
        } else if (res.data.code == '2104') {
          wx.showModal({
            title: '提示',
            content: res.data.msg,
              confirmColor:"#ffa825",
            showCancel: false,
            success: function (res) {
               that.setData({
                  inputValue: '',
                  flag: true,
                  isNum: ''
              })
            }
          })
        }else{
          //显示查询失败
          status.openFail('显示失败');
         //  console.log('scan_else_res:', res);
          that.setData({
            inputValue: '',
            flag: true,
            isNum: ''
          })
        }
      },
      fail: function (res) {
        //显示查询失败
        status.openFail('查询失败');
        console.log('scan_fail:', res);
        that.setData({
          inputValue: '',
          flag: true,
          isNum: ''
        })
      }
    })
  },

  //  弹出框蒙层截断touchmove事件
  preventTouchMove() {
  },

  //  弹窗取消按钮点击事件，隐藏模态弹窗
  onCancel() {
    var goodsArr = [];
    try {
      var value = wx.getStorageSync('testCurData')
      if (value) {
        goodsArr = value
      }
    } catch (e) {
    };
    this.setData({
      goodsNum: 1,
      showModal: false,
      inputValue: '',
      isNum: ''
    });
    //  删除数组最后一项
    goodsArr.pop();
    this.setData({
      goodsArr: goodsArr
    })
  },

  //  商品的数量加减
  goodsNum(e) {
    var isNum = this.data.isNum;
    var index = e.currentTarget.dataset.index;
    var num = this.data.goodsNum; 
    switch(index){
      case '0':
        if (num == 1){
          break;
        }
        this.setData({
          goodsNum : num - 1
        })
        wx.setStorageSync("goodsnum", num - 1);
        break;
      case '1':
        this.setData({
          goodsNum : num + 1
        })
        wx.setStorageSync("goodsnum", num + 1);
        break;
    }
  },

  //  取消
  onNext() {
     var goodsArr = [];
     var isScan = 'isNext';
     try {
        var value = wx.getStorageSync('testCurData')
        if (value) {
           goodsArr = value
        }
     } catch (e) {
     };
     this.setData({
        goodsNum: 1,
        showModal: false,
        inputValue: '',
        isNum: ''
     });
     //  删除数组最后一项
     goodsArr.pop();
     this.setData({
        goodsArr: goodsArr
     })
     if (isScan === 'isNext') {
        this.scan();
     } else {
        wx.switchTab({
           url: '../cart/index',
        })
     }
     this.updateView();
  },

  //  加入购物车
  onConfirm() {     
    var goodsArr = [],
      isScan = 'isConfirm';
    try {
      var value = wx.getStorageSync('testCurData')
      if (value) {
        goodsArr = value
      }
    } catch (e) {
    };
    
    var index = goodsArr.length - 1;
    var currentBarcodes = goodsArr[index].barCode;
    var isdzc = goodsArr[index].goods.isdzc;
    var isnew = true;
    if (index > 0) {
      for (var i = 0; i < index; i++) {
         if (goodsArr[i].barCode == currentBarcodes && isdzc == "N") { 
          this.isSame(i, isScan);
          isnew = false;
         } 
      }
      if(isnew){
         this.isDifferent(isScan);
      }
    }else{
       this.isDifferent(isScan);
    }
    this.requestgoods();    
  },
  
  //扫码商品信息加入购物车
  requestgoods(){
     var that = this,
        storeId = getApp().globalData.storeId || app.globalData.storeId;//门店
     wx.request({
        url: getApp().API.SaveCart,
        data: {
           "data": {
              "code": wx.getStorageSync("inputvalue"),
              "num": wx.getStorageSync("goodsnum"),
           },
           "token": wx.getStorageSync('token'),
           "store": storeId,
        },
        header: {
           'content-type': 'application/json'
        },
        method: 'POST',
        success: function (res) {
           if (res.data.code == "0") {

           }
        },
        fail: function (res) {
           //显示查询失败
           status.openFail('查询失败');
           console.log('scan_fail:', res);
           that.setData({
              inputValue: '',
              flag: true,
              isNum: ''
           })
        }
     })
  },
  //  同种商品
  isSame(index, isScan) {
    var goodsArr = [];
    var goodsnum = 0;
    try {
      var goodsnum = wx.getStorageSync("goodsnum");
      var value = wx.getStorageSync('testCurData')
      if (value) {
        goodsArr = value
      }
    } catch (e) {
    };
    //  增加数量
    goodsArr[index].quantity += this.data.goodsNum; 
    //  删除数组最后一项
    goodsArr.pop();
    this.setData({
      goodsNum: 1,
      showModal: false,
      goodsArr: goodsArr,
      inputValue: '',
      isNum: ''
    })
    //  存入本地
    try {
       wx.setStorageSync(app.globalData.storeId, goodsArr)
    } catch (e) {
    }
   //   清除缓存testCurData
    try {
      wx.removeStorageSync('testCurData')
      wx.removeStorageSync('goodsnum')
    } catch (e) {
    }
    if (isScan === 'isNext') {
      this.scan();
    } else {
      wx.switchTab({
        url: '../cart/index',
      })
    }
    this.updateView();
  },

  //  非同种商品 2559261005760
  isDifferent(isScan) {
    var goodsArr = [];
    var goodsnum = 0;
    try {
      var goodsnum = wx.getStorageSync("goodsnum");
      var value = wx.getStorageSync('testCurData')
      if (value) {
        goodsArr = value
      }
    } catch (e) {
    };
    //  添加数量
    var index = goodsArr.length - 1;
    var isdzc = goodsArr[index].goods.isdzc;
    if (isdzc === 'N') {
      goodsArr[index].quantity = this.data.goodsNum;//数量
    }
    goodsArr[index].goods.checked = true;//默认勾选
    goodsArr[index].goods.isTouchMove = false;//默认不显示删除按钮
    this.setData({
      goodsNum: 1,
      showModal: false,
      goodsArr: goodsArr,
      inputValue: '',
      isNum: ''
    })
    //  存入本地
    try {
       wx.setStorageSync(app.globalData.storeId, goodsArr)
    } catch (e) {
    }
    //  清除缓存testCurData
    try {
      wx.removeStorageSync('testCurData')
      wx.removeStorageSync('goodsnum')
    } catch (e) {
    }
    if (isScan === 'isNext') {
      this.scan();
    } else {
      wx.switchTab({
        url: '../cart/index',
      })
    }
    this.updateView();
  },

  //  刷新界面
  updateView() {
    var goodsArr = [];//总商品
    try {
       var value = wx.getStorageSync(app.globalData.storeId)
      if (value) {
        goodsArr = value
      }
    } catch (e) {
    };
    
    // 计算
    var totalPrice = 0,// 折前总价格
      totalAllowance = 0,//总折扣
      totalReceivable = 0,//总实付金额
      totalNum = 0,  //总件数
      totalType = 0; // 种数
    for (var i = 0; i < goodsArr.length; i++) {
      var price = goodsArr[i].goods.lsj,//每个单价
        num = goodsArr[i].quantity;//每个数量
      var currentPrice = 0,// 单个总价格
        currentAllowance = 0,//单个总折扣
        currentReceivable = 0;//单个总实付金额
      if (goodsArr[i].allowances) {
        //  判断是否有折扣
        var value = goodsArr[i].allowances[0].value;//单个的折扣金额
        currentAllowance = value * num;//  单个的总折扣
        currentAllowance = parseFloat(currentAllowance.toFixed(2));
      }
      //  原总价
      currentPrice = price * num;//单个的总价
      currentPrice = parseFloat(currentPrice.toFixed(2));
      totalPrice += currentPrice;//所有的总价
      totalPrice = parseFloat(totalPrice.toFixed(2));
      totalAllowance += currentAllowance;//总折扣
      totalAllowance = parseFloat(totalAllowance.toFixed(2));
      totalNum += num; //总件数
    }
    totalReceivable = totalPrice - totalAllowance;//总折扣
    totalReceivable = parseFloat(totalReceivable.toFixed(2));
    totalType = goodsArr.length; //种数
    this.setData({
      totalType: totalType,
      totalNum: totalNum,
      totalPrice: totalPrice,
      totalAllowance: totalAllowance,
      totalReceivable: totalReceivable
    })
    
    //  当前商品
    var index = goodsArr.length - 1;
    if (index >= 0) {
      this.setData({
        show: true,
        showData: goodsArr[index]
      })
      if (goodsArr[index].allowances) {
        this.setData({
          showAllowance: false
        })
      } else {
        this.setData({
          showAllowance: true
        })
      }
    } else {
      this.setData({
        show: false
      })
    }
  }
})