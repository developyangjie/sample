<?php
function getCfgClubLv($params) {
    $info = sql_fetch_rows("select * from cfg_clublv");
    return array(
        1,
        $info
    );
}

function _getUZhanli($uid)
{
    $uzhanli = intval(sql_fetch_one_cell("select zhanli from uinfo where uid=$uid"));
    return $uzhanli;
}

function _getULevel($uid)
{
    $ulevel = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    return $ulevel;
}

function _wordsFilterOK($string)
{
    $key_words = sql_fetch_rows("select * from cfg_keywords");
    $count = count($key_words);
    for ($i = 0; $i < $count; $i ++) {
        $word = $key_words[$i]['word'];
        if (stristr($word, $string)) {
            return false;
            break;
        }
    }
    return true;
}

/**
 * 接口：创建公会
 * 
 * @param
 *            $uid
 * @param $params ['name']            
 * @return array
 */
function createClub($uid, $params)
{
    $cname = urldecode($params[0]);
    $cname = _filterstr($cname);
   	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    global $conn;
    $cname = $conn->escape_string($cname);
    if (! _wordsFilterOK($cname)) {
        return array(
            0,
            STR_Club_NameErr
        );
    }
    if (strlen($cname) > 32) {
        return array(
            0,
            STR_NameTooLang
        );
    }
    if (strlen($cname) == 0) {
        return array(
            0,
            STR_USER_NAMEERR
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if ($uclub && intval($uclub['cid']) > 0) {
        return array(
            0,
            STR_Club_Exist
        );
    }
    $renameclub = sql_fetch_one("select * from sysclub where cname='$cname'");
    if ($renameclub) {
        return array(
            0,
            STR_Club_NameSame
        );
    }
    $uidclub = sql_fetch_one("select * from sysclub where uid=$uid");
    if ($uidclub) {
        return array(
            0,
            STR_Club_Exist_Create
        );
    }
    $uinfo = sql_fetch_one("select ulv,uname from uinfo where uid=$uid");
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 11");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    if(intval($uinfo['ulv']) < intval($cfg['lv'])){
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $uname = $uinfo['uname'];
    $ret = _spendGbytype($uid, 100, '创建公会');
    if (! $ret) {
        return array(
            0,
            STR_UgOff
        );
    }
    $cid = sql_insert("insert into sysclub (uid,cname,count) values ($uid,'$cname',1)");
    if ($cid > 0) {
        $ret = sql_update("insert into uclub (uid,cid,state,gift1,gift2,gift3,schoolcount,schoolreward) values ($uid,$cid,1000,0,0,0,'','') on DUPLICATE KEY update cid=$cid,state=1000,gift1=0,gift2=0,gift3=0,schoolcount='', schoolreward=''");
        if ($ret > 0) {
        	$taskarr=_randomclubtask($cid);
            $myclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
            $sysclub = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where s.cid = $cid");
            return array(
                1,
                $myclub,
                $sysclub,
            	$taskarr
            );
        }
        return array(
            0,
            STR_SystemErr
        );
    }
    sql_update("delete from sysclubreq where uid=$uid");
    return array(
        0,
        STR_SystemErr
    );
}

//随机出10个任务
function _randomclubtask($cid)
{
	sql_update("delete from sysclubtask where cid=$cid");
	$task_arr=array();
	$key=0;
	for ($i=1;$i<=11;$i++)
	{
		if($i==9)
		{
			$x=rand(9, 10);
			$taskid=intval(sql_fetch_one_cell("select  tid from cfg_clubtask where type=$x order by rand() limit 1"));
			sql_insert("insert into sysclubtask(cid,tid) values($cid,$taskid)");
			$task_arr[$key]['tid']=$taskid;
			$i++;
		}
		else
		{	
			$taskid=intval(sql_fetch_one_cell("select  tid from cfg_clubtask where type=$i order by rand() limit 1"));
			sql_insert("insert into sysclubtask(cid,tid) values($cid,$taskid)");
			$task_arr[$key]['tid']=$taskid;
		}
		$key++;
	}
	return $task_arr;
}
//设置选好的工会任务
function setuclubtask($uid,$params)
{
	$tid=$params[0];
	if($tid!=0)
	{
		$uclub=sql_fetch_one("select t.cid from uclub u inner join sysclub s on s.cid=u.cid inner join sysclubtask t on t.cid=u.cid where  u.uid=$uid and t.tid=$tid");
		if(!isset($uclub))
		{
			return array(
					0,
					STR_Club_IdMiss
			);
		}
	}
	
	sql_insert("insert into uclubtask(uid,tid,num) values($uid,$tid,0) on DUPLICATE KEY update tid=$tid,num=0");
	return array(
			1
	);
		
}

//随机出这次能完成任务数量
function _startclubtask($uid,$type)
{
	//是否有工会
	$uclub=sql_fetch_one("select s.cid from uclub u inner join sysclub s on s.cid=u.cid inner join sysclubtask t on t.cid=u.cid where  u.uid=$uid");
	if(!isset($uclub))
	{
		return array(0);
	}
	//是否领取了工会任务
	$clubtask=sql_fetch_one("select * from uclubtask where uid=$uid");
	if(!isset($clubtask))
	{
		return array(0);
	}
	$tid=$clubtask['tid'];
	$nownum=$clubtask['num'];
	
	$cfgclubtask=sql_fetch_one("select * from cfg_clubtask where tid=$tid");
	$maxnum=$cfgclubtask['num'];
	//任务类型对齐
	if(intval($cfgclubtask['type'])==$type)
	{
		//给多少任务物品
		$num=rand($cfgclubtask['least_get'],$cfgclubtask['most_get']);
		if($maxnum<($nownum+$num))
		{
			$num=$maxnum-$nownum;
		}
		sql_update("update uclubtask set getnum=$num where uid=$uid");
		return array($num);
	}
	else 
	{
		sql_update("update uclubtask set getnum=0 where uid=$uid");
		return array(0);
	}
}

function _endclubtask($uid,$type)
{
	//是否有工会
	$uclub=sql_fetch_one("select s.cid,u.* from uclub u inner join sysclub s on s.cid=u.cid inner join sysclubtask t on t.cid=u.cid where  u.uid=$uid");
	if(!isset($uclub))
	{
		return array(0);
	}
	//是否领取工会任务
	$clubtask=sql_fetch_one("select * from uclubtask where uid=$uid");
	if(!isset($clubtask))
	{
		return array(0);
	}
	$tid=$clubtask['tid'];
	$cid=$uclub['cid'];
	$getnum=$clubtask['getnum'];
	
	$cfgclubtask=sql_fetch_one("select * from cfg_clubtask where tid=$tid");
	//类型对齐，并完成任务
	if(intval($cfgclubtask['type'])==$type)
	{
// 		sql_update("update sysclub set activity=activity+20 where cid=$cid");
// 		sql_update("update uclub set activity=activity+20 where uid=$uid");
		sql_update("update uclubtask set num=num+$getnum where uid=$uid");
		return array($getnum);
	}
	else 
	{
		return array(0);
	}
}
//领取工会任务奖励
function getclubtaskgift($uid,$params)
{
	//是否有工会
	$uclub=sql_fetch_one("select s.cid,u.* from uclub u inner join sysclub s on s.cid=u.cid inner join sysclubtask t on t.cid=u.cid where  u.uid=$uid");
	if(!isset($uclub))
	{
		return array(0);
	}
	$cid=$uclub['cid'];
	$clubtask=sql_fetch_one("select * from uclubtask where uid=$uid");
	$tid=$clubtask['tid'];
	$nownum=$clubtask['num'];
	//工会任务是否完成
	$cfgclubtask=sql_fetch_one("select * from cfg_clubtask where tid=$tid");
	$maxnum=$cfgclubtask['num'];
	if($nownum<$maxnum)
	{
		return array(
				0,
				STR_NeedTimes
		);
	}
	$reward=$cfgclubtask['reward'];
	$item_arr=explode(",",$reward);
	$ret=array();
	foreach ($item_arr as $value)
	{
		$item_date=explode("|",$value);
		$itme_id=intval($item_date[0]);
		$itme_num=intval($item_date[1]);
		array_push($ret, array($itme_id,$itme_num));
		switch ($itme_id)
		{
			//活跃度
			case 9:
				sql_update("update sysclub set activity=activity+$itme_num where cid=$cid");
			break;
			//工会经验
			case 10:
				sql_update("update sysclub set exp=exp+$itme_num where cid=$cid");
			break;
			//贡献
			case 8:
				sql_update("update sysclub set score=score+$itme_num where cid=$cid");
			break;
			//金币
			case 1:
			_addCoin($uid,$itme_num,'工会任务奖励');
			break;
			
		}
		$task = intval(sql_fetch_one_cell("select task from sysclub where cid=$cid"));
		if($task + 20 > 8000){
		    sql_update("update sysclub set task=8000 where cid=$cid");
		}
		else{
		    sql_update("update sysclub set task=task+20 where cid=$cid");
		}
	//	sql_update("update uclub set activity=activity+20 where uid=$uid");
	}
	sql_update("update uclubtask set tid = 0 where uid = $uid and tid = $tid");
	$activity=intval(sql_fetch_one_cell("select activity from sysclub where cid=$cid"));
	return array(
			1,
			$ret,
			$activity
	);
}
//获取玩家当前公会任务状态
function getuclubtask($uid,$params)
{
	$uclubtask=sql_fetch_rows("select * from uclubtask where uid=$uid");
	$activity=intval(sql_fetch_one_cell("select s.task from uclub u inner join sysclub s on s.cid=u.cid inner join sysclubtask t on t.cid=u.cid where u.uid=$uid"));
	return array(
			1,
			$uclubtask,
			$activity
	);
}
//只获取玩家所在的工会信息。刷新用
function getuclubdate($uid,$params)
{
    $uclubtask=sql_fetch_one("select s.*,i.uname as chairman from sysclub s inner join uclub u on s.cid=u.cid inner join uinfo i on s.uid=i.uid where u.uid=$uid");
    return array(
        1,
        $uclubtask
    );
}

function _checkClub()
{
    if (! CLUB_OPENTS) {
        $ret = array(
            0,
            STR_Club_WillOpen
        );
        return $ret;
    }
    if (time() < CLUB_OPENTS) {
        $ret = array(
            0,
            date('m月d日 H:i', CLUB_OPENTS) . STR_Club_Open
        );
        return $ret;
    }
    return array(
        1,
        STR_Welcome
    );
}

/**
 * 接口：领取公会奖励
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function getclubtaskchest($uid,$params)
{
	$tasknum=array(1000,4000,8000);
	$type=intval($params[0]);
	$uclub=sql_fetch_one("select u.gift1,u.gift2,u.gift3,s.task from uclub u inner join sysclub s on s.cid=u.cid where u.uid=$uid");
	$items = array();
	switch ($type)
	{
		case 1:
			if($uclub['task']<$tasknum[0])
			{
				return array(
						0,
						STR_Club_Activity
				);
			}
			if($uclub['gift1']!=0)
			{
				return array(
						0,
						STR_Club_IsGet
				);
			}
			sql_update("update uclub set gift1=1 where uid=$uid");
			_addItem($uid, 413, 1);	
			$items = array(413, 1);
			break;
			
		case 2:
			if($uclub['task']<$tasknum[1])
			{
				return array(
						0,
						STR_Club_Activity
				);
			}
			if($uclub['gift2']!=0)
			{
				return array(
						0,
						STR_Club_IsGet
				);
			}
			sql_update("update uclub set gift2=1 where uid=$uid");
			_addItem($uid, 414, 1);
			$items = array(414, 1);
			break;
			
		case 3:
			if($uclub['task']<$tasknum[2])
			{
				return array(
						0,
						STR_Club_Activity
				);
			}
			if($uclub['gift3']!=0)
			{
				return array(
						0,
						STR_Club_IsGet
				);
			}
			sql_update("update uclub set gift3=1 where uid=$uid");
			_addItem($uid, 415, 1);
			$items = array(415, 1);
			break;
		
		
		
	}
	return array(
	    1,
	    $items
	);
}


/**
 * 接口：获取我的公会信息
 * 
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getMyClub($uid, $params)
{
    $res = _checkClub();
    if ($res && $res[0] == 0) {
        return $res;
    }
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $myclub = sql_fetch_one("select c.*,s.* from uclub c inner join sysclub s on c.cid=s.cid inner join  uinfo u on s.uid=u.uid where c.uid=$uid and u.serverid = $serverid and c.state > 0");
    if ($myclub && intval($myclub['cid']) > 0) {
        $cinum = intval($myclub['cinum']);
        $ts = time();
        $checkin = false;
        if($cinum == 0){
            $checkin = true;
        }
        $cid = intval($myclub['cid']);
        $sysclub = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where s.cid = $cid");
       	$uclubtask=sql_fetch_rows("select * from uclubtask where uid=$uid");
       	$sysclubtask=sql_fetch_rows("select tid from sysclubtask where cid=$cid");
        //==============================================
        $checkincount = $sysclub['checkincount'];
        $breadnum = 0;
        if($checkincount < 5){
            $breadnum = 5 - $checkincount;
        }
        return array(
            1,
            $myclub,
            $sysclub,
            $checkin,
            $breadnum,
        	$sysclubtask,
        	$uclubtask
        );
    } else {
        $newclub = array();
        $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 25 and count < 30 and u.serverid = $serverid order by rand() limit 1");
        if($res){
            $newclub[] = $res;
        }
        $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 20 and count <= 25 and u.serverid = $serverid order by rand() limit 1");
        if($res){
            $newclub[] = $res;
        }
        $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 15 and count <= 20 and u.serverid = $serverid order by rand() limit 1");
        if($res){
            $newclub[] = $res;
        }
        $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 10 and count <= 15 and u.serverid = $serverid order by rand() limit 1");
        if($res){
            $newclub[] = $res;
        }
        $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 0 and count <= 10 and u.serverid = $serverid order by rand() limit 1");
        if($res){
            $newclub[] = $res;
        }
        if(count($newclub) < 5){
            $res = sql_fetch_rows("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where count > 0 and count < 30 and u.serverid = $serverid order by rand() limit 5");
            if($res){
                $newclub = $res;
            }
        }
        foreach($newclub as &$v){
            $cid = intval($v['cid']);
            $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid and uid = $uid");
            if($clubreq){
                $v['join'] = 1;
            }
            else{
                $v['join'] = 0;
            }
        }
        return array(
            2,
            $newclub
        );
    }
    return array(
        0,
        STR_UnknowErr
    );
}

/**
 * 接口：获取公会成员
 * 
 * @param
 *            $uid
 * @param $params ['cid']            
 * @return array
 */
function getClubMember($uid, $params)
{
    $cid = intval($params[0]);
    $res = sql_fetch_rows("select c.uid,c.state,u.uname,u.ulv,u.uexp,u.vip,u.ujob,u.zhanli,c.totalscore,UNIX_TIMESTAMP()-u.logintime as tsleave from uclub c inner join uinfo u on c.uid=u.uid where c.cid=$cid");
    foreach ($res as &$v){
        $muid = $v['uid'];
        $brave = sql_fetch_one("select p.pid from uequip e left outer join upartner p on p.partnerid = e.brave where e.uid = $muid");
        $v['pid'] = $brave;
    }
    return array(
        1,
        $res
    );
}

//请求加入公会
function reqJoinClub($uid, $params)
{
    $cid = intval($params[0]);
    $uinfo = sql_fetch_one("select zhanli,ulv,uname from uinfo where uid=$uid");
    $uzhanli = intval($uinfo['zhanli']);
    $ulv = intval($uinfo['ulv']);
    $clubInfo = sql_fetch_one("select count,maxcount,zhanli from sysclub where cid=$cid");
    if (! $clubInfo) {
        return array(
            0,
            STR_Club_IdMiss
        );
    }
    $count = intval($clubInfo['count']);
    $maxCount = intval($clubInfo['maxcount']);
    if ($count >= $maxCount) {
        return array(
            0,
            STR_Club_renshu
        );
    }
    /*  $zhanliNeed = intval($clubInfo['zhanli']);
     if ($zhanliNeed > $uzhanli) {
     return array(
     0,
     STR_Club_PowerLow
     );
     }*/
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 11");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    if(intval($uinfo['ulv']) < intval($cfg['lv'])){
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $myclub = sql_fetch_one("select *,UNIX_TIMESTAMP() as nowtime from uclub where uid=$uid");
    if ($myclub && intval($myclub['cid']) > 0) {
        return array(
            0,
            STR_Club_Exist
        );
    }
    if ($myclub && intval($myclub['exitts']) > 0) {
        if (intval($myclub['nowtime']) < intval($myclub['exitts'])) {
            return array(
                0,
                STR_Club_Out
            );
        }
    }
    sql_update("insert into sysclubreq (cid, uid) values ($cid, $uid) on DUPLICATE KEY update cid=$cid,uid=$uid");
    return array(
        1
    );
}

//同意请求加入公会
function agreeReqJoinClub($uid, $params)
{
    $muid = $params[0];
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if(!$uclub){
        return array(
            0,
            STR_NoJoinIn
        );
    }
    if (!($uclub['state'] == 1000 || $uclub['state'] == 100)) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $cid = $uclub['cid'];
    $req = sql_fetch_one("select * from sysclubreq where cid = $cid and uid=$muid");
    if(!$req){
        return array(
            0,
            STR_Club_Not_Req
        );
    }
    $mclub = sql_fetch_one("select * from uclub where uid=$muid and state > 0");
    if($mclub){
        sql_update("delete from sysclubreq where uid=$muid");
        return array(
            0,
            STR_Member_JoinIn
        );
    }
    $result = sql_update("delete from sysclubreq where cid = $cid and uid=$muid");
    if($result){
        $ret = sql_update("insert into uclub (uid,cid,state) values ($muid,$cid,1) on DUPLICATE KEY update cid=$cid,state=1");
        if ($ret) {
            sql_update("update sysclub set count=count+1 where cid=$cid");
        }
    }
    return array(
        1,
        $muid
    );
}

//拒绝请求加入公会
function refuseReqJoinClub($uid, $params)
{
    $muid = $params[0];
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if(!$uclub){
        return array(
            0,
            STR_NoJoinIn
        );
    }
    if (!($uclub['state'] == 1000 || $uclub['state'] == 100)) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $cid = $uclub['cid'];
    sql_update("delete from sysclubreq where cid=$cid and uid=$muid");
    return array(
        1
    );
}

//取消请求加入公会
function cancleReqJoinClub($uid, $params)
{
    $cid = $params[0];
    sql_update("delete from sysclubreq where cid=$cid and uid=$uid");
    return array(
        1
    );
}

//获取公会申请列表
function getClubRequestList($uid, $params)
{
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if(!$uclub){
        return array(
            0,
            STR_NoJoinIn
        );
    }
    $cid = $uclub['cid'];
    $uinfolist = array();
    $reqlist = sql_fetch_rows("select * from sysclubreq where cid = $cid");
    foreach ($reqlist as $v){
        $cuid = $v['uid'];
        $mclub = sql_fetch_one("select * from uclub where uid=$cuid and state > 0");
        if($mclub){
            sql_update("delete from sysclubreq where uid=$cuid");
        }
        $uinfolist[] = sql_fetch_one("select uid,uname,ulv,zhanli from uinfo where uid = $cuid");
    }
    return array(
        1,
        $uinfolist
    );
}

/**
 * 接口：转让公会
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function changeClub($uid, $params)
{
    $muid = intval($params[0]);
    $cid = intval($params[1]);
    $sysclub = sql_fetch_one("select * from sysclub where cid=$cid and uid=$uid");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $ret = sql_update("update uclub set state=1000 where uid=$muid and cid=$cid and state=100");
    if ($ret != 1) {
        return array(
            0,
            STR_Club_Transfer
        );
    }
    sql_update("update uclub set state=100 where uid=$uid and cid=$cid");
    sql_update("update sysclub set uid=$muid where cid=$cid and uid=$uid");
    return array(
        1,
        $ret
    );
}

/**
 * 接口：搜索公会
 * 
 * @param
 *            $uid
 * @param $params ['cid']            
 * @return array
 */
function findClub($uid, $params)
{
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $cid = intval($params[0]);
    $res = sql_fetch_one("select s.*,u.uname as chairman from sysclub s inner join uinfo u on s.uid=u.uid where s.cid=$cid and u.serverid=$serverid");
    if($res){
        $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid");
        if($clubreq){
            $res['join'] = 1;
        }
        else{
            $res['join'] = 0;
        }
    }
    return array(
        1,
        $res
    );
}

/**
 * 接口：设置公会战力
 * 
 * @param
 *            $uid
 * @param $params ['zhanli','cid']            
 * @return array
 */
function setClubzhanli($uid, $params)
{
    $zhanli = intval($params[0]);
    $cid = intval($params[1]);
    $ret = sql_update("update sysclub set zhanli=$zhanli where cid=$cid and uid=$uid");
    return array(
        1,
        $ret
    );
}

/**
 * 接口：设置公会公告
 * 
 * @param
 *            $uid
 * @param $params ['note']            
 * @return array
 */
function setClubNote($uid, $params)
{
    $note = urldecode($params[0]);
    $note = _filterstr($note);
    
    $cid = intval($params[1]);
    global $conn;
    $note = $conn->escape_string($note);
    if (strlen($note) > 120) {
        return array(
            0,
            STR_Club_Post
        );
    }
    $sysclub = sql_fetch_one("select * from uclub where cid=$cid and uid=$uid and state>=100");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo2
        );
    }
    $ret = sql_update("update sysclub set note='$note' where cid=$cid");
    return array(
        1,
        $ret
    );
}

