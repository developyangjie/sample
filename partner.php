<?php

/**
 * 接口：获取武将
 * @param $uid
 * @param $params []
 * @return array
 */
function getPartner($uid, $params)
{
    $res = sql_fetch_rows("select * from upartner where uid=$uid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $ulv = intval($uinfo['ulv']);
        
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    //上阵佣兵
    $stagerpids = '';
    $pvestagerpids = '';
    $pvpstagerpids = '';
    $leader = '';
    $pveleader = '';
    $pvpleader = '';
    for($num = 1; $num <= 3; $num ++){
        if($num == 1){
            $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
        }
        elseif($num == 2){
            $partnerid = $myequip['pvestagepartner'] ? $myequip['pvestagepartner'] : '';
        }
        elseif($num == 3){
            $partnerid = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
        }
        $pids = explode(",", $partnerid);
        $stagepartner = array();
        $stageids = array();
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $stagepartner[] = $pinfo;
                $stageids[] = intval($pinfo['partnerid']);
            }
        }
        if($num == 1){
            $stagerpids = implode(",", $stageids);
            if(intval($myequip['leader']) > 0 && in_array(intval($myequip['leader']), $stageids)){
                $leader = $myequip['leader'];
            }
        }
        elseif($num == 2){
            $pvestagerpids = implode(",", $stageids);
            if(intval($myequip['pveleader']) > 0 && in_array(intval($myequip['pveleader']), $stageids)){
                $pveleader = $myequip['pveleader'];
            }
        }
        elseif($num == 3){
            $pvpstagerpids = implode(",", $stageids);
            if(intval($myequip['pvpleader']) > 0 && in_array(intval($myequip['pvpleader']), $stageids)){
                $pvpleader = $myequip['pvpleader'];
            }
        }
        $combineadd = getcombineaddinfo($stagepartner);
        $otheradd = array();
        
        if (in_array(intval($pinfo['partnerid']), $pids)){
            $otheradd = $combineadd;
        }
    }

    if (!empty($res)) {
        foreach ($res as &$v){
            $pinfo = $v;
            $partner = new my(4, $pinfo, $pinfo);
            $v['ppppp'] = $partner->format_to_array2();
            $starlv = intval($pinfo['starlv']);
            $quality = intval($pinfo['quality']);
            
            $v['pinfo'][] = getPartnerInfoByStar($uid,intval($pinfo['partnerid']),$starlv);
            if($starlv < 10){
                $v['pinfo'][] = getPartnerInfoByStar($uid,intval($pinfo['partnerid']),$starlv + 1);
            }
            if($quality<5){
            	$v['pinfo'][] = getPartnerInfoByQuality($uid,intval($pinfo['partnerid']),$quality + 1);
            }
            unset($v['uid']);
            //unset($res[$i]['skill']);
            unset($v['partnerbase']);
            unset($v['upep']);
            //unset($res[$i]['starlv']);
            //unset($res[$i]['starexp']);
        }
    }

    $braveid = intval($myequip['brave']);
    $girl = intval($myequip['girl']);
    $pvegirl = intval($myequip['pvegirl']);
    $pvpgirl = intval($myequip['pvpgirl']);
    return array(
        1,
        $res,
        $stagerpids,
        $pvestagerpids,
        $pvpstagerpids,
        $leader,
        $pveleader,
        $pvpleader,
        $braveid,
        $girl,
        $pvegirl,
        $pvpgirl
    );
}
//wxltest
function test1partner($uid,$params)
{
// 	return _createPartner($uid, $params[0]);
// 	return getPartnerAttr($uid,$params[0]);
	$myequip = sql_fetch_one("select * from uequip where uid=$uid");
	$myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	$my=new my(1, $myinfo, $myequip);
	return $my;
// 	return $my->zhanli;
// 	$partnerid=$params[0];
// 	$res = sql_fetch_one("select * from upartner where uid=$uid and partnerid=$partnerid");
// 	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
// 	$ulv = intval($uinfo['ulv']);
// 	$my = new my(4, $res, $ulv);
// 	return $my->zhanli;
	
}
//wxltest
function test2partner($uid,$params)
{
// 	// 	return _createPartner($uid, $params[0]);
// // 	return getPartnerAttr($uid,$params[0]);
// 		return _createPartner($uid, $params[0]);
		$partnerid=$params[0];
		$res = sql_fetch_one("select * from upartner where uid=$uid and partnerid=$partnerid");
		$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
		$ulv = intval($uinfo['ulv']);
		$my = new my(4, $res, $ulv);
		return $my->zhanli;
}

function _createPartner($uid, $ptype,$qua=0,$sendmsg = true)
{
// 	$partnerbase=sql_fetch_one_cell("select partnerbase from cfg_partner where partnerid=$ptype");
// 	$partnerbase_arr=explode(";", $partnerbase);
// 	$partnerbaseji=$partnerbase_arr[0];
	$mypid = sql_insert("insert into upartner (uid,pid,`name`,skill,skilled,mainp,picindex,quality,rare) select '$uid' as `uid`,partnerid,`name`,skill,skilled,mainp,picindex,$qua as quality,rare from cfg_partner where partnerid=$ptype");
// sql_insert("insert into upartner (uid,pid,`name`,skill,skilled,partnerbase,upep,mainp,picindex,quality,rare) select '$uid' as `uid`,partnerid,`name`,skill,skilled,$partnerbaseji as partnerbase,upep,mainp,picindex,$qua as quality,rare from cfg_partner where partnerid=$ptype");
    $myinfo = sql_fetch_one("select u.uname, p.name from uinfo u left join upartner p on u.uid = p.uid where u.uid=$uid and partnerid = $mypid");
    if($sendmsg) {
        $sysMsg = '';
        if(!empty($myinfo['uname'])) {
            if ($qua == 3){
                $sysMsg = sprintf(STR_PARTNER_SysMsg1,$myinfo['uname'],$myinfo['name']);
            }else if ($qua == 4){
                $sysMsg = sprintf(STR_PARTNER_SysMsg2,$myinfo['uname'],$myinfo['name']);
            }
            if (!empty($sysMsg)) {
                _addSysMsg($sysMsg);
            }
        }
    }
    //如果是第一次获得的卡就加入图鉴
    $res=sql_fetch_one("select * from ucollection where uid=$uid and type=1 and peid=$ptype");
    if(!isset($res))
    {
    	sql_update("insert into ucollection(uid,type,peid) values($uid,1,$ptype)");
    }
    return $mypid;
}

/**
 * 设置队长
 *
 * @param $uid uid
 * @param $params 佣兵信息
 * @return array
 */
function setBattleLeader($uid, $params)
{
    $leader = $params[0];
    $type = intval($params[1]);
    $res = sql_fetch_rows("select * from upartner where uid=$uid");
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    if($type == 1){
        $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
        $pids = explode(",", $partnerid);
        $stagepartner = array();
        $stageids = array();
        $stagerpids = '';
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $stageids[] = intval($pinfo['partnerid']);
            }
        }
        if($leader > 0 && in_array($leader, $stageids)){
            sql_update("update uequip set leader=$leader where uid=$uid");
            return array(
                1,
                $leader
            );
        }
        else{
            return array(
                0,
                STR_Partner_Not_Stage
            );
        }
    }
    elseif($type == 2){
        $partnerid = $myequip['pvestagepartner'] ? $myequip['pvestagepartner'] : '';
        $pids = explode(",", $partnerid);
        $stagepartner = array();
        $stageids = array();
        $stagerpids = '';
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $stageids[] = intval($pinfo['partnerid']);
            }
        }
        if($leader > 0 && in_array($leader, $stageids)){
            sql_update("update uequip set pveleader=$leader where uid=$uid");
            return array(
                1,
                $leader
            );
        }
        else{
            return array(
                0,
                STR_Partner_Not_Stage
            );
        } 
    }
    elseif($type == 3){
        $partnerid = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
        $pids = explode(",", $partnerid);
        $stagepartner = array();
        $stageids = array();
        $stagerpids = '';
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $stageids[] = intval($pinfo['partnerid']);
            }
        }
        if($leader > 0 && in_array($leader, $stageids)){
            sql_update("update uequip set pvpleader=$leader where uid=$uid");
            return array(
                1,
                $leader
            );
        }
        else{
            return array(
                0,
                STR_Partner_Not_Stage
            );
        }
    }
    else{
        return array(
            0,
            STR_DataErr
        );
    }
}

/**
 * 设置勇者
 *
 * @param $uid uid
 * @param $params 佣兵信息
 * @return array
 */
function setNBPartner($uid, $params)
{
    $braveid = $params[0];
    sql_update("update uequip set brave=$braveid where uid=$uid");
    return array(
        1,
        $braveid
    );
}

/**
 * 获取勇者
 *
 * @param $uid uid
 * @param $params 佣兵信息
 * @return array
 */
function getNBPartner($uid, $params)
{
    $braveid = intval(sql_fetch_one_cell("select brave from uequip where uid=$uid"));
    if($braveid == 0){
        $braveid = intval(sql_fetch_one_cell("select partnerid from upartner where uid=$uid order by rand() limit 1"));
        if($braveid > 0){
            sql_update("update uequip set brave=$braveid where uid=$uid");
        }
    }
    $partner = sql_fetch_one("select * from upartner where uid=$uid and partnerid=$braveid");
    return array(
        1,
        $braveid,
        $partner
    );
}

/**
 * 接口：设置上阵佣兵
 * 
 * @param $uid
 * @param $params ['parterid','state']
 * @return arra1y
 */
function setPartner($uid, $params)
{
    //佣兵职业:1为骑士  2为剑士  3为牧师 4为法师  5为枪手  6为弓手
 /*   $jobs = array();
    for($i = 0; $i < count($params)-1;$i++){
        $partnerid = intval($params[$i]);
        $job = intval(sql_fetch_one_cell("select mainp from upartner where uid=$uid and partnerid=$partnerid"));
        for($j = 0; $j < count($jobs);$j++){
            if($jobs[$j] > $job){
                return array(
                    0,
                    STR_Partner_Pos_Error
                );
            }
        }
        $jobs[] = $job;
    }*/
// 	if(count($params)<4){
//         return array(
//             0,
//             STR_DataErr
//         );		
// 	}
	
    $type = intval($params[0]);
    $newpartners = '';
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    if($type == 1){
        $leader = intval($myequip['leader']);
        if(count($params)>2){
            for($num = 1;$num < count($params)-1;$num++){
                $partnerid = intval($params[$num]);
                if (empty($newpartners)){
                    $newpartners = $partnerid;
                }else{
                    $newpartners .= ",".$partnerid;
                }
            }
            $partners = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($newpartners)");
            if (count($partners) != (count($params)-2)){
                return array(
                    0,
                    STR_Partner_NotExist
                );
            }
            if(!in_array($leader, $params)){
                sql_update("update uequip set leader=$params[1] where uid=$uid");
            }
            /*   if(count($params)>2 && $partnerns[0]['picindex'] == $partnerns[1]['picindex']){
             return array(
             0,
             STR_Partner_On_Stage
             );
             }*/
        }
        sql_update("insert into uequip (uid,stagepartner) values ($uid,'$newpartners') on duplicate key update stagepartner = '$newpartners'");
        $leader = intval(sql_fetch_one_cell("select leader from uequip where uid=$uid"));
    }
    elseif($type == 2){
        $leader = intval($myequip['pveleader']);
        if(count($params)>2){
            for($num = 1;$num < count($params)-1;$num++){
                $partnerid = intval($params[$num]);
                if (empty($newpartners)){
                    $newpartners = $partnerid;
                }else{
                    $newpartners .= ",".$partnerid;
                }
            }
            $partners = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($newpartners)");
            if (count($partners) != (count($params)-2)){
                return array(
                    0,
                    STR_Partner_NotExist
                );
            }
            if(!in_array($leader, $params)){
                sql_update("update uequip set pveleader=$params[1] where uid=$uid");
            }
            /*   if(count($params)>2 && $partnerns[0]['picindex'] == $partnerns[1]['picindex']){
             return array(
             0,
             STR_Partner_On_Stage
             );
             }*/
        }
        sql_update("insert into uequip (uid,pvestagepartner) values ($uid,'$newpartners') on duplicate key update pvestagepartner = '$newpartners'");
        $leader = intval(sql_fetch_one_cell("select pveleader from uequip where uid=$uid"));
    }
    elseif($type == 3){
        $leader = intval($myequip['pvpleader']);
        if(count($params) < 5){
            return array(
                0,
                STR_Partner_Not_Three
            );
        }
        if(count($params)>2){
            for($num = 1;$num < count($params)-1;$num++){
                $partnerid = intval($params[$num]);
                if (empty($newpartners)){
                    $newpartners = $partnerid;
                }else{
                    $newpartners .= ",".$partnerid;
                }
            }
            $partners = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($newpartners)");
            if (count($partners) != (count($params)-2)){
                return array(
                    0,
                    STR_Partner_NotExist
                );
            }
            if(!in_array($leader, $params)){
                sql_update("update uequip set pvpleader=$params[1] where uid=$uid");
            }
            /*   if(count($params)>2 && $partnerns[0]['picindex'] == $partnerns[1]['picindex']){
             return array(
             0,
             STR_Partner_On_Stage
             );
             }*/
        }
        sql_update("insert into uequip (uid,pvpstagepartner) values ($uid,'$newpartners') on duplicate key update pvpstagepartner = '$newpartners'");
        $leader = intval(sql_fetch_one_cell("select pvpleader from uequip where uid=$uid"));
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
    }
    return array(
        1,
        $leader
    );
}

