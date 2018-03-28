<?php

/**
 * 接口：普通副本
 *
 * @param
 *            $uid
 * @param
 *            $params
 * @return array
 */
function beginPveFight($uid, $params)
{
    $ret = 2;
    $mapid = intval($params[0]);
    $myinfo = sql_fetch_one("select *,LEAST(UNIX_TIMESTAMP()-pvetime,86400) as offlinetime from uinfo where uid=$uid");
    if (! $myinfo) {
        return array(
                0,
                STR_PlayerErr
        );
    }
    if (intval($myinfo['umid']) < $mapid || $mapid > MAXMAPNUM) {
        return array(
                0,
                STR_MapUnopen
        );
    }
    // 超出2分钟,即认为是离线战斗
    if (intval($myinfo['pvetime']) > 0 && intval($myinfo['offlinetime']) > 120) {
        return _rework2($uid, $myinfo);
    }
    if ($mapid < 1) {
        $mapid = 1;
    }
    // 战斗休息中
    if (intval($myinfo['offlinetime']) <= 0) {
        return array(
                1,
                STR_Rest
        );
    }
    $mapinfo = sql_fetch_one("select * from cfg_map where mid=$mapid");
    if (! $mapinfo) {
        return array(
                0,
                STR_PVE_MapErr
        );
    }
    $mylv = intval($myinfo['ulv']);
    $sellstar = intval($myinfo['sellstar']);
    $bag = max(intval($myinfo['bag']), 50);
    // 剩余背包数量
    $bagfree = $bag - intval(sql_fetch_one_cell("select count(*) from ubag where uid=$uid and euser=0"));
    // 如果背包满，全部出售
    if ($bagfree <= 0) {
        $sellstar = 5;
    }
    // 随机怪物数量
    $num = rand(intval($mapinfo['minnum']), intval($mapinfo['maxnum']));
    // 随机怪物信息
    $monsterinfo = sql_fetch_rows("select * from cfg_monster where mapid='$mapid' and lvneed<='$mylv' and lvmax>='$mylv' order by rand() limit $num");
    // 获取装备信息
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $oldzhanli = $myinfo['zhanli'];
    $my = new my(1, $myinfo, $myequip);
    $zhanli = $my->zhanli;
    $partnerid = $myequip['stagepartner'];
    $partnerinfo = null;
    if(!empty($partnerid)){
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
    for ($i = 0; $i < count($monsterinfo); $i ++) {
        $monster = new my(2, $monsterinfo[$i], "");
        $monsterList[] = get_object_vars($monster);
    }
    $addcoin = 0;
    $addexp = 0;
    $addcoin2 = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    $nowmid = intval($myinfo['nowmid']);
    $winrate = intval($myinfo['winrate']);
    $winrand = intval($myinfo['winrand']);
    $wincoin = intval($myinfo['wincoin']);
    $winexp = intval($myinfo['winexp']);
    $winequip = intval($myinfo['winequip']);
    $ujob = intval($myinfo['ujob']);
    $pcount = 0;
    //!增加的eid,存utempbag中的eid
    $eid1 = 0;
    $eid2 = 0;
    $eid3 = 0;
    $eid4 = 0;
    $eid5 = 0;
    $pcount = 0;
    $mlv = 0;
    //!计算奖励   
    for ($i = 0; $i < count($monsterinfo); $i ++) {
        //!经验和金币
        if(intval($monsterinfo[$i]['pcount']) > $pcount){
            $pcount = intval($monsterinfo[$i]['pcount']);
        }
        if(intval($monsterinfo[$i]['lv']) > $mlv){
            $mlv = intval($monsterinfo[$i]['lv']);
        }
        $addexp += intval($monsterinfo[$i]['exp']);
        $addcoin = intval($monsterinfo[$i]['coin']);
        //!装备
        if (intval($myinfo['step']) < 5) {
            if (intval($myinfo['step']) == 2) { // 武器
                $tempequip = _newuserCreateEquip($uid, 2, $ujob, 1);
            } elseif (intval($myinfo['step']) == 3) { // 副手
                $tempequip = _newuserCreateEquip($uid, 2, $ujob, 2);
            } elseif (intval($myinfo['step']) == 4) { // 衣服
                $tempequip = _newuserCreateEquip($uid, 2, $ujob, 5);
            }
            sql_update("update uinfo set step=step+1 where uid=$uid");
        } else {
            $equiprate = 0;
            if ($my->addequip + $paddequip > 0) {
                $addnum = 6;
                $equipadd = _addoff($uid, $addnum);
                $equiprate = $my->addequip + $paddequip + $equipadd;
            }
            $tempequip = _createTempEquipFromPve($uid, $monsterinfo[$i], $sellstar, 0, $equiprate, $mylv);
        }
        if ($tempequip != 0) {
            $addequip[] = $tempequip;
        }
        if ($tempequip[0] == 2) {
            $addcoin2 += $tempequip[2];
            $adds1 += $tempequip[3];
        }elseif($tempequip[0] == 1){
            $tmp = 'eid'.$i;
            $$tmp = $tempequip[1]['eid'];
        }
    }

    sql_update("insert into upvedraw (uid,pcount,lv) values ($uid,$pcount,$mlv) on duplicate key update pcount = $pcount,lv = $mlv,eids = '',tids = '',times = 0 ");
    //!公会加成
    $addnum = 4;
    $coinadd = _addoff($uid, $addnum);
    if (($my->addcoin + $paddcoin + $coinadd) > 0) {
        $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin + $coinadd) / 10000));
    }
    $addcoin += $addcoin2; //!总加的金币
    
    //!公会加成
    $addnum = 5;
    $expadd = _addoff($uid, $addnum);
    if (($my->addexp + $paddexp + $expadd) > 0) {
        $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp + $expadd) / 10000)); //!总加的经验
    }

    //!战斗间隔时间
    $t = 20;
    switch (count($monsterList)) {
        case 2:
            $t = 40;
            break;
        case 3:
            $t = 60;
        default:
            ;
            break;
    }
    sql_update("update uinfo set  zhanli=$zhanli where uid=$uid");
    if ($oldzhanli != $zhanli) {
        
        if ($oldzhanli < $zhanli) {
            log_newzhanli($zhanli);
        } else {
            sql_update("update upvp set zhanli=$zhanli where uid=$uid");
        }
    }
      
    $r = array();
    $r[] = $addexp;
    $r[] = $addcoin;
    $r[] = $addequip;
    $r[] = $adds1; // 获得精华
    $r[] = $addug; // 奖励充值币
    
    $boxrand = rand(1, 10000);
    if ($mapid < 5) {
        $boxrand = 10000;
    }
    $box = 0;
    $boxitem = 0;
    $boxcount = 0;
    
    if ($boxrand < 27) {
        // 金宝箱;
        $box = 33;
    } elseif ($boxrand < 81) {
        // 银宝箱;
        $box = 32;
    } elseif ($boxrand < 190) {
        // 铜宝箱;
        $box = 31;
    }
    if ($box > 0) {
        $r['box'] = $box;
        // 添加对应钥匙
        if (_checkGem($uid, $box - 10, 1)) {
            // 开宝箱
            $boxinfo = _openbox($uid, $box, $mylv, 0);
            $boxitem = $boxinfo['boxitem'];
            $boxcount = $boxinfo['boxcount'];
        }
    }
    $r['boxitem'] = $boxitem;
    $r['boxcount'] = $boxcount;
    //!增加已经领取状态
    $now = time();
    $r['ts'] = $now;
    //!清除临时背包及更新掉落表   
    $dropInfo = sql_fetch_one("select * from ufightdrop where uid=$uid");
    if ($dropInfo) {
        $eids = $dropInfo['equipId1'] . "," . $dropInfo['equipId2'] . "," . $dropInfo['equipId3'] . "," . $dropInfo['equipId4'] . "," . $dropInfo['equipId5'];
        sql_update("delete from utempbag where eid in ($eids) and uid=$uid");
    }
    sql_update("insert into ufightdrop (uid, ts, mapid, addexp, addcoin, adds1, addug, box, boxitem, boxcount, equipId1, equipId2, equipId3, equipId4, equipId5) 
                    values ($uid, $now, $mapid, $addexp, $addcoin, $adds1, $addug, $box, $boxitem, $boxcount, $eid1, $eid2, $eid3, $eid4, $eid5) 
                    on duplicate key update ts = $now, mapid = $mapid, addexp = $addexp, addcoin = $addcoin, adds1 = $adds1, addug = $addug,
                    box = $box, boxitem = $boxitem, boxcount = $boxcount, equipId1 = $eid1, equipId2 = $eid2, equipId3 = $eid3, equipId4 = $eid4, equipId5 = $eid5");
    return array(
            1,
            $myList,
            $monsterList,
            $r
    );
}

