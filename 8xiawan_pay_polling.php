<?php
function xiawanstartorder($uid,$params)
{
	$ret=_sql_fetch_one("select * from upolling where times>UNIX_TIMESTAMP() and uid=$uid");
	if(isset($ret))
	{
		return array(
				0,
				"订单出错"
		);
	}
	$serverid=$params[1];	
	_sql_insert("insert into upolling(uid,serverid,times,num,state) values($uid,$serverid,UNIX_TIMESTAMP(),0,0) on DUPLICATE KEY update times=UNIX_TIMESTAMP(),num=0,state=0"); 	
	
	
	$param=array(
		'uid'=>$params[0],	
		'sid'=>$params[1],	
		'cpoid'=>$params[2],	
		'itemid'=>$params[3],	
		'money'=>$params[4],	
		'ext'=>$params[5],			
	);
	$sign=_createSign2($param, severNeXiaWanKey);
	return array(1,$sign);
}
//内部方法
function _createSign2($params , $appkey){
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
	return md5($str.$appkey);
}

function xiawanpollingorder($uid,$params)
{
	$ret=_sql_fetch_one("select * from upolling  where uid=$uid");
	if(!isset($ret))
	{
		return array(
				1,
				"订单出错"
		);
	}
	if($ret['times']<(time()-1200))
	{
		return array(
				1,
				"订单超时"
		);
	}
	
	if($ret['num']>=100)
	{
		return array(
				1,
				"轮询次数超出"
		);
	}
	
	if($ret['state']==0)
	{
		return array(
				3
		);
	}
	
	_sql_update("update upolling set num=num+1 where uid=$uid");
	if($ret['state']==1)
	{
		$uinfo=sql_fetch_one("select ug,vip,vippay from uinfo where uid=$uid");
		return array(2,$uinfo);
	}
}
?>