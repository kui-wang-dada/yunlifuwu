'use strict';
const baseURL = 'https://papi.yunlitech.com/Api/v1/'
module.exports = {
  Login: baseURL + 'user/login',
  Sendsms: baseURL + 'user/sendsms',
  Register: baseURL + 'user/register',
  Stores: baseURL + 'stores',
  OrderBalance: baseURL + 'order/balance',
  Scan: baseURL + 'commod/search',
  List: baseURL + "commod/list",
  SaveCart: baseURL + "commod/savecart",
  OrderPay: baseURL + "order/pay",
  OrderCancel: baseURL + "order/changestatus",
  OrderDelete: baseURL + "order/delete",
  OrderConfirm: baseURL + "order/confirm",
  OrderList: baseURL + "order/list",
  OrderInfo: baseURL + "order/info",
}