/**
 * 接口：公会成员升职
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function upMember($uid, $params)
{
    $muid = intval($params[0]);
    $cid = intval($params[1]);
    $sysclub = sql_fetch_one("select * from sysclub where cid=$cid and uid=$uid");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $clv = 1;
    $count = intval(sql_fetch_one_cell("select count(*) from uclub where cid=$cid and state=100"));
    if ($count >= $clv) {
        sql_update("update uclub set state=1 where cid=$cid and state=100");
    }
    $ret = sql_update("update uclub set state=100 where uid=$muid and cid=$cid");
    _addMail($muid, STR_Club_UpMember,STR_Club_UpMember, 0, 0, 0);
    $muinfo = sql_fetch_one("select uname from uinfo where uid=$muid");
    _addSysMsg(sprintf(STR_Club_SysMsg2,$muinfo['uname']),$cid);
    return array(
        1,
        $ret
    );
}

/**
 * 接口：公会成会员降职
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function downMember($uid, $params)
{
    $muid = intval($params[0]);
    $cid = intval($params[1]);
    $sysclub = sql_fetch_one("select * from sysclub where cid=$cid and uid=$uid");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $ret = sql_update("update uclub set state=1 where uid=$muid and cid=$cid");
    return array(
        1,
        $ret
    );
}

/**
 * 接口：移除会员
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function delMember($uid, $params)
{
    $cuid = intval($params[0]);
    $cid = intval($params[1]);
    $sysclub = sql_fetch_one("select * from uclub where cid=$cid and uid=$uid and state>=100");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo2
        );
    }
    $ret = sql_update("update uclub set cid=0,totalscore=0,state=0,cinum=0,exitts=GREATEST(exitts,UNIX_TIMESTAMP()+300) where uid=$cuid and cid=$cid and state<1000");
    if ($ret) {
        sql_update("update sysclub set count=count-1 where cid=$cid");
        $mtitle = STR_Club_Off;
        $mcontent = STR_OffClub;
        _addMail($cuid,$mtitle, $mcontent, 0, 0, 0);
    }
    return array(
        1,
        $ret
    );
}

/**
 * 发版本之后没有使用过
 */
