//  购物车界面
//  接口地址
var url = getApp().globalData.url;
var util = require('../../utils/util');
var statusShow = require('../../utils/status');
var app = getApp();
function deepCopy(p, c) {
   var c = c || {};
   for (var i in p) {
      if (typeof p[i] === 'object') {
         c[i] = (p[i].constructor === Array) ? [] : {};
         deepCopy(p[i], c[i]);
      } else {
         c[i] = p[i];
      }
   }
   return c;
}
Page({
   data: {
      goodsNum: 0,  //商品种类数
      totalPrice: 0,  //商品总价格
      allChooseBtn: false,  //全选按钮的状态
      itemH: 0, //列表的高度
      startX: 0, //开始坐标
      startY: 0,
      bagInfo: [],//  购物袋信息
      listInfo: [],//  商品信息
      flag: true,  //跳转次数控制
      maxNum: 15,   //购买上限
      appName: '自购', //小程序名
      showModalStatus: false,//购物袋弹窗状态
      img: ["../../images/bagnew.png", "../../images/bigbag.png","../../images/bigbag.png"],//购物袋样式
      boxshow:1,//初始选中小购物袋
      bagnumsmall: 0,//购物袋数量初始值
      bagnummid: 0,
      bagnumbig:0,
      smallquantity:0,//购物袋选中的初始数值,中间变量
      midquantity: 0,
      bigquantity:0,
      checked:1,//是否需要小票
      left:120,//购物袋初始位置
   },
   onLoad: function () {
      this.getStorageInfo();
      //  设置列表的高度
      var res = wx.getSystemInfoSync(),
         itemH = res.windowHeight * 0.14;
      this.setData({
         itemH: itemH
      })
      //  获取购物袋信息
       this.getBagInfo();
   },

   onShow() {
      //  获取购物袋信息
      this.getBagInfo();
      this.getStorageInfo();
      //  设置标题
      wx.setNavigationBarTitle({
         title: app.globalData.shopName
      })
   },

   //改变小票的状态
   click(){
      var check = this.data.checked;
      if(check == 1){
         this.setData({
            checked: 2,
         })
      }else if(check == 2){
         this.setData({
            checked: 1,
         })
      }

      console.log(check)
   },
   //  获取本地数据
   getStorageInfo() {
      var storeNum = app.globalData.storeId;
      //  获取本地数据
      var listInfo = [];
      try {
         var value = wx.getStorageSync(app.globalData.storeId)
         if (value) {
            listInfo = value
         }
         this.setData({
            listInfo:listInfo,
         })
         this.preStatus();
      } catch (e) {
      };
   },

   //购物袋弹窗
   powerDrawer: function (e) {
      var currentStatu = e.currentTarget.dataset.statu;
      this.util(currentStatu)
   },
   util: function (currentStatu) {
      /* 动画部分 */
      // 第1步：创建动画实例 
      var animation = wx.createAnimation({
         duration: 200, //动画时长 
         timingFunction: "linear", //线性 
         delay: 0 //0则不延迟 
      });

      // 第2步：这个动画实例赋给当前的动画实例 
      this.animation = animation;

      // 第3步：执行第一组动画 
      animation.opacity(0).rotateX(-100).step();

      // 第4步：导出动画对象赋给数据对象储存 
      this.setData({
         animationData: animation.export()
      })

      // 第5步：设置定时器到指定时候后，执行第二组动画 
      setTimeout(function () {
         // 执行第二组动画 
         animation.opacity(1).rotateX(0).step();
         // 给数据对象储存的第一组动画，更替为执行完第二组动画的动画对象 
         this.setData({
            animationData: animation
         })
         //关闭 
         if (currentStatu == "close") {
            this.setData(
               {
                  showModalStatus: false,
                  bagnumsmall:0,
                  bagnummid:0,
                  bagnumbig:0,
               }
            );
         }
      }.bind(this), 200)
      // 显示 
      if (currentStatu == "open") {
         this.setData(
            {
               showModalStatus: true,
               bagnumsmall: 0,
               bagnummid: 0,
               bagnumbig: 0,
            }
         );
      }
   },

   //  后台获取购物袋信息
     getBagInfo() {
       //  显示加载框
        statusShow.openLoading('');
        wx.hideLoading();//隐藏加载框
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
         //   console.log('data_success: ', res.data)
           if (res.data.code == '0') {
              console.log("res.data.data:", res.data.data)
             that.setData({
               bagInfo: res.data.data,   
             });
            //  wx.setStorageSync("bagInfo",res.data.data );
           }
         },
         fail: function (res) {
           console.log('data_fail: ', res.data)
         }
       })
     },

   //  勾选商品
   checkBox(e) {
      var index = e.currentTarget.dataset.index,
         newListInfo = this.data.listInfo; // 新状态
      newListInfo[index].goods.checked = !newListInfo[index].goods.checked;
      this.setData({
         listInfo: newListInfo
      }),
         this.preStatus();
   },

   //  当前状态
   preStatus() {
      var preListInfo = this.data.listInfo,
         totalPrice = 0,// 总价格
         chooseNum = 0;//  当前选择商品数
      for (var i = 0; i < preListInfo.length; i++) {
         var price = preListInfo[i].oldprice,//单价
            num = preListInfo[i].quantity;//数量
         if (preListInfo[i].allowances) {
            //  判断是否有折扣
            var value = preListInfo[i].allowances[0].value;
            var newPrice = price - value;
            //  总折扣
            var newAllowance = value * num;
            newAllowance = parseFloat(newAllowance.toFixed(2));
            preListInfo[i].allowance = newAllowance;
            newPrice = parseFloat(newPrice.toFixed(2));//折扣后的单价
            preListInfo[i].price = newPrice;//添加到商品数组
            var currentPrice = newPrice * num;//单个的总价
            currentPrice = parseFloat(currentPrice.toFixed(2));
         } else {
            var currentPrice = price * num;//单个的总价
            currentPrice = parseFloat(currentPrice.toFixed(2));
         }
         //  原总价
         var newAmount = price * num;//单个的总价
         newAmount = parseFloat(newAmount.toFixed(2));
         if (preListInfo[i].goods.checked) {
            chooseNum++; //被选中,总数加1
            totalPrice += currentPrice;//所有的总价
            totalPrice = parseFloat(totalPrice.toFixed(2));
         }
         preListInfo[i].receivable = currentPrice;//添加到商品数组  
         preListInfo[i].amount = newAmount;
      }
      //  全选按钮的状态
      var preChooseBtn = false;
      if (chooseNum == preListInfo.length && chooseNum != 0) {
         preChooseBtn = true
      }
      this.setData({
         allChooseBtn: preChooseBtn,
         goodsNum: chooseNum,
         totalPrice: totalPrice,
         listInfo: preListInfo
      })
      //  存入本地
      wx.setStorageSync(app.globalData.storeId, preListInfo)
   },

   //  全选按钮
   chooseAllGoods() {
      var newStatus = !this.data.allChooseBtn,
         newListInfo = this.data.listInfo;
      //  每个商品的选中状态重新赋值
      for (var i = 0; i < newListInfo.length; i++) {
         newListInfo[i].goods.checked = newStatus;
      }
      //  商品总数
      var preGoodsNum = 0;
      if (newStatus) {
         preGoodsNum = newListInfo.length
      }

      this.setData({
         allChooseBtn: newStatus,
         listInfo: newListInfo,
         goodsNum: preGoodsNum
      })
      this.preStatus();
   },

   //  商品的数量加减
   goodsNum(e) {
      var data = e.currentTarget.dataset,
         status = data.status, //加减状态
         index = data.index, //第index个商品
         num = data.num; //商品数量
      switch (status) {
         case '0':
            if (num == 1) {
               this.touchmove(e);
               return;
            }
            num--
            break;
         case '1':
            num++
            break;
      }
      var newListInfo = this.data.listInfo;
      newListInfo[index].quantity = num;
      this.setData({
         listInfo: newListInfo
      })
      this.preStatus();
   },

   //  数量输入框
   numInput(e) {
      var index = e.currentTarget.dataset.index,
         newNum = parseInt(e.detail.value),
         lastNum = this.data.listInfo[index].quantity;
      if (newNum < 1 || isNaN(newNum)) {
         wx.showModal({
            title: '提示',
            content: '商品数量只能输入0以上的正整数',
             confirmColor:"#ffa825",
            showCancel: false
         })
         this.data.listInfo[index].quantity = lastNum;
      } else {
         this.data.listInfo[index].quantity = newNum;
      }
      this.setData({
         listInfo: this.data.listInfo
      })
      this.preStatus();
   },

   //手指触摸动作开始 记录起点X坐标
   touchstart(e) {
      //开始触摸时 重置所有删除
      this.data.listInfo.forEach(function (v, i) {
         if (v.goods.isTouchMove)//只操作为true的
            v.goods.isTouchMove = false;
      })
      this.setData({
         startX: e.changedTouches[0].clientX,
         startY: e.changedTouches[0].clientY,
         listInfo: this.data.listInfo
      })
   },
   //滑动事件处理
   touchmove(e) {
      var that = this,
         index = e.currentTarget.dataset.index,//当前索引
         startX = that.data.startX,//开始X坐标
         startY = that.data.startY,//开始Y坐标
         touchMoveX = e.changedTouches[0].clientX,//滑动变化坐标
         touchMoveY = e.changedTouches[0].clientY,//滑动变化坐标
         //获取滑动角度
         angle = that.angle({ X: startX, Y: startY }, { X: touchMoveX, Y: touchMoveY });
      that.data.listInfo.forEach(function (v, i) {
         v.goods.isTouchMove = false
         //滑动超过30度角 return
         if (Math.abs(angle) > 30) return;
         if (i == index) {
            if (touchMoveX > startX) //右滑
               v.goods.isTouchMove = false
            else //左滑
               v.goods.isTouchMove = true
         }
      })
      //更新数据
      that.setData({
         listInfo: that.data.listInfo
      })
   },
   /**
    * 计算滑动角度
    * @param {Object} start 起点坐标
    * @param {Object} end 终点坐标
    */
   angle(start, end) {
      var _X = end.X - start.X,
         _Y = end.Y - start.Y
      //返回角度 /Math.atan()返回数字的反正切值
      return 360 * Math.atan(_Y / _X) / (2 * Math.PI);
   },

   // 删除商品
   del(e) {
      var data = e.currentTarget.dataset,
         index = data.index,
         name = data.item.name;

      //  删除指定商品
      var newLisInfo = this.data.listInfo;
      newLisInfo.splice(index, 1);
      this.setData({
         listInfo: newLisInfo
      })

      //  更新状态
      this.preStatus();
   },

   //  结算按钮的点击事件
   clickToAccount() {
      var data = this.data.listInfo,
         goodsNum = this.data.goodsNum,
         flag = this.data.flag,
         num = 0;
      if (!flag) {
         console.log('禁止点击')
         return;
      }
      for (var i = 0; i < data.length; i++) {
         if (data[i].goods.checked && (data[i].goods.stick === 'N')) {
            num++;
         }
      }
      //  判断商品数量
      if (goodsNum === 0) {
         wx.showModal({
            title: '提示',
            content: '必须选择商品才能结算',
             confirmColor:"#ffa825",
            showCancel: false
         })
         
      } else if (num > this.data.maxNum) {
         wx.showModal({
            title: '提示',
            content: '仅支持购买' +this.data.maxNum+ '种及以内商品',
             confirmColor:"#ffa825",
            showCancel: false
         })
      } else {
         var bag = this.data.bagInfo;
         console.log("bag:",bag);
         if(bag == ''){
            statusShow.openLoading('结算中');
            //  结算 创建订单
            this.createOrder();
         } else {
            this.setData({
               showModalStatus: true,//显示购物袋弹窗
               // flag: false  //禁止点击
               bagnumsmall: 0,
               bagnumbig: 0,
            })
            wx.removeStorageSync('smallbag')
            wx.removeStorageSync('bigbag')
         }
      }
   },

   // 购物袋选择
   choosesmall: function () {
      this.setData({
         img: ["../../images/bagnew.png", "../../images/bigbag.png", "../../images/bigbag.png"],//选中小购物袋
         boxshow: 1,//显示小购物袋数量
         left:0,
      })
   },
   choosemid: function () {
      this.setData({
         img: ["../../images/bigbag.png", "../../images/bagnew.png","../../images/bigbag.png"],//选中中购物袋
         boxshow: 2,//显示小购物袋数量
         left:280,
      })
   },
   choosebig: function () {
      this.setData({
         img: ["../../images/smallbag.png" , "../../images/bigbag.png", "../../images/bagnew.png"],//选中大购物袋
         boxshow: 3,//显示大购物袋数量
         left: 420,
      })
   },

   //小购物袋数量加减
   bagsNumsmall: function (e) {
      var data = e.currentTarget.dataset,
         status = data.status, //加减状态
         index = data.index, //第index个商品
         num = data.num; //商品数量
      switch (status) {
         case '0':
            if (num == 0) {
               return;
            }
            num--
            break;
         case '1':
            num++
            break;
      }
      this.setData({
         bagnumsmall: num,
         smallquantity: num,
      })
      wx.setStorageSync('smallbag', num)
   },
   //中购物袋的数量加减
   bagsNummid: function (e) {
      var data = e.currentTarget.dataset,
         status = data.status, //加减状态
         index = data.index, //第index个商品
         num = data.num; //商品数量
      switch (status) {
         case '0':
            if (num == 0) {
               return;
            }
            num--
            break;
         case '1':
            num++
            break;
      }
      this.setData({
         bagnummid: num,
         midquantity: num,
      })
      wx.setStorageSync('midbag', num)
   },
   //大购物袋的数量加减
   bagsNumbig: function (e) {
      var data = e.currentTarget.dataset,
         status = data.status, //加减状态
         index = data.index, //第index个商品
         num = data.num; //商品数量
      switch (status) {
         case '0':
            if (num == 0) {
               return;
            }
            num--
            break;
         case '1':
            num++
            break;
      }
      this.setData({
         bagnumbig: num,
         bigquantity: num,
      })
      wx.setStorageSync('bigbag', num)
   },

   //关闭购物袋弹窗
   powerno: function () {
      this.setData({
         showModalStatus: false,
         flag: false , //禁止点击
         bagnumsmall: 0,
         bagnummid:0,
         bagnumbig: 0,
         smallquantity: 0,//购物袋选中的初始数值,中间变量
         midquantity:0,
         bigquantity: 0,
      })
      statusShow.openLoading('结算中');
      //  结算 创建订单
      this.createOrder();
   },

   //增加购物袋
   powerneed: function () { 
      this.putinsmall();
      this.putinmid();
      this.putinbig();
      this.preStatus();
      this.setData({
         showModalStatus: false,
         flag: false , //禁止点击
      })  
      statusShow.openLoading('结算中');
      //  结算 创建订单
      this.createOrder();
   },
 
   //导入购物袋
   putinsmall() {
      if (this.data.bagnumsmall == 0) {
         console.log("没有添加小购物袋")
      } else {
            var valuesmall = wx.getStorageSync("smallbag");
            if (valuesmall) {
               // console.log("value:",valuesmall)
               var bagData = wx.getStorageSync('bagData');
               console.log("bagData:",bagData)
               this.setBagQuantity(valuesmall, bagData[0].name, bagData[0].price, bagData[0].oldprice, bagData[0].matnr, bagData[0].barCode, bagData[0].hasWeight, bagData[0].imgUrl, bagData[0].sellStatus, bagData[0].stock, bagData[0].storeCode, bagData[0].type, bagData[0].wareCodeType, bagData[0].weight)
            }
            this.getStorageInfo();
            this.preStatus();
      }
   },
   putinmid() {
      if (this.data.bagnummid == 0) {
         console.log("没有添加中购物袋")
      } else {
         var valuemid = wx.getStorageSync("midbag");
         if (valuemid) {
            // console.log("value:", valuemid)
            var bagData = wx.getStorageSync("bagData");
            this.setBagQuantity(valuemid, bagData[1].name, bagData[1].price, bagData[1].oldprice, bagData[1].matnr, bagData[1].barCode, bagData[1].hasWeight, bagData[1].imgUrl, bagData[1].sellStatus, bagData[1].stock, bagData[1].storeCode, bagData[1].type, bagData[1].wareCodeType, bagData[1].weight)
         }
         this.getStorageInfo();
         this.preStatus();
      }
   },
   putinbig(){
      if (this.data.bagnumbig == 0) {
         console.log("没有添加大购物袋")
      } else {
         var valuebig = wx.getStorageSync("bigbag");
         if (valuebig) {
            // console.log("value:", valuebig)
            var bagData = wx.getStorageSync("bagData");
            this.setBagQuantity(valuebig, bagData[2].name, bagData[2].price, bagData[2].oldprice, bagData[2].matnr, bagData[2].barCode, bagData[2].hasWeight, bagData[2].imgUrl, bagData[2].sellStatus, bagData[2].stock, bagData[2].storeCode,bagData[2].type, bagData[2].wareCodeType, bagData[2].weight)
         }
         this.getStorageInfo();
         this.preStatus();
      }
   },
   //添加商品
   setBagQuantity: function (quantity, name, price, oldprice, mat, code,has,img,sell,stock,storecode,type,ware,weight) {
      var goodsArr = wx.getStorageSync(app.globalData.storeId);
      goodsArr = goodsArr ? goodsArr : [];
      goodsArr.push({
         quantity: quantity,
         name: name,
         price: price,
         oldprice: oldprice,
         matnr: mat,
         barcode: code,//条形码
         hasWeight:has,
         imgUrl:img,  
         sellStatus:sell,
         stock:stock,
         storeCode: storecode,
         type:type,
         wareCodeType:ware,
         weight:weight,
         goods: {
            checked: true,//是否选中
            isdzc: 'N',//是否可增加减
            isTouchMove: false,//可以删除
         }
      })
      wx.setStorageSync(app.globalData.storeId, goodsArr);
     
      var index = goodsArr.length - 1;
      var currentBarcodes = goodsArr[index].barcode;
      var isdzc = goodsArr[index].goods.isdzc;
      if (index > 0) {
         for (var i = 0; i < index; i++) {
            if (goodsArr[i].barcode == currentBarcodes && isdzc === 'N') {
               this.isSame(i, currentBarcodes);
               return;
            }
         }
      }
      this.isDifferent(currentBarcodes);
   },
   //  同种商品
   isSame(index, code) {
      var goodsArr = [];
      try {
         var bagData = wx.getStorageSync("bagData");
         var value = wx.getStorageSync(app.globalData.storeId);
         if (value) {
            goodsArr = value
         }
      } catch (e) {
      };
      //  增加数量
      if (code == bagData[0].matnr){
         goodsArr[index].quantity += this.data.smallquantity;
      } else if (code == bagData[1].matnr){
         goodsArr[index].quantity += this.data.midquantity;
      }
      else if (bagData[2].matnr){
         goodsArr[index].quantity += this.data.bigquantity;
      }
      // goodsArr[index].quantity += this.data.goodsNum;
      //  删除数组最后一项
      goodsArr.pop();
      this.setData({
         goodsArr: goodsArr,
      })
      //  存入本地
      try {
         wx.setStorageSync(app.globalData.storeId, goodsArr)
      } catch (e) {
      }
   },

   //  非同种商品 2559261005760
   isDifferent(code) {
      var goodsArr = [];
      try {
         var bagData = wx.getStorageSync("bagData");
         var value = wx.getStorageSync(app.globalData.storeId)
         if (value) {
            goodsArr = value
         }
      } catch (e) {
      };
      //  添加数量
      var index = goodsArr.length - 1;
      var isdzc = goodsArr[index].goods.isdzc;
      if (isdzc === 'N') {
         if (code == bagData[0].matnr){
            goodsArr[index].quantity = this.data.smallquantity;//数量
         }
         else if (code == bagData[1].matnr){
            goodsArr[index].quantity = this.data.midquantity;//数量
         }
         else if (code == bagData[2].matnr){
            goodsArr[index].quantity = this.data.bigquantity;//数量
         }
      }
      goodsArr[index].goods.checked = true;//默认勾选
      goodsArr[index].goods.isTouchMove = false;//默认不显示删除按钮
      this.setData({
         goodsArr: goodsArr,
      })
      //  存入本地
      try {
         wx.setStorageSync(app.globalData.storeId, goodsArr);
      } catch (e) {
      } 
   },

   //  跳转扫码
   toScan() {
      wx.switchTab({
         url: '../scan/index',
      })
   },

   //  选择门店
   chooseShop() {
      wx.navigateTo({
         url: '../shop/index?',
      })
   },

   //  创建订单
   createOrder() {
      var that = this,
         listInfo = this.data.listInfo,  //商品信息
         date = util.formatTime(new Date), //日期
         store = getApp().globalData.storeId;   //门店
      console.log("list:", listInfo)
      //  已选择的商品
      var checkedInfo = [];
      for (var i in listInfo) {
         if (listInfo[i].goods.checked) {
            checkedInfo.push(listInfo[i]);
         }
      }
      for (var i = 0; i < checkedInfo.length; i++) {
         if (checkedInfo[i].allowances) {
            var allowance = checkedInfo[i].allowances.slice(0);
            allowance[0].value = allowance[0].value * checkedInfo[i].quantity;
            allowance[0].value = parseFloat(allowance[0].value.toFixed(2));
            checkedInfo[i].allowances[0] = allowance[0];
         }
      }
      // console.log('checkedInfo: ', checkedInfo);
      wx.request({
         url: getApp().API.OrderBalance,
         data: {
            "token": wx.getStorageSync('token'),
            "data": {
               "commods": checkedInfo
            },
            "checked":this.data.checked,
            "session": {
               "datetime": date,
               "store": store
            }
         },
         header: {
            'content-type': 'application/json'
         },
         method: 'POST',
         success: function (res) {
            // console.log('cart_success: ', res);
            if (res.data.code == "0") {
               var identity = res.data.data.order_no,
                     status = res.data.data.order_status,
                     totalprice = res.data.data.order_amt,
                     disprice = res.data.data.dis_amt,
                     route = 'cart',
                     data = res.data.data.detail;
                     // console.log("order_no:",identity)
                     // console.log("res.data.data.order_amt:", res.data.data.order_amt)
                     // console.log("res.data.data.dis_amt:", res.data.data.dis_amt)
               data = JSON.stringify(data);
               wx.hideLoading();//隐藏加载框
               wx.navigateTo({
                  url: '../paydetail/index?status=1&data=' + data + '&route=' + route + '&status=' + status + '&identity=' + identity + '&totalprice=' + totalprice +'&disprice=' + disprice ,
               });
               var time = setTimeout(function () {
                  that.setData({
                     flag: true     // 可以点击
                  })
               } , 1000)
               var storeNum = app.globalData.storeId;
               //  获取本地数据
               try {
                  var value = wx.getStorageSync(app.globalData.storeId)
                  if (value) {
                     wx.removeStorageSync(app.globalData.storeId)
                  }
               } catch (e) {
               };  
                //  跳转
               // wx.navigateTo({
               //    url: '../account/index?identity=' + identity + '&data=' + data
               // })
            } else if (res.data.code == '2000' || res.data.code == '4000') {
               wx.hideLoading();//隐藏加载框
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
            } else if (res.data.code == 1003) {
              statusShow.openFail("请先登录");
              wx.redirectTo({
                url: '../login/index',
              })
            }else {
               //显示结算失败
               statusShow.openFail("请重新结算");
               that.setData({
                  flag: true     // 可以点击
               })
            }
         },
         fail: function (res) {
            //显示结算失败
            statusShow.openFail('网络较差');
            that.setData({
               flag: true     // 可以点击
            })
         }
      })
   },

})