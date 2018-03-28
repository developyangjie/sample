<?php
require_once 'db.php';
require_once 's_login.php';
require_once 's_user_new.php';
require_once 'memcache.php';
require_once 'config.php';
header ( "Content-type: text/html; charset=utf-8" );
// 虾丸登录验证
function _xiawanlogin($params) {
	$arr=explode( "|",$params);
	$param_arr=array();
	foreach ($arr as $value)
	{
		
		$date=explode("=",$value);
		if(in_array("$date[0]",array('uid','sid','time')))
		$param_arr["$date[0]"]=$date[1];
		if($date[0]=="sign")
		{
			$sign=$date[1];
		}
	}
	$APP_LOGIN_KEY='dIgNklMgsz4s1Utl2yYk';
	// 构建验证key
	$sign_my=_createSign1($param_arr,$APP_LOGIN_KEY);
	if($sign_my==$sign)
	{
		return 1;
	}
	else 
	{
		return array($sign,$sign_my);
	}
}

//内部方法
function _createSign1($params , $appkey){
	if (isset($params["sign"])) {
		unset($params["sign"]);
	}
	if (isset($params["page"])) {
		unset($params["page"]);
	}
	if (isset($params["per"])) {
		unset($params["per"]);
	}
	ksort($params);
	$str  = "";
	foreach($params as $key=>$value){
		$str  .=  $key ."=". $value;
	}
	
// 	return $str.$appkey;
	return md5($str.$appkey);
}
?>