function upClub($uid, $params)
{
    $cid = intval($params[0]);
    $sysclub = sql_fetch_one("select * from sysclub where cid=$cid and uid=$uid");
    if (! $sysclub) {
        return array(
            0,
            STR_Club_CaoZuo
        );
    }
    $clv = intval($sysclub['clv']);
    $needscore = pow(2, $clv) * 10000;
    if (sql_update("update sysclub set score=score-$needscore,clv=$clv+1 where cid=$cid and score>=$needscore")) {
        return array(
            1,
            $clv + 1
        );
    } else {
        return array(
            0,
            STR_Club_Resource . $needscore
        );
    }
}

/**
 * 接口：同意加入公会
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function agreeClub($uid, $params)
{
    $cuid = intval($params[0]);
    $cid = intval($params[1]);
    $ret = sql_update("update uclub set state=1 where uid=$cuid and cid=$cid");
    return array(
        $ret
    );
}

/**
 * 接口：拒绝加入公会
 * 
 * @param
 *            $uid
 * @param $params ['uid','cid']            
 * @return array
 */
function rejectClub($uid, $params)
{
    $cuid = intval($params[0]);
    $cid = intval($params[1]);
    $ret = sql_update("delete from uclub where uid=$cuid and cid=$cid");
    return array(
        $ret
    );
}

/**
 * 接口：获取公会排行
 * 
 * @param
 *            $uid
 * @param $params ['cid']            
 * @return array
 */
function getClubRank($uid, $params)
{
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $res = sql_fetch_rows("select s.*,u.uname from sysclub s inner join uinfo u on s.uid=u.uid where u.serverid=$serverid ORDER by activity desc limit 20");
    $rank = sql_fetch_rows("select s.clv,s.cid,s.cname,u.uname from sysclub s inner join uinfo u on s.uid=u.uid where u.serverid=$serverid ORDER by activity desc limit 100");
    $myclub = sql_fetch_one("select *,cits-UNIX_TIMESTAMP() as signleft,reitemts-UNIX_TIMESTAMP() as itemleft, UNIX_TIMESTAMP() as nowts from uclub where uid=$uid");
    if ($res) {
        for($i = 0; $i < count($res); $i ++){
            $res[$i]['order'] = $i + 1;
        }
    }
    $mycount = 0;
    if ($myclub && intval($myclub['cid']) > 0) {
        $mycount = 101;
        for($i = 0; $i < count($rank); $i ++){
            if(intval($rank[$i]['cid']) == intval($myclub['cid'])){
                $mycount = $i + 1;
            }
        }
    }
    return array(
        1,
        $res,
        $mycount
    );
}


/**
 * 接口：删除公会
 * 
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function delClub($uid, $params)
{
    $cid = intval(sql_fetch_one_cell("select cid from sysclub where uid=$uid"));
    if ($cid > 0) {
        $ret = sql_update("delete from sysclub where cid=$cid");
        sql_update("delete from uclub where cid=$cid");
        return array(
            1
        );
    }
    return array(
        0,
        STR_Club_IdMiss
    );
}

/**
 * 接口：退出公会
 * 
 * @param
 *            $uid
 * @param $params ['cid']            
 * @return array
 */
function exitClub($uid, $params)
{
    $cid = intval($params[0]);
    $state = intval(sql_fetch_one_cell("select state from uclub where uid=$uid"));
    sql_update("delete from uclubtask where uid = $uid");
    if($state == 1000){
        $fclub = sql_fetch_one("select * from uclub where cid=$cid and state = 100");
        if($fclub){
            $fuid = intval($fclub['uid']);
            sql_update("update sysclub set uid=$fuid where cid=$cid");
            sql_update("update uclub set state=1000 where uid=$fuid and cid=$cid");
        }
        else{
            $fclub = sql_fetch_one("select * from uclub where cid=$cid and state = 1 order by totalscore");
            if($fclub){
                $cuid = intval($fclub['uid']);
                sql_update("update sysclub set uid=$cuid where cid=$cid");
                sql_update("update uclub set state=1000 where uid=$cuid and cid=$cid");
            }
            else{
                sql_update("update uclub set cid=0,totalscore=0,state=0,cinum=0,exitts=UNIX_TIMESTAMP()+86400 where uid=$uid and cid=$cid");
                sql_update("delete from sysclub where cid=$cid");
                $resclub = getMyClub($uid, $params);
                $resrank = getClubRank($uid, $params);
                return array(
                    1,
                    $resclub,
                    $resrank
                );
            }
        }
    }
    $ret = sql_update("update uclub set cid=0,totalscore=0,state=0,cinum=0,exitts=UNIX_TIMESTAMP()+86400 where uid=$uid and cid=$cid");
    if ($ret > 0) {
        sql_update("update sysclub set count=count-1 where cid=$cid");
    }
    $resclub = getMyClub($uid, $params);
    $resrank = getClubRank($uid, $params);
    return array(
        1,
        $resclub,
        $resrank
    );
}

