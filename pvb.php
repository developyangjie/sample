<?php

/**
 * 接口：挑战BOSS
 * @param $uid
 * @param $params ['mapid']
 * @return array
 */
function pvb($uid, $params)
{
    $mapid = intval($params[0]);
    $monsterinfo = sql_fetch_one("select * from cfg_boss where mapid='$mapid' limit 1");
    $myinfo = sql_fetch_one("select *,LEAST(UNIX_TIMESTAMP()-pvetime,86400) as offlinetime from uinfo where uid=$uid");
    $mylv = intval($myinfo['ulv']);
    $vip = intval($myinfo['vip']);
    $mymapid = intval($myinfo['umid']);
    $turn = intval($monsterinfo['turn']);
    // 最多打30轮
    if ($turn <= 30) {
        $turn = 30;
    }
    // Boss挑战次数不足
    if (intval($myinfo['pvb']) < 1) {
        return array(
            0,
            STR_PVP_Boss
        );
    }
    // 战斗休息
    if (intval($myinfo['offlinetime']) <= 0) {
        return array(
            1,
            STR_Rest
        );
    }
    // 地图未开放
    if ($mymapid < $mapid || $mapid > MAXMAPNUM) {
        return array(
            0,
            STR_MapUnopen
        );
    }
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $my = new my(1, $myinfo, $myequip);
    $partnerid = $myequip['stagepartner'];
    $partnerinfo = null;
    if (!empty($partnerid)){
        $partnerinfo = sql_fetch_rows("select * from upartner where partnerid in ($partnerid)");
    }
    $paddcoin = 0;
    $paddexp = 0;
    $paddequip = 0;

    if ($partnerinfo) {
        $myList = array($my);
        $combineadd = getcombineaddinfo($partnerinfo);
        foreach($partnerinfo as $value){
            $partner = new my(4, $value, $mylv,$combineadd);
            $paddcoin += $partner->addcoin;
            $paddexp += $partner->addexp;
            $paddequip += $partner->addequip;
            $myList[] = $partner;
        }
    } else {
        $myList = array(
            $my
        );
    }
    $monster = new my(3, $monsterinfo, "");
    $monsterList = array(
        $monster
    );
    $battle = _battle($myList, $monsterList, $turn);
    $ret = $battle[0];
    $pvelog = $battle[1];
    $battletime = $battle[2];
    $addcoin = 0;
    $addexp = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    $newmapid = $mymapid;
    $msg = array();
    if ($ret == 2) {
        // 狂暴10回合;
        $monsterList[0]->setBossKuangbao();
        $battle2 = _battle($myList, $monsterList, 10);
        $ret = $battle2[0];
        $pvelog2 = $battle2[1];
        $battletime += $battle2[2];
        $pvelog = array_merge($pvelog, $pvelog2);
    }
    if ($ret == 1) {
        $addexp += intval($monsterinfo['exp']);
        $addcoin += intval($monsterinfo['coin']);
        $adds1 += intval($monsterinfo['s1count']);
        $pcount = intval($monsterinfo['pcount']);
        $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
        $maxlv = min(MAXULV, intval($monsterinfo['lv']));
        $equiplv = rand($minlv, $maxlv);
        $tempequip = _realCreateEquip($uid, $pcount, $equiplv);
        if ($tempequip != 0) {
            $addequip[] = $tempequip;
        }
        if (($my->addcoin + $paddcoin) > 0) {
            $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin) / 10000));
        }
        if (($my->addexp + $paddexp) > 0) {
            $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp) / 10000));
        }
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvb');
        _addJinghua($uid, $adds1,'pvb');
        if ($mapid == $mymapid && $mapid <= MAXMAPNUM) {
            if ($mapid == MAXMAPNUM){
                sql_update("update uinfo set umid=$mapid,pvb=pvb-1,nowmid=$mapid where uid=$uid and pvb>0");
            }else{
                $newmapid = $mymapid + 1;
                sql_update("update uinfo set umid=umid+1,pvb=pvb-1,nowmid=$newmapid where uid=$uid and umid=$mapid and pvb>0");
            }
            _setGift($uid, 2, $mymapid);
            //$mapinfo = sql_fetch_one("select mname from cfg_map where mid=$mymapid");
            //_addSysMsg(sprintf(STR_USER_SysMsg3,$myinfo['uname'],$mapinfo['mname'],$monsterinfo['name']));
        } else {
            sql_update("update uinfo set pvb=pvb-1 where uid=$uid and pvb>0");
        }
        
        //! 圣诞活动
        if (checkActivityOpenById(activityTypeDef::christmasActivity)){
            $rand = rand(1, 100);
            $rate = $maxlv / $mylv * 15;
            if ($rand < $rate){
                addShengDanItem($uid, 2);
                $msg[] = STR_ShengDan . 2;
            }
        }
    } else {
        sql_update("update uinfo set  pvetime=pvetime+30 where uid=$uid");
    }
    setSixOneLine($uid,3);
    $r = array();
    $r[0] = $ret; // 战斗胜负,1胜利,0失败,2怪逃跑
    $r[1][0] = _simpinfo($myList); // 我的信息
    $r[1][1] = _simpinfo($monsterList); // 怪物信息
    $r[2] = $pvelog; // 战斗过程
    $r[3] = $addexp;
    $r[4] = $addcoin;
    $r[5] = $addequip;
    $r[6] = $adds1;
    $r[7] = $addug;
    $res = array();
    $res['log'] = $r;
    $res['type'] = 'pvb';
    $res['nowmid'] = $newmapid;
    $res['stat'] = '';
    $res['battletime'] = $battletime;
    $res['msg'] = $msg;
    
    $box = 0;
    if ($vip > 9) {
        $box = 33;
        // 金宝箱;
    } elseif ($vip > 6) {
        // 银宝箱;
        $box = 32;
    } elseif ($vip > 3) {
        // 铜宝箱;
        $box = 31;
    }
    $boxsysopen = 1;
    if ($boxsysopen != 1 || $mapid < 5 || $ret == 0) {
        $box = 0;
    }
    if ($box > 0) {
        $res['box'] = $box;
        if (_subItem($uid, $box - 10, 1,'openBoxPvb')) {
            $boxinfo = _openbox($uid, $box, $mylv);
            $res['boxitem'] = $boxinfo['boxitem'];
            $res['boxcount'] = $boxinfo['boxcount'];
        } else {
            $res['boxitem'] = 0;
            $res['boxcount'] = 0;
        }
    }
    return array(
        1,
        $res
    );
}

