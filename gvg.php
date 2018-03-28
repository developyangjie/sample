<?php

require_once 'platformdb.php';

date_default_timezone_set('Asia/Shanghai');

function _getSystemData($uid,&$logparams)
{
	$cuid=intval(sql_fetch_one_cell("select cuid from uinfo where uid=$uid"));
    $cuser = _sql_fetch_one("select * from cuser where uid = $cuid");
    if(isset($cuser)){
        $serverid = $cuser['serverid'];
        if(!$serverid){
            $serverid = "1";
        }
        $platformid = $cuser['platformid'];
        if(!$platformid){
            $platformid = "未知";
        }
        $channelid = $cuser['channelid'];
        if(!$channelid){
            $channelid = "未知";
        }
        $subchannel = $cuser['subchannel'];
        if(!$subchannel){
            $subchannel = "未知";
        }
        $accountid = $cuser['accountid'];
        if(!$accountid){
            $accountid = "未知";
        }
       return  $logparams = array($serverid,$platformid, $channelid, $subchannel, $accountid);
    }
}

//激活
function activatelog($params)
{
    $platformid = $params[0];
    $channelid = $params[1];
    $subchannel = $params[2];
    $deviceid = $params[3];
    $resolution = $params[4];
    $systemversion = $params[5];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $log = _sql_fetch_one("select * from activate where deviceid = '$deviceid' and 	channelid = '$channelid' and subchannel = '$subchannel'");
    if(!$log){
        _sql_insert("insert into `activate` (platformid, channelid, subchannel, deviceid, resolution, systemversion, time) values('$platformid','$channelid','$subchannel','$deviceid','$resolution','$systemversion',FROM_UNIXTIME(UNIX_TIMESTAMP()))");   
    }
    return array(
        1
    );
}

//注册
function registerlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $devicemodel = $params[5];
    $devicesystem = $params[6];
    $deviceid = $params[7];
    $resolution = $params[8];
    $systemversion = $params[9];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `register` (serverid, platformid, channelid, subchannel, accid, devicemodel, devicesystem, deviceid,resolution,systemversion,time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', '$devicemodel', '$devicesystem','$deviceid', '$resolution','$systemversion',FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//创建角色
function rolebuildlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $uname = $params[6];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `rolebuild` (serverid, platformid, channelid, subchannel, accid, uid, uname, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', $uid, '$uname', FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//登录日志
function loginlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $uname = $params[6];
    $level = $params[7];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `login` (serverid, platformid, channelid, subchannel, accid, uid, uname, level, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', $uid, '$uname', $level, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//退出日志
function logoutlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $accname = $params[5];
    $uid = $params[6];
    $uname = $params[7];
    $level = $params[8];
    $onlinetime = $params[9];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `logout` (serverid, platformid, channelid, subchannel, accid, accname, uid, uname, level, onlinetime, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', '$accname', $uid, '$uname', $level,$onlinetime, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//充值日志
function rechargelog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $amount = $params[7];
    $transactionid = $params[8];
    $productid = $params[9];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `recharge` (serverid, platformid, channelid, subchannel, accid, uid, level, amount, transactionid, productid, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', $uid, $level, $amount, '$transactionid', '$productid', FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//货币获得
function acquirelog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $cause = $params[7];
    $count = $params[8];
    $total = $params[9];
    $type = $params[10];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `acquire` (serverid, platformid, channelid, subchannel,accid, uid, level, cause, count, total, itemid, time) values($serverid, '$platformid','$channelid','$subchannel', '$accid', $uid, $level, '$cause', $count, $total, $type, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//货币消耗
function moneycostlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $cause = $params[7];
    $content = $params[8];
    $count = $params[9];
    $total = $params[10];
    $type = $params[11];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `moneycost` (serverid, platformid, channelid, subchannel,accid, uid, level, cause, content, count, total, itemid, time) values($serverid,'$platformid','$channelid','$subchannel', '$accountid', $uid, $level, '$cause', '$content', $count, $total, $type, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//获得物品
function getitemlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $itemid = $params[7];
    $itemname = $params[8];
    $cause = $params[9];
    $count = $params[10];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `getitem` (serverid, platformid, channelid, subchannel,accid, uid, level, itemid, itemname, cause, count, time) values($serverid, '$platformid','$channelid','$subchannel','$accountid', $uid, $level, $itemid, '$itemname', '$cause', $count, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//失去物品
function removeitemlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $itemid = $params[7];
    $itemname = $params[8];
    $cause = $params[9];
    $count = $params[10];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `removeitem` (serverid, platformid, channelid, subchannel,accid, uid, level, itemid, itemname, cause, count, time) values($serverid, '$platformid','$channelid','$subchannel','$accountid', $uid, $level, $itemid, '$itemname', '$cause', $count, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//完成任务
function finishtasklog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $taskid = $params[7];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `finishtask` (serverid, platformid, channelid, subchannel, accid, uid, level, taskid, time) values($serverid, '$platformid','$channelid','$subchannel','$accountid', $uid, $level, $taskid, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//关卡战斗
function pvefightlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $guanqiaid = $params[7];
    $type = $params[8];
    $result = $params[9];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `pvefight` (serverid, platformid, channelid, subchannel, accid, uid, level, guanqiaid, type, result, time) values($serverid, '$platformid','$channelid','$subchannel','$accountid', $uid, $level, $guanqiaid, $type, $result, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//完成成就
function achievementlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $achievementid = $params[7];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `achievement` (serverid, platformid, channelid, subchannel, accid, uid, level, achievementid, time) values($serverid,'$platformid','$channelid','$subchannel', '$accountid', $uid, $level, $achievementid, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//活动
function activitylog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $activityid = $params[7];
    $subactivityid = $params[8];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `activity` (serverid, platformid, channelid, subchannel, accid, uid, level, activityid, subactid, time) values($serverid, '$platformid','$channelid','$subchannel','$accountid', $uid, $level, $activityid, $subactivityid, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}

//新手引导
function guidelog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $step = $params[6];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $record = _sql_fetch_one("select * from `guide` where uid = $uid and step = $step");
    if(!$record){
        _sql_update("insert into `guide` (serverid, platformid, channelid, subchannel, accid, uid, step, time) values($serverid,'$platformid','$channelid','$subchannel', '$accountid', $uid, $step, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
    }
}

//新手引导
function shoplog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $level = $params[6];
    $viplevel = $params[7];
    $shoptype = $params[8];
    $itemid = $params[9];
    $itemnum = $params[10];
    $money = $params[11];
    $cost = $params[12];
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `shop` (serverid, platformid, channelid, subchannel, accid, uid, level, viplevel, shoptype, itemid, itemnum, money, cost, time) values($serverid,'$platformid','$channelid','$subchannel', '$accountid', $uid, $level, $viplevel, $shoptype, $itemid, $itemnum, $money, $cost, FROM_UNIXTIME(UNIX_TIMESTAMP()))");
}


?>