function _rework2 ($uid, $uinfo)
{
    $starttime = microtime();
    $mapid = intval($uinfo['nowmid']);
    $offline = intval($uinfo['offlinetime']);
    if ($mapid < 1) {
        $mapid = 1;
    }
    // 战斗休息中
    if ($offline <= 10) {
        return array(
                1,
                STR_Rest
        );
    }
    // 战斗时间
    $battletime = min(86400, $offline);
    // 更新战斗时间
    sql_update("update uinfo set pvetime=UNIX_TIMESTAMP() where uid=$uid");
    // 快速战斗
    $res = _quickrun2($uid, $uinfo, $battletime, $offline, $mapid);
    $res['type'] = 'rework';
    $endtime = microtime();
    return array(
            2,
            $res
    );
}

/**
 * 快速战斗
 *
 * @param $uid uid            
 * @param $uinfo 用户信息            
 * @param $battlets 战斗时间            
 * @param $offline 离线时间            
 * @param $mapid 地图ID            
 * @return array
 */
function _quickrun2 ($uid, $uinfo, $battlets, $offline, $mapid)
{
    global $activelist;
    // 获取地图配置
    $mapinfo = sql_fetch_one("select * from cfg_map where mid=$mapid");
    if (! $mapinfo) {
        return array(
                0,
                STR_PVE_MapInfErr
        );
    }
    // 背包数量
    $bag = max(intval($uinfo['bag']), 50);
    // 背包余量
    $bagfree = $bag -
             intval(
                    sql_fetch_one_cell(
                            "select count(*) from ubag where uid=$uid and euser=0"));
    // 统计成果
    $addcoin = 0;
    $addexp = 0;
    $adds1 = 0;
    $equips = array();
    $wintime = 0;
    $sellequip = array(
            0,
            0,
            0,
            0,
            0
    );
    $dropequip = array(
            0,
            0,
            0,
            0,
            0
    );
    $sellcoin = 0;
    $sells1 = 0;
    $logs = array();
    $winrate = max(50, intval($uinfo['winrate']));
    $mylv = intval($uinfo['ulv']);
    $sellstar = intval($uinfo['sellstar']);
    $rate = intval($mapinfo['rate']);
    $pcount = intval($mapinfo['pcount']);
    $coin = intval($mapinfo['coin']);
    $exp = intval($mapinfo['exp']);
    // 一次最多怪
    $maxnum = intval($mapinfo['maxnum']);
    // 一次最少怪
    $minnum = intval($mapinfo['minnum']);
    $maxlv = intval($mapinfo['maxlv']);
    $minlv = intval($mapinfo['minlv']);
    $winrand = intval($uinfo['winrand']);
    // 如果快速战斗ID不是现在的副本ID，设置胜率和时间
    if ($mapid != intval($uinfo['nowmid'])) {
        $winrate = 70;
        $winrand = 90;
    }
    // 需要时间 = 10 * (最多怪 + 最少怪)
    $needtime = 10 * ($maxnum + $minnum);
    // 战斗次数 = 总时间 / 每次战斗时间
    $battletime = ceil($battlets / $needtime);
    // 如果平均战斗时长（秒） > 0
    if ($winrand > 0) {
        // 战斗次数 = 总时间 / 平均战斗时长 / 3600
        $battletime = min($battletime, ceil($battlets * $winrand / 3600));
    }
    // 胜利次数 = 战斗次数 * 胜率
    $wintime = ceil($battletime * $winrate / 100);
    $mnum = $wintime;
    
    $equiprate = 0;
    $equipcoin = 0;
    $equipexp = 0;
    // 获取玩家装备属性
    $uep = sql_fetch_one_cell("select ep from uequip where uid=$uid");
    if ($uep != '') {
        $parr = preg_split("/[\s,]+/", $uep);
        if (count($parr) > 0) {
            // 遍历装备各属性
            foreach ($parr as $p) {
                $pdetail = preg_split("/[\s|]+/", $p);
                // 获得高品质装备概率
                if ($pdetail && count($pdetail) == 2 && $pdetail[1] != 0 &&
                         $pdetail[0] == 30) {
                    $addnum = 6;
                    $equipadd = _addoff($uid, $addnum);
                    $equiprate = intval($pdetail[1]);
                    $equiprate = $equiprate + $equipadd;
                }                 // 击败怪物经验获得
                elseif ($pdetail && count($pdetail) == 2 && $pdetail[1] != 0 &&
                         $pdetail[0] == 28) {
                    $equipexp = intval($pdetail[1]);
                }                 // 击败怪物金币掉落
                elseif ($pdetail && count($pdetail) == 2 && $pdetail[1] != 0 &&
                         $pdetail[0] == 29) {
                    $equipcoin = intval($pdetail[1]);
                }
            }
        }
    }
    
    $addnum = 4;
    $coinadd = _addoff($uid, $addnum);
    $addnum = 5;
    $expadd = _addoff($uid, $addnum);
    $addcoin = ceil(
            $mnum * $coin * (10000 + $equipcoin + $coinadd) * ($maxnum + $minnum) /
                     20000);
    $addexp = ceil(
            $mnum * $exp * (10000 + $equipexp + $expadd) * ($maxnum + $minnum) /
                     20000);
    $enum = floor($mnum * $rate * ($maxnum + $minnum) / 20000);
    
    // 获取装备属性
    $parr = _getEquipPArr($pcount, $enum, $equiprate);
    // 看背包容量倒序产生装备
    $nowp = 8;
    $star = 0;
    $eids = "0";
    $isrealcreat = false;
    for ($j = 0; $j < $enum; $j ++) {
        if ($parr[8] > 0) {
            $nowp = 8;
            $parr[8] --;
        } elseif ($parr[7] > 0) {
            $nowp = 7;
            $parr[7] --;
        } elseif ($parr[6] > 0) {
            $nowp = 6;
            $parr[6] --;
        } elseif ($parr[5] > 0) {
            $nowp = 5;
            $parr[5] --;
        } elseif ($parr[4] > 0) {
            $nowp = 4;
            $parr[4] --;
        } elseif ($parr[3] > 0) {
            $nowp = 3;
            $parr[3] --;
        } elseif ($parr[2] > 0) {
            $nowp = 2;
            $parr[2] --;
        } elseif ($parr[1] > 0) {
            $nowp = 1;
            $parr[1] --;
        } else {
            $nowp = 0;
        }
        if ($nowp >= 4) {
            $star = 4;
        } else {
            $star = $nowp;
        }
        $mlv = max(rand($minlv, $maxlv), 6);
        $mlv = min($mlv, MAXULV);
        $equiplv = rand(min($mlv - 5, $mylv), $mlv);
        if ($bagfree <= 0 || $nowp < $sellstar) {
            // 卖;
            $addcoin += $equiplv * _getSellCoin($star);
            $sellequip[$star] ++;
            $dropequip[$star] ++;
        } else {
            // 创建
            $bagfree --;
            $eid = _realCreateEquipNoget($uid, $nowp, $equiplv);
            $eids = $eids . "," . $eid;
            $isrealcreat = true;
            $dropequip[$star] ++;
        }
    }
    if ($isrealcreat) {
        $equips = sql_fetch_rows("select * from ubag where eid in ($eids)");
    }
    // 添加经验
    $lvarr = _addExp($uid, $addexp);
    // 添加金币
    _addCoin($uid, $addcoin);
    // 金币添加日志
    log_log('coin', $uid . "," . $addcoin . ",pve,1");
    $msg = array();
   
    // ! 圣诞活动
    if (checkActivityOpenById(activityTypeDef::christmasActivity)) {
        $eggNum = 0;
        for ($i = 0; $i < $mnum; ++ $i) {
            $monsterNum = rand($minnum, $maxnum);
            $rand = rand(1, 100);
            $rate = $maxlv / $mylv * 2 * $monsterNum;
            if ($rand < $rate) {
                $eggNum += 1;
            }
        }
        if ($eggNum > 0) {
            addShengDanItem($uid, $eggNum);
            $msg[] = STR_ShengDan . $eggNum;
        }
    }
    
    $r = array();
    $r['offlinetime'] = $offline;
    $r['battletime'] = $battletime;
    $r['mapid'] = $mapid;
    $r['win'] = $wintime;
    $r['equips'] = $equips;
    $r['sellequip'] = $sellequip;
    $r['dropequip'] = $dropequip;
    $r['addcoin'] = $addcoin;
    $r['addexp'] = $addexp;
    $r['adds1'] = $adds1;
    $r['oldlv'] = $lvarr[0];
    $r['newlv'] = $lvarr[1];
    $res = array();
    $res['log'] = $r;
    $res['type'] = '';
    $winrate = intval($uinfo['winrate']);
    $winrand = intval($uinfo['winrand']);
    $wincoin = intval($uinfo['wincoin']);
    $winexp = intval($uinfo['winexp']);
    $winequip = intval($uinfo['winequip']);
    $battlecount = array();
    $battlecount[] = $winrate;
    $battlecount[] = $winrand;
    $battlecount[] = $wincoin;
    $battlecount[] = $winexp;
    $battlecount[] = $winequip;
    $res['boxs'] = array();
    $itemhave1 = 31;
    $itemhave2 = 32;
    $itemhave3 = 33;
    $boxsysopen = 1;
    for ($i = 0; $boxsysopen == 1 && $i < ceil($mnum * ($maxnum + $minnum) / 2); $i ++) {
        $boxrand = rand(1, 10000);
        if ($mapid < 5) {
            $boxrand = 10000;
        }
        $box = 0;
        if ($boxrand < 27) {
            // 金宝箱;
            $box = 33;
        } elseif ($boxrand < 81) {
            // 银宝箱;
            $box = 32;
        } elseif ($boxrand < 190) {
            // 铜宝箱;
            $box = 31;
        }
        if ($box > 0) {
            $bres['box'] = $box;
            if (($box == $itemhave1 || $box == $itemhave2 || $box == $itemhave3) &&
                     _subItem($uid, $box - 10, 1)) {
                $boxinfo = _openbox($uid, $box, $mylv);
                $bres['boxitem'] = $boxinfo['boxitem'];
                $bres['boxcount'] = $boxinfo['boxcount'];
                log_log('item', $uid . ",openBoxPve," . ($box - 10) . ",1,0");
                log_log('item', 
                        $uid . ",openBoxPve," . $boxinfo['boxitem'] . "," .
                                 $boxinfo['boxcount'] . ",1");
            } else {
                if ($box == 31) {
                    $itemhave1 = 0;
                } elseif ($box == 32) {
                    $itemhave2 = 0;
                } elseif ($box == 33) {
                    $itemhave3 = 0;
                }
                $bres['boxitem'] = 0;
                $bres['boxcount'] = 0;
            }
            $res['boxs'][] = $bres;
        }
    }
    $res['stat'] = $battlecount;
    $res['msg'] = $msg;
    return $res;
}