/**
 * 接口：购买挑战BOSS次数
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function buyPvb($uid, $params)
{
    $uinfo = sql_fetch_one("select u.*,c.buypvb from uinfo u inner join cfg_vip c on u.vip=c.vip where uid=$uid");
    $pvbbuy = intval($uinfo['pvbbuy']);
    $buypvb = intval($uinfo['buypvb']);
    // 购买次数不足
    if ($pvbbuy >= $buypvb) {
        return array(
            0,
            STR_PVP_BuyOff
        );
    }
    $arr = array(
        50,
        100,
        100,
        200,
        200,
        200,
        400,
        400,
        400,
        400,
        800,
        800,
        800,
        800,
        800
    );
    $g = $arr[$pvbbuy];
    /* 
    if (_checkAct18Open()) {
        $g = $g / 2;
    }
     */
    // 钻石不足
    if (! _spendGbytype($uid, $g, "buypvb")) {
        return array(
            0,
            STR_UgOff . $g
        );
    }
    $ret = sql_update("update uinfo set pvb=pvb+1,pvbbuy=pvbbuy+1 where uid=$uid");
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts
           						   FROM uinfo 
           						   WHERE uid=$uid");
    return array(
        1,
        $uinfo
    );
}

/**
 * 接口：快速战斗
 *
 * @param
 *            $uid
 * @param $params ['mapid']            
 * @return array
 */
