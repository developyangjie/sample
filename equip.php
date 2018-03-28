<?php

/**
 * 接口：获取装备
 * @param $uid
 * @param $params
 * @return
 */
function getEquip($uid, $params)
{
    $ue = sql_fetch_one("select * from uequip where uid=$uid");
    if ($ue) {
        return array(
            1,
            $ue
        );
    } else {
        $ret = sql_update("insert ignore into uequip (uid) values ('$uid')");
        if ($ret == 1) {
            $ue = sql_fetch_one("select * from uequip where uid=$uid");
            if ($ue) {
                return array(
                    1,
                    $ue
                );
            }
        }
    }
    return array(
        0,
        ""
    );
}


/**
 * 接口：更换装备
 * 
 * @param
 *            $partnerid
 * @param $params ['eid','etype']            
 * @return
 *
 */
function setEquip($uid, $params)
{ 
    /*!$params 最后一个是版本id json-gateway.php传过来
     * array(4) { [0]=> string(6) "1577-5" [1]=> string(6) "1575-7" [2]=> int(2040) }
    */
    $partnerid = intval($params[0]);
    $upartner = sql_fetch_one("select * from upartner where partnerid = $partnerid and uid = $uid");
    if(!$upartner){
        return array(
            0,
            STR_Wrong_Pid
        );
    }
    $plv = intval($upartner['plv']);
    $upjob = intval($upartner['mainp']);
    if (count($params) < 2){
        return array(
                0,
                STR_Equip_Err
        );
    }
    
    $userinfo = sql_fetch_one("select ulv,ujob from uinfo where uid=$uid");  
    if (!$userinfo){
        return array(
                0,
                STR_DataErr2
        );
    }

    $etypeIds = array();
    $newIds = array();
    for ($num = 1; $num < count($params) - 1; ++$num){
        $idType = explode("-", $params[$num]);
        if(count($idType) == 2){
            $eid = intval($idType[0]);
            $etype = intval($idType[1]);
            if ($eid != 0){  //!检查新装备是否满足条件
                $ret = sql_fetch_one("select * from ubag where eid=$eid and uid=$uid and etype='$etype' and (ejob=0 or ejob=$upjob)");
                if (!$ret) {
                    if ($etype != 1 && $etype != 2) {
                        return array(
                                0,
                                STR_Lv_JobLow
                        );
                    } else {
                        return array(
                                0,
                                STR_Lv_Low2
                        );
                    }
                }
                if($plv < _lvbystarlv(intval($ret['star']))){
                    return array(
                        0,
                        STR_Lv_Low2
                    );
                }
                $newIds[] = $eid;
            }
            $etypeIds[$etype] = $eid;
        }
    }

    //!老装备euser标记重置
    $oldeids = array();
    $cureps = sql_fetch_rows("select eid, etype from ubag where uid=$uid and euser=$partnerid");
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
        sql_update("update ubag set euser=0 where eid in ($oldeidsstr) and uid = $uid");
    }

    //!更新新装备武将euser标记
    if (count($newIds) > 0){
        $newidsstr = implode(",", $newIds);
        $ret = sql_update("update ubag set euser=$partnerid where eid in ($newidsstr) and uid=$uid");
    }
    
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $my = new my(1, $myinfo, $myequip);
    $zhanli = $my->zhanli;
    $myinfo['zhanli'] = $zhanli;
    sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
    //判断那个服
    $serverid=$myinfo['serverid'];
    $upvp_num="upvp_".$serverid;
    sql_update("update $upvp_num set zhanli=$zhanli where uid=$uid");
    $retp = getPartnerAttr($uid,$partnerid);
    return array(
        1,
        $myinfo,
        $my->format_to_array(),
        $retp
    );
}


function _createEquipByceid($uid, $ceid, $pcount, $advequip)
{
    $cfgequip = sql_fetch_one("select * from cfg_equip where eid=$ceid");
    return _doCreateEquipAndGet($uid, $cfgequip);
}

function _realCreateEquip($uid)
{
    $cfgequip = sql_fetch_one("select * from cfg_equip order by rand() limit 1");
    $etype = intval($cfgequip['etype']);
    return _doCreateEquipAndGet($uid, $cfgequip);
}

function _doCreateEquipAndGet($uid, $cfgequip)
{
    $eid = _doCreateEquip($uid, $cfgequip);
    $equip = sql_fetch_one("select * from ubag where eid='$eid'");
    return array(
        1,
        $equip
    );
}


