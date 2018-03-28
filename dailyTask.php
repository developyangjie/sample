<?php

/**
 * 更新日常任务的进度
 * @param unknown $uid
 * @param unknown $taskid
 */
function _updateUTaskProcess($uid,$taskid,$pnum = 1)
{
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    $needlv = intval(sql_fetch_one_cell("select needlv from cfg_dailytask where id = $taskid"));
    if($ulv < $needlv){
        return;
    }
    $giftinfo = sql_fetch_one("select u.*, c.*,from_unixtime(UNIX_TIMESTAMP(),'%Y-%m-%d') as today, from_unixtime(u.ts,'%Y-%m-%d') as dt from udailytask u inner join cfg_dailytask c on u.tid = c.id where u.uid = $uid and u.tid = $taskid");
    $process = 1;
    $isGet = 0;
    if($giftinfo && $giftinfo['today'] == $giftinfo['dt']){
        if($giftinfo['process'] < $giftinfo['times']){
            $process = intval($giftinfo['process'])+$pnum;
        }else{
            $process = intval($giftinfo['process']);
        }
        $isGet = intval($giftinfo['isGet']);
    }
    sql_update("insert into udailytask(uid,tid,process,isGet,ts) values($uid,$taskid,$pnum,$isGet,UNIX_TIMESTAMP()) on duplicate key update ts = UNIX_TIMESTAMP(), process = $process,isGet = $isGet");//进度加1  
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $taskid;
    finishtasklog($logparams);
}

//获取日常任务列表
function getDailyTaskList($uid,$params)
{
	//刷新日常任务
    sql_update("update udailytask set ts = UNIX_TIMESTAMP(CURDATE()),process=0,isGet=0 where uid = $uid and UNIX_TIMESTAMP(CURDATE()) > ts");
    //获取所有任务
    $utaskInfo = sql_fetch_rows("select * from udailytask where uid =$uid");
    $ts = sql_fetch_one_cell("select UNIX_TIMESTAMP() as ts");
    return array(
        1,
        $utaskInfo,
        $ts
    );
} 

//获取日常任务奖励
function getDailyTaskGift($uid,$params)
{
    $tid = intval($params[0]);
    $ugiftinfo = sql_fetch_one("select u.*,c.* ,from_unixtime(u.ts,'%Y-%m-%d') as dt ,from_unixtime(UNIX_TIMESTAMP(),'%Y-%m-%d') as today from udailytask u inner join cfg_dailytask c on u.tid = c.id where u.uid = $uid and u.tid = $tid");
    if(!$ugiftinfo){
        return array(
            0,
            STR_DataErr
        );
    }
    //判断是否是一天的
    if($ugiftinfo['dt'] != $ugiftinfo['today']){
        return array(
            0,
            STR_CanNot_Reward
        );
    }
    //次数是否满足
    if($ugiftinfo['process'] < $ugiftinfo['times']){
        return array(
            0,
            STR_ProcessNoReach
        );
    }
    //是否已经领取
    if($ugiftinfo['isGet'] == 1){
        return array(
            0,
            STR_Have_Reward
        );
    }
    $items = explode(',',$ugiftinfo['item']);
    $addReward = array();
    for($i = 0;$i< count($items);$i++){
        $addReward[]  = explode('|',$items[$i]);
    }
    //增加奖励 (金币，砖石，道具)
    for($i = 0;$i < count($addReward);$i++){
        $itemid = $addReward[$i][0];
        $count = $addReward[$i][1];
        if($itemid == 2){
            _addUg($uid,$count,'日常任务奖励');
        }elseif($itemid == 1){
            _addCoin($uid,$count,'日常任务奖励');
        }elseif($itemid == 5){
            _addExp($uid, $count);
        }else{
            _addItem($uid,$itemid,$count,'日常任务奖励');
        }
    }
    //已完成
    sql_update("update udailytask set isGet  = 1 where uid = $uid and tid = $tid");
    return array(
        1,
        $addReward
    );
}

