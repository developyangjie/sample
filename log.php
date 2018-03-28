<?php

require_once 'platformdb.php';

date_default_timezone_set('Asia/Shanghai');

function _getSystemData($serverid,$cuid,&$logparams)
{
    $cuser = _sql_fetch_one("select * from cuser where uid = $cuid");
    if(isset($cuser)){
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
        $logparams = array($serverid,$platformid, $channelid, $subchannel, $accountid);
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

//注册
function registerlog_uid($params)
{
    $cuid = $params[0];
    $serverid = $params[1];
    $uid = $params[2];
    $accid = $params[3];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `register_uid` (cuid, serverid, uid, accid, time) values('$cuid', '$serverid', '$uid', '$accid', FROM_UNIXTIME(UNIX_TIMESTAMP()))");

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
function cuidloginlog($params)
{
//    file_put_contents("log.txt", print_r($params,true));
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $cuid = $params[5];
    $uname = $params[6];
    $level = $params[7];
    $ip = $params[8];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `login_cuid` (serverid, platformid, channelid, subchannel, accid, cuid, uname, level, ipaddress, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', '$cuid', '$uname', '$level', '$ip', FROM_UNIXTIME(UNIX_TIMESTAMP()))");

}

//登录日志
function uidloginlog($params)
{
    $serverid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $accountid = $params[4];
    $uid = $params[5];
    $uname = $params[6];
    $level = $params[7];
    $ip = $params[8];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `login_uid` (serverid, platformid, channelid, subchannel, accid, uid, uname, level, ipaddress, time) values($serverid, '$platformid', '$channelid', '$subchannel', '$accountid', '$uid', '$uname', '$level', '$ip', FROM_UNIXTIME(UNIX_TIMESTAMP()))");

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
    $orderid = $params[0];
    $platformid = $params[1];
    $channelid = $params[2];
    $subchannel = $params[3];
    $cuid = $params[4];
    $uid = $params[5];
    $amount = $params[6];
    $productid = $params[7];
    $bonus = $params[8];
    $serverid = $params[9];
    $data = $params[10];
    $time = date("Y-m-d H:m:s",time());
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `recharge` (serverid, platformid, channelid, subchannel, cuid, uid, amount, buyid, bonus, time, serverid, params) values($orderid, '$platformid', '$channelid', '$subchannel', $cuid, $uid, $amount, $productid, $bonus, FROM_UNIXTIME(UNIX_TIMESTAMP()), $serverid, '$data')");

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
    $log = _sql_fetch_one("select * from `guide` where uid = $uid and step = $step");
    if($log){
        return;
    }
    _sql_update("insert into `guide` (serverid, platformid, channelid, subchannel, accid, uid, step, time) values($serverid,'$platformid','$channelid','$subchannel', '$accountid', $uid, $step, FROM_UNIXTIME(UNIX_TIMESTAMP()))");

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

//勇者和装备
function partnerandequiplog($params)
{
    $uid = $params[0];
    $id = $params[1];
    $itemid = $params[2];
    $name = $params[3];
    $handle = $params[4];
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_update("insert into `item_record` (uid, id, itemid, name, handle, times) values($uid, $id, $itemid, '$name', $handle, FROM_UNIXTIME(UNIX_TIMESTAMP()))");

}

//
function activityonlinelog($params)
{
    $uid = $params[0];
    $serverid = $params[1];
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $tenmintime = intval($nowtime / 600) * 600;
    $timestr = date('Y-m-d H:i:s',$tenmintime);
    $act = _sql_fetch_one("select * from activity_data where uid = $uid and serverid = $serverid order by times desc LIMIT 1");
    if($act){
        $time = strtotime($act['times']);
        if($nowtime > ($time + 600)){
            _sql_update("update `activity_data` set times='$timestr' where uid = $uid and serverid = $serverid");
            $timepoint = _sql_fetch_one("select * from `activity_count` where serverid = $serverid and times = '$timestr'");
            if(!$timepoint){
                _sql_update("insert into `activity_count` (num, times, serverid) values(1, '$timestr', $serverid)");
            }
            else{
                _sql_update("update `activity_count` set num = num + 1 where serverid = $serverid and times = '$timestr' ");
            }
        }
    }
    else{
        _sql_update("insert into `activity_data` (uid, serverid, times) values($uid, $serverid, '$timestr')");
        $timepoint = _sql_fetch_one("select * from `activity_count` where serverid = $serverid and times = '$timestr'");
        if(!$timepoint){
            _sql_update("insert into `activity_count` (num, times, serverid) values(1, '$timestr', $serverid)");
        }
        else{
            _sql_update("update `activity_count` set num = num + 1 where serverid = $serverid and times = '$timestr' ");
        }
    }
}


//创建日子文件
function _createlogfile($filehead,$content)
{
    $time = date('Ymd_H',time());
    $filename = $filehead.$time."00".".log";
    $content = $content."\n";
    file_put_contents($filename, $content,FILE_APPEND);
}


?>