function _doCreateEquip($uid, $cfgequip)
{
    $ename = $cfgequip['ename'];
    $ejob = intval($cfgequip['ejob']);
    $etype = intval($cfgequip['etype']);
    $ceid = intval($cfgequip['eid']);
    $star = intval($cfgequip['star']);
    $picindex = intval($cfgequip['picindex']);
    $sql = "insert into ubag (uid,ename,etype,ejob,star,ceid,picindex) values ('$uid','$ename','$etype','$ejob','$star','$ceid','$picindex')";
    $eid = sql_insert($sql);
    _upEquipAttribute($eid);
    //如果是第一次获得的装备就加入图鉴
    $res=sql_fetch_one("select * from ucollection where uid=$uid and type=2 and peid=$ceid");
    if(!isset($res))
    {
    	sql_update("insert into ucollection(uid,type,peid) values($uid,2,$ceid)");
    }
    return $eid;
}

//脱装备
function takeOffEquip($uid, $params)
{
    $eid = $params[0];
    $uequip = sql_fetch_one("select * from ubag where eid=$eid and uid=$uid");
    if(!$uequip){
        return array(
            0,
            STR_Equip_Err6
        );
    }
    sql_update("update ubag set euser = 0 where eid=$eid and uid=$uid");
    $res = sql_fetch_rows("select * from ubag where uid=$uid");
    return array(
        1
    );
}


/**
 * 接口：购买装备
 * 
 * @param
 *            $uid
 * @param $params ['eid']            
 * @return array
 */
function buyEquip($uid, $params)
{
    $buyeid = intval($params[0]);
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $res = _realCreateEquip($uid);
    if($res[0] == 1){
        $equip = sql_fetch_rows("select * from ubag where uid=$uid");
        return array(
            1,
            $equip,
            $uinfo
        );
    }
    return array(
        0,
        STR_DataErr
    );
}

function _getVipByEquipCrystal($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['equipcrystal']);
    return $percent;
}

// 装备熔炼
function equipSwallow($uid, $params)
{
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 2");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if ($ulv < intval($cfg['lv'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    if(count($params[0]) > 7){
        return array(
            0,
            STR_Equip_Too_More
        );
    }
    $coe = array(1=>0,2=>0,3=>20,4=>50,5=>150);
    $eids = implode(",",$params[0]);
    $equips = sql_fetch_rows("SELECT * FROM `ubag` WHERE `eid` in ($eids) AND `uid`='$uid'");
    $forgepoint = 0;
    $coin = 0;
    $percent = _getVipByEquipCrystal($uid);
    foreach ($equips as $value){
        $uplv = intval($value['uplv']);
        $cfgequips = sql_fetch_rows("select coin from cfg_equipqianghua where id <= $uplv order by id asc");
        foreach ($cfgequips as $c){
            $coin += $c['coin'];
        }
        $star = $value['star'];
        $forgepoint += $coe[$star];
        $eid = $value['eid'];
        sql_update("delete from ubag where eid='$eid' and uid='$uid'");
    }
    $rand = rand(1, 10000);
    $isbuff=0;
    if($rand <= $percent){
        $forgepoint = intval($forgepoint * 1.5);
        $isbuff=1;
    }
    $coinp = _getVipByEquipQianghua($uid);
    $coin = intval($coin * $coinp / 10000);
    _addCoin($uid, $coin,"装备熔炼");
    if($forgepoint > 0){
        sql_update("update uinfo set crystal=crystal+$forgepoint where uid='$uid'");
        //_addItem($uid, 9, $forgepoint);
    }
    return array(
        1,
        $forgepoint,
    	$isbuff,
        $coin
    );
}

/**
 * 接口：强化装备
 * 
 * @param
 *            $uid
 * @param $params ['eid']            
 * @return array
 */
function upEquip($uid, $params)
{
    $maineid = intval($params[0]);
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));

    $mainequip = sql_fetch_one("select *  from ubag WHERE eid ='$maineid' and uid ='$uid'");
    if (! $mainequip) {
        return array(
            0,
            STR_Equip_NotExist
        );
    }

    $uplv = intval($mainequip['uplv']);
    $star = intval($mainequip['star']);
    $newuplv = $uplv + 1;
    if($uplv >= 100){
        return array(
            0,
            STR_Equip_Not_Uplv
        );
    }
    
     if ($ulv <= $uplv) {
	     return array(
	     0,
	     STR_PLvOff
	     );
     }
     

	$equip_cfg=sql_fetch_rows("select coin from cfg_equipqianghua order by id asc");
         
   	$coin=$equip_cfg[$uplv]['coin'];
    if (! _checkCoin($uid, $coin)) {
        return array(
            0,
            STR_CoinOff . $coin
        );
    }
    if (_spendCoin($uid, $coin,'强化装备')) {
        $ret = sql_update("update ubag set uplv=uplv+1 where eid=$maineid and uid=$uid");
        _upEquipAttribute($maineid);
        if ($ret == 1) {
            $equip = sql_fetch_one("select * from ubag where eid=$maineid");
            _updateUTaskProcess($uid, 1005);
            return array(
                1,
                $equip
            );
        }
    }
    else{
        return array(
            0,
            STR_CoinOff
        );
    }
}