//公会签到
function checkinClub($uid, $params)
{
    $cid = intval($params[0]);
    $id = intval($params[1]);
    $ret = sql_fetch_one("select * from uclub where uid=$uid and cid=$cid and cinum = 0"); 
    if ($ret) {
        $cfg = sql_fetch_one("select * from cfg_clubcheckin where id=$id");
        if(!$cfg){
            return array(
                0,
                STR_Club_Cfg_Error
            );
        }
        $exp = intval($cfg['clubexp']);
        $act = intval($cfg['clubactive']);
        $score = intval($cfg['contribute']);
        $cost = intval($cfg['cost']);
        $bread = intval($cfg['bread']);
        $breadall = 0;
        if($cost > 0){
            if (! _spendGbytype($uid, $cost, '公会签到')) {
                return array(
                    0,
                    STR_UgOff
                );
            }
        }
        if($bread > 0){
            _addBreadNum($uid, $bread);
        }
        $sysclub = sql_fetch_one("select checkincount, checkinuid from sysclub where cid = $cid");
        if(intval($sysclub['checkincount']) < 5){
            $breadall = intval($cfg['breadall']);
            if($breadall > 0){
           //     _addMail($uid, "公会签到获取共享面包", "公会签到获取共享面包", 0, 0, 0,5,$breadall);
            }
            $checkinuid = $sysclub['checkinuid'];
            $uidarr = explode(",", $checkinuid);
            $uidarr[] = $uid;
            $checkinuid = implode(",", $uidarr);
            sql_update("update sysclub set checkinuid = '$checkinuid' where cid=$cid");
        }
        sql_update("update sysclub set exp=exp+$exp, activity=activity+$act, checkincount=checkincount+1 where cid=$cid");
        $level = 1;
        $clubexp = sql_fetch_one_cell("select exp from sysclub where cid=$cid");
        $cfglv = sql_fetch_rows("select * from cfg_clublv");
        for ($i = 0; $i < count($cfglv); $i ++){
            if($clubexp >= intval($cfglv[$i]['minexp']) && $clubexp < intval($cfglv[$i]['maxexp'])){
                $level = intval($cfglv[$i]['lv']);
            }
        }
        sql_update("update sysclub set clv=$level where cid=$cid");
        sql_update("update uclub set cits=UNIX_TIMESTAMP(),totalscore=totalscore+$score,citype=$id,cinum=cinum+1 where uid=$uid and cid=$cid and cinum = 0");
        //==============================================
        $myclub = sql_fetch_one("select c.*,s.* from uclub c inner join sysclub s on c.cid=s.cid where c.uid=$uid");
        $checkincount = $myclub['checkincount'];
        $allbreadnum = 0;
        if($checkincount < 5){
            $allbreadnum = 5 - $checkincount;
        }
        _updateUTaskProcess($uid, 1012);
        return array(
            1,
            $exp,
            $act,
            $score,
            $cost,
            $bread,
            $breadall,
            $allbreadnum
        );
    }
    return array(
        0,
        STR_Club_QianDao
    );
}

/**
 * 接口：公会签到奖励
 *
 * @param
 *            $uid
 * @param $params ['cid']
 * @return array
 */
/*function getCheckinReward($uid, $params)
{
    $cid = intval($params[0]);
    $progress = intval($params[1]);
    $cfg = sql_fetch_rows("select * from cfg_clubcheckin where type=1");
    if(!$cfg){
        return array(
            0,
            STR_Club_Cfg_Error
        );
    }
    $myclub = sql_fetch_one("select c.*,s.* from uclub c inner join sysclub s on c.cid=s.cid where c.uid=$uid");
    $isget = array();
    $isgetstr = $myclub['cireward'];
    if($isgetstr){
        $isget = explode(",", $isgetstr);
    }
    $cfg = sql_fetch_rows("select * from cfg_clubcheckin where type=1");
    $canget = array();
    if(count($cfg) > 0){
        $uprogress = intval($myclub['progress']);
        foreach ($cfg as $value){
            if($uprogress >= intval($value['progress'])){
                if(!in_array(intval($value['progress']), $isget)){
                    $canget[] = intval($value['progress']);
                }
            }
        }
    }
    if(!in_array($progress, $canget)){
        return array(
            0,
            STR_Club_CiReward_Err
        );
    }
    $rewardcfg = sql_fetch_one("select * from cfg_clubcheckin where progress=$progress");
    if(!$rewardcfg){
        return array(
            0,
            STR_Club_Cfg_Error
        );
    }
    $reward = array();
    for($i = 1; $i <= 4; $i ++){
        $itemid = 'itemid'.$i;
        $itemnum = 'itemcount'.$i;
        if(intval($rewardcfg[$itemid]) > 0 && intval($rewardcfg[$itemnum]) > 0){
            $reward[] = array(intval($rewardcfg[$itemid]),intval($rewardcfg[$itemnum]));
        }
    }
    foreach ($reward as $value){
        $rewardid = $value[0];
        $rewardnum = $value[1];
        if($rewardid == 1){
            _addCoin($uid, $rewardnum,'checkinreward');
        }
        elseif($rewardid == 2){
            _addUg($uid, $rewardnum, 'checkinreward');
        }
        else{
            _addItem($uid, $rewardid, $rewardnum,'checkinreward');
        }
    }
    $isget[] = $progress;
    $getids = array();
    for($j = 0; $j < count($isget); $j ++){
        $getids[] = intval($isget[$j]);
    }
    $cireward = implode(",", $isget);
    $nextcanget = array();
    if(count($cfg) > 0){
        $tempuprogress = intval($myclub['progress']);
        foreach ($cfg as $v){
            if($tempuprogress >= intval($v['progress'])){
                if(!in_array(intval($v['progress']), $isget)){
                    $nextcanget[] = intval($v['progress']);
                }
            }
        }
    }
    sql_update("update uclub set cireward='$cireward' where cid=$cid and uid=$uid ");
    return array(
        1,
        $reward,
        $getids,
        $nextcanget
    );
}*/

/**
 * 接口：贡献兑换
 * 
 * @param
 *            $uid
 * @param $params ['itemid']            
 * @return array
 */
function clubexchange($uid, $params)
{
    $stype = intval($params[0]);
    $itemid = $stype;
    switch ($stype) {
        case 12:
            $type = 1;
            $count = 8;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 3;
            break;
        case 11:
            $type = 2;
            $count = 1;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 3;
            break;
        case 41:
            $type = 3;
            $count = 6;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 1;
            break;
        case 42:
            $type = 4;
            $count = 2;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 2;
            break;
        case 21:
            $type = 5;
            $count = 12;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 1;
            break;
        case 22:
            $type = 6;
            $count = 4;
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $limit = 2;
            break;
        default:
            $limit = 3;
            return array(
                0,
                ACTIVITY_DATA_ERROR
            );
            break;
    }
    $cinfo = sql_fetch_one("select * from uclub where uid=$uid");
    $cid = $cinfo['cid'];
    $cidinf = sql_fetch_one("select * from sysclub where cid=$cid");
    $shoplv = $cidinf['shoplv'];
    $score = $cinfo['score'];
    $itemcount = intval($cinfo['citem' . $type]);
    $cost = (50 * $itemcount * ($itemcount + 1) + 500) * (100 - 5 * $shoplv) / 100;
    $itemcount2 = $itemcount + 1;
    $cost2 = (50 * $itemcount2 * ($itemcount2 + 1) + 500) * (100 - 5 * $shoplv) / 100;
    $cinum = intval($cinfo['cinum']);
    if ($cinum < $limit) {
        return array(
            0,
            STR_Club_Inf5 . $limit . STR_Club_Inf6
        );
    }
    if (! sql_update("update uclub set score=score-$cost where uid=$uid and score>=$cost")) {
        return array(
            0,
            STR_GongXian
        );
    }
    if (! sql_update("update uclub set citem$type=citem$type+1 where uid=$uid and citem$type=$itemcount")) {
        return array(
            0,
            STR_DataErr
        );
    }
    _addItem($uid, $itemid, $count,'公会贡献兑换');
    $score = sql_fetch_one_cell("select score from uclub where uid=$uid");
    return array(
        1,
        STR_Club_ExchangeOK,
        $cost2,
        $score,
        $cost,
        $item
    );
}

