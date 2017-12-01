<?php
//连接数据库
function connect1($host, $user, $password, $charset, $database)
{
    $link = mysqli_connect($host, $user, $password) or die('数据库连接失败<br/>ERROR' . mysqli_connect_errno() . ":" . mysqli_connect_error());
    mysqli_set_charset($link, $charset);
    mysqli_select_db($link, $database) or die ("指定的数据库打开失败<br/>ERROR" . mysqli_errno($link) . ":" . mysqli_error($link));
    return $link;
}

function connect2($config)
{
    $link = mysqli_connect($config['host'], $config['user'], $config['password']) or die('数据库连接失败<br/>ERROR' . mysqli_connect_errno() . ":" . mysqli_connect_error());
    mysqli_set_charset($link, $config['charset']);
    mysqli_select_db($link, $config['dbName']) or die ("指定的数据库打开失败<br/>ERROR" . mysqli_errno($link) . ":" . mysqli_error($link));
    return $link;
}

//增删改查
function insert($link, $data, $table){
    $keys = join (",", array_keys( $data ) );
    $vals = "'" . join("','", array_values( $data ) ) . "'";
    $query = "INSERT {$table}({$keys}) VALUES({$vals})";
    $res = mysqli_query( $link, $query );
    if ($res) {
        return mysqli_insert_id($link);
    } else {
        return false;
    }
}

function update($link, $data, $table, $where = null)
{
    $set = '';
    foreach ($data as $key => $val) {
        $set .= "{$key}='{$val}',";
    }
    $set = trim($set, ',');
    $where = $where == null ? '' : 'WHERE' . $where;
    $query = "update {$table} set{$set} {$where}";
    $res = mysqli_query($link, $query);
    if ($res) {
        return mysqli_affected_rows($link);
    } else {
        return false;
    }
}

function delete($link, $table, $where = null){
    $where = $where ? ' WHERE ' . $where : '';
    $query = "DELETE FROM {$table} {$where}";
    $res = mysqli_query($link, $query);
    if ($res) {
        return mysqli_affected_rows($link);
    } else {
        return false;
    }
}

function fetchOne($link, $query, $result_type = MYSQLI_ASSOC)
{
    $result = mysqli_query($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result, $result_type);
        return $row;
    } else {
        return false;
    }
}

function fetchAll($link, $query, $result_type = MYSQLI_ASSOC)
{
    $result = mysqli_query($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result, $result_type)) {
            $rows[] = $row;
        }
        return $rows;
    } else {
        return false;
    }
}

function getTotalRows($link, $table)
{
    $query = "SELECT COUNT(*) AS totalRows FROM{$table}";
    $result = mysqli_query($link, $query);
    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        return $row['totalRows'];
    } else {
        return false;
    }
}

function getResultRows($link, $query)
{
    $result = mysqli_query($link, $query);
    if ($result) {
        return mysqli_num_rows($result);
    } else {
        return false;
    }

}


function getServerInfo($link)
{
    return mysqli_get_server_info($link);
}

function getClientInfo($link)
{
    return mysqli_get_client_info($link);
}

function getHostInfo($link)
{
    return mysqli_get_host_info($link);
}

function getProtoInfo($link)
{
    return mysqli_get_proto_info($link);
}







