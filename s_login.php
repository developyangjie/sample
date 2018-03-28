<?php
define("LOGIN_FAIL", "登入失敗!");
define("LOGIN_FAIL_VERIFY", "平臺登入驗證錯誤!");
header("Access-Control-Allow-Origin: *");
require_once 'log.php';

function loginAccount1($params)
{
    return _loginAccountNew($params);
}

// 2.4.0开始用这个方法,成功返回array(1,uidsarray(uid,.../*<not use>*/),msgsarray(msg(uid,uidkey,uinfo(uname,...))))
function _loginAccountNew($params)
{	
    require_once 'cmd.php';
    $platform = $params[0];
    $loginname = urldecode($params[1]);
    $pwd = $params[2];
    $devicemodel = "0";
    $devicesystem = "0";
    $deviceid = "0";
    $resolution = "0";
    $systemversion = "0";
    $cid = "0";
    $token = "0";
    $subchannelid = "0";
    if(count($params) > 3){
        $serverid = $params[3];
        $devicemodel = $params[4];
        $devicesystem = $params[5];
        $deviceid = $params[6];
        $resolution = $params[7];
        $systemversion = $params[8];
        $cid = $params[9];
    }
    if(count($params) > 10){
        $token = $params[10];
        $subchannelid = $params[11];
    }
    
    switch ($platform) {
    	case "egret":
    		return _loginEgretNew($loginname, $pwd, $platform);
    		break;
    	case "aiweiyou":
    		
    		return _loginAiweiyouNew($loginname, $pwd, $platform);
    		break;
        case "local":
            return _loginLocalNew($loginname, $pwd, $platform);
            break;
        case "anysdk":
            return _loginAnySdk($loginname, $pwd, $platform,$serverid,$devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid);
            break;
        case "8xiawan":
            return _loginXiawan($loginname, $pwd, $platform);
            break;
        case "8xiawanh5":
            return _loginXiawan($loginname, $pwd, $platform);
            break;
        case "bili":
            return _loginBili($loginname, $pwd, $platform,$serverid,$devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid);
            break;
    }
    return array(
        0,
        "unknown platform type:" . $platform
    );
}
//解锁压缩
function deci($ps)
{
	$encryption=array(
			'w2/'=>'0',
			'g12'=>'1',
			'mjy'=>'2',
			'g#e'=>'3',
			'%e1'=>'4',
			'*/&'=>'5',
			'4w1'=>'6',
			'!q4'=>'7',
			'w9)'=>'8',
			'd+s'=>'9'

	);
	$arr=str_split($ps,3);

	$newarr=array();
	foreach($arr as $key=>$value)
	{
		$new=$encryption[$value];
		if(!isset($new))
		{
			return 0;
		}
		array_push($newarr,$new);
	}
	$wd=implode($newarr);
	return $wd;

}
//生产验证码
function ecry()
{
	$encryption=array(
			'0'=>'w2/',
			'1'=>'g12',
			'2'=>'mjy',
			'3'=>'g#e',
			'4'=>'%e1',
			'5'=>'*/&',
			'6'=>'4w1',
			'7'=>'!q4',
			'8'=>'w9)',
			'9'=>'d+s'
	);
// 	$sum=rand(256,430);
// 	$sum=$sum*2+1;
// 	$sums=rand(111,245);
// 	$sums=$sums*2+1;
// 	$num=rand(100,200);
	$time=time();
	$st=(string)$time;
// 	$ed=(($num+13)*$sum-6)*$sums;
// 	$st=(string)$ed;
// 	$sts=(string)$sum;
// 	$stss=(string)$sums;
	$arr=str_split($st);
// 	$arrs=str_split($sts);
// 	$arrss=str_split($stss);
	$newarr=array();
// 	$newarrs=array();
// 	$newarrss=array();


// 	foreach($arrs as $keys=>$values)
// 	{
// 		$news=$encryption[$values];
// 		array_push($newarr,$news);
			
// 	}

// 	foreach($arrss as $keyss=>$valuess)
// 	{
// 		$newss=$encryption[$valuess];
// 		array_push($newarr,$newss);
// 	}
	foreach($arr as $key=>$value)
	{
		$new=$encryption[$value];
		array_push($newarr,$new);
	}
	return implode("",$newarr);
}


