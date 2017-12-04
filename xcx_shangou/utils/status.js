//  加载中
function openLoading(title) {
  wx.showLoading({
    title: title,
    icon: 'loading'
  });
};
//  加载成功
function openSucces(title) {
  wx.showToast({
    title: title,
    icon: 'success',
  });
};
//  加载失败
function openFail(title) {
  wx.showToast({
    title: title,
    image: '../../images/fail.png'
  });
}

module.exports = {
  openLoading: openLoading,
  openSucces: openSucces,
  openFail: openFail
}