/**
 * 接口：强化装备
 *
 * @param
 *            $uid
 * @param $params ['eid']
 * @return array
 */
function upEquipMax($uid, $params)
{
	$maineid = intval($params[0]);
	$ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));

	$mainequip = sql_fetch_one("select *  from ubag WHERE eid ='$maineid' and uid ='$uid'");
	if (! $mainequip) {
		return array(
				0,
				STR_Equip_NotExist
		);
	}
	
	$uplv = intval($mainequip['uplv']);
	$star = intval($mainequip['star']);
	$equip_cfg=sql_fetch_rows("select coin from cfg_equipqianghua order by id asc");
	$coin=$equip_cfg[$uplv]['coin'];
	if (! _checkCoin($uid, $coin)) {
		return array(
				0,
				STR_CoinOff . $coin
		);
	}
	
	if($uplv >= 100){
		return array(
				0,
				STR_Equip_Not_Uplv
		);
	}
	
	if ($ulv <= $uplv) {
		return array(
				0,
				STR_PLvOff
		);
	}
	//计算能最大能升多少级
	$needcoin=0;
	$addlv=0;
	for($i=$uplv;$i<$ulv;$i++)
	{
		//等级达到最大
		if(intval($uplv)==$ulv)
		{
		$nextlv=$addlv;
			break;
		}
		$nowcoin=$equip_cfg[$addlv]['coin'];
		$needcoin=$needcoin+$nowcoin;
		$ret[]=$nowcoin;
		//升下一级的金币不够了
		if (!_checkCoin($uid,$needcoin))
		{
			$needcoin=$needcoin-$nowcoin;
			$nextlv=$addlv;
			break;
		}
		$addlv++;
	}
	if (_spendCoin($uid, $needcoin,'强化装备')) {
		 
		$ret = sql_update("update ubag set uplv=uplv+$addlv where eid=$maineid and uid=$uid");
		_upEquipAttribute($maineid);
		if ($ret == 1) {
			$equip = sql_fetch_one("select * from ubag where eid=$maineid");
			_updateUTaskProcess($uid, 1005);
			return array(
					1,
					$equip
			);
		}
	}
	else{
		return array(
				0,
				STR_CoinOff
		);
	}
}

//修改装备当前属性
function _upEquipAttribute($eid){
	$cfg=sql_fetch_one("select c.*,u.uplv from cfg_equip c inner join ubag  u on c.eid=u.ceid  where u.eid=$eid");
	switch (intval($cfg['etype'])) {
		//武器加攻击
		case 1:
			$attribute = ceil(intval($cfg['patk'])*(1+0.05*intval($cfg['uplv'])));
			break;
			//手套加魔防
		case 2:
			$attribute = ceil(intval($cfg['mdef'])*(1+0.05*intval($cfg['uplv'])));
			break;
			//胸甲加物理防御
		case 3:
			$attribute = ceil(intval($cfg['pdef'])*(1+0.05*intval($cfg['uplv'])));
			break;
			//鞋子加韧性
		case 4:
			$attribute = ceil(intval($cfg['cure'])*(1+0.05*intval($cfg['uplv'])));
	
			break;
			//项链加血量
		case 5:
			$attribute = ceil(intval($cfg['hp'])*(1+0.05*intval($cfg['uplv'])));
			break;
			//戒指加暴击
		case 6:
			$attribute = ceil(intval($cfg['cri'])*(1+0.05*intval($cfg['uplv'])));
			break;
	}
	sql_update("update ubag set attribute=$attribute where eid=$eid");
	return $attribute;
	
}

/**
 * 接口：一键强化装备
 *
 * @param
 *            $uid
 * @param $params ['eid']
 * @return array
 */
