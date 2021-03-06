<?php
require_once 'config.php';

$conn;
$skt;

function sql_log($type, $sql, $time)
{
    if ($lasttime > 300) {
        $executeTimeInfo = $commandFunc . "," . $lasttime . "," . time() . "," . $uid;
        @file_put_contents('../log/'.date('Ymd').'.'.DB_DATABASE.'.dbquery.log', $executeTimeInfo.PHP_EOL,FILE_APPEND);
    }
}

function sql_query($sql)
{
    if (false && (strstr($sql, "update") || strstr($sql, "delete"))) {
        if (! strstr($sql, "where")) {
            throw new Exception("update or delete query should come with \"where\" condition:" . $sql);
        }
    }
    $starttime = microtime(true);
    global $conn;
    if (! isset($conn))
        sql_connect();
    $result = $conn->query($sql);
    $err = mysqli_errno($conn);
    if ($err)
        throw new Exception("SQL query error: $err".mysqli_error($conn));
    $endtime = microtime(true);
    $lasttime = (int) (($endtime - $starttime) * 1000);
    if ($lasttime >= 300) {
        $executeTimeInfo = $sql . "#" . $lasttime . "#" . time();
        @file_put_contents('../log/'.date('Ymd').'.'.DB_DATABASE.'.dbquery.log', $executeTimeInfo.PHP_EOL,FILE_APPEND);
    }
    return $result;
}

function sql_check($sql)
{
    $res = sql_query($sql);
    return $res->num_rows > 0;
}

// 没有结果的话是FALSE
function sql_fetch_one($sql)
{
    $res = sql_query($sql);
    try {
        $data = mysqli_fetch_assoc($res);
    } catch (Exception $e) {
        throw new Exception("sql fetch one ERROR:");
    }
    mysqli_free_result($res);
    return $data;
}

function sql_fetch_rows($sql)
{
    $res = sql_query($sql);
    
    $data = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    mysqli_free_result($res);
    return $data;
}

function sql_fetch_one_cell($sql)
{
    $result = sql_query($sql);
    
    if ((! empty($result)) && ($row = mysqli_fetch_row($result))) {
        mysqli_free_result($result);
        return $row[0];
    } else {
        mysqli_free_result($result);
        return false;
    }
    return 0;
}

function sql_insert($sql)
{
    global $conn;
    $result = sql_query($sql);
    $err = mysqli_error($conn);
    if ($err)
        throw new Exception("$sql " . STR_UnknowErr);
    return $conn->insert_id;
}

function sql_update($sql)
{
    global $conn;
    $result = sql_query($sql);
    $err = mysqli_errno($conn);
    if ($err)
        throw new Exception("$sql " . STR_UnknowErr);
    
    return mysqli_affected_rows($conn);
}

function sql_connect_by_sid($sid)
{
	require_once 'server_list.php';
 	$serverid = $sid;
	$host=$server_list["$serverid"]['dbhost'];
	$username=$server_list["$serverid"]['dbuser'];
	$password=$server_list["$serverid"]['dbpass'];
	$database=$server_list["$serverid"]['dbname'];
    global $conn;
    $hostInfo = explode(":", $host);
    $conn = new mysqli("p:" . $hostInfo[0], $username, $password);
    if (mysqli_connect_errno()) {
        echo STR_SystemErr . mysqli_connect_error();
        throw new Exception("database connect error " . mysqli_connect_error());
    }
    $conn->select_db($database);
    $conn->query("SET NAMES utf8");
}

function sql_connect($host = DB_HOST, $username = DB_USERNAME, $password = DB_PASSWORD, $database = DB_DATABASE)
{
    global $conn;
    $hostInfo = explode(":", $host);
    $conn = new mysqli("p:" . $hostInfo[0], $username, $password);
    if (mysqli_connect_errno()) {
        echo STR_SystemErr . mysqli_connect_error();
        throw new Exception("database connect error " . mysqli_connect_error());
    }
    $conn->select_db($database);
    $conn->query("SET NAMES utf8");
}

?>