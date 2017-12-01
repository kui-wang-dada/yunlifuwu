<?php
header("content-type:text/html;charset=utf-8");
require_once '../config/config.php';
require_once '../functions/common.func.php';
require_once '../functions/mysql.func.php';
$link = connect2($config);
$table = "student";
$id = isset($_GET["id"])?$_GET["id"]:"";
if($id==""){
    $username = $_GET["username"];
    $age = $_GET["age"];
    $sex = $_GET["sex"];
    $data = compact('username','age','sex');
    $res = insert($link, $data, $table);
    if($res){
        alertMes("插入数据成功", "layout-index.php");
    }else{
        alertMes("插入失败", "layout-index.php");
    }
}else{
    delete($link, $table,"id = ".$id);
}