function upEquipAll($uid, $params)
{
	$pid = intval($params[0]);
	$ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));

	$mainequipAll = sql_fetch_rows("select *  from ubag WHERE euser =$pid and uid =$uid order by  etype asc");
	if (! $mainequipAll) {
		return array(
				0,
				STR_Equip_NotExist
		);
	}
	$equip_cfg=sql_fetch_rows("select coin from cfg_equipqianghua order by id asc");
	$isup=0;
	$coincount=0;
	$rets=array();
	foreach ($mainequipAll as $mainequip){
		$needcoin=0;
// 		$nextlv=0;
		$maineid = intval($mainequip['eid']);
		$uplv = intval($mainequip['uplv']);
		$star = intval($mainequip['star']);
		$newuplv = $uplv + 1;
		if($uplv >= 100){
			continue;
		}
		//计算一次消耗的
		$coin=$equip_cfg[$uplv]['coin'];
		if (! _checkCoin($uid, $coin)){
			continue;
		}
		else{
			$isup++;
		}
		if($uplv>=$ulv){
			continue;
		}
		//计算能最大能升多少级
		for($i=$uplv;$i<=$ulv;$i++){
			//等级达到最大
			if(intval($uplv)==$ulv){
				$nextlv=$uplv;
				break;
			}
			$nowcoin=$equip_cfg[$uplv]['coin'];
			$needcoin=$needcoin+$nowcoin;
			//升下一级的金币不够了
			if (!_checkCoin($uid,$needcoin)){
				$needcoin=$needcoin-$nowcoin;
				$nextlv=$uplv;
				break;
			}
			$uplv++;
		}
		//扣掉的总金币统计
		$coincount=$coincount+$needcoin;
		if (_spendCoin($uid, $needcoin,'强化装备')) {
			$ret = sql_update("update ubag set uplv=$nextlv where eid=$maineid and uid=$uid");
			_upEquipAttribute($maineid);
			if ($ret == 1) {
				$equip = sql_fetch_one("select * from ubag where eid=$maineid");
				array_push($rets, $equip);
			}
			_updateUTaskProcess($uid, 1005);
		}
		
	}
	//如果没消耗金币也就是代表没有成功强化一个装备	
	if($coincount==0){
		if($isup>0){
			return array(
					0,				
					STR_PLvOff
			);
		}
		else {
			return array(
					0,
					STR_CoinOff
			);
		}
	}
	else{
		return array(
				1,
				$rets,
				$coincount
		);
	}
}
//模拟计算一键强化影响
function upEquipAllCount($uid, $params)
{
	$pid = intval($params[0]);
	$ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));

	$mainequipAll = sql_fetch_rows("select *  from ubag WHERE euser =$pid and uid =$uid order by  etype asc");
	if (! $mainequipAll) {
		return array(
				0,
				STR_Equip_NotExist
		);
	}
	$equip_cfg=sql_fetch_rows("select coin from cfg_equipqianghua order by id asc");
	$isup=0;
	$coincount=0;
	$rets=array();
	$my_conin=intval(sql_fetch_one_cell("select ucoin from uinfo where uid=$uid"));
	foreach ($mainequipAll as $mainequip){
		$needcoin=0;
		// 		$nextlv=0;
		$maineid = intval($mainequip['eid']);
		$uplv = intval($mainequip['uplv']);
		$star = intval($mainequip['star']);
		$newuplv = $uplv + 1;
		if($uplv >= 100){
			continue;
		}
		//计算一次消耗的
		$coin=$equip_cfg[$uplv]['coin'];
		if (($my_conin-$coin)<0){
			continue;
		}
		else{
			$isup++;
		}
		if($uplv>=$ulv){
			continue;
		}
		//计算能最大能升多少级
		for($i=$uplv;$i<=$ulv;$i++){
			//等级达到最大
			if(intval($uplv)==$ulv){
				$nextlv=$uplv;
				break;
			}
			$nowcoin=$equip_cfg[$uplv]['coin'];
			$needcoin=$needcoin+$nowcoin;
			//升下一级的金币不够了
			if (($my_conin-$needcoin)<0){
				$needcoin=$needcoin-$nowcoin;
				$nextlv=$uplv;
				break;
			}
			$uplv++;
		}
		//扣掉的总金币统计
		$coincount=$coincount+$needcoin;
		if (($my_conin-$needcoin)>=0) {
// 			$ret = sql_update("update ubag set uplv=$nextlv where eid=$maineid and uid=$uid");
// 			_upEquipAttribute($maineid);
			$my_conin=$my_conin-$coincount;
			$equip = sql_fetch_one("select * from ubag where eid=$maineid");
			array_push($rets, array($equip['eid'],$nextlv,$needcoin));
			
		}
	}
	//如果没消耗金币也就是代表没有成功强化一个装备
	if($coincount==0)
	{
		if($isup>0){
			return array(
					0,
					STR_PLvOff
			);
		}
		else{
			return array(
					0,
					STR_CoinOff
			);
		}
	}
	else{
		return array(
				1,
				$rets,
				$coincount
		);
	}
}


