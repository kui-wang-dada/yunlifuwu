// 选择门店
var app = getApp();
var statusShow = require('../../utils/status');
var url = getApp().globalData.url;  //接口地址
var util = require('../../utils/util'); //  时间

//  分组排序
function pySegSort(arr) {
  if (!String.prototype.localeCompare)
    return null;

  var alphabets = "*abcdefghjklmnopqrstwxyz".split('');
  var zh = "阿八嚓哒妸发旮哈讥咔垃痳拏噢妑七呥扨它穵夕丫帀".split('');

  var segs = [];
  var curr;
  alphabets.forEach(function (item, i) {
    curr = { alphabet: item, datas: [] };
    arr.forEach(function (item2) {
      if ((!zh[i - 1] || zh[i - 1].localeCompare(item2) <= 0) && item2.localeCompare(zh[i]) == -1) {
        curr.datas.push(item2);
      }
    });
    if (curr.datas.length) {
      segs.push(curr);
      curr.datas.sort(function (a, b) {
        return a.localeCompare(b);
      });
    }
  });
  return segs;
}


Page({
  data: {
    title: '',
    allData: {},  //门店信息
    titleStatus: '定位中',    //标题信息
    location: {},   //坐标信息
    flag: false,  //   ['we','qwe','qe']
    list: [
      // { alphabet: 'A', datas: ['asome', 'aentries', 'are here'] },
      // { alphabet: 'B', datas: ['bbsome', 'bebntries', 'bare here'] },
      // { alphabet: 'c', datas: ['超市英利店'] },
    ],
    alpha: '',
    windowHeight: ''
  },

  onLoad: function (options) {
    this.getLocation();
    this.setData({
      icon: '../../images/location.png'
    });
    try {
      var res = wx.getSystemInfoSync(),
        scollH = res.windowHeight - 80;
      this.pixelRatio = res.pixelRatio;
      // console.log('res: ', res);
      // this.apHeight = 32 / this.pixelRatio;
      // this.offsetTop = 160 / this.pixelRatio;
      this.apHeight = 16;
      this.offsetTop = 80;
      this.setData({ windowHeight: scollH + 'px' })
      console.log({ windowHeight: scollH + 'px' })
    } catch (e) {

    }
  },

  //  定位获取坐标
  getLocation() {
    var that = this;
    wx.getLocation({
      type: 'wgs84',//GPS定位坐标
      success: function (res) {
        //  获取经纬度
        var latitude = res.latitude,
          longitude = res.longitude;
        //  调用后台获取门店信息
        that.requestForStore(latitude, longitude);
      },
      fail: function (res) {
        that.setData({
          titleStatus: '您已拒绝定位授权'
        })
      }
    })
  },

  requestForStore(latitude, longitude) {
    var that = this;
    var appid = getApp().globalData.appid;
    var openid = getApp().globalData.openid;
    var url = getApp().API.Stores;
    wx.request({
      url: url,
      data: {
        token: wx.getStorageSync('token'),
        appid: appid,
        latitude: latitude,
        longitude: longitude
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
      //   console.log('location_success: ', res);
        wx.hideLoading();//隐藏加载框
        if (res.data.code == 0) {
         //   statusShow.openSucces(res.data.msg);
          var storeArr = [];
          var data = res.data.data;
          for (var i in data.store) {
             storeArr.push({ alphabet: data.store[i].alphabet, datas: [data.store[i].store_name] });
          }
         //  storeArr = pySegSort(storeArr); 
          that.setData({
            list: storeArr,
            allData: data,
            titleStatus: data.near.store_name,
            flag: true
          })
         //  var timer = setTimeout(function(){
         //     app.globalData.shopName = data.near.store_name;
         //     app.globalData.storeId = data.near.store_id;
         //    // console.log('storeId: ', app.globalData.storeId);
         //    // console.log('name: ', app.globalData.shopName);
         //  //  跳转购物车界面
         //  wx.switchTab({
         //     url: '../cart/index',
         //  })
         //  that.getBagInfo();
         //  },1500);
         
        } else {
          statusShow.openFail(res.data.msg);
          var storeArr = [];
          var storedata = res.data.data;
          if (storedata){
             for (var i in storedata.store) {
                storeArr.push({ alphabet:storedata.store[i].alphabet,datas:[storedata.store[i].store_name]});
             }
          }
         // storeArr = pySegSort(storeArr); 
          that.setData({
             list: storeArr,
             allData: storedata,
             titleStatus: '请手动选择门店',
             flag: false
          })
        }
      },
      fail: function (res) {
        //显示查询失败
        console.log('location_fail: ', res);
        that.setData({
          titleStatus: '定位失败,请手动选择门店',
          flag: false
        })
      }
    })
  },

  //  索引列表
  handlerAlphaTap(e) {
    let { ap } = e.target.dataset;
    this.setData({ alpha: ap });
  },
  handlerMove(e) {
    let { list } = this.data;
    let moveY = e.touches[0].clientY;
    let rY = moveY - this.offsetTop;
    if (rY >= 0) {
      let index = Math.ceil((rY - this.apHeight) / this.apHeight);
      if (0 <= index < list.length) {
        let nonwAp = list[index];
        nonwAp && this.setData({ alpha: nonwAp.alphabet });
      }
    }
  },

  //  选择门店
  chooseShop(e) {
    //  设置门店名称
    var shopName = e.currentTarget.dataset.item;
    app.globalData.shopName = shopName;
    console.log('shopName: ', shopName);
    if(this.data.flag) {
      var data = this.data.allData;
      var storeId = '';
      for (var i in data.store) {
        if (data.store[i].store_name === shopName) {
           storeId = data.store[i].store_id;
        }
      }
      app.globalData.storeId = storeId;
    } else {
       var data = this.data.allData;
       var storeId = '';
       for (var i in data.store) {
          if (data.store[i].store_name === shopName) {
             storeId = data.store[i].store_id;
          }
       }
       app.globalData.storeId = storeId;
    }
    console.log('storeId: ', app.globalData.storeId);
    //  跳转购物车界面
    wx.switchTab({
      url: '../cart/index',
    })
    this.getBagInfo();
  },

  //  获取购物袋信息
  getBagInfo() {
    //  显示加载框
   //  statusShow.openLoading('');
    wx.hideLoading();
    var that = this,
      storeId = getApp().globalData.storeId;//门店
    wx.request({
      url: getApp().API.List,
      data: {
        "data": {},
        "token": wx.getStorageSync('token'),
         "store": storeId,
      },
      header: {
        'content-type': 'application/json'
      },
      method: 'POST',
      success: function (res) {
        wx.hideLoading();
        console.log('data_success: ', res.data)
        if (res.data.code == '0') {
          var bagData = res.data.data;
          console.log("bagData:",bagData[0])
          try {
            wx.setStorageSync('bagData', bagData)
          } catch (e) {
          }
          console.log("bagData:", bagData[0])
          wx.switchTab({
            url: '../cart/index',
          })
        } else {
          wx.switchTab({
            url: '../cart/index',
          })
        }
      },
      fail: function (res) {
        console.log('data_fail: ', res.data)
      }
    })
  }

})