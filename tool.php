<?php
require_once 'db.php';
function printgamelog($uid,$params){
	$strlog=implode("_", $params);
	 sql_insert("insert into uorder(orderid,uid,buyid,times,params) values(1,10,1,111,'$strlog')");
	return array(1);
	
}


function createAllPartner($uid,$params){
	$partnerid_arr=sql_fetch_rows("select partnerid from cfg_partner");
	$row=array();
	foreach ($partnerid_arr as $value)
	{
		if(intval($value['partnerid'])<1000){
			$partnerid_date=_createPartner($uid, $value['partnerid']);
			array_push($row, $partnerid_date);
		}
	}
	return array(
		1,
		$row
		
	);
}


function upulvandgoin($uid,$params){
	$exp=sql_fetch_one_cell("select maxexp from cfg_userlv where lv=80");
	sql_update("update uinfo set ulv=80,uexp=$exp,ug=10000000,ucoin=10000000 where uid=$uid");
	$uinfo=sql_fetch_one("select * from uinfo where uid=$uid");
	_addItem($uid, 401, 50);
	return array(
			1,//
			$uinfo,//新的用户数据
			array(408,50)
	);
}

function changePartnerDate($uid,$params){
	//$params[0]等级 
	//$params[1]进阶
	//$params[2]转职
	//$params[3]佣兵id
	$lv=$params[0];
	$starlv=$params[1];
	$quality=$params[2];
	$partnerid=$params[3];
	$ep=sql_fetch_one_cell("select brave_allexp from userlv where uid=$uid and lv=($lv-1)");
	
	sql_update("update upartner set pexp = '$ep', plv = $lv,starlv=$starlv,quality=$quality where uid = $uid and partnerid = $partnerid");
	$partner_data=sql_fetch_one("select * from upartner where uid=$uid");
	return array(
			1,
			$partner_data
	);
	
	
}

function drowpcard($uid,$params){
	$num=$params[0];//抽卡次数
	
	$onegroupid=6;//抽卡类型
	
	if ($onegroupid != 0){ //抽取佣兵
		/*********wxl******************/
		$randt=array();
		$pids=array();
		$randt=sql_fetch_one("select probability from cfg_randomtype where type=2 and subtype=$onegroupid");
		$probabilitys=$randt['probability'];
		$probabilitys_arr=explode(",", $probabilitys);
		 $x=0;
		for($i=0;$i<$num;$i++)
		{
			$min=0;
			$randnum=rand(1, 10000);
				foreach ($probabilitys_arr as $probabilitys_one)
				{
		
				$probabilitys_one_arr=explode("|", $probabilitys_one);
				$max=intval($probabilitys_one_arr[1]);
				$randtypeid=intval($probabilitys_one_arr[0]);
		
					if($randnum>$min&&$randnum<=$max)
					{
					$shopdate=sql_fetch_one("select * from cfg_group where gid = $onegroupid and probability=$randtypeid order by rand() limit 1");
					$pid=intval($shopdate['pid']);
					if($pid!=201)
					{
						$xiyou=intval(sql_fetch_one_cell("select rare from  cfg_partner where partnerid=$pid "));
						if($xiyou==4)
						{
							return $i;
						}
					}
					array_push($pids, $shopdate);
					break;
					}
				$min=$probabilitys_one_arr[1];
				}
		}
			/*************************wxl*************/	
	}
//  	echo $x;
}


// sql_update("update udailytask set ts=UNIX_TIMESTAMP() + 2,process = 0,isGet=0");
// //刷新月卡
// $date=sql_fetch_rows("select * from umonthcard");
// foreach ($date as $value)
// {
// 	$uid=intval($value['uid']);
// 	$nowtime = time();
// 	$nowday = strtotime(date("Y-m-d", $nowtime));
// 	if($value['time']>=$nowday)
// 	{
// 		sql_update("update udailytask set ts=UNIX_TIMESTAMP() + 2,process = 1,isGet=0 where uid=$uid and tid=1019");
// 	}
// }

// echo 111;



?>