/**
 * 接口：普通副本结算
 *
 * @param
 *            $uid
 * @param
 *            $params
 * @return array
 */
function endPveFight($uid, $params)
{
    $ret = intval($params[0]);
    $mapid = intval($params[1]);
    $battletime = intval($params[2]);
    $ts = 0; //!验证
    
    $myinfo = sql_fetch_one("select u.*, d.*, LEAST(UNIX_TIMESTAMP()-pvetime,86400) as offlinetime from uinfo u inner join ufightdrop d on u.uid = d.uid where u.uid=$uid");
    if (! $myinfo) {
        return array(
                0,
                STR_DataErr2
        );
    }
    
    if (intval($myinfo['mapid']) != $mapid) {
        return array(
                0,
                STR_DataErr
        );
    }
    
    $mapinfo = sql_fetch_one("select * from cfg_map where mid=$mapid");
    if (! $mapinfo) {
        return array(
                0,
                STR_PVE_MapErr
        );
    }  
    
    $nowmid = intval($myinfo['nowmid']);
    $winrate = intval($myinfo['winrate']);
    $winrand = intval($myinfo['winrand']);
    $wincoin = intval($myinfo['wincoin']);
    $winexp = intval($myinfo['winexp']);
    $winequip = intval($myinfo['winequip']);
    $ujob = intval($myinfo['ujob']);

    $addexp = intval($myinfo['addexp']);
    $addcoin = intval($myinfo['addcoin']);
    $eids = $myinfo['equipId1'] . "," . $myinfo['equipId2'] . "," . $myinfo['equipId3'] . "," . $myinfo['equipId4'] . "," . $myinfo['equipId5'];
    if ($ret == 1) {
        //！加东西
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin);
        log_log('coin',$uid . "," . $addcoin . ",pve,1");
        //!减钥匙
        _subItem($uid, intval($myinfo['box']) - 10, 1);
        log_log('item', $uid . ",openBoxPve," . (intval($myinfo['box']) - 10) . ",1,0");
        $boxitem = intval($myinfo['boxitem']);
        $boxcout = intval($myinfo['boxcount']);
        if ($boxitem == 1) {
            _addCoin($uid, $boxcout);
            log_log('coin',$uid . "," . $boxcout . ",openBox31,1");
        } elseif ($boxitem == 5) {
            _addExp($uid, $boxcout);
        } elseif ($boxitem == 2) {
            _addUg($uid, $boxcout, 31);
        } else {
            _addItem($uid, $boxitem, $boxcout);
        }
        log_log('item', $uid . ",openBoxPve," . $boxitem . "," . $boxcout . ",1");
        
        //!加装备
        $sql = "insert into ubag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock from utempbag t where t.eid in ($eids) and t.uid=$uid";
        sql_insert($sql);
    }
    
    //!清除临时背包及掉落表
    sql_update("delete from utempbag where eid in ($eids) and uid=$uid");
    sql_update("delete from ufightdrop where uid=$uid");
    
    if ($winrand == 0) {
        $winrand = 90;
    }
    
    // 换新地图
    if ($nowmid != $mapid) {
        $winrate = 70;
        if (intval($myinfo['umid']) > $mapid) {
            $winrate = 85;
        }
        $winrand = 90;
        $wincoin = 0;
        $winexp = 0;
        $winequip = $winrand * (intval($mapinfo['rate']) * (intval($mapinfo['minnum']) + intval($mapinfo['maxnum'])) / 20000);
    } else {
        if ($ret == 1) {
            $winrate = $winrate + floor(sqrt(100 - $winrate));
        } else {
            $winrate = round($winrate / 1.155);
        }
        $winrate = max($winrate, 56);
        $winrate = min($winrate, 100);

        $winrand = CEIL(3600 / (3600 * 0.8 / $winrand + ($battletime + 10) * 0.2));
        $wincoin = CEIL(($wincoin * 0.8 / $winrand + $addcoin * 0.2) * $winrand);
        $winexp = CEIL(($winexp * 0.8 / $winrand + $addexp * 0.2) * $winrand);
    }
    if (! sql_update("update uinfo set pvetime=UNIX_TIMESTAMP(),nowmid=$mapid,winrate=$winrate,winrand=$winrand,wincoin=$wincoin,winexp=$winexp,winequip=$winequip where uid=$uid")) {
        return array(
                1,
                STR_Rest
        );
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    return array(
            2,
            $uinfo
    );
}

