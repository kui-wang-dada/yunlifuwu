<?php
#parse("PHP File Header.php")
header("content-type:text/html;charset=utf-8");
$link=mysqli_connect('127.0.0.1','wangkui',"wangkui") or die("链接失败<br/>".mysqli_connect_errno().":".mysqli_connect_error());
mysqli_set_charset($link,'utf8');
mysqli_select_db($link,'school');
$query="insert student1(Names,ID,Age) values('大',6,15)";
$res=mysqli_query($link,$query);
if($res){
    echo "插入数据成功";
}else {
    echo "插入数据失败";
}