/**
 * 接口：设置佣兵装备
 * 
 * @param
 *            $uid
 * @param $params ['parterid','eid','etype']            
 * @return array
 */
function setPartnerEquip($uid, $params)
{
    /*!$params 最后一个是版本id json-gateway.php传过来
     * array(4) { [0]=> int(4) [1]=> string(6) "1577-5" [2]=> string(6) "1575-7" [3]=> int(2040) }
    */
    if (count($params) <= 2){
        return array(
                0,
                STR_Equip_Err
        );
    }
    $partnerid = intval($params[0]);

    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
    if (!$partnerinfo){
        return array(
                0,
                STR_Partner_NotExist
        );
    }
    $job = intval($partnerinfo['mainp']);
    $realep = "0|0";
    
    $etypeIds = array();
    $newIds = array();
    for ($num = 1; $num < count($params) - 1; ++$num){
        $idType = explode("-", $params[$num]);
        if(count($idType) == 2){
            
            $eid = intval($idType[0]);
            $etype = intval($idType[1]);

            //!是否能穿
        //    if ($etype != 1 && $etype != 2 && $etype != 5 && $etype != 7) {
        //        return array(
       //                 0,
        //                STR_Partner_NotUseEquip
        //        );
       //     }
            if ($eid != 0){  //!检查新装备是否满足条件
                $ret = sql_fetch_one("select * from  ubag where eid=$eid and uid=$uid and etype='$etype' and (ejob=0 or ejob=$job)");
                if (!$ret) {
                    return array(
                        0,
                        STR_Lv_JobLow
                    );
                }
                $newIds[] = $eid;
            }

            $etypeIds[$etype] = $eid;
        }
    }

    //!老装备euser标记重置
    $oldeids = array();
    $cureps = sql_fetch_rows("select eid, etype from ubag where uid=$uid and euser='$partnerid'");
    foreach ($cureps as $curep) {
        $oldType = intval($curep["etype"]);
        $oldId = intval($curep["eid"]);
        $newId = $etypeIds[$oldType];
        if (array_key_exists($oldType, $etypeIds) && $oldId != $newId){ //!说明要换装
            if ($oldId != 0){  //!老装备状态重置字符串
                $oldeids[] = $oldId;
            }
        }
    }
    if (count($oldeids) > 0){
        $oldeidsstr = implode(",", $oldeids);
        sql_update("update ubag set euser=0 where eid in ($oldeidsstr)");
    }
    
    //!更新新装备武将euser标记
    if (count($newIds) > 0){
        $newidsstr = implode(",", $newIds);
        $ret = sql_update("update ubag set euser='$partnerid' where eid in ($newidsstr) and uid=$uid");
    }
    $res = sql_fetch_rows("select * from ubag where uid=$uid and euser > 0");
    
    
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $my = new my(1, $myinfo, $myequip);
    $zhanli = $my->zhanli;
    $myinfo['zhanli'] = $zhanli;
    sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
    //判断那个服
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $upvp_num="upvp_".$serverid;
    sql_update("update $upvp_num set zhanli=$zhanli where uid=$uid");
    return array(
        1,
        $res
    );
}

/**
 * 接口：培养武将  暂时没有用此接口
 * 
 * @param
 *            $uid
 * @param $params ['parterid','type']            
 * @return array
 */
function upPartner($uid, $params)
{
    $partnerid = intval($params[0]);
    $type = intval($params[1]);
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $ulv = intval($uinfo['ulv']);
    $upartner = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    if (! $upartner) {
        return array(
            0,
            STR_Partner_NotExist
        );
    }
    $price = 5;
    $maxp = $ulv;
    // 普通培养
    if ($type == 1) {
        $price = 500;
    }    // 高级培养
    elseif ($type == 2) {
        $price = 20;
    }    // 白金培养
    elseif ($type == 3) {
        $price = 60;
    }    // 至尊培养
    elseif ($type == 4) {
        $price = 200;
    }
    $newep = "0|0";
    
    if ($type == 1) {
        $price = $price * $ulv;
    }
    if ($type == 1 && ! _spendCoin($uid, $price, 'upPartner')) {
        return array(
            0,
            STR_CoinOff . $price
        );
    }
    if ($type != 1 && ! _spendGbytype($uid, $price, 'uppartner')) {
        return array(
            0,
            STR_UgOff . $price
        );
    }
    return array(
        1,
        $newep
    );
}


/**
 * 接口：升级武将技能
 *
 * @param $uid
 * @param $params ['parterid','locked']
 * @return array
 */
function upPartnerskill($uid, $params)
{
    $partnerid = intval($params[0]);
    $skillid = intval($params[1]);
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 1");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    if(intval($partnerinfo['plv']) < intval($cfg['plv'])){
        return array(
            0,
            STR_PartnerLV_Low
        );
    }
    if($skillid < 30000){
        $skillcfg = sql_fetch_one("select * from cfg_skill where sid = $skillid");
    }
    else{
        $skillcfg = sql_fetch_one("select * from cfg_skilled where sid = $skillid");
    }
    if(!$skillcfg){
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    if($skillid < 30000){
        $skillstr = $partnerinfo['skill'];
    }
    else{
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    $skills = preg_split("/[\s,]+/", $skillstr);
    if(!in_array($skillid, $skills)){
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    $skilllv = 0;
    $skilllvstr = $partnerinfo['skilllevel'];
    $skilllvs = preg_split("/[\s,]+/", $skilllvstr);
    if($skillid < 20000){
        $skilllv = $skilllvs[0];
    }
    elseif($skillid >= 20000 && $skillid < 30000){
        $skilllv = $skilllvs[1];
    }
    if($skilllv > 99||$skilllv>$ulv){
        return array(
            0,
            STR_Max_Lv_Rune_Skill
        );
    }
    $cfguplv = sql_fetch_one("select * from cfg_skilluplevel where level = $skilllv+1");
    if(!$cfguplv){
        return array(
            0,
            STR_Param_Error
        );
    }
    if($skilllv + 1 > intval($partnerinfo['plv'])){
        return array(
            0,
            STR_PLvOff
        );
    }
    $coinnum = $cfguplv['coin'];
    $itemid = $cfguplv['itemid'];
    $itemnum = $cfguplv['count'];
    if (! _checkCoin($uid, $coinnum)) {
        return array(
            0,
            STR_CoinOff . $coinnum
        );
    }
    if(!_subItem($uid, $itemid, $itemnum)){
        return array(
            0,
            STR_ResourceOff
        );
    }
    _spendCoin($uid, $coinnum,'升级佣兵技能');
    if($skillid < 20000){
        $skilllvs[0] = $skilllvs[0] + 1;
    }
    elseif($skillid >= 20000 && $skillid < 30000){
        $skilllvs[1] = $skilllvs[1] + 1;
    }
    $skilllvstr = implode(",", $skilllvs);
    sql_update("UPDATE upartner SET skilllevel='$skilllvstr' WHERE partnerid=$partnerid and uid=$uid");
    _updateUTaskProcess($uid, 1004);
    return array(
        1,
        $skilllvstr,
        $coinnum,
        array($itemid, $itemnum)
    );
}

function _getVipByPartnerShit($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['partnershit']);
    return $percent;
}

function _getVipByPartnerDebris($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['partnerdebris']);
    return $percent;
}

// 佣兵熔炼
function forgePartner($uid, $params)
{
    if(count($params[0]) > 6){
        return array(
            0,
            STR_Equip_Too_More
        );
    }
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 2");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if($ulv < intval($cfg['lv'])){
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $nowpartner=intval(sql_fetch_one_cell("select count(pid) from upartner where uid=$uid"));
    if(($nowpartner-count($params[0]))<3){
    	return array(
    			0,
    			STR_Equip_Must_Three_Partner
    	);
    }
    $coe = array(1=>0,2=>20,3=>50,4=>150);
    $pids = implode(",",$params[0]);
    $partners = sql_fetch_rows("select * from upartner where partnerid in ($pids) and `uid`='$uid'");
    $itemnum = 0;
    $pid = array();
    $cpids = array();
    foreach ($partners as $value){
        $rare = $value['rare'];
        $itemnum += $coe[$rare];
        $pid[] = intval($value['partnerid']);
        $cpids[] = intval($value['pid']);
    }
    $stagepartner = sql_fetch_one_cell("select stagepartner from uequip where uid=$uid");
    $spids = explode(",", $stagepartner);
    foreach ($spids as $spid) {
        if (in_array($spid, $pid)){
            return array(
                0,
                STR_Stage_Partner_Not_Forge
            );
        }
    }
    $percent = _getVipByPartnerShit($uid);
    $rand = rand(1, 10000);
    $isbuff=0;
    if($rand < $percent){
        $itemnum = intval($itemnum * 1.5);
        $isbuff=1;
    }
    $percent1 = _getVipByPartnerDebris($uid);
    $pice=array();
    foreach ($cpids as $v){
        $fragmentid = intval(sql_fetch_one_cell("select fragmentid from cfg_partner where partnerid=$v"));
        $fragmentnum = intval(30 * $percent1 / 10000);
        if($fragmentnum > 0){
            _addItem($uid, $fragmentid, $fragmentnum,'佣兵熔炼');
            $pice[]=array($fragmentid,$fragmentnum);
        }
    }
    delPartnerByPids($uid, $pid);
    if($itemnum > 0){
        _addItem($uid, 1001, $itemnum,'佣兵熔炼');
    }
    return array(
        1,
        $itemnum,
    	$pice,
    	$isbuff
    );
}


//获取羁绊加成属性值
function getcombineaddinfo(&$partnerinfo)
{
    $combine = array();
    $partnerids = array();
    $addattr = array();
    $partnerindex = array();
    foreach($partnerinfo as $value){
        $partnerid = intval($value['partnerid']);
        $partnercfg = sql_fetch_one("select * from cfg_partner where partnerid=$partnerid");
        if ($partnercfg){
            $combine[intval($partnercfg['combine1'])] += 1;
            $combine[intval($partnercfg['combine2'])] += 1;
            $combine[intval($partnercfg['combine3'])] += 1;
            $combine[intval($partnercfg['combine4'])] += 1;
            $partnerids[] = intval($partnercfg['partnerid']);
            $partnerindex[intval($partnercfg['partnerid'])] = intval($partnercfg['partnerid']);
        }
    }

    $combinestr = "";
    foreach ($combine as $key => $value){
        if ($value >= 2 && $key != 0){
            if (empty($combinestr)){
                $combinestr = $key;
            }else{
                $combinestr .= ",".$key;
            }
        }
    }

    if (!empty($combinestr)){
        $combinecfg = sql_fetch_rows("select * from cfg_combine where id in ($combinestr)");
        if ($combinecfg){
            foreach ($combinecfg as $cfg){
                $pids = array(intval($cfg['pId1']), intval($cfg['pId2']), intval($cfg['pId3']), intval($cfg['pId4']), intval($cfg['pId5']));
                $add = true;
                foreach ($pids as $id){
                    if(!in_array($id, $partnerids) && $id > 0){
                        $add = false;
                        break;
                    }
                }
                if ($add){
                    foreach ($pids as $id){
                        foreach ($partnerindex as $keys => $values){
                            if($keys == $id && $values > 0 && $id > 0){
                                $addattr[$values][] = $cfg['incValue1'];
                                $addattr[$values][] = $cfg['incValue2'];
                                $addattr[$values][] = $cfg['incValue3'];
                                $addattr[$values][] = $cfg['incValue4'];
                            }
                        }
                    }
                }
            }
        }
    }
    return $addattr;
}