/**
 * 接口：挑战BOSS
 * @param $uid
 * @param $params ['mapid']
 * @return array
 */
function beginPvbFight($uid, $params)
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
        foreach($partnerinfo as $value) {
            $partner = new my(4, $value, $mylv, $combineadd);
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
            get_object_vars($monster)
    );
    
    $addcoin = 0;
    $addexp = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    $eid1 = 0;
    $newmapid = $mymapid;
    $msg = array();

    //!计算奖励
    $addexp += intval($monsterinfo['exp']);
    $addcoin += intval($monsterinfo['coin']);
    $adds1 += intval($monsterinfo['s1count']);
    
    //!装备
    $pcount = intval($monsterinfo['pcount']);
    $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
    $maxlv = min(MAXULV, intval($monsterinfo['lv']));
    $equiplv = rand($minlv, $maxlv);
    $cfgequip = sql_fetch_one("select * from cfg_equip where ismain=0 and lv=$equiplv order by rand() limit 1");
    $etype = intval($cfgequip['etype']);
    $tempequip = _doCreateEquipAndGet($uid, $cfgequip, $pcount, 0, 1); /*最后一个1表示插入到tempbag表中*/
    if ($tempequip != 0) {
        $addequip[] = $tempequip;
        $eid1 = $tempequip[1]['eid'];
    }
    if (($my->addcoin + $paddcoin) > 0) {
        $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin) / 10000));
    }
    if (($my->addexp + $paddexp) > 0) {
        $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp) / 10000));
    }
    //sql_update("update uinfo set  pvetime=pvetime+30 where uid=$uid");
    $r = array();
    $r[] = $addexp;
    $r[] = $addcoin;
    $r[] = $addequip;
    $r[] = $adds1;
    $r[] = $addug;

    $box = 0;
    $boxitem = 0;
    $boxcount = 0;
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
    if ($mapid < 5) {
        $box = 0;
    }
    if ($box > 0) {
        $r['box'] = $box;
        // 添加对应钥匙
        if (_checkGem($uid, $box - 10, 1)) {
            // 开宝箱
            $boxinfo = _openbox($uid, $box, $mylv, 0);
            $boxitem = $boxinfo['boxitem'];
            $boxcount = $boxinfo['boxcount'];
        }
    }
    $r['boxitem'] = $boxitem;
    $r['boxcount'] = $boxcount;
    //!增加已经领取状态
    $now = time();
    $r['ts'] = $now;
    
    //!清除临时背包及更新掉落表   
    $dropInfo = sql_fetch_one("select * from ufightdrop where uid=$uid");
    if ($dropInfo) {
        $eids = $dropInfo['equipId1'] . "," . $dropInfo['equipId2'] . "," . $dropInfo['equipId3'] . "," . $dropInfo['equipId4'] . "," . $dropInfo['equipId5'];
        sql_update("delete from utempbag where eid in ($eids) and uid=$uid");
    }
    sql_update("insert into ufightdrop (uid, ts, mapid, addexp, addcoin, adds1, addug, box, boxitem, boxcount, equipId1, equipId2, equipId3, equipId4, equipId5) 
                    values ($uid, $now, $mapid, $addexp, $addcoin, $adds1, $addug, $box, $boxitem, $boxcount, $eid1, 0, 0, 0, 0) 
                    on duplicate key update ts = $now, mapid = $mapid, addexp = $addexp, addcoin = $addcoin, adds1 = $adds1, addug = $addug,
                    box = $box, boxitem = $boxitem, boxcount = $boxcount, equipId1 = $eid1, equipId2 = 0, equipId3 = 0, equipId4 = 0, equipId5 = 0");
    return array(
            1,
            $myList,
            $monsterList,
            $r
    );
}


