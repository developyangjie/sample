<?php
define('MAXTID1', 1013);
define('MAXTID2', 2013);
define('MAXTID3', 3013);
define('MAXTLV', 3);

/**
 * 接口：天空塔挑战
 *
 * @param
 *            $uid
 * @param $params ['tid']            
 * @return array
 */
function pvt($uid, $params)
{
    $check = _checkPvt();
    if ($check[0] == 0) {
        return $check;
    }
    $tid = intval($params[0]);
    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $vip = intval($myinfo['vip']);
    $mylv = intval($myinfo['ulv']);
    $monsterinfo = sql_fetch_one("select * from cfg_tower where tid='$tid' limit 1");
    if (! $monsterinfo) {
        return array(
            0,
            STR_Tower_NoMaster
        );
    }
    $tlv = intval($monsterinfo['tlv']);
    $_check = _checkTowerOpen($tlv, $mylv);
    if ($_check[0] == 0) {
        return $_check;
    }
    $pvtinfo = sql_fetch_one("select *,UNIX_TIMESTAMP()-refreshtime as refreshleft,UNIX_TIMESTAMP()-pvttime as pvtleft from upvt where uid=$uid");
    if (intval($pvtinfo['pvtleft']) < 0) {
        return array(
            0,
            STR_Tower_Rest
        );
    }
    // 每日刷新
    if (intval($pvtinfo['refreshleft']) > 0) {
        $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
        $addday = - 1;
        if ($h < 6) {
            $addday = 0;
        }
        sql_update("update upvt set tids='0',refreshtime=UNIX_TIMESTAMP(SUBDATE(CURDATE(),$addday))+21600 where refreshtime<UNIX_TIMESTAMP() and uid=$uid");
        $pvtinfo = sql_fetch_one("select *,UNIX_TIMESTAMP()-refreshtime as refreshleft,UNIX_TIMESTAMP()-pvttime as pvtleft from upvt where uid=$uid");
    }
    $tidstr = $pvtinfo['tids'];
    $tidarr = preg_split("/[\s,]+/", $tidstr);
    if (in_array($tid, $tidarr)) {
        return array(
            0,
            STR_Tower_PkedBoss
        );
    }
    if (intval($pvtinfo["maxtid" . $tlv]) < $tid) {
        return array(
            0,
            STR_Tower_NoOpenFloor
        );
    }
    // 最少打30轮
    $turn = intval($monsterinfo['turn']);
    if ($turn <= 30) {
        $turn = 30;
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
    $monster = new my(6, $monsterinfo, $monsterinfo);
    $hprate = intval($monsterinfo['hprate']);
    if ($hprate > 0) {
        for ($i = 0; $i < count($myList); $i ++) {
            $myList[$i]->minatk = $myList[$i]->minatk * ($hprate / 10000);
            $myList[$i]->maxatk = $myList[$i]->maxatk * ($hprate / 10000);
        }
    }
    $monsterList = array(
        $monster
    );
    $battle = _battle($myList, $monsterList, $turn);
    $ret = $battle[0];
    $pvelog = $battle[1];
    $addcoin = 0;
    $addexp = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    $additem = array();
    
    if ($ret == 2) {
        // 狂暴10回合;
        $monsterList[0]->setBossKuangbao();
        $battle2 = _battle($myList, $monsterList, 10);
        $ret = $battle2[0];
        $pvelog2 = $battle2[1];
        $pvelog = array_merge($pvelog, $pvelog2);
    }
    if ($ret == 1) {
        // 增加已挑战列表
        $tidstr = $tidstr . "," . $tid;
        sql_update("update upvt set tids='$tidstr' where uid=$uid");
        $addexp += intval($monsterinfo['exp']);
        $addcoin += intval($monsterinfo['coin']);
        $pcount = intval($monsterinfo['pcount']);
        $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
        $maxlv = min(MAXULV, intval($monsterinfo['lv']));
        $equiplv = rand($minlv, $maxlv);
        // 生成奖励
        if ($pcount > 0) {
            $tempequip = _realCreateEquip($uid, $pcount, $equiplv);
            if ($tempequip != 0) {
                $addequip[] = $tempequip;
            }
        }
        if (($my->addcoin + $paddcoin) > 0) {
            $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin) / 10000));
        }
        if (($my->addexp + $paddexp) > 0) {
            $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp) / 10000));
        }
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvt');
        // 是否为新的一关
        switch ($tlv) {
            case 2:
                $maxtid = MAXTID2;
                break;
            case 3:
                $maxtid = MAXTID3;
                break;
            default:
                $maxtid = MAXTID1;
                break;
        }
        $userFirst = false;
        if (intval($pvtinfo["maxtid" . $tlv]) == $tid && $tid <= $maxtid) {
            if (sql_update("update upvt set maxtid$tlv=$tid+1 where uid=$uid and maxtid$tlv=$tid")) {
                // 首通奖励
                $userFirst = true;
                if (intval($monsterinfo["item1"]) > 0 && intval($monsterinfo["count1"]) > 0) {
                    $additem[] = array(
                        "itemid" => $monsterinfo["item1"],
                        "count" => $monsterinfo["firstcount1"]
                    );
                }
                if (intval($monsterinfo["item2"]) > 0 && intval($monsterinfo["count2"]) > 0) {
                    $additem[] = array(
                        "itemid" => $monsterinfo["item2"],
                        "count" => $monsterinfo["firstcount2"]
                    );
                }
                if (intval($monsterinfo["item3"]) > 0 && intval($monsterinfo["count3"]) > 0) {
                    $additem[] = array(
                        "itemid" => $monsterinfo["item3"],
                        "count" => $monsterinfo["firstcount3"]
                    );
                }
                // 处理下一难度信息
                if ($tid == $maxtid) {
                    if ($tlv < MAXTLV) {
                        $tlv2 = $tlv + 1;
                        sql_update("update upvt set maxtid$tlv2=maxtid$tlv2+1 where uid=$uid");
                    }
                }
                // 是否是第一个通关
                _checkFirstPvt($uid, $tid);
            }
        }
        if (! $userFirst) {
            // 非首通奖励
            if (intval($monsterinfo["item1"]) > 0 && intval($monsterinfo["count1"]) > 0 && rand(0, 10000) < intval($monsterinfo["itemrate1"])) {
                $additem[] = array(
                    "itemid" => $monsterinfo["item1"],
                    "count" => $monsterinfo["count1"]
                );
            }
            if (intval($monsterinfo["item2"]) > 0 && intval($monsterinfo["count2"]) > 0 && rand(0, 10000) < intval($monsterinfo["itemrate2"])) {
                $additem[] = array(
                    "itemid" => $monsterinfo["item2"],
                    "count" => $monsterinfo["count2"]
                );
            }
            if (intval($monsterinfo["item3"]) > 0 && intval($monsterinfo["count3"]) > 0 && rand(0, 10000) < intval($monsterinfo["itemrate3"])) {
                $additem[] = array(
                    "itemid" => $monsterinfo["item3"],
                    "count" => $monsterinfo["count3"]
                );
            }
        }
        // 物品奖励
        foreach ($additem as $item) {
            _addGiftItem($uid, $item['itemid'], $item['count']);
        }
    }     // TODO 战斗失败时间限制,暂时先不加,之后