//更新成长任务进度
function _updateGrowTaskProcess($uid,$taskid)
{
	
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    $growcfg = sql_fetch_one("select * from cfg_growtask where id = $taskid");
    if(!$growcfg){
        return array(
            0
        );
    }
    $process = 0;
	    
    switch (intval($growcfg['tasktype'])) {
    	//等级成长任务
    	case 1:
    		$ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    		if($ulv >= intval($growcfg['needcondition'])){
    			$process=1;
    		}
    		 
    		break;
    		//精英副本任务
    	case 6:
    		$upve = sql_fetch_one("select * from upve where uid = $uid");
    		if(!$upve){
    			break;
    		}
    		$needcondition = intval($growcfg['needcondition']);
    		$emapid = intval($upve['emapid']);
    		if($emapid >= $needcondition){
    			$process=1;
    		}
    		break;
    		//普通副本任务
    	case 2:
    		$upve = sql_fetch_one("select * from upve where uid = $uid");
    		if(!$upve){
    			break;
    		}
    		$needcondition = intval($growcfg['needcondition']);
    		$mapid = intval($upve['mapid']);
    		if($mapid >= $needcondition){
    			$process=1;
    		}
    		break;
    		//三星精英副本通
    	case 7:
    		$upve=sql_fetch_one("select * from upve where uid=$uid");
    		$mapstar=$upve['mapstar'];
    		$mapstar_arr=explode(",", $mapstar);
    		$mapstar_arrs=array();
    		foreach ($mapstar_arr as $values) {
    			$star_arr=array();
    			$star_arr=explode("|", $values);
    			$keys=$star_arr[0];
    			$value=$star_arr[1];
    			if(intval($keys)==intval($growcfg['needcondition']))
    			{
    				if(intval($value)==3)
    				{
    					$process=1;
    				}
    			}
    		}
    		break;
    		//三星普通副本通
    	case 5:
    		$upve=sql_fetch_one("select * from upve where uid=$uid");
    		$mapstar=$upve['mapstar'];
    		$mapstar_arr=explode(",", $mapstar);
    		$mapstar_arrs=array();
    		foreach ($mapstar_arr as $values) {
    			$star_arr=array();
    			$star_arr=explode("|", $values);
    			$keys=$star_arr[0];
    			$value=$star_arr[1];
    			if(intval($keys)==intval($growcfg['needcondition']))
    			{
    				if(intval($value)==3)
    				{
    					$process=1;
    				}
    			}
    		}
    		break;
    		//一个勇者升级到
    	case 4:
    		$upartnerlv=sql_fetch_one_cell("select max(plv) from upartner where uid=$uid");
    		if(intval($upartnerlv)>=$growcfg['needcondition'])
    		{
    			$process=1;
    		}
    		break;
    
    }
    
    $isGet = 0;
    sql_update("insert into ugrowtask(uid,tid,process,isGet) values($uid,$taskid,$process,$isGet) on duplicate key update  process = $process");//进度加1
    if($process==1)
    {
	    $logparams = array();
	    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
	    $cuid=$uinfo['cuid'];
	    $serverid=$uinfo['serverid'];
	    _getSystemData($serverid, $cuid, $logparams);
	    $logparams[] = $uid;
	    $logparams[] = $uinfo['ulv'];
	    $logparams[] = $taskid;
	    finishtasklog($logparams);
    }
}

//获取成长任务列表
function getGrowTaskList($uid,$params)
{
    $utaskInfo = sql_fetch_rows("select * from ugrowtask where uid = $uid");
    foreach ($utaskInfo as $value) {
    	$taskid=$value['tid'];
    	_updateGrowTaskProcess($uid, $taskid);
    }
    $utaskInfos = sql_fetch_rows("select * from ugrowtask where uid = $uid");
    return array(
        1,
        $utaskInfos
    );
}

