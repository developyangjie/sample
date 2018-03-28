<?php

/**
 * 接口：获取PVP配置
 * @param $params
 * @return array
 */
function getCfgPvp($params)
{
    $res = array();
    return array(
        1,
        $res
    );
}

/**
 * 接口：获取竞技场排行榜
 *
 * @param
 *            $params
 * @return array
 */
function getPvprank($uid,$params)
{
	$muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
	$serverid=$muinfo['serverid'];
	//判断那个服
	$upvp_num="upvp_".$serverid;
	
    $res = sql_fetch_rows("select u.uid,u.uname,p.index,p.zhanli from uinfo u  LEFT OUTER JOIN $upvp_num p on u.uid=p.uid  where p.index<21 and u.serverid=$serverid order by p.index asc");
    for ($i = 0; $i < count($res); $i ++) {
        $backuid = $res[$i]['uid'];
        $minfo = sql_fetch_one("select * from uinfo where uid=$backuid");
        $mequip = sql_fetch_one("select * from uequip where uid=$backuid");
        $mres = sql_fetch_rows("select * from upartner where uid=$backuid");
        $partnerid = '';
        if($mequip){
            $partnerid = $mequip['pvpstagepartner'];
        }
        $mpids = array();
        if (!empty($partnerid)){
            $mpids = explode(",", $partnerid);
            if(count($mpids) < 3){
                $add = 3 - count($mpids);
                $randp = sql_fetch_rows("select * from `upartner` where uid=$backuid order by rare desc limit 5");
                if(count($randp) >= $add){
                    for($r = 0; $r < $add; $r ++){
                        $mpids[] = $randp[$r]['partnerid'];
                    }
                }
            }
        }
        else{
            $randp = sql_fetch_rows("select * from `upartner` where uid=$backuid order by rare desc limit 5");
            if(count($randp) == 0){
            }
            elseif(count($randp) >= 3){
                for($p = 0; $p < 3; $p ++){
                    $mpids[] = $randp[$p]['partnerid'];
                }
            }
        }
        if(count($mpids) > 0){
            $mpidstr = implode(",",$mpids);
            $mjobs = array();
            $mstageids = array();
            $mpjob = sql_fetch_rows("select partnerid,mainp from upartner where uid=$backuid and partnerid in ($mpidstr)");
            foreach($mpjob as $value){
                $partnerid = $value['partnerid'];
                $mainp = $value['mainp'];
                $mjobs[$partnerid] = $mainp;
            }
            asort($mjobs);
            foreach($mjobs as $key=>$value){
                $mstageids[] = $key;
            }
            
            if(count($mstageids) > 0){
                $res[$i]['partners'] = getPartnerbyPids($backuid,$mstageids);
                $res[$i]['partners']['leader'] = $mstageids[0];
            }
            else{
                $res[$i]['partners'] = array();
                $res[$i]['partners']['leader'] = 0;
            }
        }
        else{
            $res[$i]['partners'] = array();
            $res[$i]['partners']['leader'] = 0;
        }
    }
    return array(
        1,
        $res
    );
}

function _getPvpAward($index)
{
    $pvpawardarr = sql_fetch_rows("select * from cfg_pvpaward order by minindex desc;");
    for ($i = 0; $i < count($pvpawardarr); $i ++) {
        $awardarr = $pvpawardarr[$i];
        $minindex = intval($awardarr['minindex']);
        $maxindex = intval($awardarr['maxindex']);
        $ug = intval($awardarr['dayug']);
        $shengwang = intval($awardarr['dayshengwang']);
        if ($index <= $maxindex && $index >= $minindex) {
            $award['dayug'] = $ug;
            $award['dayshengwang'] = $shengwang;
            return $award;
        }
    }
    $award['dayug'] = 0;
    $award['dayshengwang'] = 0;
    return $award;
}