//获取VIP装备重生百分比
function _getVipByEquipQianghua($uid)
{
    $percent = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $percent;
    }
    $percent = intval($cfg['equipqianghua']);
    return $percent;
}

//装备重生
function retEquip($uid, $params)
{
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if ($ulv < 15) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    if(count($params[0]) > 7){
        return array(
            0,
            STR_Equip_Too_More
        );
    }
    $viplv = intval(sql_fetch_one_cell("select vip from uinfo where uid=$uid"));
    $eids = implode(",",$params[0]);
    $equips = sql_fetch_rows("SELECT * FROM `ubag` WHERE eid in ($eids) AND uid = $uid");
    $cost = (count($params[0]) - 1) * 100;
    if (! _spendGbytype($uid, $cost, '装备重生')) {
        return array(
            0,
            STR_UgOff
        );
    }
    $itemcount = 0;
    $items = array();
    $percent = _getVipByEquipQianghua($uid);
    foreach ($equips as $value){
    	$itemid = 0;
        $uplv = intval($value['uplv']);
        $star = intval($value['star']);
        $etype = intval($equips['etype']);
		$ceid=intval($value['ceid']);
        $itemcount = intval((sql_fetch_one_cell("select sum(itemamout) from cfg_equipqianghua where star = $star and uplv<=$uplv"))/2);
        $itemid = intval(sql_fetch_one_cell("select qianghuaitem  from  cfg_equip  where eid=$ceid"));
//         if($uplv > 0){
//             $temp = ceil(10 * ($uplv + ($uplv * ($uplv + 1) / 2) * $strcoe));
// //             $itemcount += ceil($temp * $vipcoe[$viplv]);
//             $itemcount += ceil($temp);
//         }
        $eid = $value['eid'];
        sql_update("update ubag set uplv = 0 where eid='$eid' and uid='$uid'");
        _upEquipAttribute($eid);
        $itemcount = intval($itemcount * (1 + $percent / 10000));
        if($itemid > 0){
            _addItem($uid, $itemid, $itemcount,'装备重生');
            $items[] = array($itemid, $itemcount);
        }
    }
    $newequips = sql_fetch_rows("SELECT * FROM `ubag` WHERE eid in ($eids) AND uid = $uid");
    return array(
        1,
        $items,
        $cost,
        $newequips
    );
}

/**
 * 接口：购买背包
 * 
 * @param
 *            $uid
 * @param
 *            $params
 * @return array
 */
function buyBag($uid, $params)
{
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    if (intval($uinfo['bag']) >= 200) {
        return array(
            0,
            STR_BAG_NotEnough
        );
    }
    if (! _spendGbytype($uid, 100, "购买背包")) {
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("update uinfo set bag=bag+10 where uid=$uid");
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts FROM uinfo WHERE uid=$uid");
    return array(
        1,
        $uinfo['bag'],
    	100
    );
}

function _lvbystarlv($starlv)
{
    $level = 0;
    if($starlv == 4){
        $level = 20;
    }
    elseif($starlv == 5){
        $level = 40;
    }
    elseif($starlv == 6){
        $level = 60;
    }
    return $level;
}


/**
 * 接口：一键设置装备
 *
 * @param
 *            $uid
 * @param $params ['parterid','eid','etype']
 * @return array
 */
function oneKeySetEquip($uid, $params)
{
    $partnerid = intval($params[0]);
    $partnerinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid");
    if (!$partnerinfo){
        return array(
            0,
            STR_Partner_NotExist
        );
    }
    $plv = intval($partnerinfo['plv']);
    $job = intval($partnerinfo['mainp']);
    $newIds = array();
    $starlv=3;
    if($plv>=20){
    	 $starlv=4;
    }
    else if($plv>=40){
    	$starlv=5;
    }
    else if($plv>=60){
    	$starlv=6;
    }
    for($i = 1; $i <= 6; $i ++){
		$res = sql_fetch_one("select eid from ubag where uid=$uid and (euser=0 or euser=$partnerid) and (ejob=0 or ejob=$job) and etype=$i and star<=$starlv order by attribute desc limit 1");
        $newIds[] = intval($res['eid']); 
    }

    //!更新新装备武将euser标记
    if (count($newIds) > 0){
        $newidsstr = implode(",", $newIds);
        sql_update("update ubag set euser=0 where uid=$uid and euser=$partnerid");
        sql_update("update ubag set euser='$partnerid' where eid in ($newidsstr) and uid=$uid");
    }
    $res = sql_fetch_rows("select * from ubag where uid=$uid");
    $pres = sql_fetch_rows("select * from ubag where uid=$uid and euser='$partnerid'");
    return array(
        1,
        $res,
        $pres
    );
}


?>