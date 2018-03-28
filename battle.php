<?php

date_default_timezone_set('Asia/Shanghai');

//获取特殊副本数据
function getSpecialMap($uid, $params)
{
    $cfgs = sql_fetch_rows("select * from cfg_specialmaptime");
    $uspve = sql_fetch_one("select * from uspve where uid=$uid");
    $nowtime = time();
    $opencfg = array();
    $umapids = $uspve['mapid'];
    $umapinfo = array();
    if(!empty($umapids)){
        $umaparr = explode(",", $umapids);
        foreach ($umaparr as $v){
            $umap = explode("|", $v);
            $type = intval($umap[0]);
            $id = intval($umap[1]);
            $umapinfo[$type] = $id;
        }
    }
    foreach ($cfgs as &$cfg){
        $openstr = $cfg['opentime'];
        $closestr = $cfg['closetime'];
        $maptype = intval($cfg['maptype']);
        $opentime = strtotime($openstr);
        $closetime = strtotime($closestr);
        $isopen = 0;
        $time = 0;
        if($nowtime >= $opentime && $nowtime <= $closetime){
            $isopen = 1;
        }
        $cfg['type'] = 0;
        if($isopen){
            $time = $closetime - $nowtime; 
        }
        else{
            $usmapinfo = sql_fetch_one("select * from usmapticket where uid = $uid and maptype = $maptype");
            if($usmapinfo){
                $endtime = intval($usmapinfo['time']);
                if($nowtime < $endtime){
                    $time = $endtime - $nowtime;
                    $isopen = 1;
                    $cfg['type'] = 1;
                }
            }   
        }
        $cfg['opentimestamp'] = $opentime;
        $cfg['closetimestamp'] = $closetime;
        $cfg['isopen'] = $isopen;
        $cfg['time'] = $time;
        if(count($umapinfo) == 0){
            $cfg['mapid'] = 0;
        }
        else{
            if(array_key_exists($maptype,$umapinfo)){
                foreach ($umapinfo as $key => $value){
                    if($key == $maptype){
                        $cfg['mapid'] = $value;
                    }
                }
            }
            else{
                $cfg['mapid'] = 0;
            }
        }
        if($isopen){
            $opencfg[] = $cfg;
        }
    }
    return array(
        1,
        $opencfg
    );
}

function _checkSpecialMapStagePartner($uid)
{
    $cfgs = sql_fetch_rows("select * from cfg_specialmaptime");
    $uspve = sql_fetch_one("select * from uspve where uid=$uid");
    $nowtime = time();
    $isopen = 0;
    foreach ($cfgs as &$cfg){
        $openstr = $cfg['opentime'];
        $closestr = $cfg['closetime'];
        $maptype = intval($cfg['maptype']);
        $opentime = strtotime($openstr);
        $closetime = strtotime($closestr);
        if($nowtime >= $opentime && $nowtime <= $closetime){
            $isopen = 1;
        }
    }
    if(!$isopen){
        sql_update("update uequip set stagepartner='',leader=0 where uid=$uid");
    }
}

//随机技能
function _randomSMapSkill($uid)
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
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 100 + $pos, 'skill' => $skills[0], 'rate' => 5000);
            }
            else {
                $stagepartner[] = array('id' => $pinfo['partnerid'], 'pos' => 100 + $pos, 'skill' => $skills[0], 'rate' => 2500);
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
    sql_update("insert into uspve (uid,pos) values ($uid,'$parterstr') on DUPLICATE KEY update uid=$uid,pos='$parterstr'");
    //=========================================
    return array(
        $skillstrs
    );
}