/**
 * 接口：获取竞技场
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getPvp($uid, $params)
{
    $refresh = $params[0];
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if ($ulv < 20) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    //判断那个服
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $upvp_num="upvp_".$serverid;
    $pvpleader = intval(sql_fetch_one_cell("select pvpleader from uequip where uid = $uid"));
    $uinfo = sql_fetch_one("select *,pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid");
    if (!isset($uinfo)) {
        sql_update("INSERT INTO $upvp_num (uid,win,lose) VALUES ($uid,0,0)");
        $uinfo = sql_fetch_one("select *,pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid");
        //拿出等级最高的3个佣兵
        $partner_pvpstr="";
        $partner_arr=array();
		$bestparter=sql_fetch_rows("select partnerid from upartner where uid=$uid order by  plv desc limit 3");
		foreach ($bestparter as $partersting){
			array_push($partner_arr,(string)$partersting['partnerid']);			
		}
        $partner_pvpstr=implode(",", $partner_arr);
        $leader_pvp=$bestparter[0]['partnerid'];
        sql_update("update uequip set pvpstagepartner='$partner_pvpstr',pvpleader='$leader_pvp' where uid=$uid");
        //设置战力
        $myequip = sql_fetch_one("select * from uequip where uid=$uid");
        $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        $my = new my(1, $myinfo, $myequip);
        $zhanli = $my->zhanli;
        sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
        $upvp=sql_fetch_one("select * from $upvp_num where uid=$uid");
        $bestindex=intval($upvp['index']);
        sql_update("update $upvp_num set zhanli=$zhanli,bestindex=$bestindex where uid=$uid");
        
    }
    $uinfo['pvp'] = intval(sql_fetch_one_cell("select pvp from uinfo where uid=$uid"));
    $uinfo['pvpleader'] = $pvpleader;
    //设置战力
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $my = new my(1, $myinfo, $myequip);
    $zhanli = $my->zhanli;
    sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
    //判断那个服
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $upvp_num="upvp_".$serverid;
    sql_update("update $upvp_num set zhanli=$zhanli where uid=$uid");
    
    $uinfo['zhanli'] =$zhanli;
    $index = intval($uinfo['index']);
    $matchindex = $uinfo['match'];
    $backindex = array();
    if(!$matchindex || $refresh){
        if ($index == 1) {
            $backindex[] = 1;
        }elseif ($index == 2){
            $backindex[] = 1;
            $backindex[] = 2;
        }elseif ($index == 3){
            $backindex[] = 1;
            $backindex[] = 2;
            $backindex[] = 3;
        } elseif($index >= 4 && $index <= 10 ) {
            $backindex[] = $index - 3;
            $backindex[] = $index - 2;
            $backindex[] = $index - 1;
            $backindex[] = $index;
        }else{
            $backindex[] = rand(floor($index * 0.8) - 1, floor($index * 0.89) - 1);
            $backindex[] = rand(floor($index * 0.9) - 1, floor($index * 0.97) - 1);
            $backindex[] = rand(floor($index * 0.98) - 1, (intval($index) - 1));
            $backindex = array_unique($backindex);
            if(count($backindex) < 3)
            {
                $backindex[] = rand($backindex[0], $index);
            }
            $backindex[] = $index;
            $backindex = array_unique($backindex);
            if(count($backindex) < 4)
            {
                $backindex[] = $backindex[0] - 1;
            }
        }
        if(count($backindex) > 0){
            $matchstr = implode(",", $backindex);
            sql_update("update $upvp_num set `match` = '$matchstr' where `index` = $index");
        }
    }
    else{
        $backindex = explode(",", $matchindex);
    }
    $res = array();
    if(count($backindex) == 0){
        return array(
            1,
            $res,
            $uinfo
        );
    }
    $backindexstr = implode(",",$backindex);
    $res = sql_fetch_rows("select u.uid,u.ulv,u.ujob,u.uname,u.zhanli,p.win,p.lose,p.index from uinfo u  LEFT OUTER JOIN $upvp_num p on u.uid=p.uid  where (p.index in ($backindexstr)) order by p.index asc");
    $uward = _getPvpAward($index);
    if ($uward) {
        $uwards = array();
        $a = array();
        $a['itemid'] = 2;
        $a['count'] = intval($uward['dayug']);
        $uwards[] = $a;
        $a['itemid'] = 8;
        $a['count'] = intval($uward['dayshengwang']);
        $uwards[] = $a;
        $uinfo['awards'] = $uwards;
    }
    for ($i = 0; $i < count($res); $i ++) {
        $pindex = intval($res[$i]['index']);
        $muid = intval($res[$i]['uid']);
        $pward = _getPvpAward($pindex);
        if ($pward) {
            $pwards = array();
            $a = array();
            $a['itemid'] = 2;
            $a['count'] = intval($pward['dayug']);
            $pwards[] = $a;
            $a['itemid'] = 8;
            $a['count'] = intval($pward['dayshengwang']);
            $pwards[] = $a;
            $res[$i]['awards'] = $pwards;
        }
        $backuid = $res[$i]['uid'];
        $minfo = sql_fetch_one("select * from uinfo where uid=$backuid");
        $mequip = sql_fetch_one("select * from uequip where uid=$backuid");
        $mres = sql_fetch_rows("select * from upartner where uid=$backuid");
        $partnerid = '';
        $leader = intval($mequip['pvpleader']);
        $braveid = intval($mequip['brave']);
        $brave = 0;
        if($braveid){
            $brave = intval(sql_fetch_one_cell("select pid from upartner where partnerid = $braveid"));
        }
        if($mequip){
            $partnerid = $mequip['pvpstagepartner'];
        }
        $mpids = array();
        if (!empty($partnerid)){
            $mpids = explode(",", $partnerid);
            if(count($mpids) < 3){
                $add = 3 - count($mpids);
                $randp = sql_fetch_rows("select * from `upartner` where uid=$backuid order by rare desc limit 5");
                if(count($randp) >= $add){
                    for($r = 0; $r < $add; $r ++){
                        $mpids[] = $randp[$r]['partnerid'];
                    }
                }
            }
        }
        elseif($uid != $muid){
            $randp = sql_fetch_rows("select * from `upartner` where uid=$backuid order by rare desc limit 5");
            if(count($randp) == 0){
            }
            elseif(count($randp) >= 3){
                for($p = 0; $p < 3; $p ++){
                    $mpids[] = $randp[$p]['partnerid'];
                }
            }
        }
        if(count($mpids) > 0){
            $mpidstr = implode(",",$mpids);
            $mjobs = array();
            $mstageids = array();
            $mpjob = sql_fetch_rows("select partnerid,mainp from upartner where uid=$backuid and partnerid in ($mpidstr)");
            foreach($mpjob as $value){
                $partnerid = $value['partnerid'];
                $mainp = $value['mainp'];
                $mjobs[$partnerid] = $mainp;
            }
            asort($mjobs);
            foreach($mjobs as $key=>$value){
                $mstageids[] = $key;
            }    
            if(count($mstageids) > 0){
                $res[$i]['partners'] = getPartnerbyPids($backuid,$mstageids);
                $res[$i]['partners']['leader'] = $leader;
                $res[$i]['partners']['brave'] = $brave;
            }
            else{
                $res[$i]['partners'] = array();
                $res[$i]['partners']['leader'] = 0;
                $res[$i]['partners']['brave'] = 0;
            }
        }
        else{
            $res[$i]['partners'] = array();
            $res[$i]['partners']['leader'] = 0;
            $res[$i]['partners']['brave'] = 0;
        }
    }
    //=================================================
    return array(
        1,
        $res,
        $uinfo
    );
}
// 随机玩家PVS技能
function randomMySkill($uid)
{
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $spartners = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);
    $leader = 0;
    if (intval($myequip['leader']) > 0) {
        $leader = intval($myequip['leader']);
    } else {
        $leader = $pids[0];
    }

    $skillstrs = array();
    for ($p = 0; $p < count($pids); $p ++) {
        $stagepartner = array();
        for ($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $skillstr = $pinfo['skill'];
            $skills = explode(",", $skillstr);
            $pos = $i + 1;
            if (intval($pinfo['partnerid']) == $pids[$p]) {
                $stagepartner[] = array(
                    id => $pinfo['partnerid'],
                    pos => 100 + $pos,
                    skill => $skills[0],
                    rate => 5000
                );
            } else {
                $stagepartner[] = array(
                    id => $pinfo['partnerid'],
                    pos => 100 + $pos,
                    skill => $skills[0],
                    rate => 2500
                );
            }
        }
        $skill = array();
        if (count($stagepartner) > 0) {
            do {
                foreach ($stagepartner as $value) {
                    $rate = rand(1, 10000);
                    if ($rate < $value['rate']) {
                        $skill[] = $value['pos'] . '_' . $value['skill'];
                    }
                    if (count($skill) == 100) {
                        break 2;
                    }
                }
            } while (count($skill) <= 100);
        }
        $skillstr = implode(",", $skill);
        $skillstrs[] = array(
            intval($pids[$p]),
            $skillstr
        );
    }

    $partnerattr = array();
    foreach ($pids as $v) {
        $partnerattr[] = getPartnerAttr($uid, intval($v));
    }
    $girlid = intval(sql_fetch_one_cell("select girl from uequip where uid=$uid"));
    $girl = array();
    if ($girlid) {
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    }
    return array(
        $uid,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader
    );
}
//随机玩家PVP技能
function randomMyPvpSkill($uid)
{
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $spartners = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);
    $leader = 0;
    if(intval($myequip['pvpleader']) > 0){
        $leader = intval($myequip['pvpleader']);
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
    sql_update("insert into upvpdata (uid,mypos) values ($uid,'$parterstr') on DUPLICATE KEY update uid=$uid,mypos='$parterstr'");
    //=========================================
    $partnerattr = array();
    foreach ($pids as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $girlid = intval(sql_fetch_one_cell("select pvpgirl from uequip where uid=$uid"));
    $girl = array();
    if($girlid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    }
    return array($uid,$skillstrs,$partnerattr,$girl,$leader);
}

//随机对手PVP技能
function randomMatchPvpSkill($muid)
{
    $mequip = sql_fetch_one("select * from uequip where uid=$muid");
    $mpidstr = '';
    if($mequip){
        $mpidstr = $mequip['pvpstagepartner'];
    }
    $mpids = array();
    if (!empty($mpidstr)){
        $mpids = explode(",", $mpidstr);
        if(count($mpids) < 3){
            $add = 3 - count($mpids);
            $randp = sql_fetch_rows("select * from `upartner` where uid=$muid order by rare desc limit 5");
            if(count($randp) >= $add){
                for($i = 0; $i < $add; $i ++){
                    $mpids[] = $randp[$i]['partnerid'];
                }
            }
        }
    }
    else{
        $randp = sql_fetch_rows("select * from `upartner` where uid=$muid order by rare desc limit 5");
        if(count($randp) >= 3){
            for($i = 0; $i < 3; $i ++){
                $mpids[] = $randp[$i]['partnerid'];
            }
        }
    }
    $mpidstr = implode(",",$mpids);
    $mjobs = array();
    $mstageids = array();
    $mres = sql_fetch_rows("select partnerid,uid,skill,mainp from upartner where uid=$muid and partnerid in ($mpidstr) order by mainp asc, partnerid asc");
    foreach($mres as $value){
        $partnerid = $value['partnerid'];
        $mainp = $value['mainp'];
        $mjobs[$partnerid] = $mainp;
    }
    asort($mjobs);
    foreach($mjobs as $key=>$value){
        $mstageids[] = $key;
    }
    $mleader = $mstageids[0];
    
    $skillstrs = array();
    for($p = 0; $p < count($mstageids); $p ++){
        $stagepartner = array();
        for($i = 0; $i < count($mres); $i ++) {
            $pinfo = $mres[$i];
            $skillstr = $pinfo['skill'];
            $skills = explode(",", $skillstr);
            $pos = $i + 1;
            if (intval($pinfo['partnerid']) == $mstageids[$p]){
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
        $skillstrs[] = array(intval($mstageids[$p]),$skillstr);
    }
    
    $mpartnerattr = array();
    foreach ($mstageids as $v){
    	$value = getPartnerAttr ( $muid, intval ( $v ) );
    	$value["$v"]['addattr_hp']=intval($value["$v"]['addattr_hp']*1.3);
        $mpartnerattr[] = $value;
    }

    $mgirlid = intval(sql_fetch_one_cell("select pvpgirl from uequip where uid=$muid"));
    $mgirl = array();
    if($mgirlid){
        $mgirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$muid and `gid`=$mgirlid");
    }
    return array($muid,$skillstrs,$mpartnerattr,$mgirl,$mleader);
}

function _getVipByPvpTimes($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['pvptimes']);
    return $count;
}


// 开始PVP战斗
function startPvpBattle($uid, $params)
{
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	
    $time = sql_fetch_one("select UNIX_TIMESTAMP()-pktime as pvpleft from $upvp_num where uid=$uid");
    if ($time["pvpleft"] < 0) {
        return array(
            0,
            STR_PVP_REST
        );
    }
    $muid = $params[0];
    $pvpcount = intval(sql_fetch_one_cell("select pvp from uinfo where uid=$uid"));
    if($pvpcount <= 0){
        return array(
            0,
            STR_PVP_TIMESOFF
        );
    }
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
    if(!$partnerid){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $myskill_arr=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerid)");
    
    
    $minfo = sql_fetch_one("select * from uinfo where uid=$muid");
    $mequip = sql_fetch_one("select * from uequip where uid=$muid");
    if(!$minfo || !$mequip){
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $mpartnerid=$mequip['pvpstagepartner'] ? $mequip['pvpstagepartner'] : '';
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($mpartnerid)");
    $mskill_arr[]=$skills;
    
    $mname = $minfo['uname'];
    $randp = sql_fetch_rows("select * from `upartner` where uid=$muid order by rare desc limit 5");
    if(count($randp) == 0){
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $gid = intval(sql_fetch_one_cell("SELECT pvpgirl FROM `uequip` WHERE `uid`=$uid"));
    if(!$gid){
        return array(
            0,
            STR_Not_Girl
        );
    }
    sql_update("update uinfo set pvp=pvp-1 where uid=$uid");
    sql_update("update $upvp_num set pktime=UNIX_TIMESTAMP()+300 where uid=$uid");
    //========================玩家的佣兵技能===========================
    $my = randomMyPvpSkill($uid);
    //========================对手的佣兵技能===========================
    $match = randomMatchPvpSkill($muid);
    $upos = sql_fetch_one_cell("select mypos from `upvpdata` where `uid`=$uid");
    _updateUTaskProcess($uid, 1013);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $muid;
    $logparams[] = 3;
    $logparams[] = 100;
    pvefightlog($logparams);

    $clubtask=_startclubtask($uid,9);
    return array(
        1,
        $my,
        $match,
        $mname,
        $upos,
    	$clubtask,
    	$myskill_arr,
    	$mskill_arr
    );
}

// 开始PVP战斗
function startPvpBattleh5($uid, $params)
{
	$sign=ecry();
		//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	
    $time = sql_fetch_one("select UNIX_TIMESTAMP()-pktime as pvpleft from $upvp_num where uid=$uid");
    if ($time["pvpleft"] < 0) {
        return array(
            0,
            STR_PVP_REST
        );
    }
    $muid = $params[0];
    $pvpcount = intval(sql_fetch_one_cell("select pvp from uinfo where uid=$uid"));
    if($pvpcount <= 0){
        return array(
            0,
            STR_PVP_TIMESOFF
        );
    }
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
    if(!$partnerid){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $myskill_arr=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($partnerid)");
    
    
    $minfo = sql_fetch_one("select * from uinfo where uid=$muid");
    $mequip = sql_fetch_one("select * from uequip where uid=$muid");
    if(!$minfo || !$mequip){
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $mpartnerid=$mequip['pvpstagepartner'] ? $mequip['pvpstagepartner'] : '';
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($mpartnerid)");
    $mskill_arr[]=$skills;
    
    $mname = $minfo['uname'];
    $randp = sql_fetch_rows("select * from `upartner` where uid=$muid order by rare desc limit 5");
    if(count($randp) == 0){
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $gid = intval(sql_fetch_one_cell("SELECT pvpgirl FROM `uequip` WHERE `uid`=$uid"));
    if(!$gid){
        return array(
            0,
            STR_Not_Girl
        );
    }
    sql_update("update uinfo set pvp=pvp-1 where uid=$uid");
    sql_update("update $upvp_num set pktime=UNIX_TIMESTAMP()+300 where uid=$uid");
    //========================玩家的佣兵技能===========================
    $my = randomMyPvpSkill($uid);
    //========================对手的佣兵技能===========================
    $match = randomMatchPvpSkill($muid);
    $upos = sql_fetch_one_cell("select mypos from `upvpdata` where `uid`=$uid");
    _updateUTaskProcess($uid, 1013);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $muid;
    $logparams[] = 3;
    $logparams[] = 100;
    pvefightlog($logparams);

    $clubtask=_startclubtask($uid,9);
	return array(
			1,
			$my,
			$match,
			$mname,
			$upos,
			$clubtask,
			$myskill_arr,
			$mskill_arr,
			$sign
	);
}


// 结束PVP战斗
function endPvpBattle($uid, $params)
{
    $puid = $params[0];
    $ret = $params[1];

    //判断那个服
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $upvp_num="upvp_".$serverid;
    //============================================
    $reward = 0;
    $myinfo = sql_fetch_one("select *,CURDATE() as date,CURRENT_TIME() as time from uinfo where uid=$uid");
    $monsterinfo = sql_fetch_one("select u.* from uinfo u  where u.uid=$puid");
    $upvpinfo = sql_fetch_one("select * from $upvp_num where uid=$uid");
    $pupvpinfo = sql_fetch_one("select * from $upvp_num where uid=$puid");
    if ($pupvpinfo) { // 敌人信息存在
        if ($ret == 1) {
            $sysMsg = '';
            if ($pupvpinfo['bestindex'] > $pupvpinfo['index']) {
                $pupvpinfo['bestindex'] = $pupvpinfo['index'];
            }
            //当对面的当前等级超过自己最好成绩
            if ($upvpinfo['bestindex'] > $pupvpinfo['index']) {

                if($pupvpinfo['index'] <= 100){
                    $reward = (500/$pupvpinfo['index']) * sqrt($upvpinfo['bestindex']-$pupvpinfo['index']);
                }
                elseif($pupvpinfo['index'] > 100 && $pupvpinfo['index'] < 100000){
                    $reward = (400/$pupvpinfo['index']) * sqrt($upvpinfo['bestindex']-$pupvpinfo['index']);
                }
                if($reward > 0){
                    if($reward < 5){
                        $reward = 5;
                    }
                    $reward=intval($reward);                   
//                     _addUg($uid, $reward, 'pvp战斗');
                  	$mcontent="恭喜您取得了您的历史最高排名".$pupvpinfo['index']."名，以下奖励请您笑纳";
                    _addMail($uid, "竞技场最高排名奖", $mcontent, $reward, 0, 0);
                }
            }
            $upvpinfo['win'] += 1;
            $pupvpinfo['lose'] += 1;
            // 如果最好排名比敌人当前排名差，发奖励,设置最好排名为敌人的当前排名
            if ($upvpinfo['bestindex'] > $pupvpinfo['index']) {
                $upvpinfo['bestindex'] = $pupvpinfo['index'];
                $sysMsg = sprintf(STR_PVP_SysMsg2,$myinfo['uname'],$monsterinfo['uname'],$pupvpinfo['index']);
            }
            // 如果当前排名低，则交换排名信息
            if ($upvpinfo['index'] > $pupvpinfo['index']) {
                // 更新敌人的排名
                $newwin = intval($pupvpinfo['win']);
                $newlose = intval($pupvpinfo['lose']);
                $newbestindex = intval($pupvpinfo['bestindex']);
                $newindex = intval($upvpinfo['index']);
                // 更新自己的排名
                $pnewwin = intval($upvpinfo['win']);
                $pnewlose = intval($upvpinfo['lose']);
                $pnewbestindex = intval($upvpinfo['bestindex']);
                $pnewindex = intval($pupvpinfo['index']);
                sql_update("update $upvp_num set `match` = '' where `index` = $newindex");
                $pres1 = sql_update("UPDATE $upvp_num SET uid='$puid',win='$newwin',lose='$newlose',bestindex='$newbestindex',pktime=0 where `index`=$newindex and uid=$uid");
                $pres2 = sql_update("update $upvp_num set uid='$uid', win='$pnewwin', lose='$pnewlose', bestindex='$pnewbestindex', pktime=0 where `index`=$pnewindex and uid=$puid");
                if ($pres1 && $pres2) {
                    if (empty($sysMsg)) {
                        if ($pnewindex <= 3) {
                            $sysMsg = sprintf(STR_PVP_SysMsg4,$myinfo['uname'],$monsterinfo['uname']);
                        } elseif ($pnewindex < 10) {
                            $sysMsg = sprintf(STR_PVP_SysMsg3,$myinfo['uname'],$monsterinfo['uname']);
                        }
                    }
                    if (!empty($sysMsg)) {
                        _addSysMsg($sysMsg);
                    }
                    $uname = $myinfo['uname'];
                    $time = $myinfo['date'] . " " . $myinfo['time'];
                    $mcontent = sprintf(STR_PVP_MAIL_LOSS,$time,$uname,$pupvpinfo['index'],$upvpinfo['index']);
                    sql_insert("insert into umail (uid,mtitle,mcontent,mtype,ts) values ($puid,'竞技场排名变化','$mcontent',0,UNIX_TIMESTAMP())");
                    sql_update("update uinfo set mail=1 where uid=$puid");
                }
                $myequip = sql_fetch_one("select * from uequip where uid=$uid");
                $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
                $my = new my(1, $myinfo, $myequip);
                $zhanli = $my->zhanli;
                sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
                sql_update("update $upvp_num set zhanli=$zhanli where uid=$uid");
            } else {}
            $logparams = array();
            $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
            $cuid=$uinfo['cuid'];
            $serverid=$uinfo['serverid'];
            _getSystemData($serverid, $cuid, $logparams);
            $logparams[] = $uid;
            $logparams[] = $uinfo['ulv'];
            $logparams[] = $puid;
            $logparams[] = 3;
            $logparams[] = $ret;
            pvefightlog($logparams);
            //胜利获取荣誉
            $honor=20;
            _addHonor($uid,$honor);
        } else {
            $logparams = array();
            $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
            $cuid=$uinfo['cuid'];
            $serverid=$uinfo['serverid'];
            _getSystemData($serverid, $cuid, $logparams);
            $logparams[] = $uid;
            $logparams[] = $uinfo['ulv'];
            $logparams[] = $puid;
            $logparams[] = 3;
            $logparams[] = $ret;
            pvefightlog($logparams);
            //失败获取荣誉
            $honor=5;
            _addHonor($uid,$honor);
        }
    }
    $clubtask=_endclubtask($uid,9);
    return array(
        1,
        $reward,
    	$honor,
    	$clubtask
    );
}

// 结束PVP战斗
function endPvpBattleh5($uid, $params)
{
	$puid = $params[0];
	$ret = $params[1];
	$verifystr = $params[2];

	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	//     验证
	//     ============================================
	//h5
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	if($serverid==1)
	{
		$yanzhen=_battleCheck($uid,array($verifystr,'',2));
		if(intval($yanzhen[0])!=1)
		{
			file_put_contents("log.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
			return array(
					0,
					STR_Battle_Verify_Error,

			);
		}
	}
	/*if(strlen($verifystr) <= 10){
	 return array(
	 0,
	 STR_Battle_Verify_Error
	 );
	 }
	 $verifydata = array();
	 $verifyarr = explode(";", $verifystr);
	 foreach ($verifyarr as $v){
	 $verifydata[] = explode("|", $v);
	 }
	 $upos = sql_fetch_one_cell("SELECT mypos FROM `upvpdata` WHERE `uid`=$uid");
	 if(!$upos){
	 return array(
	 0,
	 STR_Battle_Verify_Error
	 );
	 }
	 $posarr = explode(",", $upos);
	 $posinfo = array();
	 foreach ($posarr as $pos){
	 $posinfo[] = explode("|", $pos);
	 }
	 foreach ($posinfo as &$p){
	 $p[] = _getPartnerAttrByVerify($uid,intval($p[1]));
	 }
	 foreach($posinfo as $info){
	 foreach ($verifydata as $data){
	 if(intval($info[0]) == intval($data[1])){
	 if(intval($info[2]['patk']) != intval($data[4]) || intval($info[2]['mdef']) != intval($data[5]) || intval($info[2]['pdef']) != intval($data[6]) ||
	 intval($info[2]['crit']) != intval($data[7]) || intval($info[2]['cure']) != intval($data[8]) ){
	 file_put_contents("log2.txt", "$verifystr"."--".print_r($posinfo, TRUE));
	 return array(
	 0,
	 STR_Battle_Verify_Error
	 );
	 }
	 }
	 }
	 }*/
	//============================================
	$reward = 0;
	$myinfo = sql_fetch_one("select *,CURDATE() as date,CURRENT_TIME() as time from uinfo where uid=$uid");
	$monsterinfo = sql_fetch_one("select u.* from uinfo u  where u.uid=$puid");
	$upvpinfo = sql_fetch_one("select * from $upvp_num where uid=$uid");
	$pupvpinfo = sql_fetch_one("select * from $upvp_num where uid=$puid");
	if ($pupvpinfo) { // 敌人信息存在
		if ($ret == 1) {
				//     验证
				//     ============================================
				//h5 验证1
				$sign=$params[3];
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
				$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
				if($serverid==1)
				{
					$yanzhen=_battleCheck($uid,array($verifystr,'',2));
					if(intval($yanzhen[0])!=1)
					{
						file_put_contents("log.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
						return array(
								0,
								STR_Battle_Verify_Error,
								 
						);
					}
				}
				 
				 
			$sysMsg = '';
			if ($pupvpinfo['bestindex'] > $pupvpinfo['index']) {
				$pupvpinfo['bestindex'] = $pupvpinfo['index'];
			}
			//当对面的当前等级超过自己最好成绩
			if ($upvpinfo['bestindex'] > $pupvpinfo['index']) {

				if($pupvpinfo['index'] <= 100){
					$reward = (500/$pupvpinfo['index']) * sqrt($upvpinfo['bestindex']-$pupvpinfo['index']);
				}
				elseif($pupvpinfo['index'] > 100 && $pupvpinfo['index'] < 100000){
					$reward = (400/$pupvpinfo['index']) * sqrt($upvpinfo['bestindex']-$pupvpinfo['index']);
				}
				if($reward > 0){
					if($reward < 5){
						$reward = 5;
					}
					$reward=intval($reward);
					//                     _addUg($uid, $reward, 'pvp战斗');
					$mcontent="恭喜您取得了您的历史最高排名".$pupvpinfo['index']."名，以下奖励请您笑纳";
					_addMail($uid, "竞技场最高排名奖", $mcontent, $reward, 0, 0);
				}
			}
			$upvpinfo['win'] += 1;
			$pupvpinfo['lose'] += 1;
			// 如果最好排名比敌人当前排名差，发奖励,设置最好排名为敌人的当前排名
			if ($upvpinfo['bestindex'] > $pupvpinfo['index']) {
				$upvpinfo['bestindex'] = $pupvpinfo['index'];
				$sysMsg = sprintf(STR_PVP_SysMsg2,$myinfo['uname'],$monsterinfo['uname'],$pupvpinfo['index']);
			}
			// 如果当前排名低，则交换排名信息
			if ($upvpinfo['index'] > $pupvpinfo['index']) {
				// 更新敌人的排名
				$newwin = intval($pupvpinfo['win']);
				$newlose = intval($pupvpinfo['lose']);
				$newbestindex = intval($pupvpinfo['bestindex']);
				$newindex = intval($upvpinfo['index']);
				// 更新自己的排名
				$pnewwin = intval($upvpinfo['win']);
				$pnewlose = intval($upvpinfo['lose']);
				$pnewbestindex = intval($upvpinfo['bestindex']);
				$pnewindex = intval($pupvpinfo['index']);
				sql_update("update $upvp_num set `match` = '' where `index` = $newindex");
				$pres1 = sql_update("UPDATE $upvp_num SET uid='$puid',win='$newwin',lose='$newlose',bestindex='$newbestindex',pktime=0 where `index`=$newindex and uid=$uid");
				$pres2 = sql_update("update $upvp_num set uid='$uid', win='$pnewwin', lose='$pnewlose', bestindex='$pnewbestindex', pktime=0 where `index`=$pnewindex and uid=$puid");
				if ($pres1 && $pres2) {
					if (empty($sysMsg)) {
						if ($pnewindex <= 3) {
							$sysMsg = sprintf(STR_PVP_SysMsg4,$myinfo['uname'],$monsterinfo['uname']);
						} elseif ($pnewindex < 10) {
							$sysMsg = sprintf(STR_PVP_SysMsg3,$myinfo['uname'],$monsterinfo['uname']);
						}
					}
					if (!empty($sysMsg)) {
						_addSysMsg($sysMsg);
					}
					$uname = $myinfo['uname'];
					$time = $myinfo['date'] . " " . $myinfo['time'];
					$mcontent = sprintf(STR_PVP_MAIL_LOSS,$time,$uname,$pupvpinfo['index'],$upvpinfo['index']);
					sql_insert("insert into umail (uid,mcontent,mtype,ts) values ($puid,'$mcontent',0,UNIX_TIMESTAMP())");
					sql_update("update uinfo set mail=1 where uid=$puid");
				}
				$myequip = sql_fetch_one("select * from uequip where uid=$uid");
				$myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
				$my = new my(1, $myinfo, $myequip);
				$zhanli = $my->zhanli;
				sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
				sql_update("update $upvp_num set zhanli=$zhanli where uid=$uid");
			} else {}
			$logparams = array();
			$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    		$cuid=$uinfo['cuid'];
    		$serverid=$uinfo['serverid'];
    		_getSystemData($serverid, $cuid, $logparams);
			$logparams[] = $uid;
			$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
			$logparams[] = $uinfo['ulv'];
			$logparams[] = $puid;
			$logparams[] = 3;
			$logparams[] = $ret;
			pvefightlog($logparams);
			//胜利获取荣誉
			$honor=20;
			_addHonor($uid,$honor);
		} else {
			$logparams = array();
			$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    		$cuid=$uinfo['cuid'];
    		$serverid=$uinfo['serverid'];
    		_getSystemData($serverid, $cuid, $logparams);
			$logparams[] = $uid;
			$uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
			$logparams[] = $uinfo['ulv'];
			$logparams[] = $puid;
			$logparams[] = 3;
			$logparams[] = $ret;
			pvefightlog($logparams);
			//失败获取荣誉
			$honor=5;
			_addHonor($uid,$honor);
		}
	}
	$clubtask=_endclubtask($uid,9);
	return array(
			1,
			$reward,
			$honor,
			$clubtask
	);
}