/**
 * 接口：公会信息
 * 
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function clubinfo($uid, $params)
{
    $cinfo = sql_fetch_one("select *,refreshts-UNIX_TIMESTAMP() as tsleft from uclub where uid=$uid");
    if ($cinfo && intval($cinfo['tsleft']) <= 0) {
        sql_update("update uclub set donate=0,refreshts=UNIX_TIMESTAMP(CURDATE())+86400 where uid=$uid");
    }
    $cid = $cinfo['cid'];
    $cidinf = sql_fetch_one("select * from sysclub where cid=$cid");
    $shoplv = $cidinf['shoplv'];
    $cmoney = $cidinf['cmoney'];
    $clv = $cidinf['clv'];
    $schoollv = $cidinf['schoollv'];
    return array(
        1,
        $cmoney,
        $clv,
        $schoollv,
        $shoplv
    );
}

//获取公会学院数据
function getClubSchool($uid, $params)
{
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $cfgschool = sql_fetch_rows("select * from cfg_clubschool");
    $time = intval(sql_fetch_one_cell("select UNIX_TIMESTAMP(CURDATE())"));
    foreach ($cfgschool as &$c){
        $stimearr = explode(",", $c['starttime']);
        $etimearr = explode(",", $c['endtime']);
        $c['stime1'] = date('H:i', $time + $stimearr[0]);
        $c['etime1'] = date('H:i', $time + $etimearr[0]);
        $c['stime2'] = date('H:i', $time + $stimearr[1]);
        $c['etime2'] = date('H:i', $time + $etimearr[1]);
        $c['st1'] = $time + intval($stimearr[0]);
        $c['et1'] = $time + intval($etimearr[0]);
        $c['st2'] = $time + intval($stimearr[1]);
        $c['et2'] = $time + intval($etimearr[1]);
    }
    $allcfgs = sql_fetch_rows("select * from cfg_clubschool");
    $hpinfo = sql_fetch_one_cell("select bosshp from sysclub where cid=$cid");
    $uschool = sql_fetch_one_cell("select schoolcount from uclub where uid=$uid");
    $ureward = sql_fetch_one_cell("select schoolreward from uclub where uid=$uid");
    if(!$hpinfo){
        $bosshpstr = '';
        foreach ($allcfgs as $c){
            $id = intval($c['id']);
            $bossid = intval($c['monsterid']);
            $cfgboss = sql_fetch_one("select * from cfg_monster where mid = $bossid");
            if($cfgboss){
                $bosshp = $cfgboss['hp'];
            }
            $hparr = array($id, $bosshp);
            $hpdata = implode("|", $hparr);
            if (empty($bosshpstr)){
                $bosshpstr = $hpdata;
            }
            else{
                $bosshpstr .= ",".$hpdata;
            }
        }
        sql_update("update sysclub set bosshp = '$bosshpstr' where cid=$cid");
    }
    if(!$uschool){
        $schoolcount = '';
        foreach ($allcfgs as $v){
            $id = intval($v['id']);
            $countarr = array($id, 0);
            $countdata = implode("|", $countarr);
            if (empty($schoolcount)){
                $schoolcount = $countdata;
            }
            else{
                $schoolcount .= ",".$countdata;
            }
        }
        sql_update("update uclub set schoolcount='$schoolcount' where uid=$uid");
    }
    if(!$ureward){
        $schoolreward = '';
        foreach ($allcfgs as $r){
            $id = intval($r['id']);
            $rewardarr = array($id, 0);
            $rewarddata = implode("|", $rewardarr);
            if (empty($schoolreward)){
                $schoolreward = $rewarddata;
            }
            else{
                $schoolreward .= ",".$rewarddata;
            }
        }
        sql_update("update uclub set schoolreward='$schoolreward' where uid=$uid");
    }
    $clubschool = sql_fetch_one("select bosshp from sysclub where cid=$cid");
    $schoolinfo = sql_fetch_one("select schoolcount, schoolreward from uclub where uid=$uid");
    return array(
        1,
        $cfgschool,
        $clubschool,
        $schoolinfo
    );
}

//获取公会学院数据
function getClubLvSchool($uid, $params)
{
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
    $cfgschool = sql_fetch_rows("select * from cfg_clubschool where clublv <= $clublv");
    $time = intval(sql_fetch_one_cell("select UNIX_TIMESTAMP(CURDATE())"));
    foreach ($cfgschool as &$c){
        $stimearr = explode(",", $c['starttime']);
        $etimearr = explode(",", $c['endtime']);
        $c['stime1'] = date('H:i', $time + $stimearr[0]);
        $c['etime1'] = date('H:i', $time + $etimearr[0]);
        $c['stime2'] = date('H:i', $time + $stimearr[1]);
        $c['etime2'] = date('H:i', $time + $etimearr[1]);
        $c['st1'] = $time + intval($stimearr[0]);
        $c['et1'] = $time + intval($etimearr[0]);
        $c['st2'] = $time + intval($stimearr[1]);
        $c['et2'] = $time + intval($etimearr[1]);
    }
    $allcfgs = sql_fetch_rows("select * from cfg_clubschool");
    $hpinfo = sql_fetch_one_cell("select bosshp from sysclub where cid=$cid");
    if(!$hpinfo){
        $bosshpstr = '';
        foreach ($allcfgs as $c){
            $id = intval($c['id']);
            $bossid = intval($c['monsterid']);
            $cfgboss = sql_fetch_one("select * from cfg_monster where mid = $bossid");
            if($cfgboss){
                $bosshp = $cfgboss['hp'];
            }
            $hparr = array($id, $bosshp);
            $hpdata = implode("|", $hparr);
            if (empty($bosshpstr)){
                $bosshpstr = $hpdata;
            }else{
                $bosshpstr .= ",".$hpdata;
            }
        }
        sql_update("update sysclub set bosshp = '$bosshpstr' where cid=$cid");
    }
    $sysclub = sql_fetch_one("select bosshp from sysclub where cid=$cid");
    return array(
        1,
        $cfgschool,
        $sysclub    //是否设置关卡信息
    );
}


//设置公会学院数据
/*function setClubSchool($uid, $params)
{
    $idstr = $params[0];
    $ids =  explode(",", $idstr);
    foreach ($ids as $id){
        $cfg = sql_fetch_one("select * from cfg_clubschool where id = $id");
        if(!$cfg){
            return array(
                0,
                STR_DataErr
            );
        }
    }
    $bossid = intval($cfg['monsterid']);
    $cfgboss = sql_fetch_one("select * from cfg_monster where mid = $bossid");
    if(!$cfgboss){
        return array(
            0,
            STR_DataErr
        );
    }
    $bosshp = $cfgboss['hp'];
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $state = intval(sql_fetch_one_cell("select state from uclub where cid=$cid and uid=$uid"));
    if ($state < 100) {
        return array(
            0,
            STR_CaoZuo2
        );
    }
    $clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
    $cfgschool = sql_fetch_rows("select * from cfg_clubschool where clublv <= $clublv");
    return array(
        1,
        $cfgschool
    );
}*/

//随机技能
function _randomClubBossSkill($uid)
{
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $spartners = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);
    $leader = 0;
    if(intval($myequip['leader']) > 0){
        $leader = intval($myequip['leader']);
    }
    else{
        $leader = $pids[0];
    }
    $skillstrs = array();
    for($p = 0; $p < count($pids); $p ++){
        $stagepartner = array();
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $skillstr = $pinfo['skill'];
            $skills = explode(",", $skillstr);
            $pos = $i + 1;
            if (intval($pinfo['partnerid']) == $pids[$p]){
                $stagepartner[] = array(id => $pinfo['partnerid'], pos => 100 + $pos, skill => $skills[0], rate => 5000);
            }
            else {
                $stagepartner[] = array(id => $pinfo['partnerid'], pos => 100 + $pos, skill => $skills[0], rate => 2500);
            }
        }
        $skill = array();
        if(count($stagepartner) > 0){
            do{
                foreach ($stagepartner as $value){
                    $rate = rand(1,10000);
                    if($rate < $value['rate']){
                        $skill[] = $value['pos'].'_'.$value['skill'];
                    }
                    if(count($skill) == 100){
                        break 2;
                    }
                }
            }while(count($skill) <= 100);
        }
        $skillstr = implode(",", $skill);
        $skillstrs[] = array(intval($pids[$p]),$skillstr);
    }
    //========================================
    foreach ($stagepartner as &$v){
        unset($v['skill']);
        unset($v['rate']);
    }
    $parterstr = '';
    foreach ($stagepartner as $s){
        if(empty($parterstr)){
            $parterstr = $s['pos']."|".$s['id'];
        }
        else{
            $parterstr = $parterstr.",".$s['pos']."|".$s['id'];
        }
    }
    sql_update("insert into uclub (uid,pos) values ($uid,'$parterstr') on DUPLICATE KEY update uid=$uid,pos='$parterstr'");
    //=========================================
    return array(
        $skillstrs
    );
}

