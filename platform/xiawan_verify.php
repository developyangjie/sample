<?php
require_once 'db.php';
require_once 's_login.php';
require_once 's_user_new.php';
require_once 'memcache.php';
header ( "Content-type: text/html; charset=utf-8" );

define('APPID_XIAWAN', "10004");
define('APPKEY_XIAWAN', '9S2lElyabIVNFpyPlb6B');
define('LOGINURL','http://www.8xiawan.com/sdk/auth/server_check');
// 虾丸登录验证
function _xiawanlogin($open_id, $token, $platform,$serverid,$devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid,$channelid,$subchannelid)
{
    $time = time();
    $signarr = array('app_id' => APPID_XIAWAN, 'open_id' => $open_id, 'token' => $token, 'time' => $time);
    // 构建验证key
    ksort($signarr);
    $signstr = "";
    $poststr = "";
    foreach ($signarr as $k => $v){
        $signstr = $signstr.$k."=".$v;
        if(empty($poststr))
        {
            $poststr = $k."=".$v;
        }
        else{
            $poststr = $poststr."&".$k."=".$v;
        }
    }
    $signstr = $signstr.APPKEY_XIAWAN;
    $sign = md5($signstr);
    $poststr = $poststr."&"."sign"."=".$sign;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, LOGINURL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $poststr );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0 );
    curl_setopt($ch, CURLOPT_TIMEOUT, 30 );
    $result = curl_exec($ch);
    curl_close($ch);
    $res=json_decode($result,true);
    if($res['state'] == 'success' && $res['status'] == 1)
    {
        // 插入帐号
        $ret = _xiaWan($open_id,$channelid, $platform, $serverid,$devicemodel, $devicesystem, $deviceid,$resolution,$systemversion,$cid,$subchannelid, $token);
        return $ret;
    }
    else{
        return array(0,$res['info']);
    }

}

//创建帐号
function _xiaWan($loginname, $pwd, $platform, $serverid, $devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid,$subchannelid, $token)
{
    $db_uinfo = sql_fetch_one ( "SELECT * FROM cuser WHERE loginname='$loginname'" );
    if ($db_uinfo == null) {
        $db_uid = sql_insert ( "INSERT INTO cuser (loginname,pwd,ts,platform,serverid,platformid,channelid,subchannel,accountid) VALUES ('$loginname','$pwd',unix_timestamp(),'$platform','$serverid','$devicesystem','$pwd','$subchannel','$loginname')" );
        $uid = $db_uid;
        $logparams = array($serverid,$platform,$pwd,$subchannelid,$loginname,"0","0","0","0","0");
        registerlog($logparams);
        return array (
            1,
            $uid,
            _getUidKey($uid)
        );
    }
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $register = _sql_fetch_one("SELECT * FROM register WHERE accid='$loginname'");
    if($register){
        $model = intval($register['devicemodel']);
        if(!$model){
            _sql_update("update register set serverid = '$serverid',devicemodel = '$devicemodel', devicesystem = '$devicesystem', deviceid = '$deviceid',resolution = '$resolution',systemversion = '$systemversion' where accid='$loginname'");
        }
    }
    if ($pwd != $db_uinfo ['pwd']) {
        return array (
            0,
            "密碼錯誤"
        );
    } else {
        $uid = intval ( $db_uinfo ['uid'] );
        return array (
            1,
            $uid,
            _getUidKey($uid)
        );
    }
    return array (
        0,
        LOGIN_FAIL
    );
}


?>
