<?php
header("content-type:text/html;charset=utf-8");
//建立连接并且返回连接对象
$link=mysqli_connect('127.0.0.1','wangkui',"wangkui") or die("链接失败<br/>".mysqli_connect_errno().":".mysqli_connect_error());
//设置字符集
mysqli_set_charset($link,'utf8');
//打开制定数据库
mysqli_select_db($link,'school');
//想要执行的sql语句
$query="select * from student1";
$result=mysqli_query($link,$query);
if($result&&mysqli_num_rows($result)>0){
    $rows=mysqli_fetch_all($result);
}
print_r($rows);