/**
 * 接口：boss副本结算
 *
 * @param
 *            $uid
 * @param
 *            $params
 * @return array
 */
function endPvbFight($uid, $params)
{
    $ret = intval($params[0]);
    $mapid = intval($params[1]);
    $battletime = intval($params[2]);
    $ts = 0; //!验证

    $myinfo = sql_fetch_one("select u.*, d.*, LEAST(UNIX_TIMESTAMP()-pvetime,86400) as offlinetime from uinfo u inner join ufightdrop d on u.uid = d.uid where u.uid=$uid");
    if (! $myinfo) {
        return array(
                0,
                STR_DataErr2
        );
    }
    $mymapid = intval($myinfo['umid']);
    if (intval($myinfo['mapid']) != $mapid) {
        return array(
                0,
                STR_DataErr
        );
    }

    $mapinfo = sql_fetch_one("select * from cfg_map where mid=$mapid");
    if (! $mapinfo) {
        return array(
                0,
                STR_PVE_MapErr
        );
    }

    $addexp = intval($myinfo['addexp']);
    $addcoin = intval($myinfo['addcoin']);
    $adds1 = intval($myinfo['adds1']);
    $eids = $myinfo['equipId1'] . "," . $myinfo['equipId2'] . "," . $myinfo['equipId3'] . "," . $myinfo['equipId4'] . "," . $myinfo['equipId5'];
    if ($ret == 1) {
        //！加东西
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin);
        _addJinghua($uid, $adds1);
        log_log('coin',$uid . "," . $addcoin . ",pve,1");
        //!减钥匙
        _subItem($uid, intval($myinfo['box']) - 10, 1);
        log_log('item', $uid . ",openBoxPvb," . (intval($myinfo['box']) - 10) . ",1,0");
        $boxitem = intval($myinfo['boxitem']);
        $boxcout = intval($myinfo['boxcount']);
        if ($boxitem == 1) {
            _addCoin($uid, $boxcout);
            log_log('coin',$uid . "," . $boxcout . ",openBox31,1");
        } elseif ($boxitem == 5) {
            _addExp($uid, $boxcout);
        } elseif ($boxitem == 2) {
            _addUg($uid, $boxcout, 31);
        } else {
            _addItem($uid, $boxitem, $boxcout);
        }
        log_log('item', $uid . ",openBoxPve," . $boxitem . "," . $boxcout . ",1");

        //!加装备
        $sql = "insert into ubag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock from utempbag t where t.eid in ($eids) and t.uid=$uid";
        sql_insert($sql);
        
        if ($mapid == $mymapid && $mapid <= MAXMAPNUM) {
            $newmapid = $mymapid + 1;
            sql_update("update uinfo set umid=umid+1,pvb=pvb-1,nowmid=$newmapid where uid=$uid and umid=$mapid and pvb>0");
            _setGift($uid, 2, $mymapid);
        } else {
            sql_update("update uinfo set pvb=pvb-1 where uid=$uid and pvb>0");
        }
    }
    sql_update("update uinfo set  pvetime=UNIX_TIMESTAMP() where uid=$uid");
    //!清除临时背包及掉落表
    sql_update("delete from utempbag where eid in ($eids) and uid=$uid");
    sql_update("delete from ufightdrop where uid=$uid");

    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    return array(
            2,
            $uinfo
    );
}