//开始特殊副本战斗
function startSpecialMapBattle($uid, $params)
{
    $mapid = $params[0];
    $cfgid = $params[1];
    $mapcfg = sql_fetch_one("select * from cfg_specialmap where id=$mapid");
    $limitcfg = sql_fetch_one("select * from cfg_specialmaptime where id=$cfgid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $uspve = sql_fetch_one("select * from uspve where uid=$uid");
    $umapids = $uspve['mapid'];
    $maptype = $mapcfg['maptype'];
    $limittype = $mapcfg['limittype'];
    $limitjobstr = $mapcfg['limitocc'];
    $isfirst = intval($mapcfg['isfirst']);
    $keynum = $mapcfg['key'];
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 3");
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
    if($limitjobstr && $limittype == 5){
        $limitjob = explode(",", $limitjobstr);
        $partnerinfo = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($partnerid)");
        foreach ($partnerinfo as $info){
            $job = intval($info['mainp']);
            if(in_array($job, $limitjob)){
                return array(
                    0,
                    STR_Partner_Job_Error
                );
            }
        }
    }
    //判断地图是否合法
    $umapinfo = array();
    if(!empty($umapids)){
        $umaparr = explode(",", $umapids);
        foreach ($umaparr as $v){
            $umap = explode("|", $v);
            $type = intval($umap[0]);
            $id = intval($umap[1]);
            $umapinfo[$type] = $id;
        }
        if(array_key_exists("$maptype",$umapinfo)){
            foreach ($umapinfo as $key => $value){
                if($key == $maptype){
                    if(!($value >= $mapid || $value == ($mapid - 1))){
                        return array(
                            0,
                            STR_Special_Map_Error
                        );
                    }
                }
            }
        }
        else{
            if($isfirst == 0){
                return array(
                    0,
                    STR_Special_Map_Error
                );
            }
        }
    }
    else{
        if($isfirst == 0){
            return array(
                0,
                STR_Special_Map_Error
            );
        }
    }
    if(!_checkKey($uid,$keynum)){
        return array(
            0,
            STR_KeyOff
        );
    }
    $ulv = intval($uinfo['ulv']);
    if ($ulv < intval($limitcfg['openlv'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $opentime = strtotime($limitcfg['opentime']);
    $closetime = strtotime($limitcfg['closetime']);
    $nowtime = time();
    if($nowtime < $opentime || $nowtime > $closetime){
        return array(
            0,
            STR_Act_Not_Start
        );
    }
    $awardid = intval($mapcfg['awardid']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$awardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    //随机技能
    //======================================
    $randskill = _randomSMapSkill($uid);
    $skillstrs = $randskill[0];
    //=====================================
    $partnerattr = array();
    $stagep = explode(",", $partnerid);
    foreach ($stagep as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $coin = $mapcfg['coin'];
    $rewardnum = rand(intval($cfg['minget']),intval($cfg['maxget']));
    sql_update("insert into uspve (uid,time,rewardnum) values ($uid,UNIX_TIMESTAMP(),$rewardnum) on DUPLICATE KEY update uid=$uid,time=UNIX_TIMESTAMP(),rewardnum=$rewardnum");
    $skillnum = 0;
    foreach ($skillstrs as $skillv){
        $skilldata = $skillv[1];
        if($skillnum == 0){
            sql_update("update uspve set randskill = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 1){
            sql_update("update uspve set randskill1 = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 2){
            sql_update("update uspve set randskill2 = '$skilldata' where uid = $uid");
        }
        $skillnum ++;
    }
    $girl = array();
    $girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
    if($girlid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    }
    $upos = sql_fetch_one_cell("select pos from `uspve` where `uid`=$uid");
    _updateUTaskProcess($uid, 1015);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 2;
    $logparams[] = 100;
    pvefightlog($logparams);
    return array(
        1,
        $coin,
        $rewardnum,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader,
        $upos,
    	$skilllv_arr
    );
}

//开始特殊副本战斗
function startSpecialMapBattleh5($uid, $params)
{
	$sign=ecry();
	$mapid = $params[0];
	$cfgid = $params[1];
	$mapcfg = sql_fetch_one("select * from cfg_specialmap where id=$mapid");
	$limitcfg = sql_fetch_one("select * from cfg_specialmaptime where id=$cfgid");
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$uspve = sql_fetch_one("select * from uspve where uid=$uid");
	$umapids = $uspve['mapid'];
	$maptype = $mapcfg['maptype'];
	$limittype = $mapcfg['limittype'];
	$limitjobstr = $mapcfg['limitocc'];
	$isfirst = intval($mapcfg['isfirst']);
	$keynum = $mapcfg['key'];
	$cfg = sql_fetch_one("select * from cfg_funcunlock where id = 3");
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
	if($limitjobstr && $limittype == 5){
		$limitjob = explode(",", $limitjobstr);
		$partnerinfo = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($partnerid)");
		foreach ($partnerinfo as $info){
			$job = intval($info['mainp']);
			if(in_array($job, $limitjob)){
				return array(
						0,
						STR_Partner_Job_Error
				);
			}
		}
	}
	//判断地图是否合法
	$umapinfo = array();
	if(!empty($umapids)){
		$umaparr = explode(",", $umapids);
		foreach ($umaparr as $v){
			$umap = explode("|", $v);
			$type = intval($umap[0]);
			$id = intval($umap[1]);
			$umapinfo[$type] = $id;
		}
		if(array_key_exists("$maptype",$umapinfo)){
			foreach ($umapinfo as $key => $value){
				if($key == $maptype){
					if(!($value >= $mapid || $value == ($mapid - 1))){
						return array(
								0,
								STR_Special_Map_Error
						);
					}
				}
			}
		}
		else{
			if($isfirst == 0){
				return array(
						0,
						STR_Special_Map_Error
				);
			}
		}
	}
	else{
		if($isfirst == 0){
			return array(
					0,
					STR_Special_Map_Error
			);
		}
	}
	if(!_checkKey($uid,$keynum)){
		return array(
				0,
				STR_KeyOff
		);
	}
	$ulv = intval($uinfo['ulv']);
	if ($ulv < intval($limitcfg['openlv'])) {
		return array(
				0,
				STR_Lv_Low2
		);
	}
	$opentime = strtotime($limitcfg['opentime']);
	$closetime = strtotime($limitcfg['closetime']);
	$nowtime = time();
	if($nowtime < $opentime || $nowtime > $closetime){
		return array(
				0,
				STR_Act_Not_Start
		);
	}
	$awardid = intval($mapcfg['awardid']);
	$cfg = sql_fetch_one("select * from cfg_reward where id=$awardid");
	if(!$cfg){
		return array(
				0,
				STR_Param_Error
		);
	}
	//随机技能
	//======================================
	$randskill = _randomSMapSkill($uid);
	$skillstrs = $randskill[0];
	//=====================================
	$partnerattr = array();
	$stagep = explode(",", $partnerid);
	foreach ($stagep as $v){
		$partnerattr[] = getPartnerAttr($uid,intval($v));
	}
	$coin = $mapcfg['coin'];
	$rewardnum = rand(intval($cfg['minget']),intval($cfg['maxget']));
	sql_update("insert into uspve (uid,time,rewardnum) values ($uid,UNIX_TIMESTAMP(),$rewardnum) on DUPLICATE KEY update uid=$uid,time=UNIX_TIMESTAMP(),rewardnum=$rewardnum");
	$skillnum = 0;
	foreach ($skillstrs as $skillv){
		$skilldata = $skillv[1];
		if($skillnum == 0){
			sql_update("update uspve set randskill = '$skilldata' where uid = $uid");
		}
		elseif($skillnum == 1){
			sql_update("update uspve set randskill1 = '$skilldata' where uid = $uid");
		}
		elseif($skillnum == 2){
			sql_update("update uspve set randskill2 = '$skilldata' where uid = $uid");
		}
		$skillnum ++;
	}
	$girl = array();
	$girlid = intval(sql_fetch_one_cell("SELECT girl FROM `uequip` WHERE `uid`=$uid"));
	if($girlid){
		$girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
	}
	$upos = sql_fetch_one_cell("select pos from `uspve` where `uid`=$uid");
	_updateUTaskProcess($uid, 1015);
	$logparams = array();
	    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
	$logparams[] = $uid;
	$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
	$logparams[] = $uinfo['ulv'];
	$logparams[] = $mapid;
	$logparams[] = 2;
	$logparams[] = 100;
	pvefightlog($logparams);
	return array(
			1,
			$coin,
			$rewardnum,
			$skillstrs,
			$partnerattr,
			$girl,
			$leader,
			$upos,
			$skilllv_arr,
			$sign
	);
}

//结束特殊副本战斗
function endSpecialMapBattle($uid, $params)
{
    $mapid = $params[0];
    $cfgid = $params[1];
    $win = $params[2];

    if($win != 1){
        $logparams = array();
        	    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $mapid;
        $logparams[] = 2;
        $logparams[] = $win;
        pvefightlog($logparams);
        return array(
            2,
            $win
        );
    }
    //============================================
    $mapcfg = sql_fetch_one("select * from cfg_specialmap where id=$mapid");
    $limitcfg = sql_fetch_one("select * from cfg_specialmaptime where id=$cfgid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $uspve = sql_fetch_one("select * from uspve where uid=$uid");
    if(!$uspve){
        return array(
            0,
            STR_DataErr2
        );
    }
    $umapids = $uspve['mapid'];
    $maptype = $mapcfg['maptype'];
    $isfirst = intval($mapcfg['isfirst']);
    $ulv = intval($uinfo['ulv']);
    if ($ulv < intval($limitcfg['openlv'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $opentime = strtotime($limitcfg['opentime']);
    $closetime = strtotime($limitcfg['closetime']);
    $nowtime = time();
    if($nowtime < $opentime || $nowtime > $closetime){
        return array(
            0,
            STR_Act_Not_Start
        );
    }
    //判断地图是否合法
    $umapinfo = array();
    if(!empty($umapids)){
        $umaparr = explode(",", $umapids);
        foreach ($umaparr as $v){
            $umap = explode("|", $v);
            $type = intval($umap[0]);
            $id = intval($umap[1]);
            $umapinfo[$type] = $id;
        }
        if(array_key_exists("$maptype",$umapinfo)){
            foreach ($umapinfo as $key => $value){
                if($key == $maptype){
                    if(!($value >= $mapid || $value == ($mapid - 1))){
                        return array(
                            0,
                            STR_Special_Map_Error
                        );
                    }
                }
            }
        }
        else{
            if($isfirst == 0){
                return array(
                    0,
                    STR_Special_Map_Error
                );
            }
        }
    }
    else{
        if($isfirst == 0){
            return array(
                0,
                STR_Special_Map_Error
            );
        }
    }
    if(array_key_exists("$maptype",$umapinfo)){
        foreach ($umapinfo as $key => $value){
            if($key == $maptype){
                if($value < $mapid){
                    $umapinfo[$maptype] = $mapid;
                }
            }
        }
    }
    else{
        $umapinfo[$maptype] = $mapid;
    }
    asort($umapinfo);
    $mapstr = "";
    foreach ($umapinfo as $key => $value){
        if(empty($mapstr)){
            $mapstr = "$key"."|"."$value";
        }
        else{
            $mapstr = $mapstr.","."$key"."|"."$value";
        }
    }
    $rewardid = intval($mapcfg['awardid']);
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
    $num = $uspve['rewardnum'];
    if($totalprob > 0){
        $rewarditem = array();
        $equip_arr=array();
        for($i = 1; $i <= $num; $i ++){
            $rand = rand(1,$totalprob);
            $addprob = 0;
            for ($j = 0; $j < count($items); $j ++){
                $addprob += intval($items[$j]['prob']);
                if($rand <= $addprob){
                    if(intval($items[$j]['id'])>100000){
                        if(in_array(intval($items[$j]['id']),$equip_arr)){
                            continue;
                        }
                        else {
                            $rewarditem[] = $items[$j];
                            array_push($equip_arr,intval($items[$j]['id']));
                        }
                    }
                    else {
                        if(in_array(intval($items[$j]['id']),$equip_arr)){
                            continue;
                        }
                        else{
                            $rewarditem[] = $items[$j];
                            array_push($equip_arr,intval($items[$j]['id']));
                        }
                    }
                    break 1;
                }
            }
        }
    }
    $key = $mapcfg['key'];
    $coin = $mapcfg['coin'];
    $exp = $mapcfg['exp'];
    _addCoin($uid, $coin,'特殊副本战斗');
    _addExp($uid, $exp);
    $rewarditems = array();
    $equips = array();
    foreach ($rewarditem as $value){
        if(intval($value['id']) == 1){
            _addCoin($uid,intval($value['count']),'特殊副本战斗');
            $coin += intval($value['count']);
        }
        elseif(intval($value['id']) > 100000){
            $equips[] = _createEquipByceid($uid, $value['id'], $value['count'], 0);
        }
        else{
            _addItem($uid, $value['id'], $value['count'],'特殊副本战斗');
            $rewarditems[] = $value;
        }
    }
    _spendKey($uid,$key,'endSpecialMapBattle');
    $girl = array();
    if ($uspve){
        sql_update("insert into uspve (uid,mapid) values ($uid,'$mapstr') on DUPLICATE KEY update uid=$uid,mapid='$mapstr'");
        $girlres = addGirl($uid, $mapid);
        if($girlres[0] == 1){
            $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlres[1]");
        }
    }
    sql_update("update uinfo set pvetime=UNIX_TIMESTAMP() where uid=$uid");
    $star = 0;
    if($win == 1){
        $star = 3;
    }
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 2;
    $logparams[] = $win;
    pvefightlog($logparams);
    return array(
        1,
        $key,
        $exp,
        $coin,
        $rewarditems,
        $win,
        $star,
        $girl,
        $equips
    );
}

//结束特殊副本战斗
function endSpecialMapBattleh5($uid, $params)
{
	$mapid = $params[0];
	$cfgid = $params[1];
	$win = $params[2];
	$verifystr = $params[4];
	if($win != 1){
		$logparams = array();
		$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    	$cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
		$logparams[] = $uid;
		$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
		$logparams[] = $uinfo['ulv'];
		$logparams[] = $mapid;
		$logparams[] = 2;
		$logparams[] = $win;
		pvefightlog($logparams);
		return array(
				2,
				$win
		);
	}
	//============================================
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	if($serverid==1)
	{
		//h5 验证1
		$sign=$params[5];
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
		//h5 验证2
		$yanzhen=_battleCheck($uid,array($verifystr,$mapid,4));
		if(intval($yanzhen[0])!=1)
		{
			file_put_contents("log.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
			return array(
					0,
					STR_Battle_Verify_Error,
			   
			);
		}
	}
	$mapcfg = sql_fetch_one("select * from cfg_specialmap where id=$mapid");
	$limitcfg = sql_fetch_one("select * from cfg_specialmaptime where id=$cfgid");
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$uspve = sql_fetch_one("select * from uspve where uid=$uid");
	if(!$uspve){
		return array(
				0,
				STR_DataErr2
		);
	}
	$umapids = $uspve['mapid'];
	$maptype = $mapcfg['maptype'];
	$isfirst = intval($mapcfg['isfirst']);
	$ulv = intval($uinfo['ulv']);
	if ($ulv < intval($limitcfg['openlv'])) {
		return array(
				0,
				STR_Lv_Low2
		);
	}
	$opentime = strtotime($limitcfg['opentime']);
	$closetime = strtotime($limitcfg['closetime']);
	$nowtime = time();
	if($nowtime < $opentime || $nowtime > $closetime){
		return array(
				0,
				STR_Act_Not_Start
		);
	}
	//判断地图是否合法
	$umapinfo = array();
	if(!empty($umapids)){
		$umaparr = explode(",", $umapids);
		foreach ($umaparr as $v){
			$umap = explode("|", $v);
			$type = intval($umap[0]);
			$id = intval($umap[1]);
			$umapinfo[$type] = $id;
		}
		if(array_key_exists("$maptype",$umapinfo)){
			foreach ($umapinfo as $key => $value){
				if($key == $maptype){
					if(!($value >= $mapid || $value == ($mapid - 1))){
						return array(
								0,
								STR_Special_Map_Error
						);
					}
				}
			}
		}
		else{
			if($isfirst == 0){
				return array(
						0,
						STR_Special_Map_Error
				);
			}
		}
	}
	else{
		if($isfirst == 0){
			return array(
					0,
					STR_Special_Map_Error
			);
		}
	}
	if(array_key_exists("$maptype",$umapinfo)){
		foreach ($umapinfo as $key => $value){
			if($key == $maptype){
				if($value < $mapid){
					$umapinfo[$maptype] = $mapid;
				}
			}
		}
	}
	else{
		$umapinfo[$maptype] = $mapid;
	}
	asort($umapinfo);
	$mapstr = "";
	foreach ($umapinfo as $key => $value){
		if(empty($mapstr)){
			$mapstr = "$key"."|"."$value";
		}
		else{
			$mapstr = $mapstr.","."$key"."|"."$value";
		}
	}
	$rewardid = intval($mapcfg['awardid']);
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
	$num = $uspve['rewardnum'];
	if($totalprob > 0){
		$rewarditem = array();
		$equip_arr=array();
		for($i = 1; $i <= $num; $i ++){
			$rand = rand(1,$totalprob);
			$addprob = 0;
			for ($j = 0; $j < count($items); $j ++){
				$addprob += intval($items[$j]['prob']);
				if($rand <= $addprob){
					if(intval($items[$j]['id'])>100000){
						if(in_array(intval($items[$j]['id']),$equip_arr)){
							continue;
						}
						else {
							$rewarditem[] = $items[$j];
							array_push($equip_arr,intval($items[$j]['id']));
						}
					}
					else {
						if(in_array(intval($items[$j]['id']),$equip_arr)){
							continue;
						}
						else{
							$rewarditem[] = $items[$j];
							array_push($equip_arr,intval($items[$j]['id']));
						}
					}
					break 1;
				}
			}
		}
	}
	$key = $mapcfg['key'];
	$coin = $mapcfg['coin'];
	$exp = $mapcfg['exp'];
	_addCoin($uid, $coin,'特殊副本战斗');
	_addExp($uid, $exp);
	$rewarditems = array();
	$equips = array();
	foreach ($rewarditem as $value){
		if(intval($value['id']) == 1){
			_addCoin($uid,intval($value['count']),'特殊副本战斗');
			$coin += intval($value['count']);
		}
		elseif(intval($value['id']) > 100000){
			$equips[] = _createEquipByceid($uid, $value['id'], $value['count'], 0);
		}
		else{
			_addItem($uid, $value['id'], $value['count'],'特殊副本战斗');
			$rewarditems[] = $value;
		}
	}
	_spendKey($uid,$key,'endSpecialMapBattle');
	$girl = array();
	if ($uspve){
		sql_update("insert into uspve (uid,mapid) values ($uid,'$mapstr') on DUPLICATE KEY update uid=$uid,mapid='$mapstr'");
		$girlres = addGirl($uid, $mapid);
		if($girlres[0] == 1){
			$girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlres[1]");
		}
	}
	sql_update("update uinfo set pvetime=UNIX_TIMESTAMP() where uid=$uid");
	$star = 0;
	if($win == 1){
		$star = 3;
	}
	$logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
	$logparams[] = $uid;
	$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
	$logparams[] = $uinfo['ulv'];
	$logparams[] = $mapid;
	$logparams[] = 2;
	$logparams[] = $win;
	pvefightlog($logparams);
	return array(
			1,
			$key,
			$exp,
			$coin,
			$rewarditems,
			$win,
			$star,
			$girl,
			$equips
	);
}


?>