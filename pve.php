<?php

function _getPvequickG($pvequick)
{
    if ($pvequick < 1) {
        return 30;
    } elseif ($pvequick < 3) {
        return 50;
    } elseif ($pvequick < 6) {
        return 90;
    } elseif ($pvequick < 10) {
        return 150;
    } elseif ($pvequick < 15) {
        return 250;
    } else {
        return 400;
    }
}

function _simpinfo($arr)
{
    $nearr = array();
    for ($i = 0; $i < count($arr); $i ++) {
        $obj['name'] = $arr[$i]->name;
        $obj['lv'] = $arr[$i]->lv;
        $obj['hp'] = $arr[$i]->hp;
        $obj['mp'] = $arr[$i]->mp;
        $obj['picindex'] = $arr[$i]->picindex;
        $obj['job'] = $arr[$i]->job;
        $obj['sex'] = $arr[$i]->sex;
        $obj['quality'] = $arr[$i]->quality;
        $nearr[$i] = $obj;
    }
    return $nearr;
}

//获取vip的pve金币加成
function _getVipByPveCoin($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['pvecoin']);
    return $percent;
}

//获取vip的pve经验加成
function _getVipByPveExp($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['pveexp']);
    return $percent;
}


//随机任务
function randomTask($uid,$mapid)
{
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
    if(!$mapcfg){
        return array(
            0,
            0
        );
    }
    $md1 = intval($mapcfg['md1']);
    $mdrange1 = $mapcfg['mdrange1'];
    $md2 = intval($mapcfg['md2']);
    $mdrange2 = $mapcfg['mdrange2'];
    $md3 = intval($mapcfg['md3']);
    $mdrange3 = $mapcfg['mdrange3'];
    $rand = rand(1,10000);
    $md = 0;
    if($md1){
        $range1 = explode(",", $mdrange1);
        if(count($range1) == 2){
            if($rand >= intval($range1[0]) && $rand <= intval($range1[1])){
                $md = $md1;
            }
            else{
                $range2 = explode(",", $mdrange2);
                if(count($range2) == 2){
                    if($rand >= intval($range2[0]) && $rand <= intval($range2[1])){
                        $md = $md2;
                    }
                    else{
                        $range3 = explode(",", $mdrange3);
                        if($rand >= intval($range3[0]) && $rand <= intval($range3[1])){
                            $md = $md3;
                        }
                        else{
                            return array(
                                0,
                                0
                            );
                        }
                    }
                }
                else{
                    return array(
                        0,
                        0
                    );
                }
            }
        }
        else{
            return array(
                0,
                0
            );
        }
    }
    //随机任务
    $cfgtask = sql_fetch_rows("select * from cfg_randomtask where difficlut = $md");
    if(!$cfgtask){
        return array(
            0,
            0
        );
    }
    $taskstr = "";
    for($i = 1; $i <= 20; $i ++){
        $rt = rand(0,count($cfgtask)-1);
        $task = $cfgtask[$rt];
        if($task){
            $mid = $task['mid'];
            $r1 = rand(intval($task['min']), intval($task['max']));
            if(empty($taskstr)){
                $taskstr = "$mid"."|"."$i"."|"."$r1";
            }
            else{
                $taskstr = $taskstr.","."$mid"."|"."$i"."|"."$r1";
            } 
        }
    }
    //随机奖励
    $cfgreward = sql_fetch_rows("select * from cfg_randomtaskaward where difficlut = $md");
    if(!$cfgreward){
        return array(
            0,
            0
        );
    }
    $rewardstr = "";
    for($j = 1; $j <= 20; $j ++){
        $rr = rand(0, count($cfgreward)-1);
        $reward = $cfgreward[$rr];
        if($reward){
            $rid = $reward['rid'];
            if(empty($rewardstr)){
                $rewardstr = "$rid"."|"."$j";
            }
            else{
                $rewardstr = $rewardstr.","."$rid"."|"."$j";
            }
        }
    }
    sql_update("insert into upve (uid,mids,rids) values ($uid,'$taskstr','$rewardstr') on DUPLICATE KEY update uid=$uid,mids='$taskstr',rids='$rewardstr'");
    return array(
        1,
        1
    );
}