function beginPvtFight($uid,$params){
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

    $addcoin = 0;
    $addexp = 0;
    $addequip = array();
    $adds1 = 0;
    $addug = 0;
    $additem = array();
    $eid = 0;
    $addexp += intval($monsterinfo['exp']);
    $addcoin += intval($monsterinfo['coin']);
    $pcount = intval($monsterinfo['pcount']);
    $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
    $maxlv = min(MAXULV, intval($monsterinfo['lv']));
    $equiplv = rand($minlv, $maxlv);
    // 生成奖励
    if ($pcount > 0) {
        $tempequip = _realCreateEquip($uid, $pcount, $equiplv,0,1);
        if ($tempequip != 0) {
            $addequip[] = $tempequip;
        }
        if($tempequip[0] == 1){
            $eid = $tempequip[1]['eid'];
        }
    }
    if (($my->addcoin + $paddcoin) > 0) {
        $addcoin = ceil($addcoin * (1 + ($my->addcoin + $paddcoin) / 10000));
    }
    if (($my->addexp + $paddexp) > 0) {
        $addexp = ceil($addexp * (1 + ($my->addexp + $paddexp) / 10000));
    }
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
    $userFirst = 0;
    if (intval($pvtinfo["maxtid" . $tlv]) == $tid && $tid <= $maxtid){
        //普通奖励
        $userFirst = 1;
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
    }
    if($userFirst == 0){
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



    // TODO生成奖励
    $r = array();
    $r[] = $addexp;
    $r[] = $addcoin;
    $r[] = $addequip;
    $r[] = $adds1;
    $r[] = $addug;
    $r[] = $additem;
    $now = time();
    $r['ts'] = $now;
    $itemStr = '';
    for($i = 0;$i < count($additem);$i++){
        if($i == 0){
            $itemStr .= $additem[$i]['itemid'].'|'.$additem[$i]['count'];
        }else{
            $itemStr .= ','.$additem[$i]['itemid'].'|'.$additem[$i]['count'];
        }
    }


    //!清除临时背包及更新掉落表
    $dropInfo = sql_fetch_one("select * from upvtdrop where uid=$uid");
    if ($dropInfo) {
        $eid = $dropInfo['equipId'];
        sql_update("delete from utempbag where eid = $eid and uid=$uid");
    }
    sql_update("insert into upvtdrop (uid, ts, tid, addexp, addcoin, adds1, addug, additem,userfirst,equipId,tlv)
                    values ($uid, $now, $tid, $addexp, $addcoin, $adds1, $addug, $itemStr, $userFirst,$eid,$tlv)
                    on duplicate key update ts = $now, tid = $tid, addexp = $addexp, addcoin = $addcoin, adds1 = $adds1, addug = $addug,
                    additem = $itemStr,userfirst = $userFirst,equipId = $eid,tlv=$tlv");
    return array(
        1,
        $myList,
        $monsterinfo,
        $r
    );
}

function endPvtFight($uid,$params)
{
    $ret = intval($params[0]);
    $tid = intval($params[1]);
    $ts = 0;//验证

    $myinfo = sql_fetch_one("select u.*, d.*, LEAST(UNIX_TIMESTAMP()-pvetime,86400) as offlinetime from uinfo u inner join upvtdrop d on u.uid = d.uid where u.uid=$uid");
    if (! $myinfo) {
        return array(
            0,
            STR_DataErr2
        );
    }
    if(intval($myinfo['tid']) != $tid){
        return array(
            0,
            STR_DataErr
        );
    }
    $pvtinfo = sql_fetch_one("select *  from upvt where uid=$uid");
    if(!$pvtinfo){
        return array(
            0,
            STR_DataErr
        );
    }

    $addexp = intval($myinfo['addexp']);
    $addcoin = intval($myinfo['addcoin']);
    $additem = explode(',',$myinfo['additem']);
    $equipId = intval($myinfo['equipId']);
    $userFirst = intval($myinfo['userfirst']);
    $tlv = intval($myinfo['tlv']);
    if($ret == 1){
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvt');
        // 增加已挑战列表
        $tidstr = $pvtinfo['tids'];
        $tidstr = $tidstr . "," . $tid;
        sql_update("update upvt set tids='$tidstr' where uid=$uid");
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
        if ($userFirst == 1) {
           sql_update("update upvt set maxtid$tlv=$tid+1 where uid=$uid and maxtid$tlv=$tid") ;

                // 物品奖励
                foreach ($additem as $item) {
                    $item = explode('|',$item);
                    _addGiftItem($uid, $item[0], $item[1]);
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
        }else{
            // 物品奖励
            foreach ($additem as $item) {
                $item = explode('|',$item);
                _addGiftItem($uid, $item[0], $item[1]);
            }
        }

        //!加装备
        $sql = "insert into ubag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock from utempbag t where t.eid = $equipId and t.uid=$uid";
        sql_insert($sql);
        //!清除临时背包及掉落表
        sql_update("delete from utempbag where eid = $equipId and uid=$uid");
        sql_update("delete from upvtdrop where uid=$uid");

    }elseif($ret == 0){
        // TODO 战斗失败时间限制,暂时先不加,之后
        sql_update("update upvt set pvttime=UNIX_TIMESTAMP()+300 where uid=$uid");
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    return array(
        1,
        $uinfo
    );
}

/**
 * pve获取十件装备
 * @param $uid
 * @param $params
 * @return array
 */
function getPveDrawEquip($uid,$params){
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $upvedrawInfo = sql_fetch_one("select * from upvedraw where uid = $uid");
    $itemInfo = sql_fetch_rows("select * from cfg_chouka");
    if(!$uinfo || !$upvedrawInfo){
        return array(
            0,
            STR_DataErr
        );
    }

    $ulv = $uinfo['ulv'];
    $maxpcount = intval($upvedrawInfo['pcount']);
    $minPcount  = max(1,$maxpcount-3);
    $maxLv = intval($upvedrawInfo['lv']);
    $minLv  = max(1,$maxLv-3);
    $drawArr = array();
    $eids = array();
    $tids = array();
    for($i = 0;$i < 3;$i++){
        $pcount = rand($minPcount,$maxpcount);
        $lv = rand($minLv,$maxLv);
        $equip = _createTempEquipPvedraw($uid, $pcount, $lv,  $ulv);
        $drawArr[] = $equip;
        $eids[] = $equip['eid'];
    }
    $eids = implode(',',$eids);
    $itemids = array_rand($itemInfo,2);
    for($i = 0;$i < count($itemids);$i++){
        $drawArr[] = $itemInfo[$itemids[$i]];
        $tids[] = $itemInfo[$itemids[$i]]['id'];
    }
    $tids = implode(',',$tids);
    sql_update("update upvedraw set eids = '$eids',tids = '$tids',times = 0 where uid = $uid");
    shuffle($drawArr);
    return array(
        1,
        $drawArr
    );
}

/**
 * pve抽奖，用户得到的装备
 * @param $uid
 * @param $params
 * @return array
 */
function getPveLuckyDraw($uid,$params){
    $typeid = intval($params[0]);
    if($typeid < 0 || $typeid > 6){
        return array(
            0,
            STR_DataErr.'1'
        );
    }
    $uinfo  = sql_fetch_one("select * from upvedraw where uid = $uid ");
    if(!$uinfo){
        return array(
            0,
            STR_DataErr.'2'
        );
    }
    $times = $uinfo['times'];
    if($typeid <= $times){
        return array(
            0,
            STR_DataErr.'3'
        );
    }
    if($times >= 5){
        return array(
            0,
            STR_Reward_over
        );
    }
    // 检测背包容量
    $ubag = sql_fetch_one("select bag from uinfo where uid=$uid");
    $bag = max(array($ubag['bag'],50));
    $cntBag = sql_fetch_one_cell("select count(*) from ubag where euser=0 and uid=$uid");
    $bagFree = $bag - $cntBag;
    if ($bagFree <= 0) {
        return array(
            0,
            STR_BAG_NotEnough
        );
    }
    $costUgArr1 = array(
        2=>10,
        3=>20,
        4=>40,
        5=>80
    );
    $costUgArr2 = array(
        0=>120,
        1=>120,
        2=>110,
        3=>100,
        4=>80
    );
    $costUg = 0;
    if($typeid != 1){
        if($typeid <= 5){
            $costUg = $costUgArr1[$typeid];
        }else{
            $costUg = $costUgArr2[$times];
        }
        if(!_spendGbytype($uid,$costUg,'pvechouka')){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    $rewards = array();
    if($uinfo['eids'] != ''){
        $eids = explode(',',$uinfo['eids']);
        for($i = 0;$i < count($eids);$i++){
            $rewards[] = array(1,$eids[$i]);
        }
    }else{
        $eids = array();
    }
    if($uinfo['tids'] != ''){
        $tids = explode(',',$uinfo['tids']);
        for($i = 0;$i < count($tids);$i++){
            $rewards[] = array(2,$tids[$i]);
        }
    }else{
        $tids = array();
    }
    $addReward = array();
    if($typeid != 6){
        $rewardId = array_rand($rewards,1);
        $id = $rewards[$rewardId][1];
        if($rewards[$rewardId][0] == 1){
            $sql = "insert into ubag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock from utempbag t where t.eid = $id and t.uid=$uid";
            sql_insert($sql);
            $addReward[]  = sql_fetch_one("select * from utempbag where eid = $id and uid = $uid");
            sql_update("delete from utempbag where eid  = $id and uid=$uid");
            $key = array_search($id,$eids);
            unset($eids[$key]);
        }else{
            $addReward[] = sql_fetch_one("select * from cfg_chouka where id = $id");
            if($addReward['itemid'] == 1){
                _addCoin($uid,$addReward['count'],'pvechouka');
            }elseif($addReward['itemid'] == 2){
                _addUg($uid,$addReward['count'],'pvechouka');
            }else{
                _addItem($uid,$addReward['itemid'],$addReward['count'],'pvechouka');
            }
            $key = array_search($id,$tids);
            unset($tids[$key]);
        }
        $times = $times+1;
    }else{
        if(!empty($eids)){
            $eidstr = implode(',',$eids);
            $sql = "insert into ubag (uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock) select uid,ename,etype,ejob,p1,p2,p1value,p2value,pstr,picindex,star,elv,ceid,lv,advp,advvalue,advlv,sock from utempbag t where t.eid in ($eidstr) and t.uid=$uid";
            sql_insert($sql);
            $addReward  = sql_fetch_rows("select * from utempbag where eid in ($eidstr)  and uid = $uid");
            sql_update("delete from utempbag where eid  in ($eidstr)  and uid=$uid");
            $eids = array();
        }
        if(!empty($tids)){
            $tidstr = implode(',',$tids);
            $tidArr = sql_fetch_rows("select * from cfg_chouka where id in ($tidstr)");
            $addReward = array_merge($addReward,$tidArr);
            for($i = 0;$i < count($tidArr);$i++){
                if($tidArr['itemid'] == 1){
                    _addCoin($uid,$tidArr['count'],'pvechouka');
                }elseif($tidArr['itemid'] == 2){
                    _addUg($uid,$tidArr['count'],'pvechouka');
                }else{
                    _addItem($uid,$tidArr['itemid'],$tidArr['count'],'pvechouka');
                }
            }
            $tids = array();
        }
        $times = 5;
        shuffle($addReward);
   }
    $tids = implode(',',$tids);
    $eids = implode(',',$eids);
    sql_update("update upvedraw set eids = '$eids',tids = '$tids',times = $times where uid = $uid");
    return array(
        1,
        $costUg,
        $addReward
    );

}

function _createTempEquipPvedraw($uid, $pcount, $lv,  $ulv){
    $mlv = intval($lv);
    $equiplv = rand(min($mlv - 5, $ulv), $mlv);
    if ($equiplv <= 0) {
        $equiplv = rand(1, 5);
    }
    $pcount = intval($pcount);
    $cfgequip = sql_fetch_one("select * from cfg_equip where ismain=0 and lv=$equiplv order by rand() limit 1");
    $eid = _doCreateEquip($uid, $cfgequip, $pcount, 0, 1);
    $equip = sql_fetch_one("select * from utempbag where eid='$eid'");
    return $equip;
}


?>