//开始公会Boss战斗
function startClubBossBattle($uid, $params)
{
    $id = $params[0];
    $cfg = sql_fetch_one("select * from cfg_clubschool where id = $id");
    if(!$cfg){
        return array(
            0,
            STR_DataErr
        );
    }
    $time = intval(sql_fetch_one_cell("select UNIX_TIMESTAMP(CURDATE())"));
    $nowtime = time();
    $stimearr = explode(",", $cfg['starttime']);
    $etimearr = explode(",", $cfg['endtime']);
    $starttime1 = $time + intval($stimearr[0]);
    $endtime1 = $time + intval($etimearr[0]);
    $starttime2 = $time + intval($stimearr[0]);
    $endtime2 = $time + intval($etimearr[0]);
    if(!(($nowtime > $starttime1 && $nowtime < $endtime1) || ($nowtime > $starttime2 && $nowtime < $endtime2))){
        return array(
            0,
            STR_Act_Not_Start
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    if(!$partnerid){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $leader = intval($myequip['leader']);
    if ($clublv < intval($cfg['clublv'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    //随机技能
    //======================================
    $randskill = _randomClubBossSkill($uid);
    $skillstrs = $randskill[0];
    //=====================================
    $partnerattr = array();
    $stagep = explode(",", $partnerid);
    foreach ($stagep as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $partnerstr=implode(",", $stagep);
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerstr)");
    $skilllv_arr[]=$skills;
    
    $girl = array();
    $girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
    if($girlid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    }
    //学院次数
    $countinfo = array();
    $isexist = false;
    $countstr = $uclub['schoolcount'];
    $countarr = explode(",", $countstr);
    foreach ($countarr as $c){
        $countinfo[] = explode("|", $c);
    }
    foreach ($countinfo as &$ci){
        if(intval($ci[0]) == $id){
            if(intval($ci[1]) >= 5){
                return array(
                    0,
                    STR_PVP_Boss
                );
            }
            $ci[1] += 1;
            $isexist = true;
        }
    }
    if(!$isexist){
        $countinfo[] = array($id,1);
    }
    foreach ($countinfo as $cc){
        $countstrarr[] = implode("|", $cc);
    }
    $countstr = implode(",", $countstrarr);
    sql_update("update uclub set schoolcount = '$countstr' where uid=$uid");
    $bosshp = sql_fetch_one_cell("select bosshp from sysclub where cid=$cid");
    _updateUTaskProcess($uid, 1008);
    return array(
        1,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader,
        $bosshp,
    	$skilllv_arr
    );
}

//结束公会战斗
function endClubBossBattle($uid, $params)
{
    $id = $params[0];
    $hp = $params[1];
    $cfg = sql_fetch_one("select * from cfg_clubschool where id = $id");
    if(!$cfg){
        return array(
            0,
            STR_DataErr
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $sysclub = sql_fetch_one("select * from sysclub where uid = $uid");

    $hpinfo = array();
    $hpstr = $sysclub['bosshp'];
    $hparr = explode(",", $hpstr);
    foreach ($hparr as $h){
        $hpinfo[] = explode("|", $h);
    }
    foreach ($hpinfo as &$hi){
        if(intval($hi[0]) == $id){
            if(intval($hi[1]) >= $hp){
                $hi[1] -= $hp;
            }
            else{
                $hi[1] = 0;
            }
        }
    }
    foreach ($hpinfo as $cc){
        $hpstrarr[] = implode("|", $cc);
    }
    $hpstr = implode(",", $hpstrarr);
    sql_update("update sysclub set bosshp = '$hpstr' where uid=$uid");
    $score = intval($cfg['contribute']);
    sql_update("update uclub set totalscore=totalscore+$score where uid=$uid");
    return array(
        1,
        $score
    );
}

//开始公会Boss战斗
function startClubBossBattleh5($uid, $params)
{
	$sign=ecry();
	$id = $params[0];
	$cfg = sql_fetch_one("select * from cfg_clubschool where id = $id");
	if(!$cfg){
		return array(
				0,
				STR_DataErr
		);
	}
	$time = intval(sql_fetch_one_cell("select UNIX_TIMESTAMP(CURDATE())"));
	$nowtime = time();
	$starttime = $time + intval($cfg['starttime']);
	$endtime = $time + intval($cfg['endtime']);
	if($nowtime < $starttime || $nowtime > $endtime){
		return array(
				0,
				STR_Act_Not_Start
		);
	}
	$uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
	if (!$uclub) {
		return array(
				0,
				STR_Club_JoinIn
		);
	}
	$cid = intval($uclub['cid']);
	$clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$myequip = sql_fetch_one("select * from uequip where uid=$uid");
	$partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
	if(!$partnerid){
		return array(
				0,
				STR_Partner_Load_Error
		);
	}
	$leader = intval($myequip['leader']);
	if ($clublv < intval($cfg['clublv'])) {
		return array(
				0,
				STR_Lv_Low2
		);
	}
	//随机技能
	//======================================
	$randskill = _randomClubBossSkill($uid);
	$skillstrs = $randskill[0];
	//=====================================
	$partnerattr = array();
	$stagep = explode(",", $partnerid);
	foreach ($stagep as $v){
		$partnerattr[] = getPartnerAttr($uid,intval($v));
	}
	$partnerstr=implode(",", $stagep);
	$skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerstr)");
	$skilllv_arr[]=$skills;

	$girl = array();
	$girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
	if($girlid){
		$girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
	}
	//学院次数
	$countinfo = array();
	$isexist = false;
	$countstr = $uclub['schoolcount'];
	$countarr = explode(",", $countstr);
	foreach ($countarr as $c){
		$countinfo[] = explode("|", $c);
	}
	foreach ($countinfo as &$ci){
		if(intval($ci[0]) == $id){
			if(intval($ci[1]) >= 5){
				return array(
						0,
						STR_PVP_Boss
				);
			}
			$ci[1] += 1;
			$isexist = true;
		}
	}
	if(!$isexist){
		$countinfo[] = array($id,1);
	}
	foreach ($countinfo as $cc){
		$countstrarr[] = implode("|", $cc);
	}
	$countstr = implode(",", $countstrarr);
	sql_update("update uclub set schoolcount = '$countstr' where uid=$uid");
	$bosshp = sql_fetch_one_cell("select bosshp from sysclub where cid=$cid");
	_updateUTaskProcess($uid, 1008);
	return array(
			1,
			$skillstrs,
			$partnerattr,
			$girl,
			$leader,
			$bosshp,
			$skilllv_arr,
			$sign
	);
}

//结束公会战斗
function endClubBossBattleh5($uid, $params)
{
	//h5 验证1
	$sign=$params[2];
	$times=deci($sign);
	if($times==0)
	{
		return array(
				0,
				STR_Battle_Verify_Error,
					
		);
	}
	else
	{
		if($times-time()>600)
		{
			return array(
					0,
					STR_Battle_Verify_Error,
	
			);
		}
	}
	$id = $params[0];
	$hp = $params[1];
	$cfg = sql_fetch_one("select * from cfg_clubschool where id = $id");
	if(!$cfg){
		return array(
				0,
				STR_DataErr
		);
	}
	$uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
	if (!$uclub) {
		return array(
				0,
				STR_Club_JoinIn
		);
	}
	$cid = intval($uclub['cid']);
	$sysclub = sql_fetch_one("select * from sysclub where uid = $uid");

	$hpinfo = array();
	$hpstr = $sysclub['bosshp'];
	$hparr = explode(",", $hpstr);
	foreach ($hparr as $h){
		$hpinfo[] = explode("|", $h);
	}
	foreach ($hpinfo as &$hi){
		if(intval($hi[0]) == $id){
			if(intval($hi[1]) >= $hp){
				$hi[1] -= $hp;
			}
			else{
				$hi[1] = 0;
			}
		}
	}
	foreach ($hpinfo as $cc){
		$hpstrarr[] = implode("|", $cc);
	}
	$hpstr = implode(",", $hpstrarr);
	sql_update("update sysclub set bosshp = '$hpstr' where uid=$uid");
	return array(
			1,
			1
	);
}






//领取公会战斗奖励
function getClubBossBattleReward($uid, $params)
{
    $id = $params[0];
    $cfgschool = sql_fetch_one("select * from cfg_clubschool where id = $id");
    if(!$cfgschool){
        return array(
            0,
            STR_DataErr
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $rewardstr = $uclub['schoolreward'];
    $rewardinfo = array();
    $rewardarr = explode(",", $rewardstr);
    foreach ($rewardarr as $c){
        $rewardinfo[] = explode("|", $c);
    }
    foreach ($rewardinfo as &$r){
        if(intval($r[0]) == $id){
            if(intval($r[1]) == 1){
                return array(
                    0,
                    STR_Club_Not_SchoolReward
                );
            }
            $r[1] = 1;
        }
    }
    foreach ($rewardinfo as $rr){
        $rewardstrarr[] = implode("|", $rr);
    }
    $rewardstr = implode(",", $rewardstrarr);
    $bosshp = 1000;
    $hpstr = sql_fetch_one_cell("select bosshp from sysclub where cid = $cid");
    $hparr = explode(",", $hpstr);
    foreach ($hparr as $h){
        $hpinfo[] = explode("|", $h);
    }
    foreach ($hpinfo as $hi){
        if(intval($hi[0]) == $id){
            $bosshp = $hi[1];
        }
    }
    if($bosshp > 0){
        return array(
            0,
            STR_Boss_Hp
        );
    }
    $rewardid = intval($cfgschool['reward1']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $items = array();
    if(intval($cfg['item1']) > 0){
        $items[] = array('id' => $cfg['item1'], 'count' => $cfg['count1'], 'prob' => $cfg['prob1']);
    }
    if(intval($cfg['item2']) > 0){
        $items[] = array('id' => $cfg['item2'], 'count' => $cfg['count2'], 'prob' => $cfg['prob2']);
    }
    if(intval($cfg['item3']) > 0){
        $items[] = array('id' => $cfg['item3'], 'count' => $cfg['count3'], 'prob' => $cfg['prob3']);
    }
    if(intval($cfg['item4']) > 0){
        $items[] = array('id' => $cfg['item4'], 'count' => $cfg['count4'], 'prob' => $cfg['prob4']);
    }
    if(intval($cfg['item5']) > 0){
        $items[] = array('id' => $cfg['item5'], 'count' => $cfg['count5'], 'prob' => $cfg['prob5']);
    }
    if(intval($cfg['item6']) > 0){
        $items[] = array('id' => $cfg['item6'], 'count' => $cfg['count6'], 'prob' => $cfg['prob6']);
    }
    $totalprob = 0;
    foreach ($items as $item){
        $totalprob += intval($item['prob']);
    }
    $num = rand(intval($cfg['minget']),intval($cfg['maxget']));
    if($totalprob > 0){
        $rewarditem = array();
        for($i = 1; $i <= $num; $i ++){
            $rand = rand(1,$totalprob);
            $addprob = 0;
            for ($j = 0; $j < count($items); $j ++){
                $addprob += intval($items[$j]['prob']);
                if($rand <= $addprob){
                    $rewarditem[] = $items[$j];
                    break 1;
                }
            }
        }
    }
    $exp = $cfgschool['clubexp'];
    _addExp($uid, $exp);
    $rewarditems = array();
    $equips = array();
    foreach ($rewarditem as $value){
        if(intval($value['id']) == 1){
            _addCoin($uid,intval($value['count']),'公会BOSS战奖励');
            $coin += intval($value['count']);
        }
        elseif(intval($value['id']) == 2){
            _addUg($uid,intval($value['count']),'公会BOSS战奖励');
        }
        elseif(intval($value['id']) > 100000){
            $equips[] = _createEquipByceid($uid, $value['id'], $value['count'], 0);
        }
        else{
            _addItem($uid, $value['id'], $value['count'],'公会BOSS战奖励');
            $rewarditems[] = $value;
        }
    }
    sql_update("update uclub set schoolreward = '$rewardstr' where uid=$uid");
    $schoolinfo = sql_fetch_one("select schoolcount,schoolreward from uclub where uid=$uid");
    return array(
        1,
        $exp,
        $rewarditems,
        $schoolinfo,
        $equips
    );
}

//攻城掠地地图信息
function getClubBattleMapInfo($uid, $params)
{
    $nowtime = time();
    $rows = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
    $openstr = $rows[6]." "."10:00";
    $opentime = strtotime($openstr);
    $counttime = 0;
    if($opentime > $nowtime){
        $counttime = $opentime - $nowtime;
    }
    $clubwar = sql_fetch_rows("select * from sysclubwar");
    if(!$clubwar){
        $clubwar = array('actstate' => 0, 'counttime' => $counttime);
        return array(
            2,
            $clubwar
        );
    }
    $cid = $params[0];
    $clubmap = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
    if($clubmap){
        $cid1 = intval($clubmap['cid1']);
        $cid2 = intval($clubmap['cid2']);
        if($cid1 > 0){
            $clubname1 = sql_fetch_one_cell("select cname as name1 from sysclub where cid = $cid1");
            $clubmap['name1'] = $clubname1;
        }
        else{
            $clubmap['name1'] = '';
        }
        if($cid2 > 0){
            $clubname2 = sql_fetch_one_cell("select cname as name2 from sysclub where cid = $cid2");
            $clubmap['name2'] = $clubname2;
        }
        else{
            $clubmap['name2'] = '';
        }
    }
    $clubmap['counttime'] = $counttime;
    $id = intval($clubmap['id']);
    if(intval($clubmap['time']) > 0){
        $clubmap['difftime'] = intval($clubmap['time']) - $nowtime;
    }
    $holeinfo = sql_fetch_rows("select * from sysclubwarhole where id = $id");
    foreach ($holeinfo as &$hole){
        $uid = intval($hole['uid']);
        if($uid > 0){
            $uinfo = sql_fetch_one("select uname,ulv from uinfo where uid = $uid");
            $hole['name'] = $uinfo['uname'];
            $hole['lv'] = $uinfo['ulv'];
        }
        else{
            $hole['name'] = '';
            $hole['lv'] = 0;
        }
        $stagepartners = $hole['stagepartner'];
        $pidstr = '';
        if($stagepartners){
            $pids = array();
            $pinfos = sql_fetch_rows("select pid,plv from upartner where partnerid in ($stagepartners)");
            foreach($pinfos as $v){
                $pid = intval($v['pid']);
                $plv = intval($v['plv']);
                $pdata = array($pid,$plv);
                $pids[] = implode("|", $pdata);
            }
            $pidstr = implode(",", $pids);
        }
        $hole['pids'] = $pidstr;
        if(intval($hole['time']) > 0){
            $hole['difftime'] = $nowtime - intval($hole['time']);
        }
        if(intval($hole['ts']) > 0){
            $hole['ts'] = $nowtime - intval($hole['ts']);
        }
    }
    //==============
    $cid1 = intval($clubmap['cid1']);
    $cid2 = intval($clubmap['cid2']);
    $score1 = 0;
    $score2 = 0;
    $holeinfo1 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid1");
    foreach($holeinfo1 as $hole1){
        $time = intval($hole1['time']);
        $mintime = intval(($nowtime - $time) / 60 / intval($hole1['needtime']));
        if($mintime > intval($hole1['limit'])){
            $mintime = intval($hole1['limit']);
        }
        $score1 += intval($hole1['occupyscore']) + $mintime * intval($hole1['score']);
    }
    $holeinfo2 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid2");
    foreach($holeinfo2 as $hole2){
        $time = intval($hole2['time']);
        $mintime = intval(($nowtime - $time) / 60 / intval($hole2['needtime']));
        if($mintime > intval($hole2['limit'])){
            $mintime = intval($hole2['limit']);
        }
        $score2 += intval($hole2['occupyscore']) + $mintime * intval($hole2['score']);
    }
    $clubmap['score1'] = $score1;
    $clubmap['score2'] = $score2;
    return array(
        1,
        $clubmap,
        $holeinfo
    );
}

//随机技能
function _randomClubBattleSkill($uid)
{
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $spartners = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);
    $leader = 0;
    if(intval($myequip['leader']) > 0){
        $leader = intval($myequip['leader']);
    }
    else{
        $leader = $pids[0];
    }
    $skillstrs = array();
    for($p = 0; $p < count($pids); $p ++){
        $stagepartner = array();
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $skillstr = $pinfo['skill'];
            $skills = explode(",", $skillstr);
            $pos = $i + 1;
            if (intval($pinfo['partnerid']) == $pids[$p]){
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 100 + $pos, 'skill' => $skills[0], 'rate' => 4286);
            }
            else {
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 100 + $pos, 'skill' => $skills[0], 'rate' => 2857);
            }
        }
        $skill = array();
        if(count($stagepartner) > 0){
            do{
                foreach ($stagepartner as $value){
                    $rate = rand(1,10000);
                    if($rate < $value['rate']){
                        $skill[] = $value['pos'].'_'.$value['skill'];
                    }
                    if(count($skill) == 100){
                        break 2;
                    }
                }
            }while(count($skill) <= 100);
        }
        $skillstr = implode(",", $skill);
        $skillstrs[] = array(intval($pids[$p]),$skillstr);
    }
    //========================================
    foreach ($stagepartner as &$v){
        unset($v['skill']);
        unset($v['rate']);
    }
    $parterstr = '';
    foreach ($stagepartner as $s){
        if(empty($parterstr)){
            $parterstr = $s['pos']."|".$s['id'];
        }
        else{
            $parterstr = $parterstr.",".$s['pos']."|".$s['id'];
        }
    }
    sql_update("insert into uclubwardata (uid,mypos) values ($uid,'$parterstr') on DUPLICATE KEY update uid=$uid,mypos='$parterstr'");
    //=========================================
    return array(
        $skillstrs
    );
}

//随机对手技能
function _randomClubBattleMatchSkill($uid,$id,$holeid)
{
    $holeinfo = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
    $muid = intval($holeinfo['uid']);
    $stagepartners = $holeinfo['stagepartner'];
    $mleader = intval($holeinfo['leader']);
    $skillstrs = array();
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($stagepartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $stagepartners);
    for($p = 0; $p < count($pids); $p ++){
        $stagepartner = array();
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $skillstr = $pinfo['skill'];
            $skills = explode(",", $skillstr);
            $pos = $i + 1;
            if (intval($pinfo['partnerid']) == $pids[$p]){
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 200 + $pos, 'skill' => $skills[0], 'rate' => 4286);
            }
            else {
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 200 + $pos, 'skill' => $skills[0], 'rate' => 2857);
            }
        }
        $skill = array();
        if(count($stagepartner) > 0){
            do{
                foreach ($stagepartner as $value){
                    $rate = rand(1,10000);
                    if($rate < $value['rate']){
                        $skill[] = $value['pos'].'_'.$value['skill'];
                    }
                    if(count($skill) == 100){
                        break 2;
                    }
                }
            }while(count($skill) <= 100);
        }
        $skillstr = implode(",", $skill);
        $skillstrs[] = array(intval($pids[$p]),$skillstr);
    }

    return array(
        $skillstrs
    );
}

//开始攻城掠地战斗
function startClubBattle($uid, $params)
{
    $id = $params[0];
    $holeid = $params[1];
    if($holeid < 1 || $holeid > 11){
        return array(
            0,
            STR_DataErr
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $mapcfg = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
    if(!$mapcfg){
        return array(
            0,
            STR_DataErr
        );
    }
    $actstate = $mapcfg['actstate'];
    if($actstate != 1){
        return array(
            0,
            STR_Act_Not_Start
        );
    }
    $clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    if(!$partnerid){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerid)");
    $skilllv_arr[]=$skills;
    
    $leader = intval($myequip['leader']);
    $girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
    //对手数据
    //===================================
    $holeinfo = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
    $nowtime = time();
    $starttime = intval($holeinfo['ts']);
    if($starttime > 0 && (($nowtime - $starttime) < 180)){
        return array(
            0,
            STR_Club_Battle_Protect
        );
    }
    $mcid = intval($holeinfo['cid']);
    $muid = intval($holeinfo['uid']);
    //============================================
    if($muid == 0){
        sql_update("update sysclubwarhole set cid = $cid, uid = $uid, stagepartner = '$partnerid', leader = $leader, girl = $girlid, time = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
        $hole = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
        return array(
            2,
            $hole
        );
    }
    $mname = sql_fetch_one_cell("select uname from uinfo where uid = $muid");
    $mstagepartner = $holeinfo['stagepartner'];
    $mleader = intval($holeinfo['leader']);
    $mgirlid = intval($holeinfo['girl']);
    $mtime = intval($holeinfo['time']);
    if(!$mstagepartner){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $mskills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($mstagepartner)");
    $mskilllv_arr[]=$mskills;
    //随机技能
    //======================================
    $randskill = _randomClubBattleSkill($uid);
    $skillstrs = $randskill[0];
    //=====================================
    $partnerattr = array();
    $stagep = explode(",", $partnerid);
    foreach ($stagep as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $girl = array();
    if($girlid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    }
    //随机对手技能
    //======================================
    $randskill = _randomClubBattleMatchSkill($muid,$id,$holeid);
    $mskillstrs = $randskill[0];
    //=====================================
    $mpartnerattr = array();
    $mstagep = explode(",", $mstagepartner);
    foreach ($mstagep as $value){
        $mpartnerattr[] = getPartnerAttr($muid,intval($value));
    }
    $mgirl = array();
    if($mgirlid){
        $mgirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$muid and `gid`=$mgirlid");
    }
    sql_update("update sysclubwarhole set ts = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
    $upos = sql_fetch_one_cell("select mypos from uclubwardata where uid = $uid");
    return array(
        1,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader,
        $mskillstrs,
        $mpartnerattr,
        $mgirl,
        $mleader,
        $mname,
        $upos,
    	$skilllv_arr,
    	$mskilllv_arr
    );
}

//结束攻城掠地战斗
function endClubBattle($uid, $params)
{
    $id = $params[0];
    $holeid = $params[1];
    $win = $params[2];
    $verifystr=$params[3];
    if($id < 1 || $id > 11){
        return array(
            0,
            STR_DataErr
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $mapcfg = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
    if(!$mapcfg){
        return array(
            0,
            STR_DataErr
        );
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    if(!$partnerid){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }

    
    $leader = intval($myequip['leader']);
    $girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
    //对手数据
    //===================================
    $holeinfo = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
    $mcid = intval($holeinfo['cid']);
    $muid = intval($holeinfo['uid']);
    $mstagepartner = $holeinfo['stagepartner'];
    $mleader = intval($holeinfo['leader']);
    $mgirlid = intval($holeinfo['girl']);
    $mtime = intval($holeinfo['time']);
    if($win == 1){
    	// 验证
    	// ============================================
    	$yanzhen=_battleCheck($uid,array($verifystr,$holeinfo,3));
    	if(intval($yanzhen[0])==0)
    	{
    		file_put_contents("logpvp.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
    		// 		return array(
    		// 				0,
    		// 				STR_Battle_Verify_Error,
    	
    		// 		);
    	}
    	//===============================================================================
        sql_update("update sysclubwarhole set cid = $cid, uid = $uid, stagepartner = '$partnerid', leader = $leader, girl = $girlid, time = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
    }
    return array(
        1
    );
}


//开始攻城掠地战斗
function startClubBattleh5($uid, $params)
{
	$sign=ecry();
	$id = $params[0];
	$holeid = $params[1];
	if($holeid < 1 || $holeid > 11){
		return array(
				0,
				STR_DataErr
		);
	}
	$uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
	if (!$uclub) {
		return array(
				0,
				STR_Club_JoinIn
		);
	}
	$cid = intval($uclub['cid']);
	$mapcfg = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
	if(!$mapcfg){
		return array(
				0,
				STR_DataErr
		);
	}
	$actstate = $mapcfg['actstate'];
	if($actstate != 1){
		return array(
				0,
				STR_Act_Not_Start
		);
	}
	$clublv = intval(sql_fetch_one_cell("select clv from sysclub where cid=$cid"));
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$myequip = sql_fetch_one("select * from uequip where uid=$uid");
	$partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
	if(!$partnerid){
		return array(
				0,
				STR_Partner_Load_Error
		);
	}
	$skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerid)");
	$skilllv_arr[]=$skills;

	$leader = intval($myequip['leader']);
	$girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
	//对手数据
	//===================================
	$holeinfo = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
	$nowtime = time();
	$starttime = intval($holeinfo['ts']);
	if($starttime > 0 && (($nowtime - $starttime) < 180)){
		return array(
				0,
				STR_Club_Battle_Protect
		);
	}
	$mcid = intval($holeinfo['cid']);
	$muid = intval($holeinfo['uid']);
	//============================================
	if($muid == 0){
		sql_update("update sysclubwarhole set cid = $cid, uid = $uid, stagepartner = '$partnerid', leader = $leader, girl = $girlid, time = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
		$hole = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
		return array(
				2,
				$hole
		);
	}
	$mname = sql_fetch_one_cell("select uname from uinfo where uid = $muid");
	$mstagepartner = $holeinfo['stagepartner'];
	$mleader = intval($holeinfo['leader']);
	$mgirlid = intval($holeinfo['girl']);
	$mtime = intval($holeinfo['time']);
	if(!$mstagepartner){
		return array(
				0,
				STR_Partner_Load_Error
		);
	}
	$mskills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($mstagepartner)");
	$mskilllv_arr[]=$mskills;
	//随机技能
	//======================================
	$randskill = _randomClubBattleSkill($uid);
	$skillstrs = $randskill[0];
	//=====================================
	$partnerattr = array();
	$stagep = explode(",", $partnerid);
	foreach ($stagep as $v){
		$partnerattr[] = getPartnerAttr($uid,intval($v));
	}
	$girl = array();
	if($girlid){
		$girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
	}
	//随机对手技能
	//======================================
	$randskill = _randomClubBattleMatchSkill($muid,$id,$holeid);
	$mskillstrs = $randskill[0];
	//=====================================
	$mpartnerattr = array();
	$mstagep = explode(",", $mstagepartner);
	foreach ($mstagep as $value){
		$mpartnerattr[] = getPartnerAttr($muid,intval($value));
	}
	$mgirl = array();
	if($mgirlid){
		$mgirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$muid and `gid`=$mgirlid");
	}
	sql_update("update sysclubwarhole set ts = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
	$upos = sql_fetch_one_cell("select mypos from uclubwardata where uid = $uid");
	return array(
			1,
			$skillstrs,
			$partnerattr,
			$girl,
			$leader,
			$mskillstrs,
			$mpartnerattr,
			$mgirl,
			$mleader,
			$mname,
			$upos,
			$skilllv_arr,
			$mskilllv_arr,
			$sign
	);
}

//结束攻城掠地战斗
function endClubBattleh5($uid, $params)
{
	$id = $params[0];
	$holeid = $params[1];
	$win = $params[2];
	$verifystr=$params[3];
	if($id < 1 || $id > 11){
		return array(
				0,
				STR_DataErr
		);
	}
	$uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
	if (!$uclub) {
		return array(
				0,
				STR_Club_JoinIn
		);
	}
	$cid = intval($uclub['cid']);
	$mapcfg = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
	if(!$mapcfg){
		return array(
				0,
				STR_DataErr
		);
	}
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$myequip = sql_fetch_one("select * from uequip where uid=$uid");
	$partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
	if(!$partnerid){
		return array(
				0,
				STR_Partner_Load_Error
		);
	}


	$leader = intval($myequip['leader']);
	$girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
	//对手数据
	//===================================
	$holeinfo = sql_fetch_one("select * from sysclubwarhole where id = $id and holeid = $holeid");
	$mcid = intval($holeinfo['cid']);
	$muid = intval($holeinfo['uid']);
	$mstagepartner = $holeinfo['stagepartner'];
	$mleader = intval($holeinfo['leader']);
	$mgirlid = intval($holeinfo['girl']);
	$mtime = intval($holeinfo['time']);
	if($win == 1){
		// 验证
		//h5 验证1
		$sign=$params[4];
		$times=deci($sign);
		if($times==0)
		{
			return array(
					0,
					STR_Battle_Verify_Error,
						
			);
		}
		else
		{
			if($times-time()>600)
			{
				return array(
						0,
						STR_Battle_Verify_Error,
		
				);
			}
		}
		// ============================================
		$yanzhen=_battleCheck($uid,array($verifystr,$holeinfo,3));
		if(intval($yanzhen[0])==0)
		{
			file_put_contents("logpvp.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
			// 		return array(
			// 				0,
			// 				STR_Battle_Verify_Error,
			 
			// 		);
		}
		//===============================================================================
		sql_update("update sysclubwarhole set cid = $cid, uid = $uid, stagepartner = '$partnerid', leader = $leader, girl = $girlid, time = UNIX_TIMESTAMP() where id = $id and holeid = $holeid");
	}
	return array(
			1
	);
}


function _my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
    if(is_array($arrays)){
        foreach ($arrays as $array){
            if(is_array($array)){
                $key_arrays[] = $array[$sort_key];
            }else{
                return false;
            }
        }
    }else{
        return false;
    }
    array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
    return $arrays;
}

//获取攻城掠地战斗排行
function getClubBattleRank($uid, $params)
{
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $cid = intval($uclub['cid']);
    $mapcfg = sql_fetch_one("select * from sysclubwar where cid1 = $cid or cid2 = $cid");
    if(!$mapcfg){
        return array(
            0,
            STR_DataErr
        );
    }
    $id = intval($mapcfg['id']);
    $ranks = array();
    $nowtime = time();
    $holeinfo = sql_fetch_rows("select s.id,s.holeid,s.cid,s.uid,s.time,u.uname,u.ulv,u.zhanli,b.cname,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join uinfo u on s.uid = u.uid inner join sysclub b on b.cid = s.cid where s.id = $id");
    foreach($holeinfo as $hole){
        $id = intval($hole['id']);
        $suid = intval($hole['uid']);
        $name = $hole['uname'];
        $lv = intval($hole['ulv']);
        $zhanli = intval($hole['zhanli']);
        $time = intval($hole['time']);
        $mintime = intval(($nowtime - $time) / 60 / intval($hole['needtime']));
        if($mintime > intval($hole['limit'])){
            $mintime = intval($hole['limit']);
        }
        $score = intval($hole['occupyscore']) + $mintime * intval($hole['score']);
        $cname = $hole['cname'];
        $isexit = false;
        foreach($ranks as &$r){
            if($suid == intval($r['uid'])){
                $r['score'] = intval($r['score']) + $score;
                $isexit = true;
            }
        }
        if(!$isexit){
            $ranks[] = array('uid' => $suid, 'name' => $name, 'lv' => $lv, 'zhanli' => $zhanli, 'score' => $score, 'cname' => $cname);
        }  
    }
    $ranks = _my_sort($ranks,'score',SORT_DESC,SORT_NUMERIC);
    $index = 1;
    foreach ($ranks as &$r){
        $r['index'] = $index;
        $index ++;
    }
    return array(
        1,
        $ranks
    );
}

//初始化攻城掠地活动
function initClubBattleAct($uid, $params)
{
 //   sql_update("update sysclubwar set time = 0, actstate = 0, wincid = 0");
    sql_update("delete from sysclubwar");
    sql_update("update sysclubwarhole set cid = 0, uid = 0, stagepartner = '', leader = 0, girl = 0, time = 0, ts = 0, goods = '', goodtime = 0");
    return array(
        1
    );
}

//开启攻城掠地活动
function startClubBattleAct($uid, $params)
{
    $rank = sql_fetch_rows("select cid,zhanli from sysclub order by zhanli");
    for($i = 0; $i < count($rank); $i ++){
        $cid = 0;
        $cid = intval($rank[$i]['cid']);
        if($i % 2 == 0){
            $index = $i + 1;
            sql_insert("insert into sysclubwar (id,time,cid1) values ($index,UNIX_TIMESTAMP() + 172800 ,$cid) on duplicate key update time = UNIX_TIMESTAMP() + 172800, cid1 = $cid");
        }
        if($i % 2 == 1){
            sql_insert("insert into sysclubwar (id,cid2) values ($i,$cid) on duplicate key update cid2 = $cid");
        }
        for($j = 1; $j <= 11; $j ++){
            sql_insert("insert ignore into sysclubwarhole (id,holeid) values ($index,$j)");
        }
    }
    sql_update("update sysclubwar set actstate = 1");
    return array(
        1
    );
}

//结束攻城掠地活动
function endClubBattleAct($uid, $params)
{
    $clubwar = sql_fetch_rows("select * from sysclubwar");
    foreach($clubwar as $value){
        $id = $value['id'];
        $cid1 = $value['cid1'];
        $cid2 = $value['cid2'];
        $score1 = 0;
        $score2 = 0;
        $nowtime = time();
        $holeinfo1 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid1");
        foreach($holeinfo1 as $hole1){
            $cid1 = intval($hole1['cid']);
            $time = intval($hole1['time']);
            $mintime = intval(($nowtime - $time) / 60 / intval($hole1['needtime']));
            if($mintime > intval($hole1['limit'])){
                $mintime = intval($hole1['limit']);
            }
            $score1 += intval($hole1['occupyscore']) + $mintime * intval($hole1['score']);
        }
        $holeinfo2 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid2");
        foreach($holeinfo2 as $hole2){
            $cid2 = intval($hole2['cid']);
            $time = intval($hole2['time']);
            $mintime = intval(($nowtime - $time) / 60 / intval($hole2['needtime']));
            if($mintime > intval($hole2['limit'])){
                $mintime = intval($hole2['limit']);
            }
            $score2 += intval($hole2['occupyscore']) + $mintime * intval($hole2['score']);
        }
        if($score1 > $score2){
            sql_update("update `sysclubwar` set wincid = $cid1 where id = $id");
        }
        elseif($score1 < $score2){
            sql_update("update `sysclubwar` set wincid = $cid2 where id = $id");
        }
    }
    sql_update("update sysclubwar set time = UNIX_TIMESTAMP(),actstate = 2");
    return array(
        1
    );
}

?>