else {
        sql_update("update upvt set pvttime=UNIX_TIMESTAMP()+300 where uid=$uid");
    }
    // TODO生成奖励
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
    $r[8] = $additem;
    $msg = "";
    $res = array();
    $res['log'] = $r;
    $res['type'] = 'pvt';
    $res['msg'] = $msg;
    $res['stat'] = '';
    return array(
        1,
        $res
    );
}

function _checkFirstPvt($uid, $tid)
{
    $tid = intval($tid);
    // 没人通过
    if (! sql_fetch_one("select * from syspvt where tid=$tid")) {
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        $uname = $uinfo['uname'];
        $tinfo = sql_fetch_one("select * from cfg_tower where tid=$tid");
        // 记录
        if ($tinfo && sql_update("insert into syspvt (tid,uid,uname,ts) values ($tid,$uid,'$uname',UNIX_TIMESTAMP())")) {
            if (intval($tinfo['tlv']) > 0) {
                // 上电视
                if (intval($tinfo['tlv']) == 1) {
                    $tdif = STR_Tower_Normal;
                } else if (intval($tinfo['tlv']) == 2) {
                    $tdif = STR_Tower_Hard;
                } else if (intval($tinfo['tlv']) == 3) {
                    $tdif = STR_Tower_Nightmare;
                }
                $tname = $tinfo['name'];
                _addSysMsg(sprintf(STR_Tower_SysMsg1,$uname,$tdif,$tname));
                if ($tid == MAXTID2) {
                    // 首通+称号
                    _addChenghao($uid, 11);
                    _addSysMsg(sprintf(STR_Tower_SysMsg2,$uname,$tdif));
                }
                if ($tid == MAXTID3) {
                    // 首通+称号
                    _addChenghao($uid, 12);
                    _addSysMsg(sprintf(STR_Tower_SysMsg3,$uname,$tdif));
                }
            }
        }
    }
}

