import Vue from 'vue'
import App from './App'
import VueRouter from 'vue-router'
import VueResource from 'vue-resource'

Vue.use(VueRouter)
Vue.use(VueResource)
Vue.config.productionTip = false



// 3.0.2 导入路由规则对应的组件对象
import home from './components/home.vue';
import xiaofei from './components/xiaofeijindu.vue'
import msg from './components/message.vue'



// mint-ui导入
import mintui from 'mint-ui'
import 'mint-ui/lib/style.css'
import '../static/mui/css/mui.css'
Vue.use(mintui)


var router1 = new VueRouter({
  linkActiveClass:'mui-active',  //改变路由激活时的class名称
  routes:[
    {path:'/home',component:home},   //首页
    {path:'/xiaofei',component:xiaofei},
    {path:'/message',component:msg}
  ]
});


new Vue({
  el:'#app',
  // 使用路由对象实例
  router:router1,
  // render:function(create){create(App)} //es5的写法
  render:c=>c(App)  // es6的函数写法 =>：goes to
});
