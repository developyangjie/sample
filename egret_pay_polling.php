<?php
function startorder($uid,$params)
{
	$ret=sql_fetch_one("select * from upolling where times>UNIX_TIMESTAMP() and uid=$uid");
	if(isset($ret))
	{
		return array(
				0,
				"订单出错"
		);
	}
		
	sql_insert("insert into upolling(uid,times,num,state) values($uid,UNIX_TIMESTAMP(),0,0) on DUPLICATE KEY update times=UNIX_TIMESTAMP(),num=0,state=0"); 	
	return array(1);
}


function pollingorder($uid,$params)
{
	$ret=sql_fetch_one("select * from upolling  where uid=$uid");
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
	
	sql_update("update upolling set num=num+1 where uid=$uid");
	if($ret['state']==1)
	{
		$uinfo=sql_fetch_one("select ug,vip,vippay from uinfo where uid=$uid");
		return array(2,$uinfo);
	}
}

function makesign($uid,$params){
	$param=array(
		'action'=>$params[0],	
		'appId'=>$params[1],		
		'serverId'=>$params[2],		
		'time'=>$params[3],		
		'token'=>$params[4]		
			
	);
	$appkey='oG2aBZ1ZzyYVb1lF8ZYxR';
	$sign_str='';
	foreach ($param as $key=>$value)
	{
		$sign_str .=$key.'='.$value;
	}
	$sign=md5($sign_str.$appkey);
	return array(1,$sign);
	
	
}


?>