function loginCheck($uid,$params)
{
	return array(1);
}

function _getUserId($loginId, $pwd, $platform)
{
    $uid = intval(sql_fetch_one_cell("select uid from cuser where loginname='$loginId'"));
    if ($uid == 0) {
        if (strlen($pwd) > 60) {
            $pwd = substr($pwd, 0, 60);
        }
        $uid = sql_insert("insert into cuser(loginname,pwd,ts,platform)values('$loginId','$pwd',unix_timestamp(),'$platform')");
    }
    return $uid;
}

function _getUserIdNew($loginId, $pwd, $platform, $channel)
{
    $uid = intval(sql_fetch_one_cell("select uid from cuser where loginname='$loginId'"));
    if ($uid == 0) {
        if (strlen($pwd) > 60) {
            $pwd = substr($pwd, 0, 60);
        }
        if (strlen($channel) > 32) {
            $channel = substr($channel, 32);
        }
        $uid = sql_insert("insert into cuser(loginname,pwd,ts,platform,publickey)values('$loginId','$pwd',unix_timestamp(),'$platform','$channel')");
    }
    return $uid;
}

function _loginLocal($loginname, $pwd, $platform)
{
    $db_uinfo = sql_fetch_one("SELECT * FROM cuser WHERE loginname='$loginname'");
    if ($db_uinfo == null) {
        $db_uid = sql_insert("INSERT INTO cuser (loginname,pwd,ts,platform) VALUES ('$loginname','$pwd',unix_timestamp(),'$platform')");
        $uid = $db_uid;
        return array(
            1,
            $uid
        );
    }
    if ($pwd != $db_uinfo['pwd']) {
        return array(
            0,
            "密碼錯誤"
        );
    } else {
        $uid = intval($db_uinfo['uid']);
        return array(
            1,
            $uid
        );
    }
    return array(
        0,
        LOGIN_FAIL
    );
}

function _loginAnySdk($loginname, $pwd, $platform, $serverid,$devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid)
{
    $db_uinfo = sql_fetch_one("SELECT * FROM cuser WHERE loginname='$loginname'");
    if ($db_uinfo == null) {
        return array(
            0,
            LOGIN_FAIL
        );
    }
    if ($pwd != $db_uinfo['pwd']) {
        return array(
            0,
            "密碼錯誤"
        );
    } else {
        $sid = intval($db_uinfo['serverid']);
        if(!$sid){
            sql_update("update cuser set serverid = '$serverid', cid = '$cid' where loginname='$loginname'");
        }
        //处理注册数据
        _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
        $register = _sql_fetch_one("SELECT * FROM register WHERE accid='$loginname'");
        if($register){
            $model = intval($register['devicemodel']);
            //echo "$devicemodel";
            if(!$model){
                _sql_update("update register set serverid = '$serverid',devicemodel = '$devicemodel', devicesystem = '$devicesystem', deviceid = '$deviceid',resolution = '$resolution',systemversion = '$systemversion' where accid='$loginname'");
            }
        }
        $uid = intval($db_uinfo['uid']);
        return array(
            1,
            $uid,
            _getUidKey($uid)
        );
    }
    return array(
        0,
        LOGIN_FAIL
    );
}

function _InsertloginAnySdk($loginname, $pwd, $platform, $serverid,$platformid,$channelid,$subchannel,$accountid)
{
    $db_uinfo = sql_fetch_one("SELECT * FROM cuser WHERE loginname='$loginname'");
    if ($db_uinfo == null) {
        $db_uid = sql_insert("INSERT INTO cuser (loginname,pwd,ts,platform,serverid,platformid,channelid,subchannel,accountid) VALUES ('$loginname','$pwd',unix_timestamp(),'$platform','$serverid','$platformid','$channelid','$subchannel','$accountid')");
        $uid = $db_uid;
        $logparams = array($serverid,$platformid,$channelid,$subchannel,$accountid,"0","0","0","0","0");
        registerlog($logparams);
        return array(
            1,
            $uid
        );
    }
    if ($pwd != $db_uinfo['pwd']) {
        return array(
            0,
            "密碼錯誤"
        );
    } else {
        $uid = intval($db_uinfo['uid']);
        return array(
            1,
            $uid
        );
    }
    return array(
        0,
        LOGIN_FAIL
    );
}



