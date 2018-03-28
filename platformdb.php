<?php
require_once 'config.php';

$platconn;

function _sql_query($sql)
{
    if (false && (strstr($sql, "update") || strstr($sql, "delete"))) {
        if (! strstr($sql, "where")) {
            throw new Exception("update or delete query should come with \"where\" condition:" . $sql);
        }
    }
    $starttime = microtime(true);
    global $platconn;
    if (! isset($platconn))
        _sql_connect();
    $result = $platconn->query($sql);
    $err = mysqli_errno($platconn);
    if ($err)
        throw new Exception("SQL query error: $err".mysqli_error($platconn));
    $endtime = microtime(true);
    $lasttime = (int) (($endtime - $starttime) * 1000);
    if ($lasttime >= 300) {
        $executeTimeInfo = $sql . "#" . $lasttime . "#" . time();
        @file_put_contents('../log/'.date('Ymd').'.'.DB_DATABASE.'.dbquery.log', $executeTimeInfo.PHP_EOL,FILE_APPEND);
    }
    return $result;
}

function _sql_check($sql)
{
    $res = _sql_query($sql);
    return $res->num_rows > 0;
}

// 没有结果的话是FALSE
function _sql_fetch_one($sql)
{
    $res = _sql_query($sql);
    try {
        $data = mysqli_fetch_assoc($res);
    } catch (Exception $e) {
        throw new Exception("sql fetch one ERROR:");
    }
    mysqli_free_result($res);
    return $data;
}

function _sql_fetch_rows($sql)
{
    $res = _sql_query($sql);
    
    $data = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    mysqli_free_result($res);
    return $data;
}

function _sql_fetch_one_cell($sql)
{
    $result = _sql_query($sql);
    
    if ((! empty($result)) && ($row = mysqli_fetch_row($result))) {
        mysqli_free_result($result);
        return $row[0];
    } else {
        mysqli_free_result($result);
        return false;
    }
    return 0;
}

function _sql_insert($sql)
{
    global $platconn;
    $result = _sql_query($sql);
    $err = mysqli_error($platconn);
    if ($err)
        throw new Exception("$sql " . STR_UnknowErr);
    return $platconn->insert_id;
}

function _sql_update($sql)
{
    global $platconn;
    $result = _sql_query($sql);
    $err = mysqli_errno($platconn);
    if ($err)
        throw new Exception("$sql " . STR_UnknowErr);
    
    return mysqli_affected_rows($platconn);
}

function _sql_connect($host = LOG_DB_HOST, $username = LOG_DB_USERNAME, $password = LOG_DB_PASSWORD, $database = LOG_DB_DATABASE)
{
    global $platconn;
    $hostInfo = explode(":", $host);
    $platconn = new mysqli("p:" . $hostInfo[0], $username, $password);
    if (mysqli_connect_errno()) {
        echo STR_SystemErr . mysqli_connect_error();
        throw new Exception("database connect error " . mysqli_connect_error());
    }
    $platconn->select_db($database);
    $platconn->query("SET NAMES utf8");
}

?>