// 获取成长任务礼包
function getGrowTaskGift($uid,$params)
{
    $tid = intval($params[0]);
    $ugiftinfo = sql_fetch_one("select u.*,c.* from ugrowtask u inner join cfg_growtask c on u.tid = c.id where u.uid = $uid and u.tid = $tid");
    if(!$ugiftinfo){
        return array(
            0,
            STR_DataErr
        );
    }

    if($ugiftinfo['process'] < 1){
        return array(
            0,
            STR_ProcessNoReach
        );
    }
    if($ugiftinfo['isGet'] == 1){
        return array(
            0,
            STR_Have_Reward
        );
    }
    $items = explode('|',$ugiftinfo['rewardid']);
    $addReward = array();
    for($i = 0;$i< count($items);$i++){
        $addReward[]  = explode(',',$items[$i]);
    }
    //增加奖励
    for($i = 0;$i < count($addReward);$i++){
        $itemid = $addReward[$i][0];
        $count = $addReward[$i][1];
        if($itemid == 2){
            _addUg($uid,$count,'成长任务礼包');
        }elseif($itemid == 1){
            _addCoin($uid,$count,'成长任务礼包');
        }elseif($itemid == 5){
            _addExp($uid, $count);
        }else{
            _addItem($uid,$itemid,$count,'成长任务礼包');
        }
    }
    sql_update("update ugrowtask set isGet  = 1 where uid = $uid and tid = $tid");
    
    //后续任务
    $tid=intval($ugiftinfo['id']);
    $tasktype=intval($ugiftinfo['tasktype']);
    $numbers=intval($ugiftinfo['numbers']);
    $next_nubers=$numbers+1;
    //取出下一个任务的id
    $next_task=sql_fetch_one("select * from cfg_growtask where tasktype=$tasktype and numbers=$next_nubers");
    if(!isset($next_task))
    {
		$next_date=array();
    }	
   	else 
   	{   	
   		$process=0;
   		$isGet=0;
   		$next_type=intval($next_task['tasktype']);
   		$next_tid=intval($next_task['id']);
	    switch ($next_type) {
	    	//等级成长任务
	    	case 1:
	        $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
        	if($ulv >= intval($next_task['needcondition'])){
           	 	$process=1;
        	}
    			
	    	break;
	    	//精英副本任务
	    	case 6:
	    		$upve = sql_fetch_one("select * from upve where uid = $uid");
	    		if(!$upve){
	    			break;
	    		}
	    		$needcondition = intval($next_task['needcondition']);
	    		$emapid = intval($upve['emapid']);
	    		if($emapid >= $needcondition){
	    			$process=1;
	    		}
	    	break;
	    	//普通副本任务
	    	case 2:
	    	    $upve = sql_fetch_one("select * from upve where uid = $uid");
	    		if(!$upve){
	    			break;
	    		}
	    		$needcondition = intval($next_task['needcondition']);
	    		$mapid = intval($upve['mapid']);
	    		if($mapid >= $needcondition){
	    			$process=1;
	    		}
	    	break;
	    	//三星精英副本通
	    	case 7:
	    		$upve=sql_fetch_one("select * from upve where uid=$uid");
	    		$mapstar=$upve['mapstar'];
	    		$mapstar_arr=explode(",", $mapstar);
	    		$mapstar_arrs=array();
	    		foreach ($mapstar_arr as $values) {
	    			$star_arr=array();
	    			$star_arr=explode("|", $values);
	    			$keys=$star_arr[0];
	    			$value=$star_arr[1];
					if(intval($keys)==intval($next_task['needcondition']))
					{
						if(intval($value)==3)
						{
							$process=1;
						}
					}
	    		}
	    	break;
	    	//三星普通副本通
		   	case 5:
	    	    $upve=sql_fetch_one("select * from upve where uid=$uid");
	    		$mapstar=$upve['mapstar'];
	    		$mapstar_arr=explode(",", $mapstar);
	    		$mapstar_arrs=array();
	    		foreach ($mapstar_arr as $values) {
	    			$star_arr=array();
	    			$star_arr=explode("|", $values);
	    			$keys=$star_arr[0];
	    			$value=$star_arr[1];
					if(intval($keys)==intval($next_task['needcondition']))
					{
						if(intval($value)==3)
						{
							$process=1;
						}
					}
	    		}
		    break;
		    //一个勇者升级到
		    case 4:
		    	$upartnerlv=sql_fetch_one_cell("select max(plv) from upartner where uid=$uid");
		    	if(intval($upartnerlv)>$next_task['needcondition'])
		    	{
		    		$process=1;
		    	}
		    	break;
	    	
	    }
	    
	    sql_update("insert into ugrowtask(uid,tid,process,isGet) values($uid,$next_tid,$process,$isGet) on duplicate key update  process = $process");//进度加1
	    $next_date=sql_fetch_one("select * from ugrowtask where uid=$uid and tid=$next_tid");
	    sql_update("delete from ugrowtask where uid=$uid and tid=$tid");
   	}
    return array(
        1,
        $addReward,
    	$next_date
    );
}

//获取装备和勇者图鉴
function gethandbook($uid,$params){
	
	$result=sql_fetch_rows("select * from ucollection where uid=$uid");
	$equip_arr=array();
	$partner_arr=array();
	foreach ($result as $value)
	{
		if(intval($value['type'])==1)
		{
			array_push($equip_arr, intval($value['peid']));
		}
		else 
		{
			array_push($partner_arr, intval($value['peid']));
		}
		
	}
	return array(
		1,
		$partner_arr,
		$equip_arr	
			
	);
	
}

function getmonthcard($uid,$params){
	//刷新月卡
	$date=sql_fetch_one("select * from umonthcard where uid=$uid");
	if(!isset($date))
	{
		return array(1,0);
	}
	$nowtime = time();
	$nowday = strtotime(date("Y-m-d", $nowtime));
	if($date['time']<$nowday)
	{
		return array(1,0);
	}
	else 
	{
		return array(1,$date['time']);
	}
	
	

	
	
}


?>