function _loginLocalNew($loginname, $pwd, $platform)
{
		_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $db_user = _sql_fetch_one("select * from cuser where loginname='$loginname' and pwd='$pwd' limit 1");
    if (!$db_user) {
    	$uid = _sql_insert("insert into cuser(loginname,pwd,ts,platform) values('$loginname','$pwd',unix_timestamp(),'$platform')");
    } else {
    	$uid = intval(_sql_fetch_one_cell("select uid from cuser where loginname='$loginname'"));
    }
    $serlist = _sql_fetch_rows("select id,name,ip,spareip,chatip,chatport,type,state from server_list order by id asc");
        return array(
                1,
                $uid,
        		$serlist
        );
}


function _loginEgretNew($loginname, $pwd, $platform)
{
	_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
	require_once 'platform/egret_verify.php';
// 	$mid = $loginname;
// 	$loginname =$loginname;
	$users = _sql_fetch_one("select uid from cuser where loginname='$loginname' and pwd='$pwd'");
	if (! $users) { // /不存在的话去重新验证下
		$platformInfo = _checkLoginEgret();
		if ($platformInfo) {
			$logined = $platformInfo[0];
			if ($logined==1) { // 已登录
				$ret = _sql_update("update cuser set pwd='$pwd' where loginname='$loginname'");
				if (! $ret) { // 不存在
					$uid = _sql_insert("insert into cuser(loginname,pwd,ts,platform)values('$loginname','$pwd',unix_timestamp(),'$platform')");
				} else {
					$uid = intval(_sql_fetch_one_cell("select uid from cuser where loginname='$loginname'"));
				}
				$serlist = _sql_fetch_rows("select id,name,type,state from server_list order by id asc");
				return array(
						1,
						$uid,
						$serlist
				);
			} else { // 未登录
				$msg = $platformInfo['errcode'];
				return array(
						2,
						"login error code:" . $msg
				);
			}
		} else {
			return array(
					5,
					LOGIN_FAIL_VERIFY
			);
		}
	} else {
		$serlist = _sql_fetch_rows("select id,name,type,state from server_list");
		$uid = intval($users['uid']);
		return array(
				1,
				$uid,
				$serlist
		);
	}
	return array(
			0,
			LOGIN_FAIL
	);
}



function _loginAiweiyouNew($loginname, $pwd, $platform)
{
	_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
// 	require_once 'platform/egret_verify.php';
	// 	$mid = $loginname;
	// 	$loginname =$loginname;
	$users = _sql_fetch_one("select uid from cuser where loginname='$loginname' and pwd='$pwd'");
	if (! $users) { // /不存在的话去重新验证下
		$platformInfo = _checkLoginEgret();
		if ($platformInfo) {
			$logined = $platformInfo[0];
			if ($logined==1) { // 已登录
				$ret = _sql_update("update cuser set pwd='$pwd' where loginname='$loginname'");
				if (! $ret) { // 不存在
					$uid = _sql_insert("insert into cuser(loginname,pwd,ts,platform)values('$loginname','$pwd',unix_timestamp(),'$platform')");
				} else {
					$uid = intval(_sql_fetch_one_cell("select uid from cuser where loginname='$loginname'"));
				}
				$serlist = _sql_fetch_rows("select id,name,type,state from server_list order by id asc");
				return array(
						1,
						$uid,
						$serlist
				);
			} else { // 未登录
				$msg = $platformInfo['errcode'];
				return array(
						2,
						"login error code:" . $msg
				);
			}
		} else {
			return array(
					5,
					LOGIN_FAIL_VERIFY
			);
		}
	} else {
		$serlist = _sql_fetch_rows("select id,name,type,state from server_list");
		$uid = intval($users['uid']);
		return array(
				1,
				$uid,
				$serlist
		);
	}
	return array(
			0,
			LOGIN_FAIL
	);
}