//随机任务奖励
function _randomTaskReward($uid,$randtasks,$basecoin)
{
    $randomtask = sql_fetch_one("select mids,rids from upve where uid=$uid");
    $mids = explode(",", $randomtask['mids']);
    $rids = explode(",", $randomtask['rids']);
    $marr = array();
    $rarr = array();
    foreach ($mids as $mid){
        $m = explode("|", $mid);
        $marr[] = array(intval($m[1]) => intval($m[0]));
    }
    foreach ($rids as $rid){
        $r = explode("|", $rid);
        $rarr[] = intval($r[0]);
    }
    $taskarr = explode(",", $randtasks);
    $arrcoe = array();
    foreach($taskarr as $task){
        $t = explode("|", $task);
        $arrcoe[] = intval($t[1]);
    }
    $rewardids = array();
    for($i = 0; $i < count($arrcoe); $i ++){
        $rewardids[] = $rarr[$arrcoe[$i]-1];
    }
    $coin = 0;
    $item = array();
    for($j = 0; $j < count($rewardids); $j ++){
        $id = intval($rewardids[$j]);
        $cfgreward = sql_fetch_one("select * from cfg_randomtaskaward where rid=$id");
        if(intval($cfgreward['rtype']) == 1){
            if (intval($cfgreward['itemid']) == 1) {
                _addCoin($uid, intval($cfgreward['amount']),'pve随机任务奖励');
                $coin += intval($cfgreward['amount']);
            } else if(intval($cfgreward['itemid']) == 2){
                _addItem($uid, intval($cfgreward['itemid']), intval($cfgreward['amount']), 'pve随机任务奖励');
                $item[] = array(intval($cfgreward['itemid']), intval($cfgreward['amount']));
            }
        }
        else if(intval($cfgreward['rtype']) == 3){
        	$basecoin = intval($basecoin*intval($cfgreward['amount'])/10000);
        }
    }
    
    return array(
        1,
        $coin,
        $item,
    	$basecoin
    );
}

function _randomPveSkill($uid)
{
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $spartners = $myequip['pvestagepartner'] ? $myequip['pvestagepartner'] : '';
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);
    //好友上阵勇者
    //=====================================
    if(count($pids) == 2){
        $fuid = intval(sql_fetch_one_cell("select fuid from uequip where uid = $uid"));
        if($fuid > 0){
            $fbrave = intval(sql_fetch_one_cell("select brave from ufriend where uid = $uid and fuid = $fuid"));
            $pids[] = $fbrave;
            $fres=sql_fetch_one("select partnerid,uid,skill from upartner where uid=$fuid and partnerid=$fbrave");
            $res[]=$fres;
        }
    }
    //=====================================
    $leader = 0;
    if(intval($myequip['pveleader']) > 0){
        $leader = intval($myequip['pveleader']);
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
    sql_update("insert into upve (uid,pos) values ($uid,'$parterstr') on DUPLICATE KEY update uid=$uid,pos='$parterstr'");
    //=========================================
    return array(
        $skillstrs
    );
}

/**
 * 开始pve战斗
 *
 * @param $uid uid
 * @param $uinfo 用户信息
 * @return array
 */