/**
 * 接口：获取竞技场CD时间
 *
 * @param
 *            $uid传入uid
 * @param $params []
 * @return array
 */
function getPvpCDtime($uid, $params)
{
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	 $uinfo = intval(sql_fetch_one_cell("select pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid"));
	 if(!isset($uinfo))
	 {
			return array(
					0,
					STR_DataErr
			);
	 }
	if($uinfo<0)
	{
		$uinfo=0;
	}
	 
	return array(
			1,
			$uinfo//cd时间
	);
}

/**
 * 接口：购买竞技场次数
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function buyPvp($uid, $params)
{
    $uinfo = sql_fetch_one("select buypvp,pvp from uinfo where uid = $uid");
    $buypvp = intval($uinfo['buypvp']);
    $buycount = 0; 
    if($buypvp < 10){
        $buycount = $buypvp + 1;
    }
    elseif($buypvp >= 10){
        $buycount = 10;
    }
    $cfg = sql_fetch_one("select * from cfg_reflash where type = 1 and times = $buycount");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $money = intval($cfg['money']);
    $cost = intval($cfg['amout']);
    if($money == 1){
        if (! _spendCoin($uid, $cost, "购买PVP挑战次数")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 2){
        if (! _spendGbytype($uid, $cost, "购买PVP挑战次数")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    sql_update("update uinfo set pvp = pvp + 1, buypvp = buypvp + 1 where uid=$uid");
    $uinfo = sql_fetch_one("SELECT pvp as ts FROM uinfo WHERE uid=$uid");
    return array(
        1,
        $uinfo
    );
}


/**
 * 接口：买卖竞技场cd刷新
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function refreshPvpCD($uid, $params)
{
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	if (! _spendGbytype($uid, 20, "buyPvp")) {
		return array(
				0,
				STR_UgOff . "20"
		);
	}
	sql_update("update $upvp_num set pktime=UNIX_TIMESTAMP() where uid=$uid");
	return array(
			1
			
	);
}


function _pvpaward($new, $old)
{
    $ug = 0;
    if ($new >= $old || $old <= 0 || $new <= 0) {
        return 0;
    }
    $award = sql_fetch_rows("select * from cfg_pvpaward where `minindex`<='$old' and `maxindex`>='$new'");
    for ($i = 0; $i < count($award); $i ++) {
        if (count($award) == 1) {
            $ug += intval(($old - $new) * ($award[$i]['bestawardrate'] / 10000));
        } else {
            if ($new >= $award[$i]['minindex']) {
                $ug += intval($award[$i]['bestawardtotal'] - ($new - $award[$i]['minindex']) * ($award[$i]['bestawardrate'] / 10000));
            } elseif ($old <= $award[$i]['maxindex']) {
                $ug += intval(($old - $award[$i]['minindex']) * ($award[$i]['bestawardrate'] / 10000));
            } else {
                $ug += $award[$i]['bestawardtotal'];
            }
        }
    }
    return $ug;
}

/**
 * 接口：获取竞技场用户详情
 *
 * @param
 *            $uid
 * @param $params ['uid']            
 * @return array
 */