function _loginXiawan($loginname, $pwd, $platform)
{
	$ret=_xiawanlogin($pwd);
	if($ret==1)
	{
		_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
	    $db_user = _sql_fetch_one("select * from cuser where loginname='$loginname'  limit 1");
	    if (!$db_user) {
	    	$uid = _sql_insert("insert into cuser(loginname,ts,platform) values('$loginname',unix_timestamp(),'$platform')");
	    } else {
	    	$uid = intval(_sql_fetch_one_cell("select uid from cuser where loginname='$loginname'"));
	    }
	    $serlist = _sql_fetch_rows("select id,name,type,state from server_list order by id asc");
	    return array(
	        1,
	        $uid,
	        $serlist
	    );
	}
	else 
	{
		return array(0,"验证码不对");
	}
}


//注册账号
function registeredAccount($params)
{
    $loginname = urldecode($params[0]);
    $pwd = $params[1];
    $db_user = sql_fetch_one("select * from cuser where loginname='$loginname' and pwd='$pwd' limit 1");
    if ($db_user) {
        return array(
            0,
            STR_Account_Exist
        );
    } else {
        $db_uinfos = sql_fetch_rows("SELECT * FROM cuser WHERE loginname='$loginname'");
        if (! $db_uinfos || count($db_uinfos) == 0) {
            $db_uid = sql_insert("INSERT INTO cuser (loginname,pwd,ts,platform) VALUES ('$loginname','$pwd',unix_timestamp(),'local')");
            $uid = $db_uid;
            return array(
                1,
                $uid
            );
        }
    }
}

//bilibili登录
function _loginBili($loginname, $pwd, $platform,$serverid,$devicemodel,$devicesystem, $deviceid,$resolution,$systemversion,$cid)
{
    require_once 'platform/bili_loginVerify.php';
    $res = _biliAccVerify($loginname);
    if($res['code'] == 0){
        $open_id = $res['open_id'];
        _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
        $db_user = _sql_fetch_one("select * from cuser where loginname='$open_id' limit 1");
        if (!$db_user) {
            $cuid = _sql_insert("insert into cuser(loginname,ts,platform,serverid,platformid,channelid,accountid,cid) values('$open_id',unix_timestamp(),'$platform','0','$pwd','$pwd','$open_id','$cid')");
            //处理注册数据
            _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
            _sql_update("insert into `register` (cuid, platformid, channelid, subchannel, accid, devicemodel, devicesystem, deviceid,resolution,systemversion,time) values('$cuid', '0', '$pwd', '0', '$open_id', '$devicemodel', '$devicesystem','$deviceid', '$resolution','$systemversion',FROM_UNIXTIME(UNIX_TIMESTAMP()))");
            $starttime = microtime(true);
            $datestr = date('Y-m-d h:i:sa',time());
            $requestid = date('Ymdhisa',time());
            $requestid = $uid.$requestid;
            $endtime = microtime(true);
            $lasttime = (int) (($endtime - $starttime) * 1000);
            $content = 'PlayerRegister|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_loginXiawan'.'|'.$requestid.'|'.$serverid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$devicesystem.'|'.$systemversion.'|'.$systemversion.'|'.$devicemodel;
            _createlogfile('hylr',$content);
        } else {
            $db_user = _sql_fetch_one("select * from cuser where loginname='$open_id'");
            $cuid = intval($db_user['uid']);
            $usercid = $db_user['cid'];
            if(isset($cid) && strcmp($cid, $usercid) != 0){
                _sql_update("update cuser set cid = $cid where loginname='$open_id'");
            }
        }
        $serlist = _sql_fetch_rows("select id,name,ip,spareip,chatip,chatport,type,state from server_list order by id asc");
        return array(
            1,
            $cuid,
            $serlist
        );
    }
    return array(
        0,
        LOGIN_FAIL
    );
}

?>