function startPveBattle($uid, $params)
{
    $mapid = $params[0];
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
   // $allcfg = sql_fetch_rows("select id from cfg_pvemap where maptype=$type and id < 10000 order by id asc");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $umapid = intval($upve['mapid']);
    $uemapid = intval($upve['emapid']);
    $unlock = 0;
    $consume = $mapcfg['consume'];
    //检测精英副本次数
    $emapnumstr = $upve['emapnum'];
    if($mapid > 10000 && $mapid < 100000){
        $arremapnumtest = explode(',',$emapnumstr);
        $emapinfotest = array();
        foreach ($arremapnumtest as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $id = $arr[0];
                $num = $arr[1];
                $emapinfotest[] = array($id,$num);
            }
        }
        $exsit = false;
        $emapnumtest = 0;
        foreach ($emapinfotest as &$map){
            if($map[0] == $mapid){
                $emapnumtest = $map[1];
             //   $map[1] += 1;
                $exsit = true;
            }
        }
        if(!$exsit){
            $emapnumtest = 0;
            $emapinfotest[] = array($mapid,1);
        }
        if($emapnumtest + 1 > 3){
            return array(
                0,
                STR_Pve_EMap_Not_Count
            );
        }
    }
    //地图信息
    if(!$mapcfg){
        return array(
            0,
            STR_Pve_Map_Error
        );
    }
    //面包是否足够
    if(!_checkBread($uid,$consume)){
        return array(
            0,
            STR_BreadOff
        );
    }
    //取出pve阵容
    $uequip = sql_fetch_one("SELECT pvegirl,pveleader,pvestagepartner FROM `uequip` WHERE `uid`=$uid");
    $spartners = $uequip['pvestagepartner'] ? $uequip['pvestagepartner'] : '';
    if(!$spartners){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    //pve的女神
    $gid = intval($uequip['pvegirl']);
    if($gid == 0){
        return array(
            0,
            STR_Not_Girl
        );
    }
    if($umapid > 0){
        $unlock= intval(sql_fetch_one_cell("select unlockmap from cfg_pvemap where id=$umapid"));
    }
    $ulv = intval($uinfo['ulv']);
/*    if ($ulv < intval($mapcfg['level'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }*/
    if($mapid != 1000001){
        if ($upve) {
            if($mapid < 10000){
                if($umapid == 0){
                    if($mapid != 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($mapid > $umapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
            else{
                if($uemapid == 0){
                    if($mapid != 10001){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($umapid >= $unlock && $mapid > $uemapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
        }
        else if($mapid != 1){
            return array(
                0,
                STR_Pve_Map_Error
            );
        }
    }
    //掉落id
    $rewardid = intval($mapcfg['rewardid']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $leader = intval($uequip['pveleader']);
    //随机技能
    //======================================
     $randskill = _randomPveSkill($uid);  
     $skillstrs = $randskill[0];
    //随机技能结束
    //=====================================
    $partnerattr = array();
    $stagepartnerstr = $uequip['pvestagepartner'];
    $stagepartner = explode(",", $stagepartnerstr);
    //好友上阵勇者
    //=====================================
    if(count($stagepartner) == 2){
    	//找出好友的阵容配置
        $fuid = intval(sql_fetch_one_cell("select fuid from uequip where uid = $uid"));
        if($fuid > 0){
            $fbrave = intval(sql_fetch_one_cell("select brave from ufriend where uid = $uid and fuid = $fuid"));
            $stagepartner[] = $fbrave;
            sql_update("update ufriend set time = UNIX_TIMESTAMP() where uid = $uid and fuid = $fuid");
            sql_update("update uequip set fuid = 0 where uid = $uid and fuid = $fuid");
        }
    }
    //=====================================
    foreach ($stagepartner as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($stagepartnerstr)");
    $skilllv_arr[]=$skills;
    
    $coin = $mapcfg['coin'];
    $num = rand(intval($cfg['minget']),intval($cfg['maxget']));

    sql_update("insert into upve (uid,time,rewardnum) values ($uid,UNIX_TIMESTAMP(),$num) on DUPLICATE KEY update uid=$uid,time=UNIX_TIMESTAMP(),rewardnum=$num");
    sql_update("update uinfo set pvetime = UNIX_TIMESTAMP() where uid = $uid");
    $skillnum = 0;
    foreach ($skillstrs as $skillv){
        $skilldata = $skillv[1];
        if($skillnum == 0){
            sql_update("update upve set randskill = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 1){
            sql_update("update upve set randskill1 = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 2){
            sql_update("update upve set randskill2 = '$skilldata' where uid = $uid");
        }
        $skillnum ++;
    }
    $girl = array();
    if($gid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$gid");
    }
    randomTask($uid,$mapid);
    $randomtask = sql_fetch_one("select mids,rids from upve where uid=$uid");
    $upos = sql_fetch_one_cell("select pos from `upve` where `uid`=$uid");
    //============================
    _updateUTaskProcess($uid, 1014);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 1;
    $logparams[] = 100;
    pvefightlog($logparams);
    
    $type=sql_fetch_one_cell("select type from cfg_clubtask where clientmapid=$mapid");
   	$clubtask=_startclubtask($uid,$type);
    return array(
        1,
        $coin,
        $num,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader,
        $randomtask,
        $upos,
    	$clubtask,
    	$skilllv_arr
    );
}

/**
 * 开始pve战斗
 *
 * @param $uid uid
 * @param $uinfo 用户信息
 * @return array
 */
function startPveBattleh5($uid, $params)
{
	$sign=ecry();
	$mapid = $params[0];
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
   // $allcfg = sql_fetch_rows("select id from cfg_pvemap where maptype=$type and id < 10000 order by id asc");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $umapid = intval($upve['mapid']);
    $uemapid = intval($upve['emapid']);
    $unlock = 0;
    $consume = $mapcfg['consume'];
    //检测精英副本次数
    $emapnumstr = $upve['emapnum'];
    if($mapid > 10000 && $mapid < 100000){
        $arremapnumtest = explode(',',$emapnumstr);
        $emapinfotest = array();
        foreach ($arremapnumtest as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $id = $arr[0];
                $num = $arr[1];
                $emapinfotest[] = array($id,$num);
            }
        }
        $exsit = false;
        $emapnumtest = 0;
        foreach ($emapinfotest as &$map){
            if($map[0] == $mapid){
                $emapnumtest = $map[1];
           //     $map[1] += 1;
                $exsit = true;
            }
        }
        if(!$exsit){
            $emapnumtest = 0;
            $emapinfotest[] = array($mapid,1);
        }
        if($emapnumtest + 1 > 3){
            return array(
                0,
                STR_Pve_EMap_Not_Count
            );
        }
    }
    //地图信息
    if(!$mapcfg){
        return array(
            0,
            STR_Pve_Map_Error
        );
    }
    //面包是否足够
    if(!_checkBread($uid,$consume)){
        return array(
            0,
            STR_BreadOff
        );
    }
    //取出pve阵容
    $uequip = sql_fetch_one("SELECT pvegirl,pveleader,pvestagepartner FROM `uequip` WHERE `uid`=$uid");
    $spartners = $uequip['pvestagepartner'] ? $uequip['pvestagepartner'] : '';
    if(!$spartners){
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    //pve的女神
    $gid = intval($uequip['pvegirl']);
    if($gid == 0){
        return array(
            0,
            STR_Not_Girl
        );
    }
    if($umapid > 0){
        $unlock= intval(sql_fetch_one_cell("select unlockmap from cfg_pvemap where id=$umapid"));
    }
    $ulv = intval($uinfo['ulv']);
/*    if ($ulv < intval($mapcfg['level'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }*/
    if($mapid != 1000001){
        if ($upve) {
            if($mapid < 10000){
                if($umapid == 0){
                    if($mapid != 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($mapid > $umapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
            else{
                if($uemapid == 0){
                    if($mapid != 10001){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($umapid >= $unlock && $mapid > $uemapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
        }
        else if($mapid != 1){
            return array(
                0,
                STR_Pve_Map_Error
            );
        }
    }
    //掉落id
    $rewardid = intval($mapcfg['rewardid']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $leader = intval($uequip['pveleader']);
    //随机技能
    //======================================
     $randskill = _randomPveSkill($uid);  
     $skillstrs = $randskill[0];
    //随机技能结束
    //=====================================
    $partnerattr = array();
    $stagepartnerstr = $uequip['pvestagepartner'];
    $stagepartner = explode(",", $stagepartnerstr);
    //好友上阵勇者
    //=====================================
    if(count($stagepartner) == 2){
    	//找出好友的阵容配置
        $fuid = intval(sql_fetch_one_cell("select fuid from uequip where uid = $uid"));
        if($fuid > 0){
            $fbrave = intval(sql_fetch_one_cell("select brave from ufriend where uid = $uid and fuid = $fuid"));
            $stagepartner[] = $fbrave;
            sql_update("update ufriend set time = UNIX_TIMESTAMP() where uid = $uid and fuid = $fuid");
            sql_update("update uequip set fuid = 0 where uid = $uid and fuid = $fuid");
            
            $skills=sql_fetch_one("select partnerid,skilllevel,skilledlevel from upartner where partnerid=$fbrave");
            $skilllv_arr[]=$skills;
        }
    }
    //=====================================
    foreach ($stagepartner as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $skills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($stagepartnerstr)");
    $skilllv_arr[]=$skills;
    
    $coin = $mapcfg['coin'];
    $num = rand(intval($cfg['minget']),intval($cfg['maxget']));
    if($mapid > 10000 && $mapid < 100000){
        $emapnumarr = array();
        foreach ($emapinfotest as $v){
            $emapnumarr[] = implode('|',$v);
        }
        $emapnumstr = implode(',',$emapnumarr);
    }
    sql_update("insert into upve (uid,time,rewardnum,emapnum) values ($uid,UNIX_TIMESTAMP(),$num,'$emapnumstr') on DUPLICATE KEY update uid=$uid,time=UNIX_TIMESTAMP(),rewardnum=$num,emapnum='$emapnumstr'");
    sql_update("update uinfo set pvetime = UNIX_TIMESTAMP() where uid = $uid");
    $skillnum = 0;
    foreach ($skillstrs as $skillv){
        $skilldata = $skillv[1];
        if($skillnum == 0){
            sql_update("update upve set randskill = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 1){
            sql_update("update upve set randskill1 = '$skilldata' where uid = $uid");
        }
        elseif($skillnum == 2){
            sql_update("update upve set randskill2 = '$skilldata' where uid = $uid");
        }
        $skillnum ++;
    }
    $girl = array();
    if($gid){
        $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$gid");
    }
    randomTask($uid,$mapid);
    $randomtask = sql_fetch_one("select mids,rids from upve where uid=$uid");
    $upos = sql_fetch_one_cell("select pos from `upve` where `uid`=$uid");
    //============================
    _updateUTaskProcess($uid, 1014);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 1;
    $logparams[] = 100;
    pvefightlog($logparams);
    
    $type=sql_fetch_one_cell("select type from cfg_clubtask where clientmapid=$mapid");
   	$clubtask=_startclubtask($uid,$type);
    return array(
        1,
        $coin,
        $num,
        $skillstrs,
        $partnerattr,
        $girl,
        $leader,
        $randomtask,
        $upos,
    	$clubtask,
    	$skilllv_arr,
    	$sign
    );
}


/**
 * pve战斗
 *
 * @param $uid uid
 * @param $uinfo 用户信息
 * @return array
 */
function pveBattle($uid, $params)
{
    $mapid = $params[0];
    $win = $params[1];
    $livenum = $params[2];
    if($win != 1){
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $mapid;
        $logparams[] = 1;
        $logparams[] = $win;
        pvefightlog($logparams);
        return array(
            2,
            $win
        );
    }
    if($mapid == 1000001){
        $mapid = 1;
        $livenum = 3;
    }
    //验证
    //============================================
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $umapid = intval($upve['mapid']);
    $uemapid = intval($upve['emapid']);
    $unlock = 0;
    if(!$mapcfg){
        return array(
            0,
            STR_Pve_Map_Error
        );
    }
    if($umapid > 0){
        $unlock= intval(sql_fetch_one_cell("select unlockmap from cfg_pvemap where id=$umapid"));
    }
    $ulv = intval($uinfo['ulv']);
/*    if ($ulv < intval($mapcfg['level'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }*/
    if($mapid != 1000001){
        if ($upve) {
            if($mapid < 10000){
                if($umapid == 0){
                    if($mapid != 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($mapid > $umapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
            else{
                if($uemapid == 0){
                    if($mapid != 10001){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($umapid >= $unlock && $mapid > $uemapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
        }
        else if($mapid != 1){
            return array(
                0,
                STR_Pve_Map_Error
            );
        } 
    }
    //检测精英副本次数
    $emapnumstr = $upve['emapnum'];
    if($mapid > 10000 && $mapid < 100000){
        $arremapnumtest = explode(',',$emapnumstr);
        $emapinfotest = array();
        foreach ($arremapnumtest as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $id = $arr[0];
                $num = $arr[1];
                $emapinfotest[] = array($id,$num);
            }
        }
        $exsit = false;
        $emapnumtest = 0;
        foreach ($emapinfotest as &$map){
            if($map[0] == $mapid){
                $emapnumtest = $map[1];
                $map[1] += 1;
                $exsit = true;
            }
        }
        if(!$exsit){
            $emapnumtest = 0;
            $emapinfotest[] = array($mapid,1);
        }
        if($emapnumtest + 1 > 3){
            return array(
                0,
                STR_Pve_EMap_Not_Count
            );
        }
    }
    $rewardid = intval($mapcfg['rewardid']);
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
    $num = $upve['rewardnum'];
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
    $consume = $mapcfg['consume'];
    _spendBread($uid,$consume,'pve战斗');
    $coin = $mapcfg['coin'];
    $exp = $mapcfg['exp'];
    //先计算随机任务提示金币数
    $coinpercent = _getVipByPveCoin($uid);
    $coin = intval($coin * (1 + $coinpercent/10000));
    $exppercent = _getVipByPveExp($uid);
    $exp = intval($exp * (1 + $exppercent/10000));
    _addCoin($uid, $coin,'pve战斗');
    _addExp($uid, $exp);
    $rewarditems = array();
    $equips = array();
    foreach ($rewarditem as $value){
        if(intval($value['id']) == 1){
            _addCoin($uid,intval($value['count']),'pve战斗');
            $coin += intval($value['count']);
        }
        elseif(intval($value['id']) > 100000){
            $equips[] = _createEquipByceid($uid, $value['id'], $value['count'], 0);
        }
        else{
            _addItem($uid, $value['id'], $value['count'],'pve战斗');
            $rewarditems[] = $value;
        }
    }
    $star = 0;
    if($livenum == 1){
        $star = 1;
    }
    elseif($livenum == 2){
        $star = 2;
    }
    elseif($livenum == 3){
        $star = 3;
    }
    else{
        $star = 0;
    }
    $mapstar = $upve['mapstar'];
    $stararr = explode(',',$mapstar);
    $starinfo = array();
    foreach ($stararr as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $starnum = $arr[1];
            $starinfo[] = array($id,$starnum);
        }
    }
    $exsit = false;
    foreach ($starinfo as &$map){
        if($map[0] == $mapid){
            if($map[1] < $star){
                $map[1] = $star;
            }
            $exsit = true;
            break;
        }
    }
    if(!$exsit){
        $starinfo[] = array($mapid,$star);
    }
    foreach ($starinfo as $v){
        $arrstr[] = implode('|',$v);
    }
    $starstr = implode(',',$arrstr);
    sql_update("insert into upve (uid,mapstar) values ($uid,'$starstr') on DUPLICATE KEY update uid=$uid,mapstar='$starstr'");
    $girl = array();
    if ($upve){
        if($mapid < 10000 && ($mapid > $umapid)){
            sql_update("insert into upve (uid,mapid) values ($uid,$mapid) on DUPLICATE KEY update uid=$uid,mapid=$mapid");
        }
        else if($mapid > 10000 && $mapid > $uemapid){
            sql_update("insert into upve (uid,emapid) values ($uid,$mapid) on DUPLICATE KEY update uid=$uid,emapid=$mapid");
            _updateUTaskProcess($uid, 1021);
        }
        $girlres = addGirl($uid, $mapid);
        if($girlres[0] == 1){
            $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlres[1]");
        }
    }
    sql_update("update uinfo set pvetime=UNIX_TIMESTAMP() where uid=$uid");
    if($mapid > 10000 && $mapid < 100000){
        $emapnumarr = array();
        foreach ($emapinfotest as $v){
            $emapnumarr[] = implode('|',$v);
        }
        $emapnumstr = implode(',',$emapnumarr);
        sql_update("insert into upve (uid,emapnum) values ($uid,'$emapnumstr') on DUPLICATE KEY update uid=$uid,emapnum='$emapnumstr'");
    }
    
  //  $coin += intval($res[1]);
  
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 1;
    $logparams[] = $win;
    pvefightlog($logparams);
    
    $type=sql_fetch_one_cell("select type from cfg_clubtask where clientmapid=$mapid");
    $clubtask=_endclubtask($uid,$type);
    return array(
        1,
        $consume,
        $exp,
        $coin,
        $rewarditems,
        $win,
        $star,
        $girl,
        $equips,
    	$clubtask
    );
}


/**
 * pve战斗
 *
 * @param $uid uid
 * @param $uinfo 用户信息
 * @return array
 */
function pveBattleh5($uid, $params)
{
	 $mapid = $params[0];
    $win = $params[1];
    $livenum = $params[2];
    $randomtasks = $params[3];
    $verifystr = $params[4];
    if($win != 1){
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $mapid;
        $logparams[] = 1;
        $logparams[] = $win;
        pvefightlog($logparams);
        return array(
            2,
            $win
        );
    }
    if($mapid == 1000001){
    	$upve = sql_fetch_one("select * from upve where uid=$uid");
    	$umapid = intval($upve['mapid']);
    	if($umapid!=0)
    	{
	        return array(
	            0,
	            STR_Pve_Map_Error
	        );
    	}
    	
    	
        $mapid = 1;
        $livenum = 3;
    }
    else 
    {
	    //验证
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
	    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
	    if($serverid==1)
	    {
	    	$yanzhen=_battleCheck($uid,array($verifystr,$mapid,1));
	    	if(intval($yanzhen[0])!=1)
	    	{
	    		file_put_contents("log.txt", "$verifystr"."--".print_r($yanzhen, TRUE));
	    		return array(
	    				0,
	    				STR_Battle_Verify_Error,
	    
	    		);
	    	}
	    }
    }
    //============================================
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $umapid = intval($upve['mapid']);
    $uemapid = intval($upve['emapid']);
    $unlock = 0;
    if(!$mapcfg){
        return array(
            0,
            STR_Pve_Map_Error
        );
    }
    if($umapid > 0){
        $unlock= intval(sql_fetch_one_cell("select unlockmap from cfg_pvemap where id=$umapid"));
    }
    $ulv = intval($uinfo['ulv']);
/*    if ($ulv < intval($mapcfg['level'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }*/
    if($mapid != 1000001){
        if ($upve) {
            if($mapid < 10000){
                if($umapid == 0){
                    if($mapid != 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($mapid > $umapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
            else{
                if($uemapid == 0){
                    if($mapid != 10001){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
                else{
                    if($umapid >= $unlock && $mapid > $uemapid + 1){
                        return array(
                            0,
                            STR_Pve_Map_Error
                        );
                    }
                }
            }
        }
        else if($mapid != 1){
            return array(
                0,
                STR_Pve_Map_Error
            );
        } 
    }

    $rewardid = intval($mapcfg['rewardid']);
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
    $num = $upve['rewardnum'];
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
    $consume = $mapcfg['consume'];
    _spendBread($uid,$consume,'pve战斗');
    $coin = $mapcfg['coin'];
    $exp = $mapcfg['exp'];
    //先计算随机任务提示金币数
    $coinpercent = _getVipByPveCoin($uid);
    $coin = intval($coin * (1 + $coinpercent/10000));
    $exppercent = _getVipByPveExp($uid);
    $exp = intval($exp * (1 + $exppercent/10000));
    _addCoin($uid, $coin,'pve战斗');
    _addExp($uid, $exp);
    $rewarditems = array();
    $equips = array();
    foreach ($rewarditem as $value){
        if(intval($value['id']) == 1){
            _addCoin($uid,intval($value['count']),'pve战斗');
            $coin += intval($value['count']);
        }
        elseif(intval($value['id']) > 100000){
            $equips[] = _createEquipByceid($uid, $value['id'], $value['count'], 0);
        }
        else{
            _addItem($uid, $value['id'], $value['count'],'pve战斗');
            $rewarditems[] = $value;
        }
    }
    $star = 0;
    if($livenum == 1){
        $star = 1;
    }
    elseif($livenum == 2){
        $star = 2;
    }
    elseif($livenum == 3){
        $star = 3;
    }
    else{
        $star = 0;
    }
    $mapstar = $upve['mapstar'];
    $stararr = explode(',',$mapstar);
    $starinfo = array();
    foreach ($stararr as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $starnum = $arr[1];
            $starinfo[] = array($id,$starnum);
        }
    }
    $exsit = false;
    foreach ($starinfo as &$map){
        if($map[0] == $mapid){
            if($map[1] < $star){
                $map[1] = $star;
            }
            $exsit = true;
            break;
        }
    }
    if(!$exsit){
        $starinfo[] = array($mapid,$star);
    }
    foreach ($starinfo as $v){
        $arrstr[] = implode('|',$v);
    }
    $starstr = implode(',',$arrstr);
    sql_update("insert into upve (uid,mapstar) values ($uid,'$starstr') on DUPLICATE KEY update uid=$uid,mapstar='$starstr'");
    $girl = array();
    if ($upve){
        if($mapid < 10000 && ($mapid > $umapid)){
            sql_update("insert into upve (uid,mapid) values ($uid,$mapid) on DUPLICATE KEY update uid=$uid,mapid=$mapid");
        }
        else if($mapid > 10000 && $mapid > $uemapid){
            sql_update("insert into upve (uid,emapid) values ($uid,$mapid) on DUPLICATE KEY update uid=$uid,emapid=$mapid");
            _updateUTaskProcess($uid, 1021);
        }
        $girlres = addGirl($uid, $mapid);
        if($girlres[0] == 1){
            $girl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlres[1]");
        }
    }
    sql_update("update uinfo set pvetime=UNIX_TIMESTAMP() where uid=$uid");
    
  //  $coin += intval($res[1]);
  
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $mapid;
    $logparams[] = 1;
    $logparams[] = $win;
    pvefightlog($logparams);
    
    $type=sql_fetch_one_cell("select type from cfg_clubtask where clientmapid=$mapid");
    $clubtask=_endclubtask($uid,$type);
    return array(
        1,
        $consume,
        $exp,
        $coin,
        $rewarditems,
        $win,
        $star,
        $girl,
        $equips,
    	$clubtask
    );
}



/**
 * pve扫荡
 *
 * @param $uid uid
 * @param $uinfo 用户信息
 * @return array
 */
function pveSweep($uid, $params)
{
    $mapid = $params[0];
    $count = $params[1];
    $mapcfg = sql_fetch_one("select * from cfg_pvemap where id=$mapid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $ulv = intval($uinfo['ulv']);
 /*   if ($ulv < intval($mapcfg['level']) || $ulv < 10) {
        return array(
            0,
            STR_PveSweep_Lv_Low2
        );
    }*/
 /*   if ($upve && ($mapid > intval($upve['mapid']))) {
        return array(
            0,
            STR_Pve_Map_Error
        );
    }*/
    //=====================================
    //检测精英副本次数
    $emapnumstr = $upve['emapnum'];
    if($mapid > 10000 && $mapid < 100000){
        $arremapnumtest = explode(',',$emapnumstr);
        $emapinfotest = array();
        foreach ($arremapnumtest as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $id = $arr[0];
                $num = $arr[1];
                $emapinfotest[] = array($id,$num);
            }
        }
        $exsit = false;
        $emapnumtest = 0;
        foreach ($emapinfotest as &$emap){
            if($emap[0] == $mapid){
                $emapnumtest = $emap[1];
                $emap[1] += $count;
                $exsit = true;
            }
        }
        if(!$exsit){
            $emapnumtest = 0;
            $emapinfotest[] = array($mapid,$count);
        }
        if($emapnumtest + $count > 3){
            return array(
                0,
                STR_Pve_EMap_Not_Count
            );
        }
    }
    //判断星数
    $mapstar = $upve['mapstar'];
    $stararr = explode(',',$mapstar);
    $starinfo = array();
    foreach ($stararr as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $starnum = $arr[1];
            $starinfo[] = array($id,$starnum);
        }
    }
    $exsit = false;
    foreach ($starinfo as $map){
        if($map[0] == $mapid){
            if($map[1] < 3){
                return array(
                    0,
                    STR_Pve_Sweep_StarErr
                );
            }
            $exsit = true;
            break;
        }
    }
    if(!$exsit){
        return array(
            0,
            STR_Pve_Sweep_StarErr
        );
    }
    //=====================================
    $rewardid = intval($mapcfg['rewardid']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $sweeptest = $upve['sweepinfo'];
    $arrsweeptest = explode(',',$sweeptest);
    $mapinfotest = array();
    foreach ($arrsweeptest as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $num = $arr[1];
            $mapinfotest[] = array($id,$num);
        }
    }
    $exsit = false;
    $sweepnumtest = 0;
    foreach ($mapinfotest as &$map){
        if($map[0] == $mapid){
            $sweepnumtest = $map[1];
            $exsit = true;
        }
    }
    if(!$exsit){
        $sweepnumtest = 0;
    }
 /*   if($sweepnumtest >= intval($mapcfg['sweepcount'])){
        return array(
            0,
            STR_Pve_Sweep_Count
        );
    }
    if($count > intval($mapcfg['sweepcount']) - $sweepnumtest){
        return array(
            0,
            STR_Pve_Sweep_Count
        );
    }*/
    $sweepitem = array();
    $equips = array();
    $coin = 0;
    for($k = 0; $k < $count; $k ++){
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
        $num =rand(intval($cfg['minget']),intval($cfg['maxget']));
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
        $rewarditems = array();
        $equipsarr=array();
        foreach ($rewarditem as $value){
            if(intval($value['id']) == 1){
                _addCoin($uid,intval($value['count']),'pve扫荡');
                $coin += intval($value['count']);
            }
            elseif(intval($value['id']) > 100000){
                $equipsarr = _createEquipByceid($uid, $value['id'], $value['count'], 0);
            }
            else{
                _addItem($uid, $value['id'], $value['count'],'pve扫荡');
                $rewarditems[] = $value;
            }
        }
        $sweepitem[] = array('num' => $k + 1 , 'item' => $rewarditems);
        $equips[] = array('num' => $k + 1 , 'item' => $equipsarr);
    }

    $consume = intval($mapcfg['consume']) * $count;
    $coin += intval($mapcfg['coin']) * $count;
    $exp = intval($mapcfg['exp']) * $count;
    if(!_spendBread($uid,$consume,'pve扫荡')){
        return array(
            0,
            STR_BreadOff
        );
    }
    if(!_subItem($uid, 531, $count)){
        return array(
            0,
            STR_SweepItemOff
        );
    }
    _addCoin($uid, $coin,'pve扫荡');
    _addExp($uid, $exp);
    
    $strsweep = $upve['sweepinfo'];
    $arrsweep = explode(',',$strsweep);
    $mapinfo = array();
    foreach ($arrsweep as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $num = $arr[1];
            $mapinfo[] = array($id,$num);
        }
    }
    $exsit1 = false;
    $sweepnum = 0;
    foreach ($mapinfo as &$map){
        if($map[0] == $mapid){
            $map[1] += $count;
            $sweepnum = $map[1];
            $exsit1 = true;
        }
    }
    //unset($map);
    if(!$exsit1){
        $mapinfo[] = array($mapid,$count);
        $sweepnum = $count;
    }

    $maparr = array();
    foreach ($mapinfo as $v){
        $maparr[] = implode('|',$v);
    }
    $mapstr = implode(',',$maparr);
    if($mapid > 10000 && $mapid < 100000){
        $emapnumarr = array();
        foreach ($emapinfotest as $v){
            $emapnumarr[] = implode('|',$v);
        }
        $emapnumstr = implode(',',$emapnumarr);
    }
    sql_update("insert into upve (uid,sweepinfo,emapnum) values ($uid,'$mapstr','$emapnumstr') on DUPLICATE KEY update uid=$uid,sweepinfo='$mapstr', emapnum='$emapnumstr'");
    $sweepinfo = sql_fetch_one("select sweepinfo,emapnum from upve where uid=$uid");
    //工会任务
    $type=sql_fetch_one_cell("select type from cfg_clubtask where clientmapid=$mapid");
	_startclubtask($uid,$type);
    $clubtask=_endclubtask($uid,$type);
    return array(
        1,
        $consume,
        $exp,
        $coin,
        $sweepitem,
        $sweepinfo,
        $equips,
        $clubtask
    );
}

/**
 * 地图信息
 *
 * @param $uid uid
 * @param 
 * @return array
 */
function getPveMapInfo($uid, $params)
{
    $count = intval(sql_fetch_one_cell("select count(*) from cfg_pvemap where id < 10000"));
    $cfg = sql_fetch_rows("select * from cfg_pvemap");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $strsweep = $upve['sweepinfo'];
    $mapstar = $upve['mapstar'];
    $emapnum = $upve['emapnum'];
    return array(
        1,
        $strsweep,
        $mapstar,
        $emapnum
    );
}

//获取pve地图星数奖励信息
function getPveMapStarRewardInfo($uid, $params)
{
    $upvereward = sql_fetch_rows("select * from upvereward where uid = $uid");
    return array(
        1,
        $upvereward
    );
}

//获取pve地图星数奖励
function getPveMapStarReward($uid, $params)
{
    $rewardid = $params[0];
    $cfg = sql_fetch_one("select * from cfg_pvestarreward where id = $rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $chapterid = intval($cfg['mapid']);
    $starcount = intval($cfg['starnum']);
    $rewards[] = array(intval($cfg['itemid1']),intval($cfg['count1']));
    $rewards[] = array(intval($cfg['itemid2']),intval($cfg['count2']));
    $rewards[] = array(intval($cfg['itemid3']),intval($cfg['count3']));
    $cfgmapids = sql_fetch_rows("select id from cfg_pvemap where mapid = $chapterid order by id");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    $upvereward = sql_fetch_one("select * from upvereward where uid=$uid and rewardid = $rewardid");
    if($upvereward){
        return array(
            0,
            STR_Have_Reward
        );
    }
    $mapstar = $upve['mapstar'];
    $stararr = explode(',',$mapstar);
    $starinfo = array();
    $starids = array();
    foreach ($stararr as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $starnum = $arr[1];
            $starinfo[$id] = $starnum;
            $starids[] = $id;
        }
    }
    $star = 0;
    foreach ($cfgmapids as $v){
        $mapid = intval($v['id']);
        if(in_array($mapid, $starids)){
            $star += intval($starinfo[$mapid]);
        }
    }
    if($star < $starcount){
        return array(
            0,
            STR_CanNot_Reward
        );
    }
    $coin = 0;
    $ug = 0;
    $rewarditems = array();
    foreach ($rewards as $reward){
        if (intval($reward[0]) == 1){
            _addCoin($uid,intval($reward[1]),'pve星数奖励');
            $coin += intval($reward[1]);
        }
        elseif(intval($reward[0]) == 2){
            _addUg($uid,intval($reward[1]),'pve星数奖励');
            $ug += intval($reward[1]);
        }
        else{
            _addItem($uid, intval($reward[0]), intval($reward[1]),'pve星数奖励');
            $rewarditems[] = $reward;
        }
    }
    sql_insert("insert into upvereward(uid,rewardid)values($uid,$rewardid)");
    return array(
        1,
        $coin,
        $ug,
        $rewarditems
    );
}


?>