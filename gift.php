<?php


function _addGiftItem($uid, $itemid, $count, $gifttype = 0)
{
    $itemid = intval($itemid);
    if ($itemid == 6 && is_array($count)) {
        if (intval($count['eid']) > 0) {
            $equip = _createEquipByceid($uid, intval($count['eid']), intval($count['pcount']), intval($count['advp']));
            if (intval($count['sock']) > 0) {
                $sock = intval($count['sock']);
                $eid = intval($equip[1]['eid']);
                sql_update("update ubag set sock=$sock where uid=$uid and eid=$eid");
                $equip[1]['sock'] = $sock;
            }
        } else {
            if (intval($count['elv']) > 0) {
                $ulv = intval($count['elv']);
            } else {
                $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
                $ulv = min(MAXULV - 1, $ulv);
                $ulv = $ulv - $ulv % 5;
            }
            $etype = intval($count['etype']);
            $cfgequip = sql_fetch_one("select * from cfg_equip where lv>=$ulv and lv<$ulv+5 and etype=$etype order by lv limit 1");
            $advp = 0;
            if (intval($count['advp']) > 0) {
                $advp = 0;
            }
            $equip = _doCreateEquipAndGet($uid, $cfgequip, 4, $advp);
            if (intval($count['sock']) > 0) {
                $sock = intval($count['sock']);
                $eid = intval($equip[1]['eid']);
                sql_update("update ubag set sock=$sock where uid=$uid and eid=$eid");
                $equip[1]['sock'] = $sock;
            }
        }
        return array(
            'itemid' => 6,
            'count' => $equip[1]
        );
    } else {
        $count = intval($count);
        if ($itemid == 1) {
            _addCoin($uid, $count, 'gift' . $gifttype);
        } elseif ($itemid == 2) {
            _addUg($uid, $count, 'gift' . $gifttype);
        } elseif ($itemid == 5) {
            _addExp($uid, $count);
        } else {
            _addItem($uid, $itemid, $count, 'gift' . $gifttype);
        }
        return array(
            'itemid' => $itemid,
            'count' => $count
        );
    }
}

/**
 * 购买vip礼包
 * 
 * @param
 *            $uid
 * @param $params [0]            
 * @return
 *
 */