function getPvpDetail($uid, $params)
{
    $puid = intval($params[0]);
    
    $uinfo = sql_fetch_one("SELECT u.uid,u.ulv,u.ujob,u.uname,u.zhanli,s.cname
							FROM uinfo u LEFT JOIN uclub c ON u.uid=c.uid 
								LEFT JOIN sysclub s ON c.cid=s.cid WHERE u.uid=$puid");
    $ue = sql_fetch_one("select * from uequip where uid=$puid");
    $equips = sql_fetch_rows("select * from ubag where uid=$puid and euser=1");
    return array(
        1,
        $uinfo,
        $ue,
        $equips
    );
}

/**
 * 接口：获取我的PK时间
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getMyPktime($uid, $params)
{
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	
    $uinfo = sql_fetch_one("select *,pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid");
    if (! $uinfo) {
        sql_update("INSERT INTO $upvp_num (uid,win,lose) VALUES ($uid,0,0)");
        $uinfo = sql_fetch_one("select *,pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid");
    }
    return array(
        1,
        $uinfo
    );
}

/**
 * 接口：消除PK时间
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function clearPktime($uid, $params)
{
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	
    $uinfo = sql_fetch_one("select *,pktime-UNIX_TIMESTAMP() as pkleft from $upvp_num where uid=$uid");
    $needug = 0;
    // TODO 时间冷却
    if (intval($uinfo["pkleft"]) > 0) {
        $second = intval($uinfo["pkleft"]) / 60;
        if ($second <= 1){
            $needug = 10;
        }elseif ($second > 1 && $second <= 2){
            $needug = 20;
        }elseif ($second > 2 && $second <= 3){
            $needug = 30;
        }elseif ($second > 3 && $second <= 4){
            $needug = 40;
        }elseif ($second > 4 && $second <= 5){
            $needug = 50;
        }
        
    }
    if (!_spendGbytype($uid, $needug, 'clearPktime')){
        return array(
                0,
                STR_UgOff
        );
    }
    sql_update("update $upvp_num set pktime=0 where uid=$uid");
    return array(
            1,
            $needug
    );
}

function setEmptyPvpTime($uid,$params){
	//判断那个服
	$serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	$upvp_num="upvp_".$serverid;
	
    $time = sql_fetch_one("select pktime,pktime-UNIX_TIMESTAMP() as pvpright from $upvp_num where uid=$uid");
    if(!$time){
        return array(
            0,
            STR_DataErr
        );
    }
    if($time['pvpright'] <= 0){
        return array(
            0,
            STR_NoNeedEmpty
        );
    }
    $pvpright = intval($time['pvpright']);
    $costUg = 0;
    if( $pvpright > 240){
        $costUg = 50;
    }elseif($pvpright > 180){
        $costUg = 40;
    }elseif($pvpright > 120){
        $costUg = 30;
    }elseif($pvpright > 60){
        $costUg = 20;
    }else{
        $costUg = 10;
    }
    if(!_spendGbytype($uid,$costUg,'emptypvptime')){
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("update $upvp_num set pktime=UNIX_TIMESTAMP() where uid=$uid");
    return array(
        1,
        $costUg
    );
}


?>