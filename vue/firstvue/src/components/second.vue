<template>
  <div id="second">
    <h1>i am another page</h1>
    <a href="">written by{{author}}</a>
    <p>
      感谢<a href="">大神的技术指导</a>
    </p>
    <ul>
      <li v-for="article in articles">
        {{article.title}}
      </li>
    </ul>
  </div>
</template>

<script>
  export default {
    data() {
      return {
        author: "微信公众号jinkey",
        articles: [],
      }
    },
    mounted:function(){
      this.$http.jsonp("https://api.douban.com/v2/movie/top250?count=10",{},{
        headers:{

        },
        emulateJSON:true
      }).then(function(response){
        this.articles=response.data.subjects
      },function(response){
        console.log(response)
      });
    }
  }
</script>
