<?php
require_once 'db.php';
require_once 's_login.php';
require_once 's_user_new.php';
require_once 'memcache.php';
header ( "Content-type: text/html; charset=utf-8" );
// 虾丸登录验证
function _xiawanlogin($params) {
	$arr=explode($params, "|");
	$param_arr=array();
	foreach ($arr as $value)
	{
		$date=explode($value, "=");
		$param_arr[$date[0]]=$date[1];
	}
	return $param_arr;
	$APP_LOGIN_KEY="oiJNU9h98Bg";
	$uid = $params [0];
	$loginCheckUrl = "http://cps.8xiawan.cn/sdk/validatelogin";	
	$token = $params [1];
	$td_channelid = $params [2];
	// 构建验证key
 	$time = time () * 1000;
	$sign_my = md5 ( $uid . $token . "$time" . $APP_LOGIN_KEY );
	$post_string ="uid=".$uid."&token=".$token."&sign=".$sign_my."&time=".$time."&td_channelid=".$td_channelid;
// 	$post_string ="uid=".$uid&"token=".$token&"sign=".$sign_my&"time=".$time&"td_channelid=".$td_channelid;
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $loginCheckUrl );
	curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_string );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 0 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
	$result = curl_exec ( $ch );
	$res=json_decode($result);
	if(intval($res->result)!=0)
	{
		return  array(0,$res->message);
	}
	curl_close ( $ch );
	
	// 插入帐号
	$platformid = $params[3];
	$platform = "xiawan";
	$res = _loginXiawanSdk ( $uid, $td_channelid, $platform, "1", "$platformid", $td_channelid, "0", $uid );
	
	if($res[0]==0)
	{
	return array(0,$res[1]);
	
	}
	else
	{
	return array(1,$res[1]);
	
	}
}



//绑定帐号
function xiawanbanding($params) {
	$youke_uid = $params [0];
	$youke_token = $params [1];
	$formal_uid = $params [2];
	$formal_token = $params [3];
	$formal_channelid = $params ['td_channelid'];
	// 判断是否已经绑定
	$uid = sql_fetch_one ( "select uid from cuser where loginname='$formal_uid'" );
	if (isset ( $uid )) {
		return array (
				1,
				"帐号已经绑定成功" 
		);
	}
	// 是否可以绑定
	$uid = sql_fetch_one ( "select uid from cuser where loginname='$youke_uid'" );
	if (! isset ( $uid )) {
		return array (
				0,
				"用户不存在" 
		);
	}
	// 实行绑定
	sql_update ( "update cuser set loginname='$formal_uid',channelid=$formal_channelid,accountid=$formal_uid from where uid=$uid" );
	
	// 验证绑定是否成功
	$uid = sql_fetch_one ( "select uid from cuser where loginname='$formal_uid'" );
	if (isset ( $uid )) {
		return array (
				1,
				"帐号绑定成功" 
		);
	}
}

//创建帐号
function _loginXiawanSdk($loginname, $pwd, $platform, $serverid, $platformid, $channelid, $subchannel, $accountid) {
	$db_uinfo = sql_fetch_one ( "SELECT * FROM cuser WHERE loginname='$loginname'" );
	if ($db_uinfo == null) {
		$db_uid = sql_insert ( "INSERT INTO cuser (loginname,pwd,ts,platform,serverid,platformid,channelid,subchannel,accountid) VALUES ('$loginname','$pwd',unix_timestamp(),'$platform','$serverid','$platformid','$channelid','$subchannel','$accountid')" );
		$uid = $db_uid;
		$logparams = array (
				$serverid,
				$platformid,
				$channelid,
				$subchannel,
				$accountid,
				"0",
				"0",
				"0",
				"0",
				"0" 
		);
		registerlog ( $logparams );
		return array (
				1,
				$uid 
		);
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
				$uid 
		);
	}
	return array (
			0,
			LOGIN_FAIL 
	);
}

?>