//!佣兵转职
function upqualitypartner($uid, $params)
{
    $partnerid = intval($params[0]);
    $partnercfg = sql_fetch_one("select u.starlv,u.quality,u.plv, c.* from upartner u inner join cfg_partner c on u.pid = c.partnerid where u.partnerid=$partnerid and u.uid=$uid");
    if (!$partnercfg){
        return array(
                0,
                STR_Param_Error
        );
    }
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    $skillid = intval($partnerinfo['skilled']);
    $skilledstr = "";
    if($skillid > 0){
        $skillcfg = sql_fetch_one("select * from cfg_skilled where sid = $skillid");
        if(!$skillcfg){
            return array(
                0,
                STR_Partner_SkillErr
            );
        }
        $skilllv = intval(($skillid - 30000) / 1000);
        if($skilllv >= 9){
            return array(
                0,
                STR_Max_Lv_Rune_Skill
            );
        }
        $upsid = $skillcfg['upsid'];
        $skilledstr = strval($upsid);
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    $curquality = intval($partnercfg['quality']);
    if($curquality >= 5){
        return array(
            0,
            STR_Partner_Quality_Max
        );
    }
    $qualityNeed=sql_fetch_one("select * from cfg_zhuanzhiunlock where id=($curquality+1)");
    $uplv=$qualityNeed['uplv'];
    $upstar=$qualityNeed['upstar'];
    if(intval($partnercfg['plv']) < $uplv){
        return array(
            0,
            STR_PLvOff
        );
    }
    if(intval($partnercfg['starlv']) < $upstar){
    	return array(
    		0,
    		STR_StarLvOff
    	);
    }
    $coins = explode("|",$partnercfg['upgCoin']);
    $coin = intval($coins[$curquality]);
    $items = explode("|",$partnercfg['upgItem']);
    $itemidarr = explode(",",$items[$curquality]);
    $itemnums = explode("|",$partnercfg['upgAmount']);
    $itemnumarr = explode(",",$itemnums[$curquality]);
    if(count($itemidarr) != count($itemnumarr)){
        return array(
            0,
            STR_Param_Error
        );
    }
    if (! _checkCoin($uid, $coin)) {
        return array(
            0,
            STR_CoinOff
        );
    }
	
    for($i = 0; $i < count($itemidarr); $i ++){
        if(!_checkItem($uid, intval($itemidarr[$i]), intval($itemnumarr[$i]))){
            return array(
                0,
                STR_ResourceOff
            );
        }
    }
    $itemarr = array();
    for($i = 0; $i < count($itemidarr); $i ++){
        if(!_subItem($uid, intval($itemidarr[$i]), intval($itemnumarr[$i]),'佣兵转职')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $itemarr[] = "$itemidarr[$i]"."|"."$itemnumarr[$i]";
    }
    $itemstr = implode(",", $itemarr);
    _spendCoin($uid, $coin,'佣兵转职');
    if($skillid > 0){
        sql_update("UPDATE upartner SET skilled='$skilledstr',skilledlevel=skilledlevel+1 WHERE partnerid=$partnerid and uid=$uid");
    }
    sql_update("update upartner set quality=quality+1,starlv=0 where partnerid=$partnerid and uid=$uid");
    $upskilledlv = intval(sql_fetch_one_cell("select skilledlevel from upartner where partnerid=$partnerid and uid=$uid"));
    _updateUTaskProcess($uid, 1003);
    return array(
        1,
        $curquality + 1,
        $itemstr,
        $coin,
        $skilledstr,
        $upskilledlv
    );
}

//!获取抽卡数据
function getPartnerDraw($uid, $params)
{
    $draws = sql_fetch_rows("select u.*, c.*, UNIX_TIMESTAMP() as ts from udraw u inner join cfg_draw c on u.drawid = c.id where u.uid = $uid");
    if (!$draws){
        sql_update("insert into udraw (uid, drawid, freedrawcd) values ($uid, 1, UNIX_TIMESTAMP()-172800), ($uid, 2, UNIX_TIMESTAMP()-172800), ($uid, 3, UNIX_TIMESTAMP()-172800)");
        $draws = sql_fetch_rows("select u.*, c.*, UNIX_TIMESTAMP() as ts from udraw u inner join cfg_draw c on u.drawid = c.id where u.uid = $uid");
    }
    foreach($draws as &$v){
        if(intval($v['drawid']) == 2 && (intval($v['ts']) - intval($v['freedrawcd']) >= intval($v['freeCd']))){
            $v['free'] = 0;
            sql_update("update udraw set free = 0 where uid = $uid and drawid = 2");
        }
    }
    return array(
            1,
            $draws
    );
}

/**
 * 接口：抽取武将
 *
 * @param
 *            $uid
 * @param
 *            $params
 * @return 抽取id, 是否免费，是否十连抽
 */
function drawPartner($uid, $params)
{
    $drawid = intval($params[0]);
    $isfree = intval($params[1]);
    $isfirst = intval($params[2]);
    
    $drawcfg = sql_fetch_one("select u.*, c.*, UNIX_TIMESTAMP() as ts from udraw u inner join cfg_draw c on u.drawid = c.id where u.uid = $uid and u.drawid = $drawid");
    if (!$drawcfg){
        return array(
                0,
                STR_Param_Error
        );        
    }

    $onegroupid = 0;
    $ninegroupid = 0;
    if ($isfree){//!免费抽下
        if(intval($drawcfg['free']) >= intval($drawcfg['freeCount'])){
            return array(
                0,
                STR_Reach_Limit
            );
        }
        if (intval($drawcfg['freeCd']) == 0 || intval($drawcfg['ts']) - intval($drawcfg['freedrawcd']) < intval($drawcfg['freeCd'])){
            return array(
                    0,
                    STR_Partner_Draw_Cd
            );
        }
        //!可以免费抽
        if (intval($drawcfg['free']) == 0 && $isfirst == 1){ //!第一次免费
            if ($drawid == 1 || $drawid == 2){  //金币单抽或者钻石单抽
                $onegroupid = intval($drawcfg['freeFirstGroup']); 
            }
        }
        else{
            if ($drawid == 1 || $drawid == 2){
                $onegroupid = intval($drawcfg['freeCommonGroup']);
            }
        }
        sql_update("update udraw set freedrawcd = UNIX_TIMESTAMP(), free = free + 1 where uid = $uid and drawid = $drawid");
    }else{ //!付费抽
        //!检查花费
        $costtype = intval($drawcfg['costType']);
        $cost = intval($drawcfg['costAmount']);
        if ($drawid == 3){ //!10连抽
            $cost = 2888; //!打9折
        }
        
        if ($costtype == 1){ //!铜钱
            if(!_spendCoin($uid, $cost,"抽取佣兵")){
                return array(
                        0,
                        STR_CoinOff
                );
            }
        }else if ($costtype == 2){ //!元宝
            if (!_spendGbytype($uid, $cost,"抽取佣兵")){
                return array(
                        0,
                        STR_UgOff
                );
            }
        }else if (!_subItem($uid, $costtype, $cost, "抽取佣兵")){
            return array(
                    0,
                    STR_ResourceOff
            );
        }
        if (!($drawid == 3 )){ //单次抽取
            if(intval($drawcfg['cost']) == 0){
                $onegroupid = intval($drawcfg['costFirstGroup']);
            }
            elseif(intval($drawcfg['cost']) % 10 == 0){
                if($drawid == 1){
                    $onegroupid = intval($drawcfg['costCommonGroup']);
                }
                else{
                    $onegroupid = intval($drawcfg['costTenthGroup']);
                }
            }
            else{
                $onegroupid = intval($drawcfg['costCommonGroup']);
            } 
        }
        else{ //!十连抽
      		$onegroupid = intval($drawcfg['costCommonGroup']);
      		$ninegroupid = intval($drawcfg['tenGroup']); 
        }
        sql_update("update udraw set cost = cost + 1 where uid = $uid and drawid = $drawid");
    }
    $cfg_lts = sql_fetch_rows("select * from sys_lt_control");
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $addp = array();
    $addpartner = array();
    $additem = array();
    $add = 0;
    $nbpartner = false;
    $nbpartnername = "";
    if ($onegroupid != 0){ //抽取佣兵
    	/*********wxl******************/
    	$randt=array();
    	$pids=array();
    	$randt=sql_fetch_one("select probability from cfg_randomtype where type=2 and subtype=$onegroupid");
    	$probabilitys=$randt['probability'];
    	$probabilitys_arr=explode(",", $probabilitys);
    	
    	for($i=0;$i<1;$i++){
    	    $min=0;
    	    $randnum=rand(1, 10000);
    	    foreach ($probabilitys_arr as $probabilitys_one){
    	        $probabilitys_one_arr=explode("|", $probabilitys_one);
    	        $max=intval($probabilitys_one_arr[1]);
    	        $randtypeid=intval($probabilitys_one_arr[0]);
    	        if($randnum>$min&&$randnum<=$max){
    	            $shopdate=sql_fetch_one("select * from cfg_group where gid = $onegroupid and probability=$randtypeid order by rand() limit 1");
    	            array_push($pids, $shopdate);
    	            break;
    	        }
    	        $min=$probabilitys_one_arr[1];
    	    }
    	}
    	foreach ($pids as &$v1){
    	    foreach ($cfg_lts as $c1){
    	        $vpid = intval($v1['pid']);
    	        if(intval($v1['pid']) == intval($c1['pid']) && intval($c1['switch']) == 1){
    	            $resettime = intval($c1['time']);
    	            if(intval($c1['num']) > intval($c1['totalnum'])){
    	                if($c1['timestamp'] >= $nowtime){
    	                    $v1['pid'] = $c1['replace'];
    	                }
    	                else{
    	                    sql_update("update sys_lt_control set timestamp = UNIX_TIMESTAMP() + $resettime, num = 0 where pid = $vpid");
    	                }
    	            }
    	            sql_update("update sys_lt_control set num = num + 1 where pid = $vpid");
    	        }
    	    }
    	}
    	/*************************wxl*************/
        if ($pids){
            foreach ($pids as $pid){
                if(intval($pid['type']) == 0){
                    $addpartner[] = intval($pid['pid']);
                    $addp[] = _createPartner($uid, intval($pid['pid']), intval($pid['quality']));
                    $cfgpid = intval($pid['pid']);
                    $cfgnb = sql_fetch_one("select rare,name from cfg_partner where partnerid = $cfgpid");
                    $rare = $cfgnb['rare'];
                    if($rare == 4){
                        $nbpartner = true;
                        $nbpartnername = $cfgnb['name'];
                    }
                }
                else{
                    _addItem($uid, intval($pid['pid']), intval($pid['quality']), '抽取佣兵');
                    $additem[] = array(intval($pid['pid']), intval($pid['quality']));
                }
            }         
        }
    }
    if ($ninegroupid != 0){
    	/*********wxl******************/
    	$randt=array();
    	$pids=array();
    	$randt=sql_fetch_one("select probability from cfg_randomtype where type=2 and subtype=6");
    	$probabilitys=$randt['probability'];
    	$probabilitys_arr=explode(",", $probabilitys);
    	$bichuA=rand(1,8);
    	for($i=0;$i<9;$i++){
    		$min=0;
    		$randnum=rand(1, 10000);
    		foreach ($probabilitys_arr as $probabilitys_one){
    			$probabilitys_one_arr=explode("|", $probabilitys_one);
    			$max=intval($probabilitys_one_arr[1]);
    			$randtypeid=intval($probabilitys_one_arr[0]);
    			if($randnum>$min&&$randnum<=$max){
    				if($bichuA==$i){
    					$shopdate=sql_fetch_one("select * from cfg_group where gid = 7 and probability=1 order by rand() limit 1");
    				}
    				else {
    					$shopdate=sql_fetch_one("select * from cfg_group where gid = 6 and probability=$randtypeid order by rand() limit 1");
    				}
    				array_push($pids, $shopdate);
    				break;
    			}
    			$min=$probabilitys_one_arr[1];
    		}
    	}
    	/*************************wxl*************/
//         $pids = sql_fetch_rows("select * from cfg_group where gid = $ninegroupid order by rand() limit 8");
    	$cfg_lts = sql_fetch_rows("select * from sys_lt_control");
    	foreach ($pids as &$v2){
    	    foreach ($cfg_lts as $c2){
    	        $npid = intval($v2['pid']);
    	        if(intval($v2['pid']) == intval($c2['pid']) && intval($c2['switch']) == 1){
    	            $resettime = intval($c2['time']);
    	            if(intval($c2['num']) > intval($c2['totalnum'])){
    	                if($c2['timestamp'] >= $nowtime){
    	                    $v2['pid'] = $c2['replace'];
    	                }
    	                else{
    	                    sql_update("update sys_lt_control set timestamp = UNIX_TIMESTAMP() + $resettime, num = 0 where pid = $npid");
    	                }
    	            }
    	            sql_update("update sys_lt_control set num = num + 1 where pid = $npid");
    	        }
    	    }
    	}
        if ($pids){
            foreach ($pids as $pid){
                if(intval($pid['type']) == 0){
                    $addpartner[] = intval($pid['pid']);
                    $addp[] = _createPartner($uid, intval($pid['pid']), intval($pid['quality']));
                    $cfgpid = intval($pid['pid']);
                    $cfgnb = sql_fetch_one("select rare,name from cfg_partner where partnerid = $cfgpid");
                    $rare = $cfgnb['rare'];
                    if($rare == 4){
                        $nbpartner = true;
                        $nbpartnername = $cfgnb['name'];
                    }
                }
                else{
                    _addItem($uid, intval($pid['pid']), intval($pid['quality']));
                    $additem[] = array(intval($pid['pid']), intval($pid['quality']));
                }
            }
        }
    }
    $ret = array();
    if(count($addp)>0){
        $ret = getPartnerbyPids($uid, $addp);
    }
    if ($nbpartner){
        $url = "data.hylr.igamesocial.cn/websocket_send.php";
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        $serverid = $uinfo['serverid'];
        $uname = $uinfo['uname'];
        $content = "恭喜屌丝玩家".$uname."成功获得S级勇者".$nbpartnername;
        $msg = array($content);
        sendWorldMsg($uid, $msg);
    }

    if($drawid == 1){
        _updateUTaskProcess($uid, 1016);
    }
    else{
        _updateUTaskProcess($uid, 1017);
    }
    //_updateUTaskProcess($uid, 16);//更新任务进度
    return array(
        1,
        $ret,
        $additem,
        $addp
    );
}

// /**
//  * 接口：获取武将碎片道具列表
//  * @param $uid
//  * @param $params []
//  * @return array
//  */
// function getpartnerrelatecfg($params)
// {
//     $res = sql_fetch_rows("select * from cfg_fragment");
//     $res2 = sql_fetch_rows("select * from cfg_partner");
//     $res3 = sql_fetch_rows("select * from cfg_draw");
//     $res4 = sql_fetch_rows("select * from cfg_combine");
    
//     return array(
//             1,
//             $res,
//             $res2,
//             $res3,
//             $res4
//     );
// }

/**
 * 接口：通过id获取武将
 * @param $uid
 * @param $params []
 * @return array
 */
function getPartnerbyPids($uid, $pids)
{
    $ids = implode(",", $pids);
    $res = sql_fetch_rows("select * from upartner where uid=$uid and partnerid in ($ids)");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $ulv = intval($uinfo['ulv']);
    
    if (!empty($res)) {
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $partner = new my(4, $pinfo, $ulv);
            $res[$i]['ppppp'] = $partner->format_to_array();
            $starlv = intval($pinfo['starlv']);
            $res[$i]['pinfo'][] = getPartnerInfoByStar($uid,intval($pinfo['partnerid']),$starlv);
            if($starlv < 10){
                $res[$i]['pinfo'][] = getPartnerInfoByStar($uid,intval($pinfo['partnerid']),$starlv + 1);
            }
        }
    }
    return array(
            1,
            $res
    );
}


/**
 * 接口：通过id获取武将祥细属性-包含羁绊加成
 * @param $uid
 * @param $params []
 * @return array
 */
function getPartnerdetailbyid($uid, $param)
{
    $pid = intval($param[0]);
    $res = sql_fetch_rows("select * from upartner where uid=$uid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $ulv = intval($uinfo['ulv']);

    
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['stagepartner'];
    $pids = explode(",", $partnerid);
    $stagepartner = array();
    for($i = 0; $i < count($res); $i ++) {
        $pinfo = $res[$i];
        if (in_array(intval($pinfo['partnerid']), $pids)){
            $stagepartner[] = $pinfo;
        }
    }
    $combineadd = getcombineaddinfo($stagepartner);
    
    $res2 = array();
    if (!empty($res)) {
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (intval($pinfo['partnerid']) == $pid){
                $otheradd = array();
                if (in_array(intval($pinfo['partnerid']), $pids)){
                    $otheradd = $combineadd;
                }
                $partner = new my(4, $pinfo, $ulv, $otheradd);
                
                $res[$i]['ppppp'] = $partner->format_to_array();
                $res2 = $res[$i];
                break;
            }
        }
    }
    
    return array(
            1,
            $res2
    );
}

/**
 * 购买武将背包
 * @param $uid
 * @return array
 */
function buyPartnerBag($uid, $params)
{
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    if(intval($uinfo['partnerbag']) >= 200){
        return array(
            0,
            STR_PartnerBAG_NotEnough
        );
    }
    $costitem = array(2,100);
    if(!_spendGbytype($uid,100,"购买武将背包")){
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("update uinfo set partnerbag = partnerbag + 10 where uid = $uid");
    $bagnum = intval(sql_fetch_one_cell("select partnerbag from uinfo where uid = $uid"));
    return array(
        1,
        $bagnum,
        $costitem
    );

}


/**
 * 吞噬佣兵
 * @param $uid
 * @param $params[0] partnerid
 * @param $params[1] "1,2,3"
 * @param $params[2] "1,2,3"
 */
function partnerSwallow($uid, $params){
    $otherps = array();
    $expitems = array();
    $mainp = $params[0];
    $expitemstr = $params[1];
    if(!empty($expitemstr)){
        $expitemsarr = explode(",", $expitemstr);
        foreach ($expitemsarr as $e){
            $expitems[] = explode("|", $e);
        }
    }
    else{
        return array(
            0,
            STR_Param_Error
        );
    }
    $uinfo = sql_fetch_one("select vip,ulv from uinfo where uid = $uid");
    $vip = intval($uinfo['vip']);
    $ulv = intval($uinfo['ulv']);
    if ($ulv < 2) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $Partners = array();
    $partner = sql_fetch_one("select * from upartner where partnerid = $mainp");
    $ep = 0;
    $cost = 0;
    //新成长系统限制******************************************************
    $limitlv=0;
    switch ($partner['quality']) {
    	case 0:
    		$limitlv=10;
    		break;
    	case 1:
    		$limitlv=30;
    		break;
    	case 2:
    		$limitlv=50;
    		break;
    	case 3:
    		$limitlv=70;
    		break;
    	case 4:
    		$limitlv=80;
    		break;
    	case 5:
    		$limitlv=100;
    		break;
    	default:
    		;
    	break;
    }
    
    //新成长系统限制******************************************************
    $maxep = intval(sql_fetch_one_cell("select brave_maxexp from cfg_userlv where lv = 100"));
    //确认当前能达到最小等级
    if($ulv>$limitlv){
    	$ulv=$limitlv;
    	$umaxep = intval(sql_fetch_one_cell("select brave_maxexp from cfg_userlv where lv=$ulv"));
    	$mess=STR_LV_IS_MAX;
    }
    else {
    	$umaxep = intval(sql_fetch_one_cell("select brave_maxexp from cfg_userlv c,uinfo u where c.lv = u.ulv and u.uid = $uid"));
    	$mess=STR_LV_IS_MAX2;
    }
    
    //判断道具数量是否足够
	foreach ($expitems as $itemc)
	{
	    if(($itemc[0] != 601) && ($itemc[0] != 602) && ($itemc[0] != 603)){
	        return array(
	            0,
	            STR_Param_Error
	        );
	    }
	    if(!_checkItem($uid, intval($itemc[0]), intval($itemc[1])))
	    {
	        return array(
	            0,
	            STR_ResourceOff
	        );
	    }
	    if(!_checkItem($uid, intval($itemc[0]), intval($itemc[1])))
	    {
	        return array(
	            0,
	            STR_ResourceOff
	        );
	    }
	    if(!_checkItem($uid, intval($itemc[0]), intval($itemc[1])))
	    {
	        return array(
	            0,
	            STR_ResourceOff
	        );
	    }
	}
	
	//计算获得经验
    foreach ($expitems as $item){
        if($item[0] == 601){
            $ep += 200 * $item[1];
            $cost += 100 * $item[1];
        }
        elseif($item[0] == 602){
            $ep += 500 * $item[1];
            $cost += 200 * $item[1];
        }
        elseif($item[0] == 603){
            $ep += 1000 * $item[1];
            $cost += 300 * $item[1];
        }
    }
    if(intval($partner['pexp']) + $ep >= $umaxep){
        $ep = $umaxep;
    }
    else{
        $ep += intval($partner['pexp']);
    }
    $lv = sql_fetch_one_cell("select lv from cfg_userlv where brave_allexp <= $ep and brave_maxexp >$ep limit 1");
    
    if($lv > $ulv){
        $lv = $ulv;
        
    }
    //******经验是否满
    if($umaxep<=$ep){
    	$ep=($umaxep)-1;
    }

    if($lv==$ulv&&$umaxep==(intval($partner['pexp']))+1){
        return array(
            0,
            $mess
        );
    }
     //******扣除金币
    if (!_spendCoin($uid, $cost, "佣兵吞噬") && $cost != 0){
        return array(
            0,
            STR_CoinOff
        );
    }
    //扣除道具
    foreach ($expitems as $item){
    	_subItem($uid, $item[0], $item[1], '佣兵吞噬');
    }
    $pidarr = array();
    foreach ($Partners as $pp) {
        $pidarr[] = intval($pp[partnerid]);                
    }
    delPartnerByPids($uid, $pidarr);
    sql_update("update upartner set pexp = '$ep' , plv = $lv where uid = $uid and partnerid = $mainp");
    _updateUTaskProcess($uid, 1001);
    return array(
        1,
        sql_fetch_one("select * from upartner where uid = $uid and partnerid = $mainp"),
        $cost
    );       
}

/**
 * 获取其他玩家佣兵
 * @param $uid
 * @param $params[0] uid
 * @param 
 */
function getOtherUserPartner($uid, $params)
{
    $otheruid = $params[0];
    $res = sql_fetch_rows("select * from upartner where uid=$otheruid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$otheruid");
    $ulv = intval($uinfo['ulv']);
    $myequip = sql_fetch_one("select * from uequip where uid=$otheruid");
    $partnerid = $myequip['stagepartner'] ? $myequip['stagepartner'] : '';
    $pids = explode(",", $partnerid);
    $stagepartner = array();
    for($i = 0; $i < count($res); $i ++) {
        $pinfo = $res[$i];
        if (in_array(intval($pinfo['partnerid']), $pids)){
            $stagepartner[] = $pinfo;
        }
    }
    $combineadd = getcombineaddinfo($stagepartner);

    if (!empty($res)) {
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            $otheradd = array();
    
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $otheradd = $combineadd;
            }
            $partner = new my(4, $pinfo, $ulv, $otheradd);
    
            $res[$i]['ppppp'] = $partner->format_to_array2();
    
            unset($res[$i]['uid']);
            unset($res[$i]['skill']);
            unset($res[$i]['partnerbase']);
            unset($res[$i]['upep']);
            //unset($res[$i]['starlv']);
            //unset($res[$i]['starexp']);
        }
    }
    else {
        $partnerid = "";
    }
    return array(
        1,
        $res,
        $partnerid
    );
}