/**
 * 接口：购买天空塔挑战次数
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function buyPvt($uid, $params)
{
    $check = _checkPvt();
    if ($check[0] == 0) {
        return $check;
    }
    $g = 20;
    if (! _spendGbytype($uid, $g, "buypvt")) {
        return array(
            0,
            STR_UgOff . $g
        );
    }
    sql_update("update upvt set pvttime=0 where uid=$uid");
    return array(
        1
    );
}

/**
 * 接口：天空塔快速挑战
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function pvtQuick($uid, $params)
{
    $check = _checkPvt();
    if ($check[0] == 0) {
        return $check;
    }
    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $pvtinfo = sql_fetch_one("select *,UNIX_TIMESTAMP()-refreshtime as refreshleft,UNIX_TIMESTAMP()-pvttime as pvtleft from upvt where uid=$uid");
    $vip = intval($myinfo['vip']);
    $mylv = intval($myinfo['ulv']);
    
    if (intval($pvtinfo['pvtleft']) < 0) {
        return array(
            0,
            STR_Tower_Rest
        );
    }
    
    // 每日刷新
    if (intval($pvtinfo['refreshleft']) > 0) {
        $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
        $addday = - 1;
        if ($h < 6) {
            $addday = 0;
        }
        sql_update("update upvt set tids='0',refreshtime=UNIX_TIMESTAMP(SUBDATE(CURDATE(),$addday))+21600 where refreshtime<UNIX_TIMESTAMP() and uid=$uid");
        $pvtinfo = sql_fetch_one("select *,UNIX_TIMESTAMP()-refreshtime as refreshleft,UNIX_TIMESTAMP()-pvttime as pvtleft from upvt where uid=$uid");
    }
    
    //!可以打的全部关卡
    $maxtid1 = intval($pvtinfo['maxtid1']);
    $maxtid2 = intval($pvtinfo['maxtid2']);
    $maxtid3 = intval($pvtinfo['maxtid3']);
    $monsterinfos = sql_fetch_rows("select * from cfg_tower where (tid > 1000 and tid < $maxtid1) or (tid > 2000 and tid < $maxtid2) or (tid > 3000 and tid < $maxtid3)");

    //!已经打过的确关卡
    $tidstr = $pvtinfo['tids'];
    $tidarr = preg_split("/[\s,]+/", $tidstr);

    //!计算自身属性金币和经验加成
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $my = new my(1, $myinfo, $myequip);
    $myaddcoin = $my->addcoin;
    $myaddexp = $my->addexp;
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

    //!奖励变量
    $addexp = 0;
    $addcoin = 0;
    $addequip = array();
    $additem = array();
    $retitem = array();
    if ($monsterinfos && count($monsterinfos) > 0){
        if ((count($monsterinfos) - (count($tidarr) - 1)) <= 0){//!可以打的关卡数减去已经打的关卡数
            return array(
                    0,
                    STR_DataErr
            );
        }
        //!vip 1以下200钻石
        if ($vip < 1) {
            $needug = 20 * (count($monsterinfos) - (count($tidarr) - 1/*默认的0*/)); //!可以打的关卡数减去已经打的关卡数
            if (! _spendGbytype($uid, $needug, "pvtQuick")) {
                return array(
                        0,
                        STR_UgOff . $needug
                );
            }
        }
        
        for ($index = 0; $index < count($monsterinfos); ++$index){
            $tid = intval($monsterinfos[$index]['tid']);
            $tlv = intval($monsterinfos[$index]['tlv']);           
            
            //!此关卡开启而且没有打过
            $_check = _checkTowerOpen($tlv, $mylv);
            if ($_check[0] == 1 && !in_array($tid, $tidarr)) {
                // 增加已挑战列表
                $tidstr = $tidstr . "," . $tid;
                
                //!计算奖励--金币和经验
                $tmpaddexp = intval($monsterinfos[$index]['exp']);
                $tmpaddcoin = intval($monsterinfos[$index]['coin']);
                $pcount = intval($monsterinfos[$index]['pcount']);
                $minlv = max(1, min($mylv, intval($monsterinfos[$index]['lv']) - 5));
                $maxlv = min(MAXULV, intval($monsterinfos[$index]['lv']));
                $equiplv = rand($minlv, $maxlv);
                // 生成奖励
                if ($pcount > 0) {
                    $tempequip = _realCreateEquip($uid, $pcount, $equiplv);
                    if ($tempequip != 0) {
                        $addequip[] = $tempequip;
                    }
                }
                if (($myaddcoin + $paddcoin) > 0) {
                    $addcoin += ceil($tmpaddcoin * (1 + ($myaddcoin + $paddcoin) / 10000));
                }else{
                    $addcoin += $tmpaddcoin;
                }
                if (($myaddexp + $paddexp) > 0) {
                    $addexp += ceil($tmpaddexp * (1 + ($myaddexp + $paddexp) / 10000));
                }else{
                    $addexp += $tmpaddexp;
                }
                
                //!计算奖励--道具
                if (intval($monsterinfos[$index]["item1"]) > 0 && intval($monsterinfos[$index]["count1"]) > 0 && rand(0, 10000) < intval($monsterinfos[$index]["itemrate1"])) {
                    $additem[$monsterinfos[$index]["item1"]] += $monsterinfos[$index]["count1"];
                }
                if (intval($monsterinfos[$index]["item2"]) > 0 && intval($monsterinfos[$index]["count2"]) > 0 && rand(0, 10000) < intval($monsterinfos[$index]["itemrate2"])) {
                    $additem[$monsterinfos[$index]["item2"]] += $monsterinfos[$index]["count2"];
                }
                if (intval($monsterinfos[$index]["item3"]) > 0 && intval($monsterinfos[$index]["count3"]) > 0 && rand(0, 10000) < intval($monsterinfos[$index]["itemrate3"])) {
                    $additem[$monsterinfos[$index]["item3"]] += $monsterinfos[$index]["count3"];
                }
            }
        }
        
        //!金币和经验奖励
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvtQuick');
        
        //!物品奖励
        foreach ($additem as $key => $item) {
            $retitem[] =_addGiftItem($uid, $key, $item);
        }
        
        //!更新挑战记录
        sql_update("update upvt set tids='$tidstr' where uid=$uid");
    }
    $pvtinfo = sql_fetch_one("select *,refreshtime-UNIX_TIMESTAMP() as refreshleft,pvttime-UNIX_TIMESTAMP() as pvtleft from upvt where uid=$uid");
    return array(
        1,
        $addexp,
        $addcoin,
        $addequip,
        $retitem,
        $pvtinfo
    );
}

