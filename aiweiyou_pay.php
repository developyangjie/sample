<?php
require_once 'db.php';
require_once("platformdb.php");
$appkey="oG2aBZ1ZzyYVb1lF8ZYxR";
define('PAY_LOG_FILE_NAME', 'egret_pay.log');

try {
	//订单号唯一
// 	$orderld=$_POST['orderId'];
	$orderld="42336448584246624645744765563145";
	// 用户id号唯一
// 	$id=$_POST['id'];
	$id="3b8dcc46b16b671b26fe3a6653735029";
	//充值量RMB
// 	$money=$_POST['money'];
	$money="1";
	//时间戳
// 	$time=$_POST['time'];
	$time="1456901968";
	//服务器id
// 	$serverId=$_POST['serverId'];
	$serverId="1";
	//道具id
// 	$goodsId=$_POST['goodsId'];
	$goodsId="1";
	//道具数量
// 	$goodsNumber=$_POST['goodsNumber'];
	$goodsNumber="1";
	//自定义参数
// 	$ext=$_POST['ext'];
	$ext="1";
	//验证码
// 	$sign=$_POST['sign'];
	$sign="6b9c18b9f285a5de50d9dc6110a20a61";
	$params=array(
		'orderId'=>$orderld,
		'id'=>$id,
		'money'=>$money,
		'time'=>$time,
		'serverId'=>$serverId,
		'goodsId'=>$goodsId,
		'goodsNumber'=>$goodsNumber,
		'ext'=>$ext,
		'sign'=>$sign
		
	);
	//验证
	$sign = _createSign($params,$appkey);
	// if ($sign != $params['sign']) {
	// 	echo '{"code":1006,"msg":"sign 错误，验证通不过"}';
	// 	exit();
	// }	
	_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
	$sid=intval($serverId);
	$ver = _sql_fetch_one("select * from server_list where id=$sid");
	$dbhost=$ver['dbhost'];
	$dbname=$ver['dbname'];
	$dbuser=$ver['dbuser'];
	$dbpass=$ver['dbpass'];
	sql_connect($dbhost,$dbuser,$dbpass,$dbname);
	$order=sql_fetch_one("select * from recharge where orderid=$orderld");
	if(!isset($order))
	{	
		$uid=intval(sql_fetch_one_cell("select uid from cuser where loginname='$id'"));
		if(isset($uid))
		{	
			//获取之前的VIP信息
			$uinfo=sql_fetch_one("select vippay,vip from uinfo where uid=$uid");			
			if(isset($uinfo))
			{							
				$vippay=intval($uinfo['vippay']);
				$viplv=intval($uinfo['vip']);
				//获取此次购买的道具信息
				$buydate=sql_fetch_one("select * from cfg_buydiamond where id=$goodsId");
				$vippay=$vippay+intval($buydate['diamond']);
				
				//计算应该获得多少和加入砖石
				$isbonus= _adddiamond($uid,$buydate);
				
				//根据充值量判断VIP等级
				$cfg = sql_fetch_rows("select * from cfg_vip");
				$min=0;
				$max=0;
				foreach ($cfg as $value)
				{
					$max=$value['pay'];
					if($min<=$vippay&&$vippay<$max)
					{
						$viplv=(intval($value['vip'])-1);
						break;
					}
					$min=$value['pay'];
				}				
				//修改vip
				_payupVIPLevel($uid, $viplv, $vippay);
				//如果是月卡
				if($buydate['id']==1)
				{
					_addmonthcard($uid);
				}
				// 记录订单号
				$params="orderid="."$orderld"."_"."id="."$id"."_"."money="."$money"."_"."time="."$time"."_"."serverId="."$serverId"."_"."goodsId="."$goodsId"."_"."goodsNumber="."$goodsNumber"."_"."ext="."$ext"."_"."sign="."$sign";
				sql_insert("insert into recharge(orderid,uid,buyid,bonus,times,serverid,params) values($orderld,$uid,$goodsId,$isbonus,FROM_UNIXTIME(UNIX_TIMESTAMP()),$serverId,'$params')");
				//插入轮询告知成功
				$ret=sql_fetch_one("select * from upolling where uid=$uid");
				if(isset($ret))
				{
					sql_update("update upolling set state=2 where uid=$uid");
				}
				 
				 echo '{"code":0,"msg":"成功"}';
			}
			else 
			{
				echo '{"code":1007,"msg":"该用户不存在"}';
			}
		}
		else 
		{
			echo '{"code":1008,"msg":"该用户不存在"}';
		}
	}
	else 
	{
		echo '{"code":1009,"msg":"重复订单"}';
	}
}
catch (Exception $e)
{
	echo $e->getMessage();
	@file_put_contents(PAY_LOG_FILE_NAME, "===============error=====================" . $e->getMessage() . "\n" . $e->getTrace() . "\n", FILE_APPEND);
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
 
 	return md5($str.$appkey);
 }
  
function _addmonthcard($uid)
{
	$process=sql_fetch_one_cell("select process from udailytask where uid=$uid and tid=1019");
	if($process==1)
	{
		$num=30;
	}
	else
	{
		$num=29;
		sql_update("update udailytask set process=1 where uid=$uid and tid=1019");
	}
	$nowtime = time();
	$nowdaya = strtotime(date("Y-m-d", $nowtime));
	$nowdayb=($num*86400);
	$nowdayc=$nowdayb+$nowdaya;
	
	sql_update("insert into umonthcard(uid,num,time) values($uid,0,$nowdayc) on duplicate key update num=0,time=time+$nowdayb");
}

//加入砖石
function  _adddiamond($uid,$buydate)
{
	$goodsid=intval($buydate['id']);
	$diamond=intval($buydate['diamond']);
	$bonus=$buydate['bonus'];
	$isbouns=0;
	//首次加上奖励
	$isbonus=sql_fetch_one("select * from ubuybonus where uid=$uid and id=$goodsid");
	if(!isset($isbonus))
	{
		$isbouns=1;
		$bonus_arr=explode(",",$bonus);
		switch (intval($bonus_arr[0]))
		{
// 			case 1:
// 				$diamond=+$bonus;
// 				break;
			case 2:
				
				$diamond=$diamond+intval($bonus_arr[1]);
				break;
		}
		sql_insert("insert into ubuybonus(uid,id) values($uid,$goodsid)");
	}
	//修改砖石量
	sql_update("update uinfo set ug=ug+$diamond where uid = $uid");
	return $isbouns;
}


//VIP等级提升
function _payupVIPLevel($uid, $viplevel,$pay)
{
	//修改VIP等级和VIP充值量
	sql_update("update uinfo set vip = $viplevel, vippay = $pay where uid = $uid");
	
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if($cfg){
        $partnerbag = intval($cfg['partnerbag']);
        if($partnerbag > 0){
            sql_update("update uinfo set partnerbag = $partnerbag where uid = $uid");
        }
        $equipbag = intval($cfg['equipbag']);
        if($equipbag > 0){
            sql_update("update uinfo set bag = $equipbag where uid = $uid");
        }
    }
}















?>