function buyVipItem($uid, $params)
{
    $vip = $params[0];
    $uinfo = sql_fetch_one("select vip from uinfo where uid = $uid");
    $viplv = intval($uinfo['vip']);
    if ($vip > $viplv || $viplv == 0 || $vip == 0) {
        return array(
            0,
            STR_Buy_VIPItem_Level_Not_Enough
        );
    }
    $viprecord = sql_fetch_one_cell("select record from uvipfuli where uid = $uid");
    if ($viprecord) {
        $vipArr = explode("|", $viprecord);
        if (in_array($vip, $vipArr)) {
            return array(
                0,
                STR_Buy_VIPItem_Exist
            );
        }
    }
    $cfg_vipitem = sql_fetch_one("select * from cfg_vipfuli where lv = $vip");
    if(!$cfg_vipitem){
        return array(
            0,
            STR_Param_Error
        );
    }
    if($cfg_vipitem['sellug'] > 0){
        if(!_spendGbytype($uid, $cfg_vipitem['sellug'], "购买VIP礼包")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    if ($cfg_vipitem['item1'] > 0 || $cfg_vipitem['count1'] > 0) {
        _addGiftItem($uid, $cfg_vipitem['item1'], $cfg_vipitem['count1'], 0);
    }
    if ($cfg_vipitem['item2'] > 0 || $cfg_vipitem['count2'] > 0) {
        _addGiftItem($uid, $cfg_vipitem['item2'], $cfg_vipitem['count2'], 0);
    }
    if ($cfg_vipitem['item3'] > 0 || $cfg_vipitem['count3'] > 0) {
        _addGiftItem($uid, $cfg_vipitem['item3'], $cfg_vipitem['count3'], 0);
    }
    if ($cfg_vipitem['item4'] > 0 || $cfg_vipitem['count4'] > 0) {
        _addGiftItem($uid, $cfg_vipitem['item4'], $cfg_vipitem['count4'], 0);
    }

    if (empty($viprecord)) {
        $viprecord = $vip;
    } else {
        $viprecord = $viprecord . "|" . $vip;
    }
    sql_update("insert into uvipfuli(uid,record) values ($uid,'$viprecord') on duplicate key update record='$viprecord'");
    return array(
        1,
        $cfg_vipitem
    );
}

/**
 * 获取购买vip礼包记录
 * 
 * @param
 *            $uid
 * @param $params []            
 * @return
 *
 */
function getBuyVipItemRecord($uid, $params)
{
    $viprecord = sql_fetch_one_cell("select record from uvipfuli where uid = $uid");
    $vipArr = array();
    if ($viprecord) {
        $vipArr = explode("|", $viprecord);
    }
    $recordstr = "";
    for ($i = 1; $i <= 15; $i ++) {
        if (in_array($i, $vipArr)) {
            if (empty($recordstr)) {
                $recordstr = "$i" . "|" . "1";
            } else {
                $recordstr = $recordstr . "," . "$i" . "|" . "1";
            }
        } else {
            if (empty($recordstr)) {
                $recordstr = "$i" . "|" . "0";
            } else {
                $recordstr = $recordstr . "," . "$i" . "|" . "0";
            }
        }
    }
    return array(
        1,
        $recordstr
    );
}

// 获取每日签到数据
function getEverydayCheckin($uid, $params)
{
    $checkin = sql_fetch_one("select * from ucheckin where uid = $uid");
    $day = intval(date("d"));
    if (! $checkin) {
        sql_update("insert into ucheckin(uid,day,checkinnum,time) values($uid,$day,0,(UNIX_TIMESTAMP()-86400))");
        $checkin = sql_fetch_one("select * from ucheckin where uid = $uid");
        // $checkin['total'] = 1;
    }
    $nowtime = time();
    $lasttime = intval($checkin['time']);
    $lastmonth = intval(date("m", $lasttime));
    $nowmonth = intval(date("m", $nowtime));
    $lastday = intval($checkin['day']);
    $nowday = intval(date("d", $nowtime));
    $checkin['total'] = intval($checkin['checkinnum']);
    if ($lastmonth != $nowmonth) {
        sql_update("insert into ucheckin(uid,day,checkinnum) values($uid,$day,0) on duplicate key update day=$day ,checkinnum=0");
        $checkin = sql_fetch_one("select * from ucheckin where uid = $uid");
        // $checkin['total'] = intval($checkin['checkinnum']) + 1;
    }
    if ($lastday != $nowday) {
        sql_update("insert into ucheckin(uid,day) values($uid,$day) on duplicate key update day=$day");
        $checkin = sql_fetch_one("select * from ucheckin where uid = $uid");
        // $checkin['total'] = intval($checkin['checkinnum']) + 1;
    }
    $checkin['month'] = intval(date("m"));
    // 今天时间
    $lasttimes = date('Y-m-d H:i:s', $lasttime);
    $lenth = strlen($lasttimes);
    $date1 = substr($lasttimes, 0, $lenth - 9);
    $lasttimez = strtotime($date1);
    // 最后一天时间
    $todaytimes = date('Y-m-d H:i:s', time());
    $lenth = strlen($todaytimes);
    $date2 = substr($todaytimes, 0, $lenth - 9);
    $nowtimez = strtotime($date2);
    if ($nowtimez > $lasttimez) {
        $checkin['cancheckin'] = 1;
        $checkin['total'] = intval($checkin['checkinnum']) + 1;
    } else {
        $checkin['cancheckin'] = 0;
    }
    // 本月有多少天
    $month = intval(date("m"));
    $thismonthday = intval(sql_fetch_one_cell("select count(*) from cfg_checkinreward where month=$month"));
    $checkin['thismonthday'] = $thismonthday;
    
    // 移除多余的值
    unset($checkin['checkinnum']);
    return array(
        1,
        $checkin
    )
    ;
}

// 签到
function checkin($uid, $params)
{
    // $type = $params[0];
    $checkin = sql_fetch_one("select * from ucheckin where uid = $uid");
    $day = intval($checkin['day']);
    $checkinnum = intval($checkin['checkinnum']);
    $month = intval(date("m"));
    $cfgreward = sql_fetch_one("select * from cfg_checkinreward where day = $checkinnum + 1 and month=$month");
    if (! $cfgreward) {
        return array(
            0,
            STR_Param_Error
        );
    }
    $lasttime = intval($checkin['time']);
    $nowtime = time();
    $lastday = intval(date("d", $lasttime));
    $nowday = intval(date("d", $nowtime));
    
    if ($lastday == $nowday) {
        return array(
            0,
            STR_Tomorrow
        );
    } else {
        $itemid = $cfgreward["itemid"];
        $itemnum = $cfgreward["quantity"];
        $type = $cfgreward["type"];
        // 如果VIP条件满足奖励双倍
        $needVip = $cfgreward["needVip"];
        $viplv = sql_fetch_one_cell("select vip from uinfo where uid=$uid");
        if ($viplv >= $needVip && $needVip != 0) {
            $itemnum = $itemnum * 2;
        }
        
        $items = array(
            $itemid,
            $itemnum
        );
        $coin = 0;
        $ug = 0;
        $equip = array();
        $ret = array();
        $itemarr = array();
        
        if ($itemid == 1) {
            _addCoin($uid, $itemnum, "签到奖励");
            $itemarr = $items;
        } elseif ($itemid == 2) {
            _addUg($uid, $itemnum, "签到奖励");
            $itemarr = $items;
        } elseif ($type == 15) {
            // 检测背包容量
            $ubag = sql_fetch_one("select bag from uinfo where uid=$uid");
            $bag = max(array(
                $ubag['bag'],
                50
            ));
            $cntBag = sql_fetch_one_cell("select count(*) from ubag where euser=0 and uid=$uid");
            $bagFree = $bag - $cntBag;
       /*     if ($bagFree <= 0) {
                return array(
                    0,
                    STR_BAG_NotEnough
                );
            }*/
            
            for ($i = 0; $i < $itemnum; $i ++) {
                $equipdate = _createEquipByceid($uid, $itemid, 1, 0);
                array_push($equip, $equipdate);
            }
        } elseif ($type < 16) {
            $itemcfg = sql_fetch_one("select * from cfg_item where itemid=$itemid");
            if (! $itemcfg) {
                return array(
                    0,
                    STR_Param_Error
                );
            }
            _addItem($uid, $itemid, $itemnum, '签到奖励');
            $itemarr = $items;
        } else {
            
            // 武将背包数量
     /*       $partnerbag = intval(sql_fetch_one_cell("select partnerbag from uinfo where uid = $uid"));
            $count = intval(sql_fetch_one_cell("select count(*) from upartner where uid=$uid"));
            if ($count + $itemnum > $partnerbag) {
                return array(
                    0,
                    STR_Partner_FULL
                );
            }*/
            $ret = array();
            for ($i = 0; $i < $itemnum; $i ++) {
                $addp = array();
                $addp[] = _createPartner($uid, $itemid);
                
                if (count($addp) > 0) {
                    array_push($ret, getPartnerbyPids($uid, $addp));
                }
                // shuffle($addp);
            }
        }
        sql_update("update ucheckin set checkinnum = checkinnum + 1 ,time = UNIX_TIMESTAMP() where uid = $uid");
        /*
         * if($type == 2){
         * if (!_spendGbytype($uid, 50,"checkin")){
         * return array(
         * 0,
         * STR_UgOff
         * );
         * }
         * }
         */
        return array(
            1,
            $itemarr, // 一般道具
            $equip, // 装备
            $ret
        ) // 佣兵
;
    }
    return array(
        0,
        STR_Tomorrow
    );
}


//获取七日签到配置
function getCfgWeekCheckin($uid, $params)
{
    $cfg = sql_fetch_rows("select * from cfg_weekcheckin");
    return array(
        1,
        $cfg
    );
}

//获取七日签到数据
function getWeekCheckin($uid, $params)
{
    $checkin = sql_fetch_one("select * from uweekcheckin where uid = $uid");
    if(!$checkin){
        sql_update("insert into uweekcheckin(uid,time) values($uid,UNIX_TIMESTAMP(CURDATE()-1))");
        $checkin = sql_fetch_one("select * from uweekcheckin where uid = $uid");
        $checkin['total'] = intval($checkin['checkinnum']) + 1;
    }
    else{
        $lasttime = intval($checkin['time']);
        $lastday = intval(date("d",$lasttime));
        $nowday = intval(date("d",time()));
        if($lastday != $nowday){
            $checkin['total'] = intval($checkin['checkinnum']) + 1;
        }
        else{
            $checkin['total'] = intval($checkin['checkinnum']);
        }
    }
    return array(
        1,
        $checkin
    );
}

//七日签到
function weekCheckin($uid, $params)
{
    $checkin = sql_fetch_one("select * from uweekcheckin where uid = $uid");
    $checkinnum = intval($checkin['checkinnum']);
    $cfgreward = sql_fetch_one("select * from cfg_weekcheckin where day = $checkinnum + 1");
    if(!$cfgreward){
        return array(
            0,
            STR_Param_Error
        );
    }
    $lasttime = intval($checkin['time']);
    $lastday = intval(date("d",$lasttime));
    $nowday = intval(date("d",time()));
    if($lastday == $nowday){
        return array(
            0,
            STR_Tomorrow
        );
    }
    else{
        $itemstr = $cfgreward["items"];
        $itemarr = explode(",", $itemstr);
        $items = array();
        foreach ($itemarr as $v){
            $items[] = explode("|", $v);
        }
        sql_update("update uweekcheckin set checkinnum = checkinnum + 1 ,time = UNIX_TIMESTAMP() where uid = $uid");
        foreach ($items as $item){
            if(intval($item[2]) != 16){
                if(intval($item[0]) == 1){
                    _addCoin($uid, intval($item[1]),'每日签到奖励');
                }
                elseif(intval($item[0]) == 2){
                    _addUg($uid, intval($item[1]),'每日签到奖励');
                }
                else{
                    _addItem($uid, intval($item[0]), intval($item[1]),'每日签到奖励');
                }
                $rewarditems[] = array(intval($item[0]), intval($item[1]), intval($item[2]));
            }
            else{
                $addp[] = _createPartner($uid, intval($item[0]), intval($item[1]));
            }
        }
        if(count($addp)>0){
            $partners = getPartnerbyPids($uid, $addp);
        }
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = 1;
        $logparams[] = 0;
        activitylog($logparams);
        return array(
            1,
            $rewarditems,
            $partners
        );
    }
    return array(
        0,
        STR_Tomorrow
    );
}

//开服狂欢
function getNewServerCarnival($uid, $params)
{
    $cfg = sql_fetch_rows("select * from cfg_opencarnival");
    $ucarnival = sql_fetch_rows("select * from ucarnival where uid = $uid");
    $ucarnivaltime = sql_fetch_one("select * from ucarnivaltime where uid = $uid");
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    $serveropentime = sql_fetch_one_cell("select opentime from server_opentime");
 /*   $nowday = date("Y-m-d",time());
    $datetime1 = date_create($serveropentime);
    $datetime2 = date_create($nowday);
    $interval = date_diff($datetime1, $datetime2);
    $daydiff = $interval->format('%a');*/
    $count = count($cfg);
    if(!$ucarnivaltime){
        sql_update("insert into ucarnivaltime(uid,day,time) values($uid,1,UNIX_TIMESTAMP())");
    }
    else{
        $lasttime = intval($ucarnivaltime['time']);
        $lastday = intval(date("d",$lasttime));
        $nowtime = sql_fetch_one_cell("select UNIX_TIMESTAMP()");
        $nowday = intval(date("d",$nowtime));
        if($lastday != $nowday){
            sql_update("update ucarnivaltime set day = day + 1 , time = UNIX_TIMESTAMP() where uid = $uid");
        }
    }
    $nowday = intval(sql_fetch_one_cell("select day from ucarnivaltime where uid = $uid"));
    $upve = sql_fetch_one("select mapid,emapid from upve where uid = $uid");
    $mapid = intval($upve['mapid']);
    $emapid = intval($upve['emapid']);
    $maxplv = intval(sql_fetch_one_cell("select plv from `upartner` where uid = $uid order by plv desc"));
    $maxpskill = 0;
    $upartners = sql_fetch_rows("select skill,skilled from upartner where uid=$uid");
    foreach ($upartners as $up){
        $skills = preg_split("/[\s,]+/", $up['skill']);
        foreach ($skills as $s){
            if($s < 20000){
                $lv = intval(($s - 10000) / 1000) + 1;
                if($lv > $maxpskill){
                    $maxpskill = $lv;
                }
            }
            elseif($s >= 20000 && $s < 30000){
                $lv = intval(($s - 20000) / 1000) + 1;
                if($lv > $maxpskill){
                    $maxpskill = $lv;
                }
            }
        }
        $skilled = intval($up['skilled']);
        $slv = intval(($skilled - 30000) / 1000) + 1;
        if($slv > $maxpskill){
            $maxpskill = $slv;
        }
    }
    if($nowday < $count){
        for($day = 1; $day <= $nowday; $day++){
            foreach ($cfg as $c){
                if(intval($c['day']) == $day && ((intval($c['conditiontype']) == 1 && $ulv >= intval($c['condition'])) || 
                    (intval($c['conditiontype']) == 2 && $mapid >= intval($c['condition'])) || 
                    (intval($c['conditiontype']) == 4 && $maxplv >= intval($c['condition'])) ||
                    (intval($c['conditiontype']) == 5 && $maxpskill >= intval($c['condition'])) ||
                    (intval($c['conditiontype']) == 6 && $emapid >= intval($c['condition'])))){
                    $cfgid = $c['id'];
                    sql_update("insert ignore into ucarnival(uid,day,id) values($uid,$day,$cfgid)");
                }
            }
        }
        $ucarnival = sql_fetch_rows("select * from ucarnival where uid = $uid");
    }
    else{
        return array(
            0,
            STR_Carnival_Finish
        );
    }
    
    return array(
        1,
        $nowday,
        $ucarnival
    );
}

function getNewServerCarnivalReward($uid, $params)
{
    $cfgid = $params[0];
    $ucarnival = sql_fetch_one("select * from ucarnival where uid = $uid and id = $cfgid");
    if(!$ucarnival){
        return array(
            0,
            STR_Carnival_Error
        );
    }
    if(intval($ucarnival['isget']) == 1){
        return array(
            0,
            STR_Carnival_isGet_Reward
        );
    }
    $cfg = sql_fetch_one("select * from cfg_opencarnival where id = $cfgid");
    $itemstr = explode(',',$cfg['item']);
    $items = array();
    foreach ($itemstr as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $num = $arr[1];
            $items[] = array($id,$num);
        }
    }
    $addp = array();
    $retp = array();
    $retitems = array();
    $coin = 0;
    $ug = 0;
    if($cfg['type'] == 16){
        foreach ($items as $item){
            $addp[] = _createPartner($uid, intval($item[0]), intval($item[1]));
        }
    }
    elseif($cfg['type'] == 4){
        foreach ($items as $item){
            _addItem($uid, $item[0], $item[1], '获取开服狂欢奖励');
            $retitems[] = array($item[0], $item[1]);
        }
    }
    else{
        foreach ($items as $item){
            if (intval($item[0]) == 1){
                _addCoin($uid,intval($item[1]),'获取开服狂欢奖励');
                $coin += intval($item[1]);
            }
            elseif(intval($item[0]) == 2){
                _addUg($uid,intval($item[1]),'获取开服狂欢奖励');
                $ug += intval($item[1]);
            }
            else{
                _addItem($uid, $item[0], $item[1], '获取开服狂欢奖励');
                $retitems[] = array($item[0], $item[1]);
            } 
        }
    }
    if(count($addp)>0){
        $retp = getPartnerbyPids($uid, $addp);
    }
    sql_update("update ucarnival set isget = 1 where uid = $uid and id = $cfgid");
    return array(
        1,
        $coin,
        $ug,
        $retitems,
        $retp
    );
}

//开服基金
function getNewServerFundInfo($uid, $params)
{
    $cfg = sql_fetch_rows("select * from cfg_fund");
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid = $uid"));
    $ufund = sql_fetch_one("select * from ufund where uid = $uid");
    if($ufund){
        foreach ($cfg as $c){
            if($ulv >= intval($c['lv'])){
                $cfglv = $c['lv'];
                sql_update("insert ignore into ufundreward(uid,lv) values($uid,$cfglv)");
            }
        }
    }
    $ufundreward = sql_fetch_rows("select * from ufundreward where uid = $uid");
    return array(
        1,
        $ufundreward
    );
}

//购买开服基金
function buyNewServerFund($uid, $params)
{
    $vip = intval(sql_fetch_one_cell("select vip from uinfo where uid = $uid"));
    if($vip < 2){
        return array(
            0,
            STR_Club_VIP_Not_Enough
        );
    }
    $ufund = sql_fetch_one("select * from ufund where uid = $uid");
    if($ufund){
        return array(
            0,
            STR_FUND_IS_BUY
        );
    }
    if(!_spendGbytype($uid, 1000, "购买开服基金")){
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("insert into ufund(uid, money)values($uid, 1000)");
    return array(
        1
    );
}

//获取开服基金奖励
function getNewServerFundReward($uid, $params)
{
    $cfglv = $params[0];
    $cfg = sql_fetch_one("select * from cfg_fund where lv = $cfglv");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ufund = sql_fetch_one("select * from ufundreward where uid = $uid and lv = $cfglv");
    if(!$ufund){
        return array(
            0,
            STR_FUND_ERROR
        );
    }
    if(intval($ufund['isget']) == 1){
        return array(
            0,
            STR_FUND_IS_GET_REWARD
        );
    }
    $ug = intval($cfg['diamods']);
    if($ug > 0){
        _addUg($uid, $ug);
    }
    sql_update("update ufundreward set isget = 1 where uid = $uid and lv = $cfglv");
    return array(
        1,
        $ug
    );
}



?>