/**
 * 接口：获取我的爬塔信息
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getMyPvtInfo($uid, $params)
{
    $check = _checkPvt();
    if ($check[0] == 0) {
        return $check;
    }
    $pvtinfo = sql_fetch_one("select *,refreshtime-UNIX_TIMESTAMP() as refreshleft,pvttime-UNIX_TIMESTAMP() as pvtleft from upvt where uid=$uid");
    if (! $pvtinfo) {
        $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
        $addday = - 1;
        if ($h < 6) {
            $addday = 0;
        }
        sql_update("insert into upvt (uid,tids, refreshtime,maxtid1,maxtid2,maxtid3) values ($uid,'0',UNIX_TIMESTAMP(SUBDATE(CURDATE(),$addday))+21600,1001,2000,3000)");
        $pvtinfo = sql_fetch_one("select *,refreshtime-UNIX_TIMESTAMP() as refreshleft,pvttime-UNIX_TIMESTAMP() as pvtleft from upvt where uid=$uid");
    }
    if (intval($pvtinfo['refreshleft']) < 0) {
        $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
        $addday = - 1;
        if ($h < 6) {
            $addday = 0;
        }
        sql_update("update upvt set tids='0',refreshtime=UNIX_TIMESTAMP(SUBDATE(CURDATE(),$addday))+21600 where refreshtime<UNIX_TIMESTAMP() and uid=$uid");
        $pvtinfo = sql_fetch_one("select *,refreshtime-UNIX_TIMESTAMP() as refreshleft,pvttime-UNIX_TIMESTAMP() as pvtleft from upvt where uid=$uid");
    }
    $rank = sql_fetch_rows("select * from syspvt");
    return array(
        1,
        $pvtinfo,
        $rank
    );
}

/**
 * 接口：获取天空塔排行
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getPvtRank($uid, $params)
{
    $check = _checkPvt();
    if ($check[0] == 0) {
        return $check;
    }
    // 未开放难度的信息也传给客户端
    $rank = sql_fetch_rows("select * from syspvt");
    return array(
        1,
        $rank
    );
}

function _checkTowerOpen($tlv, $ulv)
{
    $tlv = intval($tlv);
    if ($tlv < 0 || $tlv > 3){
        return array(0,STR_DataErr);
    }elseif ($tlv == 1 && $ulv < 40){
        return array(0,STR_Tower_Open40Lv);
    }elseif ($tlv == 2 && $ulv < 50){
        return array(0,STR_Tower_Open50Lv);
    }elseif ($tlv == 3 && $ulv < 60){
        return array(0,STR_Tower_Open60Lv);
    }
    return array(1,STR_Welcome);
}

function _checkPvt() {
    if (!PVT_OPENTS) {
        return array(0,STR_WillOpen);
    }
    if (time() < PVT_OPENTS) {
        return array(0,date('m月d日 H:i',PVT_OPENTS).' '.STR_Open);
    }
    return array(1,STR_Welcome);
}

?>