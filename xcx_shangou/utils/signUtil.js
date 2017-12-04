/**
 * 签名工具
 * @type {{raw: SignUtils.raw, getSign: SignUtils.getSign, paysignjs: SignUtils.paysignjs, createNonceStr: SignUtils.createNonceStr, createTimeStamp: SignUtils.createTimeStamp}}
 */

var md5 = require('./md5');

var SignUtils = {
  raw: function (args) {
    var keys = Object.keys(args);
    keys = keys.sort();
    var newArgs = {};
    keys.forEach(function (key) {
      newArgs[key] = args[key];
    });
    var string = '';
    for (var k in newArgs) {
      if (newArgs[k] === ''){
        continue;
      }
      // console.log('newArgs[k]:', newArgs[k])
      string += '&' + k + '=' + newArgs[k];
    }
    string = string.substr(1);
    return string;
  },
  /*根据参数得到签名*/
  getSign: function (data, key) {

    var string = this.raw(data);
    string = string  + key; //key为在微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置
    // string = string + '&login_token=' + key;

    //RN里没有crypto库...
    //var crypto = require('crypto');
    var sign = md5(string);//crypto.createHash('md5').update(string, 'utf8').digest('hex');
    console.log("MD5:",string,sign);
    // return sign.toUpperCase();
    return sign;
  },


  // 随机字符串产生函数
  createNonceStr: function () {
    return Math.random().toString(36).substr(2, 15);
  },

  // 时间戳产生函数
  createTimeStamp: function () {
    return parseInt(new Date().getTime() / 1000) + '';
  },
};
module.exports = SignUtils;
