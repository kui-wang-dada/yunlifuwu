import Vue from 'vue'
import App from './App'
import VueRouter from 'vue-router'
import VueResource from 'vue-resource'

Vue.use(VueRouter)
Vue.use(VueResource)
Vue.config.productionTip = false;


/* eslint-disable no-new */

// 导入组件
import hello from './components/HelloWorld.vue'
import first from './components/firstcomponent.vue'
import second from './components/second.vue'


// 引入ui组件
// 4.0 注册mint-ui
// 导入mint-ui的css文件
import Mint from 'mint-ui'

Vue.use(Mint);
import 'mint-ui/lib/style.css'




// 定义路由规则


var router = new VueRouter({
  routes: [
    {path: '/hello', component: hello},
    {path: '/first', component: first},
    {path: '/second', component: second}
  ]
})


new Vue({
  el: '#app',
  router: router,
  render: h => h(App)
})
