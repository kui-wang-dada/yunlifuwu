<template xmlns:v-on="http://www.w3.org/1999/xhtml" xmlns:v-bind="http://www.w3.org/1999/xhtml">
  <div id="app">
    <h1 v-html="msg"></h1>
    <input type="text" v-model="itemNew" v-on:keyup.enter="addNew">
    <ul>
      <li v-for="item in items" v-bind:class="{isStudent:item.student}" v-on:click="turnRed(item)">
        {{item.name}}
      </li>
    </ul>
    <ul>
      <li><router-link to="/hello">首页</router-link></li>
      <li><router-link to="/first">first</router-link></li>
      <li><router-link to="/second">second</router-link></li>
    </ul>
    <div>
      <router-view></router-view>
    </div>
    <mt-button @click.native="handleClick">按钮</mt-button>
  </div>
</template>
<script>

import Storage from './localstorage'



export default {
  name: 'app',
  data(){
    return{
      msg:'Type name and mark who is student',
      items:Storage.fetch(),
      itemNew:''
    }
  },
  methods:{
    handleClick:function(){
      this.$toast('Hello world!')
    },
    turnRed:function(item){
      item.student=!item.student;
    },
    addNew:function(){
      this.items.push({
        name:this.itemNew,
        student:false
      })
      this.itemNew=null;
    }
  },
  watch:{
    items:{
      handler:function(items){
        Storage.save(items);
      },
      deep:true
    }
  }

}
</script>

<style>
  .isStudent{
    color:red;
  }
#app {
  font-family: 'Avenir', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