// 获取武将属性
function getPartnerAttr($uid,$partnerid)
{
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
    $puid = intval($partnerinfo['uid']);
    $lv = intval($partnerinfo['plv']);
    $maip = intval($partnerinfo['mainp']);
    $starlv = intval($partnerinfo['starlv']);
    $pid = intval($partnerinfo['pid']);
    $quality = intval($partnerinfo['quality']);
    $skill = $partnerinfo['skill'];
    $skilled = $partnerinfo['skilled'];
 	 $cfginfo = sql_fetch_one("select * from cfg_partner where partnerid=$pid");
    $cfgid = intval($cfginfo['partnerid']);
    $nowbaseattr=preg_split("/[\s;]+/", $cfginfo['partnerbase']);//
    $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);
    
    
    $atkbase =0;
    $hpbase =0;
    $defbase =0;
    $mdefbase =0;
    $cribase =0;
    $curebase =0;

	//给基础属性赋值
    if (count($baseattr) > 0) {
        foreach ($baseattr as $a) {
            $detail = preg_split("/[\s|]+/", $a);
            if (count($detail) == 2) {
                switch (intval($detail[0])) {
                    case 1:
                        $atkbase = floatval($detail[1]);
                        break;
                    case 2:
                        $hpbase = floatval($detail[1]);
                        break;
                    case 3:
                        $defbase = floatval($detail[1]);
                        break;
                    case 4:
                        $mdefbase = floatval($detail[1]);
                        break;
                    case 5:
                        $cribase = floatval($detail[1]);
                        break;
                    case 6:
                        $curebase = floatval($detail[1]);
                        break;
                    default:
                        break;
                }
            }
        }
    }
    
    if($quality == 0){
        $parr = preg_split("/[\s,]+/", $cfginfo['upep']);
    }
    else{
        $parr = preg_split("/[\s,]+/",$cfginfo['incAttr' . $quality]);
    }
    
    //取出勇者等级阶段
    $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
    $atk=0;
    $hp=0;
    $def=0;
    $mdef=0;
    $cri=0;
    $cure=0;

    //计算进阶次数的升级奖励
    if (count($parr) > 0 ) {
        foreach ($parr as $p) {
            $pdetail = preg_split("/[\s|]+/", $p);
            if (count($pdetail) == 2) {
                switch (intval($pdetail[0])) {
                    case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
                	   	$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
                        $hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
                        $def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    default:
                        break;
                }
            }
        }
    }
    //计算装备属性
    $e_hp = 0;
    $e_patk = 0;
    $e_matk = 0;
    $e_pdef = 0;
    $e_mdef = 0;
    $e_cri = 0;
    $e_cure = 0;
    $suits = array();
    $stardate=array(3=>0.05,4=>0.05,5=>0.05); 
    $equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$puid and u.euser = $partnerid");
    foreach($equips as $equip){
        if(intval($equip['suit']) > 0){
            $suits[] = intval($equip['suit']);
        }
        $uplv = intval($equip['uplv']);
        $star = intval($equip['star']);
        $xishu=$stardate[$star];
    	$e_hp += ceil(intval($equip['hp']) * (1+$xishu*$uplv));
    	$e_patk += ceil(intval($equip['patk']) * (1+$xishu*$uplv));
    	$e_matk += ceil(intval($equip['matk']) * (1+$xishu*$uplv));
    	$e_pdef += ceil(intval($equip['pdef']) * (1+$xishu*$uplv));
    	$e_mdef += ceil(intval($equip['mdef']) * (1+$xishu*$uplv));
    	$e_cri += ceil(intval($equip['cri']) * (1+$xishu*$uplv));
    	$e_cure += ceil(intval($equip['cure']) * (1+$xishu*$uplv));
    }
    $suits = array_count_values($suits);
    foreach($suits as $key => $value){
        if($value >= 2){
            $cfgsuit = sql_fetch_one("select * from cfg_equipsuit where sid = $key");
            if($cfgsuit){
                if($value >= 2 && $value < 4){
                    if(intval($cfgsuit['twosuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                }
                elseif($value >= 4 && $value < 6){
                    if(intval($cfgsuit['foursuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                }
                elseif($value >= 6){
                    if(intval($cfgsuit['sixsuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                }
            }
        }
    }
  //  echo "$e_hp"."=="."$e_patk"."=="."$e_pdef"."=="."$e_mdef"."=="."$e_cri"."=="."$e_cure";
    $hp += $e_hp;
    $atk += $e_patk;
    $def += $e_pdef;
    $mdef += $e_mdef;
    $cri += $e_cri;
    $cure += $e_cure;
    $res = array($partnerid => array('partnerid' => $cfgid,'lv' => $lv, 'starlv' => $starlv, 'quality' => $quality,  'addattr_wuligongji' => $atk, 'addattr_wulifangyu' => $def, 'addattr_mofagongji' => $atk, 'addattr_mofafangyu' => $mdef, 'addattr_crit' => $cri, 'addattr_cure' => $cure, 'addattr_hp' => $hp, 'skill' => $skill , 'skilled' => $skilled));
    return $res;
}

// 获取武将属性
function _getPartnerAttrByVerify($uid,$partnerid)
{
     $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
    $puid = intval($partnerinfo['uid']);
    $lv = intval($partnerinfo['plv']);
    $maip = intval($partnerinfo['mainp']);
    $starlv = intval($partnerinfo['starlv']);
    $pid = intval($partnerinfo['pid']);
    $quality = intval($partnerinfo['quality']);
    $skill = $partnerinfo['skill'];
    $skilled = $partnerinfo['skilled'];
 	 $cfginfo = sql_fetch_one("select * from cfg_partner where partnerid=$pid");
    $cfgid = intval($cfginfo['partnerid']);
    $nowbaseattr=preg_split("/[\s;]+/", $cfginfo['partnerbase']);//
    $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);
    
    
    $atkbase =0;
    $hpbase =0;
    $defbase =0;
    $mdefbase =0;
    $cribase =0;
    $curebase =0;

	//给基础属性赋值
    if (count($baseattr) > 0) {
        foreach ($baseattr as $a) {
            $detail = preg_split("/[\s|]+/", $a);
            if (count($detail) == 2) {
                switch (intval($detail[0])) {
                    case 1:
                        $atkbase = floatval($detail[1]);
                        break;
                    case 2:
                        $hpbase = floatval($detail[1]);
                        break;
                    case 3:
                        $defbase = floatval($detail[1]);
                        break;
                    case 4:
                        $mdefbase = floatval($detail[1]);
                        break;
                    case 5:
                        $cribase = floatval($detail[1]);
                        break;
                    case 6:
                        $curebase = floatval($detail[1]);
                        break;
                    default:
                        break;
                }
            }
        }
    }
    
    if($quality == 0){
        $parr = preg_split("/[\s,]+/", $cfginfo['upep']);
    }
    else{
        $parr = preg_split("/[\s,]+/",$cfginfo['incAttr' . $quality]);
    }
    
    //取出勇者等级阶段
    $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
    $atk=0;
    $hp=0;
    $def=0;
    $mdef=0;
    $cri=0;
    $cure=0;

    //计算进阶次数的升级奖励
    if (count($parr) > 0 ) {
        foreach ($parr as $p) {
            $pdetail = preg_split("/[\s|]+/", $p);
            if (count($pdetail) == 2) {
                switch (intval($pdetail[0])) {
                    case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
                	   	$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
                        $hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
                        $def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    default:
                        break;
                }
            }
        }
    }
    //计算装备属性
    //计算装备属性
    $e_hp = 0;
    $e_patk = 0;
    $e_matk = 0;
    $e_pdef = 0;
    $e_mdef = 0;
    $e_cri = 0;
    $e_cure = 0;
    $suits = array();
    $stardate=array(3=>0.05,4=>0.05,5=>0.05); 
    $equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$puid and u.euser = $partnerid");
    foreach($equips as $equip){
        if(intval($equip['suit']) > 0){
            $suits[] = intval($equip['suit']);
        }
        $uplv = intval($equip['uplv']);
        $star = intval($equip['star']);
        $xishu=$stardate[$star];
    	$e_hp += ceil(intval($equip['hp']) * (1+$xishu*$uplv));
    	$e_patk += ceil(intval($equip['patk']) * (1+$xishu*$uplv));
    	$e_matk += ceil(intval($equip['matk']) * (1+$xishu*$uplv));
    	$e_pdef += ceil(intval($equip['pdef']) * (1+$xishu*$uplv));
    	$e_mdef += ceil(intval($equip['mdef']) * (1+$xishu*$uplv));
    	$e_cri += ceil(intval($equip['cri']) * (1+$xishu*$uplv));
    	$e_cure += ceil(intval($equip['cure']) * (1+$xishu*$uplv));
    }
    $suits = array_count_values($suits);
    foreach($suits as $key => $value){
        if($value >= 2){
            $cfgsuit = sql_fetch_one("select * from cfg_equipsuit where sid = $key");
            if($cfgsuit){
                if($value >= 2 && $value < 4){
                    if(intval($cfgsuit['twosuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                    elseif(intval($cfgsuit['twosuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['twosuit'])/10000));
                    }
                }
                elseif($value >= 4 && $value < 6){
                    if(intval($cfgsuit['foursuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                    elseif(intval($cfgsuit['foursuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['foursuit'])/10000));
                    }
                }
                elseif($value >= 6){
                    if(intval($cfgsuit['sixsuitshuxing']) == 5){
                        $e_patk = ceil($e_patk * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 6){
                        $e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 7){
                        $e_cure = ceil($e_cure * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 8){
                        $e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 10){
                        $e_hp = ceil($e_hp * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                    elseif(intval($cfgsuit['sixsuitshuxing']) == 11){
                        $e_cri = ceil($e_cri * (1 + intval($cfgsuit['sixsuit'])/10000));
                    }
                }
            }
        }
    }
  //  echo "$e_hp"."=="."$e_patk"."=="."$e_pdef"."=="."$e_mdef"."=="."$e_cri"."=="."$e_cure";
    $hp += $e_hp;
    $atk += $e_patk;
    $def += $e_pdef;
    $mdef += $e_mdef;
    $cri += $e_cri;
    $cure += $e_cure;
    return array('partnerid' => $cfgid,'patk' => $atk, 'pdef' => $def, 'matk' => $atk, 'mdef' => $mdef, 'crit' => $cri, 'cure' => $cure, 'hp' => $hp, 'skill' => $skill , 'skilled' => $skilled); 
}


// 根据进阶等级获取武将属性
function getPartnerInfoByStar($uid,$partnerid,$starlv)
{
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
    $lv = intval($partnerinfo['plv']);
    $maip = intval($partnerinfo['mainp']);
    $pid = intval($partnerinfo['pid']);
    $quality = intval($partnerinfo['quality']);
    $cfginfo = sql_fetch_one("select * from cfg_partner where partnerid=$pid");
    $cfgid = intval($cfginfo['partnerid']);
    $nowbaseattr=preg_split("/[\s;]+/", $cfginfo['partnerbase']);//
    $baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);
    
    
    $atkbase =0;
    $hpbase =0;
    $defbase =0;
    $mdefbase =0;
    $cribase =0;
    $curebase =0;

	//给基础属性赋值
    if (count($baseattr) > 0) {
        foreach ($baseattr as $a) {
            $detail = preg_split("/[\s|]+/", $a);
            if (count($detail) == 2) {
                switch (intval($detail[0])) {
                    case 1:
                        $atkbase = floatval($detail[1]);
                        break;
                    case 2:
                        $hpbase = floatval($detail[1]);
                        break;
                    case 3:
                        $defbase = floatval($detail[1]);
                        break;
                    case 4:
                        $mdefbase = floatval($detail[1]);
                        break;
                    case 5:
                        $cribase = floatval($detail[1]);
                        break;
                    case 6:
                        $curebase = floatval($detail[1]);
                        break;
                    default:
                        break;
                }
            }
        }
    }
    
    if($quality == 0){
        $parr = preg_split("/[\s,]+/", $cfginfo['upep']);
    }
    else{
        $parr = preg_split("/[\s,]+/",$cfginfo['incAttr' . $quality]);
    }
    
    //取出勇者等级阶段
    $lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
    $atk=0;
    $hp=0;
    $def=0;
    $mdef=0;
    $cri=0;
    $cure=0;

    //计算进阶次数的升级奖励
    if (count($parr) > 0 ) {
        foreach ($parr as $p) {
            $pdetail = preg_split("/[\s|]+/", $p);
            if (count($pdetail) == 2) {
                switch (intval($pdetail[0])) {
                    case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
                	   	$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
                        $hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
                        $def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
                        $cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
                        break;
                    default:
                        break;
                }
            }
        }
    }
    //=========================================================

    $res = array($partnerid => array('partnerid' => $cfgid,'lv' => $lv, 'starlv' => $starlv, 'quality' => $quality, 'add_hp' => $hp, 'addattr_wuligongji' => $atk, 'addattr_wulifangyu' => $def, 'addattr_mofagongji' => $atk, 'addattr_mofafangyu' => $mdef, 'addattr_crit' => $cri, 'addattr_cure' => $cure, 'addattr_hp' => $hp));
    return $res;
}
// 根据进阶等级获取武将属性
function getPartnerInfoByQuality($uid,$partnerid,$quality)
{
	$partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
	$lv = intval($partnerinfo['plv']);
	$maip = intval($partnerinfo['mainp']);
	$pid = intval($partnerinfo['pid']);
	$starlv = intval($partnerinfo['starlv']);
	$cfginfo = sql_fetch_one("select * from cfg_partner where partnerid=$pid");
	$cfgid = intval($cfginfo['partnerid']);
	$nowbaseattr=preg_split("/[\s;]+/", $cfginfo['partnerbase']);//
	$baseattr = preg_split("/[\s,]+/", $nowbaseattr[$quality]);


	$atkbase =0;
	$hpbase =0;
	$defbase =0;
	$mdefbase =0;
	$cribase =0;
	$curebase =0;

	//给基础属性赋值
	if (count($baseattr) > 0) {
		foreach ($baseattr as $a) {
			$detail = preg_split("/[\s|]+/", $a);
			if (count($detail) == 2) {
				switch (intval($detail[0])) {
					case 1:
						$atkbase = floatval($detail[1]);
						break;
					case 2:
						$hpbase = floatval($detail[1]);
						break;
					case 3:
						$defbase = floatval($detail[1]);
						break;
					case 4:
						$mdefbase = floatval($detail[1]);
						break;
					case 5:
						$cribase = floatval($detail[1]);
						break;
					case 6:
						$curebase = floatval($detail[1]);
						break;
					default:
						break;
				}
			}
		}
	}

	if($quality == 0){
		$parr = preg_split("/[\s,]+/", $cfginfo['upep']);
	}
	else{
		$parr = preg_split("/[\s,]+/",$cfginfo['incAttr' . $quality]);
	}

	//取出勇者等级阶段
	$lvlimit=intval(sql_fetch_one_cell("select uplv from cfg_zhuanzhiunlock where id=$quality"));
	$atk=0;
	$hp=0;
	$def=0;
	$mdef=0;
	$cri=0;
	$cure=0;

	//计算进阶次数的升级奖励
	if (count($parr) > 0 ) {
		foreach ($parr as $p) {
			$pdetail = preg_split("/[\s|]+/", $p);
			if (count($pdetail) == 2) {
				switch (intval($pdetail[0])) {
					case 1: //(基础攻击力+(等级攻击力系数)*(当前等级- 当前转职阶级对应的限制等级) )*1.05的进阶等级次方;
						$atk=(ceil((($atkbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					case 2: // 敏捷：（等级-1）*（敏捷系数*7+初始敏捷）*（1+0.01*进阶等级）
						$hp=(ceil((($hpbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					case 3: // 智力：（等级-1）*（智力系数*7+初始智力）*（1+0.01*进阶等级）
						$def=(ceil((($defbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					case 4: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
						$mdef=(ceil((($mdefbase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					case 5: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
						$cri=(ceil((($cribase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					case 6: // 体力：（等级-1）*（体力系数*7+初始体力）*（1+0.01*进阶等级）
						$cure=(ceil((($curebase+(floatval($pdetail[1]))*($lv- $lvlimit) )*pow(1.05, $starlv))*10))/10;
						break;
					default:
						break;
				}
			}
		}
	}
	//=========================================================
	//计算装备属性
	/*$e_hp = 0;
	$e_patk = 0;
	$e_matk = 0;
	$e_pdef = 0;
	$e_mdef = 0;
	$e_cri = 0;
	$e_cure = 0;
	$suits = array();
	$equips = sql_fetch_rows("select c.*,u.* from cfg_equip c inner join ubag u on c.eid=u.ceid where u.uid=$uid and u.euser = $partnerid");
	foreach($equips as $equip){
		if(intval($equip['suit']) > 0){
			$suits[] = intval($equip['suit']);
		}
		$uplv = intval($equip['uplv']);
		$e_hp += ceil(intval($equip['hp']) * (1+0.05*$uplv));
		$e_patk += ceil(intval($equip['patk']) * (1+0.05*$uplv));
		$e_matk += ceil(intval($equip['matk']) * (1+0.05*$uplv));
		$e_pdef += ceil(intval($equip['pdef']) * (1+0.05*$uplv));
		$e_mdef += ceil(intval($equip['mdef']) * (1+0.05*$uplv));
		$e_cri += ceil(intval($equip['cri']) * (1+0.05*$uplv));
		$e_cure += ceil(intval($equip['cure']) * (1+0.05*$uplv));
	}
	$suits = array_count_values($suits);
	foreach($suits as $key => $value){
		if($value >= 2){
			$cfgsuit = sql_fetch_one("select * from cfg_equipsuit where sid = $key");
			if($cfgsuit){
				if($value >= 2 && $value < 4){
					if(intval($cfgsuit['twosuitshuxing']) == 5){
						$e_patk = ceil($e_patk * (1 + intval($cfgsuit['twosuit'])/10000));
					}
					elseif(intval($cfgsuit['twosuitshuxing']) == 6){
						$e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['twosuit'])/10000));
					}
					elseif(intval($cfgsuit['twosuitshuxing']) == 7){
						$e_cure = ceil($e_cure * (1 + intval($cfgsuit['twosuit'])/10000));
					}
					elseif(intval($cfgsuit['twosuitshuxing']) == 8){
						$e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['twosuit'])/10000));
					}
					elseif(intval($cfgsuit['twosuitshuxing']) == 10){
						$e_hp = ceil($e_hp * (1 + intval($cfgsuit['twosuit'])/10000));
					}
					elseif(intval($cfgsuit['twosuitshuxing']) == 11){
						$e_cri = ceil($e_cri * (1 + intval($cfgsuit['twosuit'])/10000));
					}
				}
				elseif($value >= 4 && $value < 6){
					if(intval($cfgsuit['foursuitshuxing']) == 5){
						$e_patk = ceil($e_patk * (1 + intval($cfgsuit['foursuit'])/10000));
					}
					elseif(intval($cfgsuit['foursuitshuxing']) == 6){
						$e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['foursuit'])/10000));
					}
					elseif(intval($cfgsuit['foursuitshuxing']) == 7){
						$e_cure = ceil($e_cure * (1 + intval($cfgsuit['foursuit'])/10000));
					}
					elseif(intval($cfgsuit['foursuitshuxing']) == 8){
						$e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['foursuit'])/10000));
					}
					elseif(intval($cfgsuit['foursuitshuxing']) == 10){
						$e_hp = ceil($e_hp * (1 + intval($cfgsuit['foursuit'])/10000));
					}
					elseif(intval($cfgsuit['foursuitshuxing']) == 11){
						$e_cri = ceil($e_cri * (1 + intval($cfgsuit['foursuit'])/10000));
					}
				}
				elseif($value >= 6){
					if(intval($cfgsuit['sixsuitshuxing']) == 5){
						$e_patk = ceil($e_patk * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
					elseif(intval($cfgsuit['sixsuitshuxing']) == 6){
						$e_pdef = ceil($e_pdef * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
					elseif(intval($cfgsuit['sixsuitshuxing']) == 7){
						$e_cure = ceil($e_cure * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
					elseif(intval($cfgsuit['sixsuitshuxing']) == 8){
						$e_mdef = ceil($e_mdef * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
					elseif(intval($cfgsuit['sixsuitshuxing']) == 10){
						$e_hp = ceil($e_hp * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
					elseif(intval($cfgsuit['sixsuitshuxing']) == 11){
						$e_cri = ceil($e_cri * (1 + intval($cfgsuit['sixsuit'])/10000));
					}
				}
			}
		}
	}
	$hp += $e_hp;
	$atk += $e_patk;
	$def += $e_pdef;
	$mdef += $e_mdef;
	$cri += $e_cri;
	$cure += $e_cure;*/
	$res = array($partnerid => array('partnerid' => $cfgid,'lv' => $lv, 'starlv' => $starlv, 'quality' => $quality, 'add_hp' => $hp, 'addattr_wuligongji' => $atk, 'addattr_wulifangyu' => $def, 'addattr_mofagongji' => $atk, 'addattr_mofafangyu' => $mdef, 'addattr_crit' => $cri, 'addattr_cure' => $cure, 'addattr_hp' => $hp));
	return $res;
}
//!佣兵进阶
function upStarlvPartner($uid, $params)
{
    $partnerid = intval($params[0]);
    $ulv = sql_fetch_one("select ulv from uinfo where uid=$uid");
    $upartner = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    $plv=intval($upartner['plv']);
    $starlv = intval($upartner['starlv']);
    $quality=intval($upartner['quality']);
    if($plv<11 || $quality==0)
    {
    	return array(
    			0,
    			STR_PLvOff
    	);
    }
    if (!$upartner){
        return array(
            0,
            STR_Wrong_Pid
        );
    }
    
    if($starlv >= 10){
        return array(
            0,
            STR_Soul_SoulLv_Top
        );
    }
   /*$needlv = intval(sql_fetch_one_cell("select upstar from cfg_levelunlock where lv = $starlv+1"));
    if(intval($upartner['plv']) < $needlv){
        return array(
            0,
            STR_PLvOff
        );
    }*/
    $pid = intval($upartner['pid']);
    $partnercfg = sql_fetch_one("select * from cfg_partner where partnerid=$pid");
    $itemids = explode("|",$partnercfg['classItem']);
    $itemidarr = explode(",",$itemids[$starlv]);
    $itemnums = explode("|",$partnercfg['classAmount']);
    $itemnumarr = explode(",",$itemnums[$starlv]);
    $coins = explode("|",$partnercfg['classCoin']);
    $coin = $coins[$starlv];
    if(count($itemidarr) != count($itemnumarr)){
        return array(
            0,
            STR_Param_Error
        );
    }
    if (! _checkCoin($uid, $coin)) {
        return array(
            0,
            STR_CoinOff . $coin
        );
    }
    for($i = 0; $i < count($itemidarr); $i ++){
        if (! _checkItem($uid, intval($itemidarr[$i]), intval($itemnumarr[$i]))) {
            return array(
                0,
                STR_ResourceOff
            );
        }
    }
    $itemarr= array();
    for($i = 0; $i < count($itemidarr); $i ++){
        if (! _subItem($uid, intval($itemidarr[$i]), intval($itemnumarr[$i]),'佣兵进阶')) {
            return array(
                0,
                STR_ResourceOff
            );
        }
        $itemarr[] = "$itemidarr[$i]"."|"."$itemnumarr[$i]";
    } 
    $itemstr = implode(",", $itemarr);
    _spendCoin($uid, $coin,'佣兵进阶');
    sql_update("update upartner set starlv=starlv+1 where partnerid=$partnerid and uid=$uid");
    _updateUTaskProcess($uid, 1002);
    
//     $pinfo = getPartnerInfoByQuality($uid,intval($partnerid),$quality + 2);
    return array(
        1,
        $starlv + 1,
        $itemstr,
        $coin
    );
}

function delPartnerByPids($uid, $pids)
{
    if(count($pids) == 0){
        return array(
            0,
            0
        );
    }
    $pidstr = implode(",", $pids);
    sql_update("delete from upartner where partnerid in ($pidstr) and uid=$uid");
    $equip = sql_fetch_one("select * from uequip where uid = $uid");
    if(!$equip){
        return array(
            0,
            0
        );
    }
    $stagestr = $equip['stagepartner'];
    $leader = intval($equip['leader']);
    $brave = intval($equip['brave']);
    if(!$stagestr){
        return array(
            0,
            0
        );
    }
    if(in_array($brave, $pids)){
        sql_update("update uequip set brave = 0 where uid = $uid");
    }
    if(in_array($leader, $pids)){
        sql_update("update uequip set leader = 0 where uid = $uid");
    }
    $stageids = explode(",", $stagestr);
    foreach ($stageids as $id){
        if(in_array($id, $pids)){
            sql_update("update uequip set stagepartner = '' where uid = $uid");
        }
    }
    $ubag = sql_fetch_rows("select * from ubag where uid = $uid and euser in ($pidstr);");
    $eids = array();
    if($ubag){
        foreach ($ubag as $e){
            $eids[] = intval($e['eid']);
        }
    }
    if(count($eids) > 0){
        $eidstr = implode(",", $eids);
        sql_update("update `ubag` set euser = 0 where eid in ($eidstr);");
    }
    return array(
        1,
        1
    );
}


//获取女神信息
function getGirlInfo($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $ugirl = sql_fetch_rows("SELECT * FROM `ugirl` WHERE `uid`=$uid");
    $ugirlids = sql_fetch_one("SELECT girl,pvegirl,pvpgirl FROM `uequip` WHERE `uid`=$uid");
    $uarena = sql_fetch_one("select attgirl,defgirl from $upa_num where `uid`=$uid");
    if(!is_array($uarena)){
        $uarena = array('attgirl' => 0, 'defgirl' => 0);
    }
    $ugirls = array_merge($ugirlids,$uarena);
    return array(
        1,
        $ugirl,
        $ugirls
    );
}

//设置上阵女神
function setGirl($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $girl = $params[0];
    $type = $params[1];
    $ugirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and gid=$girl");
    if (!$ugirl) {
        return array(
            0,
            STR_Girl_NotExist
        );
    }
    $girlids = array();
    if($type == 1){
        sql_update("update uequip set girl=$girl where uid=$uid");
    }
    elseif($type == 2){
        sql_update("update uequip set pvegirl=$girl where uid=$uid");
    }
    elseif($type == 3){
        sql_update("update uequip set pvpgirl=$girl where uid=$uid");
    }
    elseif($type == 4){
        sql_update("update $upa_num set attgirl = $girl where uid = $uid");
    }
    elseif ($type == 5){
        sql_update("update $upa_num set defgirl = $girl where uid = $uid");
    }
    $ugirl = sql_fetch_rows("SELECT * FROM `ugirl` WHERE `uid`=$uid");
    $ugirlids = sql_fetch_one("SELECT girl,pvegirl,pvpgirl FROM `uequip` WHERE `uid`=$uid");
    $uarena = sql_fetch_one("select attgirl,defgirl from $upa_num where `uid`=$uid");
    if(!is_array($uarena)){
        $uarena = array('attgirl' => 0, 'defgirl' => 0);
    }
    $ugirls = array_merge($ugirlids,$uarena);
    return array(
        1,
        $ugirl,
        $ugirls
    );
}

//增加女神
function addGirl($uid, $chapter)
{
    $cfg_girl = sql_fetch_one("SELECT * FROM `cfg_girl` WHERE `chapter`=$chapter");
    $gid = $cfg_girl['id'];
    $name = $cfg_girl['name'];
    $skillid = $cfg_girl['skillid'];
    if($gid){
        $ugirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE uid=$uid and gid =$gid");
        if($ugirl){
            return array(
                0,
                0
            );
        }
    }
    if($cfg_girl){
        sql_update("insert into `ugirl`(gid,uid,name,skillid) value($gid,$uid,'$name',$skillid)");
        return array(
            1,
            $gid
        );
    }
    else{
        return array(
            0,
            0
        );
    }
}

//增加女神
function addTestGirl($uid, $params)
{
 /*   $chapter = $params[0];
    $cfg_girl = sql_fetch_one("SELECT * FROM `cfg_girl` WHERE `chapter`=$chapter");
    $gid = $cfg_girl['id'];
    $name = $cfg_girl['name'];
    $skillid = $cfg_girl['skillid'];
    $ugirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE uid=$uid and gid =$gid");
    if($ugirl){
        return array(
            1,
            0
        );
    }
    if($cfg_girl){
        sql_update("insert into `ugirl`(gid,uid,name,skillid) value($gid,$uid,'$name',$skillid)");
        $garr = array($gid,0);
        setGirl($uid, $garr);
        return array(
            1,
            $gid
        );
    }
    else{
        return array(
            1,
            0
        );
    }*/
}

//升级女神
function upGirlSkill($uid, $params)
{
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
/*    if ($ulv < 9) {
        return array(
            0,
            STR_Lv_Low2
        );
    }*/
    $girlid = $params[0];
    $ugirl = sql_fetch_one("SELECT * FROM `ugirl` WHERE `uid`=$uid and `gid`=$girlid");
    if (!$ugirl) {
        return array(
            0,
            STR_Girl_NotExist
        );
    }
    $lv = intval($ugirl['level']);
    $cfg_girl = sql_fetch_one("SELECT * FROM `cfg_girl` WHERE `id`=$girlid");
    if (!$cfg_girl) {
        return array(
            0,
            STR_Config_Null
        );
    }
    $coinarr = explode(',', $cfg_girl['coin']);
    $coin = intval($coinarr[$lv-1]);
    $proparr1 = explode(',', $cfg_girl['prop1']);
    $countarr1 = explode(',', $cfg_girl['count1']);
    $itemid1 = intval($proparr1[$lv-1]);
    $itemcount1 = intval($countarr1[$lv-1]);
    $proparr2 = explode(',', $cfg_girl['prop2']);
    $countarr2 = explode(',', $cfg_girl['count2']);
    $itemid2 = intval($proparr2[$lv-1]);
    $itemcount2 = intval($countarr2[$lv-1]);
    $proparr3 = explode(',', $cfg_girl['prop3']);
    $countarr3 = explode(',', $cfg_girl['count3']);
    $itemid3 = intval($proparr3[$lv-1]);
    $itemcount3 = intval($countarr3[$lv-1]);
    if (! _checkCoin($uid, $coin)) {
        return array(
            0,
            STR_CoinOff . $coin
        );
    }
    //==========================
  	 $skillid = $ugirl['skillid'];
    $skillcfg = sql_fetch_one("select * from cfg_skilled where sid = $skillid");
    if(!$skillcfg){
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    $upsid = $skillcfg['upsid'];
    if($skillid > 30000){
        $coe = intval(($skillid - 30000) / 1000);
    }
    if($coe >= 6){
        return array(
            0,
            STR_Max_Lv_Rune_Skill
        );
    }
//     return array($itemid1,$itemid2,$itemid3,$itemcount1,$itemcount2,$itemcount3);
    if(!_checkItem($uid,  $itemid1, $itemcount1) || !_checkItem($uid, $itemid2, $itemcount2) || !_checkItem($uid,$itemid3, $itemcount3)){
        return array(
            0,
            STR_Item_MaterialOff
        );
    }

    $upsid = $skillcfg['upsid'];
    if (_spendCoin($uid, $coin,'女神技能升级') && _subItem($uid, $itemid1, $itemcount1,'女神技能升级') 
        && _subItem($uid, $itemid2, $itemcount2,'女神技能升级') && _subItem($uid, $itemid3, $itemcount3,'女神技能升级') ) {
        $ret = sql_update("update ugirl set level=level+1,skillid = $upsid where gid=$girlid and uid=$uid");
        if ($ret == 1) {
            $girl = sql_fetch_one("select * from ugirl where gid=$girlid and uid=$uid");
            $items = array(array($itemid1,$itemcount1),array($itemid2,$itemcount2),array($itemid3,$itemcount3));
            return array(
                1,
                $girl,
                $items
            );
        }
    }
    else{
        return array(
            0,
            STR_Item_MaterialOff
        );
    }
}

//锁定佣兵
function lockPartner($uid, $params)
{
    $islock = intval($params[0]);
    $pcount = count($params);
    if ($pcount <2){
        return array(
            0,
            STR_Param_Error
        );
    }
    $pids = array();
    if($islock == 0){
        for ($i = 1; $i < $pcount-1; $i ++){
            $p = sql_fetch_one("select * from upartner where partnerid = $params[$i] and uid = $uid");
            if (!$p){
                return array(
                    0,
                    STR_Wrong_Pid
                );
            }
            $pids[] = intval($params[$i]);
        }
        $pidstr = implode(",", $pids);
        $ret = sql_update("update upartner set islock=0 where partnerid in ($pidstr) and uid=$uid");
    }
    else{
        for ($i = 1; $i < $pcount-1; $i ++){
            $p = sql_fetch_one("select * from upartner where partnerid = $params[$i] and uid = $uid");
            if (!$p){
                return array(
                    0,
                    STR_Wrong_Pid
                );
            }
            $pids[] = intval($params[$i]);
        }
        $pidstr = implode(",", $pids);
        $ret = sql_update("update upartner set islock=1 where partnerid in ($pidstr) and uid=$uid");
    }
    return array(
        1,
        $pids,
        $ret
    );
}

//获取勇者竞技场信息
function getPartnerArena($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $refresh = $params[0];
    $uarena = sql_fetch_one("select a.*,u.bravecoin from $upa_num a left outer join uinfo u on a.uid=u.uid where a.uid = $uid");
    if(!$uarena){
        sql_insert("insert into $upa_num(uid,attgirl,defgirl) values($uid,0,0)");
        $uarena = sql_fetch_one("select a.*,u.bravecoin from $upa_num a left outer join uinfo u on a.uid=u.uid where a.uid = $uid");
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    for ($num = 1; $num <= 3; $num ++){
        $defensestr = 'defense'.$num;
        $defpartners = 'partners'.$num;
        $zhanlistr = 'zhanli'.$num;
        $defense = $uarena[$defensestr];
        if($defense){
            $pids = explode(",", $defense);
            if(count($pids) > 0){
                $pidstr = implode(",",$pids);
                $jobs = array();
                $stageids = array();
                $pjob = sql_fetch_rows("select partnerid,mainp from upartner where uid=$uid and partnerid in ($pidstr)");
                foreach($pjob as $value){
                    $partnerid = $value['partnerid'];
                    $mainp = $value['mainp'];
                    $jobs[$partnerid] = $mainp;
                }
                asort($jobs);
                foreach($jobs as $key=>$value){
                    $stageids[] = $key;
                }
                if(count($stageids) > 0){
                    $uarena[$defpartners] = getPartnerbyPids($uid,$stageids);
                }
                $my = new my(2, $uinfo, $defense);
                $zhanli = $my->zhanli;
                $uarena[$zhanlistr] = $zhanli;
            }
        }
    }
    $index = $uarena['id'];
    $matchstr = $uarena['matchinfo'];
    if(!$matchstr || $refresh){
        if ($index == 1) {
            $backindex[] = 1;
        }elseif ($index == 2){
            $backindex[] = 1;
        }elseif ($index == 3){
            $backindex[] = 1;
            $backindex[] = 2;
        } elseif($index >= 4 && $index <= 10 ) {
            $backindex[] = $index - 3;
            $backindex[] = $index - 2;
            $backindex[] = $index - 1;
        }else{
            $backindex[] = rand(floor($index * 0.25) - 1, floor($index * 0.5) - 1);
            $backindex[] = rand(floor($index * 0.51) - 1, floor($index * 0.97) - 1);
            $backindex[] = rand(floor($index * 0.98) - 1, (intval($index) - 1));
            $backindex = array_unique($backindex);
            if(count($backindex) < 3)
            {
                $backindex[] = rand($backindex[0], $index);
            }
        }
        if(count($backindex) > 0){
            $matchstr = implode(",", $backindex);
            sql_update("update $upa_num set matchinfo = '$matchstr' where id = $index");
        }
    }
    $res = sql_fetch_rows("select u.ulv,u.uname,u.zhanli,a.* from uinfo u left outer join $upa_num a on u.uid=a.uid where id in ($matchstr) and attleader1 != 0");
    for($i = 0; $i < count($res); $i ++){
        $backuid = $res[$i]['uid'];
        $mbrave = sql_fetch_one("select p.pid from uequip e left outer join upartner p on p.partnerid = e.brave where e.uid = $backuid");
        $res[$i]['brave'] = $mbrave;
        $zhanli = 0;
        for ($j = 1; $j <= 3; $j ++){
            $defstr = 'defense'.$j;
            $defnum = 'def'.$j;
            $defense = $res[$i][$defstr];
            if($defense){
                $mpids = explode(",", $defense);
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
                        $res[$i][$defnum]['partners'] = getPartnerbyPids($backuid,$mstageids);
                        $myinfo = sql_fetch_one("select * from uinfo where uid=$backuid");
                        $my = new my(2, $myinfo, $defense);
                        $zhanli += $my->zhanli;
                        $res[$i]['zhanli'] = $zhanli;
                    }
                    else{
                        $res[$i][$defnum]['partners'] = array();
                        $res[$i]['zhanli'] = $zhanli;
                    }
                }
                else{
                    $res[$i][$defnum]['partners'] = array();
                    $res[$i]['zhanli'] = $zhanli;
                }
            }
        }
    }
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts, UNIX_TIMESTAMP(CURDATE()) + 8*3600 as stime FROM uinfo WHERE uid=$uid");
    $nowtime = $uinfo['ts'];
    $bravetime = $uinfo['bravecointime'];
    $pktime = intval(sql_fetch_one_cell("select time from $upa_num where uid = $uid"));
    if($bravetime < $pktime){
        $bravetime = $pktime;
    }
    $starttime = $uinfo['stime'];
    $nowhour = date("H",$nowtime);
    $uhour = date("H",$bravetime);
    $bravecoin = 0;
    $uarenaindex = sql_fetch_one_cell("select id from $upa_num where uid = $uid");
    $cfg = sql_fetch_one("select * from cfg_pvpaward where `minindex`<='$uarenaindex' and `maxindex`>='$uarenaindex'");
    if($cfg && ($nowhour >= 8 && $nowhour <= 24)){
        $hourbrave = $cfg['hourbrave'];
        $day = date("Ymd",$bravetime);
        $nowday = date("Ymd",$nowtime);
        if($day == $nowday){
            if($uhour >= 8){
                $bravecoin = round((($nowtime - $bravetime) / 3600) * $hourbrave);
            }
            else{
                $bravecoin = round((($nowtime - $starttime) / 3600) * $hourbrave);
            }
        }
        else{
            $bravecoin = round((($nowtime - $starttime) / 3600) * $hourbrave);
        }
    }
    return array(
        1,
        $uarena,
        $res,
        $bravecoin
    );
}

//查看勇者竞技场排行榜
function getPartnerArenaRank($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $res = sql_fetch_rows("select u.ulv,u.uname,a.* from uinfo u left outer join $upa_num a on u.uid=a.uid where a.id > 0 and attleader1 != 0 order by a.id asc limit 20");
    for($i = 0; $i < count($res); $i ++){
        $backuid = $res[$i]['uid'];
        $defense = $res[$i]['defense1'];
        $zhanli = 0;
        for ($j = 1; $j <= 3; $j ++){
            $defstr = 'defense'.$j;
            $defnum = 'def'.$j;
            $defense = $res[$i][$defstr];
            if($defense){
                $mpids = explode(",", $defense);
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
                        if($j == 1){
                            $res[$i]['def1']['partners'] = getPartnerbyPids($backuid,$mstageids);
                        }
                        $myinfo = sql_fetch_one("select * from uinfo where uid=$backuid");
                        $my = new my(2, $myinfo, $defense);
                        $zhanli += $my->zhanli;
                        $res[$i]['zhanli'] = $zhanli;
                    }
                    else{
                        if($j == 1){
                            $res[$i]['def1']['partners'] = array();
                        }
                        $res[$i]['zhanli'] = $zhanli;
                    }
                }
                else{
                    if($j == 1){
                        $res[$i]['def1']['partners'] = array();
                    }
                    $res[$i]['zhanli'] = $zhanli;
                }
            }
        }
    }
    return array(
        1,
        $res
    );
}

//设置勇者竞技场信息
function setAttPartnerArena($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $attack1 = $params[0];
    $attack2 = $params[1];
    $attack3 = $params[2];
    $leaderstr = $params[3];
    $attleaders = explode(",", $leaderstr);
    $attleader1 = $attleaders[0];
    $attleader2 = $attleaders[1];
    $attleader3 = $attleaders[2];
    sql_update("update $upa_num set attack1 = '$attack1', attack2 = '$attack2', attack3 = '$attack3', attleader1 = $attleader1, attleader2 = $attleader2, attleader3 = $attleader3 where uid = $uid");
    return array(
        1
    );
}

//设置勇者竞技场信息
function setDefPartnerArena($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $defense1 = $params[0];
    $defense2 = $params[1];
    $defense3 = $params[2];
    $leaderstr = $params[3];
    $defleaders = explode(",", $leaderstr);
    $defleader1 = $defleaders[0];
    $defleader2 = $defleaders[1];
    $defleader3 = $defleaders[2];
    sql_update("update $upa_num set defense1 = '$defense1', defense2 = '$defense2', defense3 = '$defense3', defleader1 = $defleader1, defleader2 = $defleader2, defleader3=$defleader3 where uid = $uid");
    return array(
        1
    );
}

//随机玩家竞技场技能
function _randomMyArenaSkill($uid,$type)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $myarena = sql_fetch_one("select * from $upa_num where uid=$uid");
    $leader = 0;
    if($type == 1){
        $spartners = $myarena['attack1'] ? $myarena['attack1'] : '';
        $leader = intval($myarena['attleader1']);
    }
    else if($type == 2){
        $spartners = $myarena['attack2'] ? $myarena['attack2'] : '';
        $leader = intval($myarena['attleader2']);
    }
    else if($type == 3){
        $spartners = $myarena['attack3'] ? $myarena['attack3'] : '';
        $leader = intval($myarena['attleader3']);
    }
    $res = sql_fetch_rows("select partnerid,uid,skill from upartner where uid=$uid and partnerid in ($spartners) order by mainp asc, partnerid asc");
    $pids = explode(",", $spartners);

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

    sql_update("update $upa_num set mypos='$parterstr' where uid = $uid");
    //=========================================
    $partnerattr = array();
    foreach ($pids as $v){
        $partnerattr[] = getPartnerAttr($uid,intval($v));
    }
    $girlid = intval(sql_fetch_one_cell("select attgirl from $upa_num where uid=$uid"));
    $girl = array();
    if($girlid){
        $girl = sql_fetch_one("select * from `ugirl` where `uid`=$uid and `gid`=$girlid");
    }
    return array($uid,$skillstrs,$partnerattr,$girl,$leader);
}

//随机对手竞技场技能
function _randomMatchArenaSkill($muid,$type)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$muid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $marena = sql_fetch_one("select * from $upa_num where uid=$muid");
    $mpidstr = '';
    if($type == 1){
        $mpidstr = $marena['defense1'];
    }
    else if($type == 2){
        $mpidstr = $marena['defense2'];
    }
    else if($type == 3){
        $mpidstr = $marena['defense3'];
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
        $value["$v"]['addattr_hp']=intval($value["$v"]['addattr_hp']*3.5);
        $mpartnerattr[] = $value;
    }

    $mgirlid = intval(sql_fetch_one_cell("select defgirl from $upa_num where uid=$muid"));
    $mgirl = array();
    if($mgirlid){
        $mgirl = sql_fetch_one("select * from `ugirl` where `uid`=$muid and `gid`=$mgirlid");
    }
    return array($muid,$skillstrs,$mpartnerattr,$mgirl,$mleader);
}

//开始勇者竞技场战斗
function startPartnerArena($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 5");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $nowtime = sql_fetch_one_cell("select UNIX_TIMESTAMP()");
    $cdtime = sql_fetch_one_cell("select cdtime from $upa_num where uid = $uid");
    if($cdtime > $nowtime){
        return array(
            0,
            STR_Tower_Rest
        );
    }
    $nowhour = date("H",$nowtime);
    if($nowhour < 8){
        return array(
            0,
            STR_Arena_Not_Opentime
        );
    }
    $type = $params[0];
    $muid = $params[1];
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    if($ulv < intval($cfg['lv'])){
        return array(
            0,
            STR_LvOff
        );
    }
    $uarena = sql_fetch_one("select * from $upa_num where uid = $uid");
    $time = sql_fetch_one("select UNIX_TIMESTAMP()-time as difftime from $upa_num where uid=$uid");
    if ($time["difftime"] < 0) {
        return array(
            0,
            STR_PVP_REST
        );
    }
    $pvpcount = intval(sql_fetch_one_cell("select count from $upa_num where uid=$uid"));
    if($type == 1 && $pvpcount <= 0){
        return array(
            0,
            STR_PVP_TIMESOFF
        );
    }
    $attack = '';
    if($type == 1){
        $attack = $uarena['attack1'];
    }
    else if($type == 2){
        $attack = $uarena['attack2'];
    }
    else if($type == 3){
        $attack = $uarena['attack3'];
    }
    if(!$attack){
        return array(
            0,
            STR_ATTACK_ERROR
        );
    }
    $myskills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($attack)");
    $myskillarr[] = $myskills;
    $minfo = sql_fetch_one("select * from uinfo where uid=$muid");
    $muarena = sql_fetch_one("select * from $upa_num where uid = $muid");
    if(!$minfo || !$muarena){
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $mpidstr = '';
    if($type == 1){
        $mpidstr = $muarena['defense1'];
    }
    else if($type == 2){
        $mpidstr = $muarena['defense2'];
    }
    else if($type == 3){
        $mpidstr = $muarena['defense3'];
    }
    $matchskills=sql_fetch_rows("select partnerid,skilllevel,skilledlevel from upartner where partnerid in($mpidstr)");
    $matchskillarr[] = $matchskills;
    $mname = $minfo['uname'];
    if($type == 1){
        sql_update("update $upa_num set winnum=0 where uid=$uid");
        sql_update("update $upa_num set count=count-1 where uid = $uid");
    }
    //========================玩家的佣兵技能===========================
    $my = _randomMyArenaSkill($uid,$type);
    //========================对手的佣兵技能===========================
    $match = _randomMatchArenaSkill($muid,$type);
    $upos = sql_fetch_one_cell("select mypos from $upa_num where uid=$uid");
    _startclubtask($uid,10);
    return array(
        1,
        $my,
        $match,
        $mname,
        $upos,
        $myskillarr,
        $matchskillarr
    );

}

//结束勇者竞技场战斗
function endPartnerArena($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $ret = $params[0];
    $muid = $params[1];
    $bout = $params[2];
    $mydata = sql_fetch_one("select * from $upa_num where uid = $uid");
    $matchdata = sql_fetch_one("select * from $upa_num where uid = $muid");
    $myindex = $mydata['id'];
    $attack1 = $mydata['attack1'];
    $attack2 = $mydata['attack2'];
    $attack3 = $mydata['attack3'];
    $defense1 = $mydata['defense1'];
    $defense2 = $mydata['defense2'];
    $defense3 = $mydata['defense3'];
    $attleader1 = $mydata['attleader1'];
    $attleader2 = $mydata['attleader2'];
    $attleader3 = $mydata['attleader3'];
    $defleader1 = $mydata['defleader1'];
    $defleader2 = $mydata['defleader2'];
    $defleader3 = $mydata['defleader3'];
    $attgirl = $mydata['attgirl'];
    $defgirl = $mydata['defgirl'];
    $fighttime = $mydata['time'];
    $mycount = $mydata['count'];
    $winnum = $mydata['winnum'];
    $allwin = $mydata['allwin'];
    $allfail = $mydata['allfail'];
    $matchindex = $matchdata['id'];
    $mattack1 = $matchdata['attack1'];
    $mattack2 = $matchdata['attack2'];
    $mattack3 = $matchdata['attack3'];
    $mdefense1 = $matchdata['defense1'];
    $mdefense2 = $matchdata['defense2'];
    $mdefense3 = $matchdata['defense3'];
    $mattleader1 = $matchdata['attleader1'];
    $mattleader2 = $matchdata['attleader2'];
    $mattleader3 = $matchdata['attleader3'];
    $mdefleader1 = $matchdata['defleader1'];
    $mdefleader2 = $matchdata['defleader2'];
    $mdefleader3 = $matchdata['defleader3'];
    $mattgirl = $matchdata['attgirl'];
    $mdefgirl = $matchdata['defgirl'];
    $mfighttime = $matchdata['time'];
    $matchcount = $matchdata['count'];
    $mwinnum = $matchdata['winnum'];
    $mallwin = $matchdata['allwin'];
    $mallfail = $matchdata['allfail'];
    if($ret == 1){
        if($bout == 1){
            sql_update("update $upa_num set winnum = 1, bout = 1 where uid = $uid");
        }
        else if($bout == 2){
            sql_update("update $upa_num set winnum = winnum + 1, bout = 2 where uid = $uid");
            if($winnum >= 1 && ($myindex > $matchindex)){
                getBraveCoin($uid);
                getBraveCoin($muid);
                sql_update("update $upa_num set uid = $uid, attack1 = '$attack1',attack2='$attack2',attack3='$attack3',defense1='$defense1',defense2='$defense2',defense3='$defense3',attleader1=$attleader1,attleader2=$attleader2,attleader3=$attleader3,defleader1=$defleader1,defleader2=$defleader2,defleader3=$defleader3,attgirl=$attgirl,defgirl=$defgirl,time=$fighttime,count=$mycount,allwin=$allwin,allfail=$allfail where id = $matchindex");
                sql_update("update $upa_num set uid = $muid, attack1 = '$mattack1',attack2='$mattack2',attack3='$mattack3',defense1='$mdefense1',defense2='$mdefense2',defense3='$mdefense3',attleader1=$mattleader1,attleader2=$mattleader2,attleader3=$mattleader3,defleader1=$mdefleader1,defleader2=$mdefleader2,defleader3=$mdefleader3,attgirl=$mattgirl,defgirl=$mdefgirl,time=$mfighttime,count=$matchcount,allwin=$mallwin,allfail=$mallfail where id = $myindex");
            }
        }
        else if($bout == 3){
            sql_update("update $upa_num set winnum = winnum + 1, bout = 3 where uid = $uid");
            if($winnum >= 1 && ($myindex > $matchindex)){
                getBraveCoin($uid);
                getBraveCoin($muid);
                sql_update("update $upa_num set uid = $uid, attack1 = '$attack1',attack2='$attack2',attack3='$attack3',defense1='$defense1',defense2='$defense2',defense3='$defense3',attleader1=$attleader1,attleader2=$attleader2,attleader3=$attleader3,defleader1=$defleader1,defleader2=$defleader2,defleader3=$defleader3,attgirl=$attgirl,defgirl=$defgirl,time=$fighttime,count=$mycount,allwin=$allwin,allfail=$allfail where id = $matchindex");
                sql_update("update $upa_num set uid = $muid, attack1 = '$mattack1',attack2='$mattack2',attack3='$mattack3',defense1='$mdefense1',defense2='$mdefense2',defense3='$mdefense3',attleader1=$mattleader1,attleader2=$mattleader2,attleader3=$mattleader3,defleader1=$mdefleader1,defleader2=$mdefleader2,defleader3=$mdefleader3,attgirl=$mattgirl,defgirl=$mdefgirl,time=$mfighttime,count=$matchcount,allwin=$mallwin,allfail=$mallfail where id = $myindex");
            }
        }
        sql_fetch_one("update $upa_num set allwin = allwin + 1 where uid = $uid");
    }
    else{
        if($bout == 1){
            sql_update("update $upa_num set winnum = 0, bout = 1 where uid = $uid");
        }
        else if($bout == 2){
            sql_update("update $upa_num set bout = 2 where uid = $uid");
            if($winnum == 0){
                sql_update("update $upa_num set cdtime = UNIX_TIMESTAMP() + 600 where uid = $uid");
            }
        }
        else if($bout == 3){
            sql_update("update $upa_num set bout = 3 where uid = $uid");
            if($winnum < 2){
                sql_update("update $upa_num set cdtime = UNIX_TIMESTAMP() + 600 where uid = $uid");
            }
        }
        sql_fetch_one("update $upa_num set allfail = allfail + 1 where uid = $uid");
    }
    _endclubtask($uid,10);
    return array(
        1
    );
}

//购买勇者竞技场挑战次数
function buyPartnerArenaCount($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $buycount = sql_fetch_one_cell("select buyarena from uinfo where uid = $uid");
    $buycount += 1;
    if($buycount > 10){
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
        if (! _spendCoin($uid, $cost, "购买勇者竞技场挑战次数")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 2){
        if (! _spendGbytype($uid, $cost, "购买勇者竞技场挑战次数")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    sql_update("update $upa_num set count = count + 1 where uid = $uid");
    sql_update("update uinfo set buyarena = buyarena + 1 where uid = $uid");
    $count = sql_fetch_one_cell("select count from $upa_num where uid = $uid");
    return array(
        1,
        $count
    );
}

//购买勇者竞技场挑战CD时间
function buyPartnerArenaCDTime($uid, $params)
{
    $muinfo=sql_fetch_one("select serverid from uinfo where uid=$uid");
    $serverid=$muinfo['serverid'];
    //判断那个服
    $upa_num="upartnerarena_".$serverid;
    $cost = 20;
    if (! _spendGbytype($uid, $cost, "购买勇者竞技场CD时间")) {
        return array(
            0,
            STR_UgOff.$cost
        );
    }
    sql_update("update $upa_num set cdtime = UNIX_TIMESTAMP() where uid = $uid");
    return array(
        1,
        $cost
    );
}

//领取勇者币奖励
function getBraveCoin($uid)
{
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts, UNIX_TIMESTAMP(CURDATE()) + 8*3600 as stime FROM uinfo WHERE uid=$uid");
    $nowtime = $uinfo['ts'];
    $bravetime = $uinfo['bravecointime'];
    //判断那个服
    $serverid=$uinfo['serverid'];
    $upa_num="upartnerarena_".$serverid;
    $pktime = intval(sql_fetch_one_cell("select time from $upa_num where uid = $uid"));
    if($bravetime < $pktime){
        $bravetime = $pktime;
    }
    $starttime = $uinfo['stime'];
    $nowhour = date("H",$nowtime);
    $uhour = date("H",$bravetime);
    $bravecoin = 0;
    $uarenaindex = sql_fetch_one_cell("select id from `$upa_num` where uid = $uid");
    $cfg = sql_fetch_one("select * from cfg_pvpaward where `minindex`<='$uarenaindex' and `maxindex`>='$uarenaindex'");
    if($cfg && ($nowhour >= 8 && $nowhour <= 24)){
        $hourbrave = $cfg['hourbrave'];
        $day = date("Ymd",$bravetime);
        $nowday = date("Ymd",$nowtime);
        if($day == $nowday){
            if($uhour >= 8){
                $bravecoin = round((($nowtime - $bravetime) / 3600) * $hourbrave);
            }
            else{
                $bravecoin = round((($nowtime - $starttime) / 3600) * $hourbrave);
            }
        }
        else{
            $bravecoin = round((($nowtime - $starttime) / 3600) * $hourbrave);
        }
        sql_update("update uinfo set bravecoin = bravecoin + $bravecoin, bravecointime = UNIX_TIMESTAMP() where uid = $uid");
    }
    return array(
        1,
        $bravecoin
    );
}


//升级武将技能到十级
function upPartnerskillToTenLevel($uid, $params)
{
    $partnerid = intval($params[0]);
    $skillid = intval($params[1]);
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 1");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    if(intval($partnerinfo['plv']) < intval($cfg['plv'])){
        return array(
            0,
            STR_PartnerLV_Low
        );
    }
    if($skillid < 30000){
        $skillcfg = sql_fetch_one("select * from cfg_skill where sid = $skillid");
    }
    else{
        $skillcfg = sql_fetch_one("select * from cfg_skilled where sid = $skillid");
    }
    if(!$skillcfg){
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    if($skillid < 30000){
        $skillstr = $partnerinfo['skill'];
    }
    else{
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    $skills = preg_split("/[\s,]+/", $skillstr);
    if(!in_array($skillid, $skills)){
        return array(
            0,
            STR_Partner_SkillErr
        );
    }
    $skilllv = 0;
    $skilllvstr = $partnerinfo['skilllevel'];
    $skilllvs = preg_split("/[\s,]+/", $skilllvstr);
    if($skillid < 20000){
        $skilllv = $skilllvs[0];
    }
    elseif($skillid >= 20000 && $skillid < 30000){
        $skilllv = $skilllvs[1];
    }
    if($skilllv > 99||$skilllv>$ulv){
        return array(
            0,
            STR_Max_Lv_Rune_Skill
        );
    }
    $totalcoin = 0;
    $itemid = 0;
    $itemnum = 0;
    for($i = $skilllv+1; $i <= $skilllv+10; $i ++){
        $cfguplv = sql_fetch_one("select * from cfg_skilluplevel where level = $i");
        if(!$cfguplv){
            return array(
                0,
                STR_Param_Error
            );
        }
        if($i > intval($partnerinfo['plv'])){
            return array(
                0,
                STR_PLvOff
            );
        }
        $totalcoin += $cfguplv['coin'];
        $itemid = $cfguplv['itemid'];
        $itemnum += $cfguplv['count'];
        if($skillid < 20000){
            $skilllvs[0] = $skilllvs[0] + 1;
        }
        elseif($skillid >= 20000 && $skillid < 30000){
            $skilllvs[1] = $skilllvs[1] + 1;
        }

    }
    if (! _checkCoin($uid, $totalcoin)) {
        return array(
            0,
            STR_CoinOff . $totalcoin
        );
    }
    if(!_subItem($uid, $itemid, $itemnum)){
        return array(
            0,
            STR_ResourceOff
        );
    }
    _spendCoin($uid, $totalcoin,'升级佣兵技能');
    $skilllvstr = implode(",", $skilllvs);
    sql_update("UPDATE upartner SET skilllevel='$skilllvstr' WHERE partnerid=$partnerid and uid=$uid");
    _updateUTaskProcess($uid, 1004);
    return array(
        1,
        $skilllvstr,
        $totalcoin,
        array($itemid, $itemnum)
    );
}

?>