function pvbQuick($uid, $params)
{
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $mapid = intval($params[0]);
    if (! $uinfo) {
        return array(
            0,
            STR_DataErr
        );
    }
    $maxmap = intval($uinfo['umid']);
    $vip = intval($uinfo['vip']);
    // VIP才有BOSS扫荡功能
    if ($vip < 1) {
        return array(
            0,
            STR_PVP_BossErr1
        );
    }
    // 挑战次数不足
    if (intval($uinfo['pvb']) < 1) {
        return array(
            0,
            STR_PVP_Bossing
        );
    }
    // 未击败过地图BOSS
    if ($mapid >= $maxmap) {
        return array(
            0,
            STR_PVP_BossErr2
        );
    }
    // 到达最大地图
    if ($mapid > MAXMAPNUM) {
        return array(
            0,
            STR_MapUnopen
        );
    }
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $my = new my(1, $uinfo, $myequip);
    $monsterinfo = sql_fetch_one("select * from cfg_boss where mapid='$mapid' limit 1");
    $mylv = intval($uinfo['ulv']);
    
    $addcoin = 0;
    $addexp = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    
    $paddcoin = 0;
    $paddexp = 0;
    $msg = array();
    
    $addexp += intval($monsterinfo['exp']);
    $addcoin += intval($monsterinfo['coin']);
    $adds1 += intval($monsterinfo['s1count']);
    $pcount = intval($monsterinfo['pcount']);
    $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
    $maxlv = min(MAXULV, intval($monsterinfo['lv']));
    $equiplv = rand($minlv, $maxlv);
    $tempequip = _realCreateEquip($uid, $pcount, $equiplv);
    if ($tempequip != 0) {
        $addequip[] = $tempequip;
    }
    if (($my->addcoin + $paddcoin) > 0) {
        $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin) / 10000));
    }
    if (($my->addexp + $paddexp) > 0) {
        $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp) / 10000));
    }
    
    if (sql_update("update uinfo set pvb=pvb-1 where uid=$uid and pvb>0")) {
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvbQuick');
        _addJinghua($uid, $adds1,'pvbQuick');
        //! 圣诞活动
        if (checkActivityOpenById(activityTypeDef::christmasActivity)){
            $rand = rand(1, 100);
            $rate = $maxlv / $mylv * 6;
            if ($rand < $rate){
                addShengDanItem($uid, 2);
                $msg[] = STR_ShengDan . 2;
            }
        }
    }
    $r = array();
    $r[0] = 1; // 战斗胜负,1胜利,0失败,2怪逃跑
    $r[1][0] = array(); // 我的信息
    $r[1][1] = array(); // 怪物信息
    $r[2] = array(); // 战斗过程
    $r[3] = $addexp;
    $r[4] = $addcoin;
    $r[5] = $addequip;
    $r[6] = $adds1;
    $r[7] = $addug;
    $res = array();
    $res['log'] = $r;
    $res['type'] = 'pvbquick';
//     $res['nowmid'] = $mapid;
    $res['stat'] = '';
    $res['msg'] = $msg;
    
    $box = 0;
    if ($vip > 9) {
        // 金宝箱;
        $box = 33;
    } elseif ($vip > 6) {
        // 银宝箱;
        $box = 32;
    } elseif ($vip > 3) {
        // 铜宝箱;
        $box = 31;
    }
    $boxsysopen = 1;
    if ($boxsysopen != 1 || $mapid < 5) {
        $box = 0;
    }
    if ($box > 0) {
        $res['box'] = $box;
        if (_subItem($uid, $box - 10, 1,'openBoxPvbQuick')) {
            $boxinfo = _openbox($uid, $box, $mylv);
            $res['boxitem'] = $boxinfo['boxitem'];
            $res['boxcount'] = $boxinfo['boxcount'];
        } else {
            $res['boxitem'] = 0;
            $res['boxcount'] = 0;
        }
    }
    setSixOneLine($uid,3);
    return array(
        1,
        $res
    );
}
?>