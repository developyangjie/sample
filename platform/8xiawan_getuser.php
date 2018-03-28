<?php
require_once '../db.php';
require_once '../platformdb.php';
require_once '../config.php';
$appkey="dIgNklMgsz4s1Utl2yYk";

try {
	$sid=intval($_POST['sid']);
	$uid=$_POST['uid'];
	$sign=$_POST['sign'];
	$params=array(
			'uid'=>$uid,
			'sid'=>$sid
	);
	//验证
	$sign_My = _createSign($params,$appkey);
	if ($sign != $sign_My) {
		echo '{"code":1001,"msg":"sign 错误，验证通不过"}';
		exit();
	}
	else
	{
		$serverid=$sid;
	}	
	_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
	$ver = _sql_fetch_one("select id from server_list where id=$serverid");
	if(isset($ver))
	{
		sql_connect_by_sid($serverid);
		$cuid=intval(_sql_fetch_one_cell("select uid from cuser where loginname='$uid'"));
		if($cuid>0)
		{
			$uid_arr=sql_fetch_rows("select uid as id,uname as name,ulv as level from uinfo where cuid=$cuid and serverid=$serverid");
			if(isset($uid_arr))
			{
				foreach ($uid_arr as $key=>$value)
				{
					$uid_arr[$key]['power']="0";	
					$uid_arr[$key]['top']="0";	
				}
				$ret_arr=array(
					'players'=>$uid_arr,
					'code'=>0,
					'msg'=>"获取角色成功"
				);
				echo json_encode($ret_arr);
			}
		}
		else 
		{
			echo '{"players":"","code":0,"msg":"没有角色"}';
		}
	}
	else 
	{
		echo '{"players":"","code":1005,"msg":"服务器id不存在"}';
	}
	
}
catch (Exception $e)
{
	echo $e->getMessage();
}

//内部方法
function _createSign($params , $appkey){
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
	//  	return $str.$appkey;
	return md5($str.$appkey);
}

?>