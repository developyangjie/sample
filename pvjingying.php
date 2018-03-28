<?php

/**
 * 接口：获取精英数据
 * @param $uid
 * @param $params ['mapid']
 * @return array
 */
function getjingyinginfo($uid, $params)
{
    $res = sql_fetch_rows("select * from ujingying where uid=$uid");
    if (!$res){
        sql_update("insert into ujingying (uid) values ('$uid')");
        $res = sql_fetch_rows("select * from ujingying where uid=$uid");
    }
    return array(
            1,
            $res
    );
}

function getjingyingcfg($params)
{
    $res = sql_fetch_rows("select * from cfg_jingying");
    $res2 = sql_fetch_rows("select * from cfg_jingyingboss");
    return array(
            1,
            $res,
            $res2
    );
}

function getguanghuancfg($params)
{
    $res = sql_fetch_rows("select * from cfg_guanghuan");
    $res2 = sql_fetch_rows("select * from cfg_partnerlv");
    return array(
            1,
            $res,
            $res2
    );
}

/**
 * 接口：挑战精英
 * @param $uid
 * @param $params ['mapid']
 * @return array
 */
function pvjingying($uid, $params)
{
    $mapid = intval($params[0]);
    $monsterinfo = sql_fetch_one("select * from cfg_jingying where copyid='$mapid' limit 1");
    if (!$monsterinfo){
        return array(
                0,
                STR_DataErr
        );
    }

    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $jingyinginfo = sql_fetch_one("select * from ujingying where uid=$uid");
    if (!$myinfo || !$jingyinginfo){
        return array(
                0,
                STR_DataErr2
        );
    }
    $mylv = intval($myinfo['ulv']);
    $ujob = intval($myinfo['ujob']);
    //!当前副本挑战情况
    $curcopyid = preg_split("/[\s,]+/", $jingyinginfo['curcopyid']);
    $copyinfo = array();
    if (count($curcopyid) > 0) {
        foreach ($curcopyid as $p) {
            $pdetail = preg_split("/[\s|]+/", $p);
            if (count($pdetail) == 2) {
                $copyinfo[$pdetail[0]] = $pdetail[1]; 
            }
        }
    }
    $challengetimes = intval($jingyinginfo['challengetimes']);
    
    //!等级不足或者职业不符
    if ($mylv + 5 <= intval($monsterinfo['copylv'])){
        return array(
                0,
                STR_LvOff
        );
        // Boss挑战次数不足
        
    }elseif ($challengetimes < 1) { //Boss挑战次数不足
        return array(
            0,
            STR_PVP_Boss
        );
    } elseif (array_key_exists($monsterinfo['copylv'], $copyinfo) && intval($copyinfo[$monsterinfo['copylv']]) + 1 < $mapid) { //地图未开启
        return array(
            0,
            STR_MapUnopen
        );
    }
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $oldzhanli = $myinfo['zhanli'];
    $my = new my(1, $myinfo, $myequip);
    $zhanli = $my->zhanli;
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
    
    $jobstr = "zs"; //!战士
    if ($ujob == 2){
        $jobstr = "gs"; //!弓手
    }elseif ($ujob == 3){
        $jobstr = "fs"; //!法师
    }
    $bossids = "";
    $bossskill = array();
    for($i = 1; $i <= 3; ++$i){
        $key = "boss1_$jobstr";
        if ($i == 2){
            $key = "boss2_$jobstr";
        }else if ($i == 3){
            $key = "boss3_$jobstr";
        }
        $bossinfo = explode(",", $monsterinfo[$key]);
        //!获取boss怪物ID和技能
        if (count($bossinfo) > 0 && $bossinfo[0] != 0){
            if (empty($bossids)){
                $bossids = $bossinfo[0];
            }else{
                $bossids = $bossids . "," . $bossinfo[0];
            }
            $bossskill[$bossinfo[0]] = substr($monsterinfo[$key], strpos($monsterinfo[$key], ',') + 1);
        }
    }
    $turn = 30;
    $bosses = sql_fetch_rows("select * from cfg_jingyingboss where mid in ($bossids)");
    $monsterList = array();
    for ($i = 0; $i < count($bosses); $i ++) {
        $bosses[$i]['skill'] = $bossskill[$bosses[$i]['mid']];
        $monster = new my(3, $bosses[$i], "");
        $monsterList[] = $monster;
        $turn = intval($bosses[$i]['turn']);
        // 最多打30轮
        if ($turn <= 30) {
            $turn = 30;
        }
    }
    $battle = _battle($myList, $monsterList, $turn);
    $ret = $battle[0];
    $pvelog = $battle[1];
    $battletime = $battle[2];
    $addcoin = 0;
    $addexp = 0;
    $addrune = 0;
    $newmapid = $curcopyid;
    $addequip = array();
    $additem = array();
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
        $addrune += intval($monsterinfo['rune']);
        $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
        $maxlv = min(MAXULV, intval($monsterinfo['lv']));
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvjingying');
        //!增加符文碎片
        if ($addrune > 0){
            sql_update("insert into uruneskill (uid, fragmentnum) values ($uid, $addrune) on duplicate key update fragmentnum = fragmentnum + $addrune");
        }
        
        for ($i = 1; $i <= 12; ++$i){
            $itemInfo = explode(',', $monsterinfo["drop$i"]);
            if (count($itemInfo) == 3 && $itemInfo[0] != 0 && $itemInfo[1] != 0) {
                $itemid = intval($itemInfo[0]);
                $itemnum = intval($itemInfo[1]);
                $randNum = rand(1, 10000);
                if (intval($itemInfo[2]) >= $randNum){ 
                    $equipCfg = sql_fetch_one("select * from cfg_equip where eid = $itemid");
                    if ($equipCfg){//!增加装备
                        $addequip[] = _doCreateEquipAndGet($uid, $equipCfg, 1, 0);
                    }else{ //!道具
                        _addItem($uid, $itemid, $itemnum,"pvjingying");
                        $additem[] = array("itemid"=>$itemid, "count"=>$itemnum);
                    }
                }
            }
        }
        if ($mapid == intval($copyinfo[$monsterinfo['copylv']]) + 1 || (intval($copyinfo[$monsterinfo['copylv']]) == 0 && $mapid % 9 == 1/*每个图的第一个本*/)) {
            $copyinfo[$monsterinfo['copylv']] = $mapid;
            $str = "";
            foreach ($copyinfo as $k => $v){
                if (empty($str)){
                    $str .= $k.'|'.$v;
                }else{
                    $str .= ','.$k.'|'.$v;
                }
            }
            sql_update("update ujingying set curcopyid='$str', challengetimes=challengetimes-1 where uid=$uid and challengetimes>0");
        } else {
            sql_update("update ujingying set challengetimes=challengetimes-1 where uid=$uid and challengetimes>0");
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
    }
    $r = array();
    $r[0] = $ret; // 战斗胜负,1胜利,0失败,2怪逃跑
    $r[1][0] = _simpinfo($myList); // 我的信息
    $r[1][1] = _simpinfo($monsterList); // 怪物信息
    $r[2] = $pvelog; // 战斗过程
    $r[3] = $addexp;
    $r[4] = $addcoin;
    $r[5] = $addequip;
    $r[6] = 0;
    $r[7] = 0;
    $r[8] = $additem;
    $r[9] = $addrune;
    $res = array();
    $res['log'] = $r;
    $res['type'] = 'pvjingying';
    //$res['nowmid'] = $newmapid;
    $res['stat'] = '';
    $res['battletime'] = $battletime;
    $res['msg'] = $msg;
    $bags = sql_fetch_rows("select * from ubag where uid=$uid");
    $items = sql_fetch_rows("select * from uitem where uid=$uid");
    
    return array(
        1,
        $res,
        $bags,
        $items
    );
}


/**
 * 接口：购买挑战精英次数
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function buyPvjingying($uid, $params)
{
    $uinfo = sql_fetch_one("select u.*,c.jingyingtimes from uinfo u inner join cfg_vip c on u.vip=c.vip where uid=$uid");
    $jingyinginfo = sql_fetch_one("select * from ujingying where uid=$uid");
    if (!$uinfo || !$jingyinginfo){
        return array(
                0,
                STR_DataErr2
        );
    }
    $buypvjingying = intval($uinfo['jingyingtimes']);
    $vipbuytimes = intval($jingyinginfo['vipbuytimes']);    
    $arr = array(
            50,
            100,
            100,
            200,
            200,
            200,
            300,
            300,
            300,
            300,
            400,
            400
    );
    // 购买次数不足
    if ($vipbuytimes >= $buypvjingying) {
        return array(
            0,
            STR_PVP_BuyOff
        );
    }
    
    if ($vipbuytimes >= count($arr)){
        return array(
                0,
                STR_Buy_Limit
        );
    }
    $g = $arr[$vipbuytimes];
    // 钻石不足
    if (! _spendGbytype($uid, $g, "buypvjingying")) {
        return array(
            0,
            STR_UgOff . $g
        );
    }
    $ret = sql_update("update ujingying set challengetimes=challengetimes+1,vipbuytimes=vipbuytimes+1 where uid=$uid");
    $uinfo = sql_fetch_one("SELECT * FROM ujingying WHERE uid=$uid");
    return array(
        1,
        $uinfo
    );
}

/**
 * 接口：合成进价之书
 *
 * @param
 *            $uid
 * @param $params ['mapid']
 * @return array
 */
function compositionadvanceBook($uid, $params)
{
    $bookId = strval($params[0]);
    $num = intval($params[1]);
    if ($num != 1){
        $num = 10;
    }
    $need['502'] = array(
            //!need ID，数量
            array(501, 5),    //!5个训练之书
            array(1, 50000) //!铜钱
    );
    $need['503'] = array(
            //!need ID，数量
            array(502, 3),    //!3个绿色进阶之书
            array(2, 10) //!元宝
    );
    $need['504'] = array(
            //!need ID，数量
            array(503, 3),    //!3个蓝色进阶之书
            array(2, 25) //!元宝
    );
    $need['505'] = array(
            //!need ID，数量
            array(504, 3),    //!3个紫色进阶之书
            array(2, 60) //!元宝
    );
    if (!array_key_exists($bookId, $need)){
        return array(
                0,
                STR_DataErr
        );
    }
    //!判断进阶书check
    if (! _checkGem($uid, $need[$bookId][0][0], $need[$bookId][0][1] * $num)) {
        return array(
                0,
                STR_Item_MaterialOff
        );
    }
    if ($need[$bookId][1][0] == 1){ //!扣铜钱
        if (! _spendCoin($uid, $need[$bookId][1][1] * $num, "compositionadvanceBook")) {
            return array(
                    0,
                    STR_CoinOff
            );
        }
    } else {
        if (! _spendGbytype($uid, $need[$bookId][1][1] * $num, "compositionadvanceBook")) {
            return array(
                    0,
                    STR_UgOff
            );
        }
    }
    

    //!扣除进阶书
    if (! _subItem($uid, $need[$bookId][0][0], $need[$bookId][0][1] * $num,'compositionadvanceBook')) {
        return array(
                0,
                STR_Item_MaterialOff
        );
    }
    _addItem($uid, intval($params[0]), 1 * $num, 'compositionadvanceBook');
    $items = sql_fetch_rows("select * from uitem where uid=$uid");
    return array(
            1,
            $items
    );
}

/**
 * 接口：使用进价书
 * @param $uid
 * @param $params ['itemid']
 * @return array
 */
function useadvanceBook($uid, $params)
{
    $exp['502'] = 30;
    $exp['503'] = 75;
    $exp['504'] = 150;
    $exp['505'] = 300;
    
    $args = $params[0];
    $args = preg_split("/[\s,]+/", $args);;
    $items = array();
    for ($i = 0; $i < count($args); ++$i){
        $items[intval($args[$i])] += 1;
    }
    if (count($args) > 6 || count($args) <= 0){
        return array(
                0,
                STR_DataErr
        );
    }
    //!check道具
    foreach ($items as $key => $value){
        if(!array_key_exists($key, $exp) || !_checkGem($uid, $key, $value)){
            return array(
                    0,
                    STR_Item_MaterialOff
            );
        }
    }
    
    $partnerid = intval($params[1]);
    $partneruinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    if (!$partneruinfo){
        return array(
                0,
                STR_DataErr
        );
    }
    
    $myinfo = sql_fetch_one("select u.*,c.advbooktimes from uinfo u inner join cfg_vip c on u.vip=c.vip where uid=$uid");
    $jingyinginfo = sql_fetch_one("select * from ujingying where uid=$uid");
    if (!$myinfo || !$jingyinginfo){
        return array(
                0,
                STR_DataErr2
        );
    }

    $canadvtimes = intval($myinfo['advbooktimes']);
    $usetimes = intval($jingyinginfo['useadvancetimes']) + count($args);
 //   if ($canadvtimes < $usetimes) { //次数不足
 //       return array(
  //              0,
  //              STR_No_Advance_Times
  //      );
  //  }
    $e = 0; //!增加经验
    //!扣除
    foreach ($items as $key => $value){
        _subItem($uid, $key, $value, "useadvanceBook");
        $e += ($exp[$key] * $value);
    }
    $lv = intval($partneruinfo['starlv']);
    $myexp = floatval($partneruinfo['starexp']);
    $allexp = $myexp + $e;
    $newlv = $lv;
    $lv_cfg = sql_fetch_one("select * from cfg_partnerlv where allexp<=$allexp and $allexp<maxexp");
    if ($lv_cfg) {
        $newlv = intval($lv_cfg['starlv']);
    }
    //!属性加成
    sql_update("update upartner set starlv=$newlv,starexp=starexp+$e where partnerid=$partnerid and uid=$uid");
    sql_update("update ujingying set useadvancetimes=$usetimes where uid=$uid");
    return array(
            1,
            $lv,
            $newlv,
            $allexp
    );
}


/**
 * 接口：光环激活
 * @param $uid
 * @param $params ['itemid']
 * @return array
 */
function activateguanghuan($uid, $params)
{
    return array(
            0,
            STR_DataErr
    );
    $partnerid = intval($params[0]);
    $partneruinfo = sql_fetch_one("select * from upartner where partnerid=$partnerid and uid=$uid");
    if (!$partneruinfo){
        return array(
                0,
                STR_DataErr
        );
    }
    $parnerindex = intval($partneruinfo['picindex']);
    $ghid = intval($partneruinfo['guanghuanid']);
    $ghcfg = array();
    if ($ghid == 0){
        $ghcfg = sql_fetch_one("select * from cfg_guanghuan where partnerid = $parnerindex order by id limit 1");
    } else {
        $ghcfg = sql_fetch_one("select * from cfg_guanghuan where partnerid = $parnerindex and id = $ghid + 1 order by id limit 1");
    }

    if (!$ghcfg){
        return array(
                0,
                STR_Guanghuan_Max
        );
    } elseif (intval($ghcfg['lv']) > intval($partneruinfo['starlv'])){
        return array(
                0,
                STR_Partner_Starlv_Limit
        );
    } elseif (! _spendCoin($uid, intval($ghcfg['needcoin']), "activateguanghuan")) {
        return array(
                0,
                STR_CoinOff
        );
    }
    //!更新主角属性加成
    $epAlls = array_fill(0, 51, 0);
    //!增加的属性
    $attrid = intval($ghcfg['attrid']);
    $uppoint = intval($ghcfg['uppoint']);
    $epAlls[$attrid] = $uppoint;

    // 装备本身属性--累加上去
    $uequip = sql_fetch_one("select ep from uequip where uid=$uid");
    $equipep = $uequip['ep'];
    $eparr = explode(',', $equipep);
    foreach ($eparr as $epstr) {
        $epstrarr = explode('|', $epstr);
        if (count($epstrarr) == 2 && $epstrarr[0] != 0 && $epstrarr[1] != 0) {
            $pindex = $epstrarr[0];
            $pvalue = $epstrarr[1];
            $epAlls[$pindex] += $pvalue;
        }
    }
    $realep = "0|0";
    foreach ($epAlls as $k => $v) {
        if ($v != 0) {
            $realep .= ','.$k.'|'.$v;
        }
    }
    sql_update("insert into uequip (uid,es,ep) values ('$uid','','$realep') ON DUPLICATE KEY UPDATE es='',ep='$realep'");
    $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
    
    $myequip = array();
    $myequip['ep'] = $realep;
    $myequip['skill'] = '';
    $oldzhanli = $uInfo['zhanli'];   
    $my = new my(1, $uInfo, $myequip);
    $zhanli = $my->zhanli;
    $uInfo['zhanli'] = $zhanli;
    
    sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
    sql_update("update upvp set zhanli=$zhanli where uid=$uid");   
    if ($oldzhanli < $zhanli) {
        log_newzhanli($zhanli);
    }

    //!增加的武将属性和光环id
    $epAlls = array_fill(0, 51, 0);
    $epAlls[$attrid] = $uppoint;
    // 装备本身属性--累加上去
    $equipep = $partneruinfo['partnerep'];
    $eparr = explode(',', $equipep);
    foreach ($eparr as $epstr) {
        $epstrarr = explode('|', $epstr);
        if (count($epstrarr) == 2 && $epstrarr[0] != 0 && $epstrarr[1] != 0) {
            $pindex = $epstrarr[0];
            $pvalue = $epstrarr[1];
            $epAlls[$pindex] += $pvalue;
        }
    }
    $realep = "0|0";
    foreach ($epAlls as $k => $v) {
        if ($v != 0) {
            $realep .= ','.$k.'|'.$v;
        }
    }
    $ghid = intval($ghcfg['id']);
 //   sql_update("update upartner set guanghuanid=$ghid, partnerep ='$realep' where partnerid=$partnerid and uid=$uid");
 //   $partneruinfo['partnerep'] = $realep;
    $combineadd = array();
    
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['stagepartner'];
    $pids = explode(",", $partnerid);
    if (in_array(intval($partneruinfo['partnerid']), $pids)){
    
        $res = sql_fetch_rows("select * from upartner where uid=$uid");
        $stagepartner = array();
        for($i = 0; $i < count($res); $i ++) {
            $pinfo = $res[$i];
            if (in_array(intval($pinfo['partnerid']), $pids)){
                $stagepartner[] = $pinfo;
            }
        }
        $combineadd = getcombineaddinfo($stagepartner);
    }
    $partner = new my(4, $partneruinfo, intval($uInfo['ulv']), $combineadd);
    return array(
            1,
            $ghid,
            $uInfo,
            $my->format_to_array(),
            $partner->format_to_array()
    );
}

/**
 * 接口：快速挑战精英
 *
 * @param
 *            $uid
 * @param $params ['mapid']            
 * @return array
 */
function quickpvjingying($uid, $params)
{
    $mapid = intval($params[0]);
    $monsterinfo = sql_fetch_one("select * from cfg_jingying where copyid='$mapid' limit 1");
    if (!$monsterinfo){
        return array(
                0,
                STR_DataErr
        );
    }

    $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $jingyinginfo = sql_fetch_one("select * from ujingying where uid=$uid");
    if (!$myinfo || !$jingyinginfo){
        return array(
                0,
                STR_DataErr2
        );
    }
    $mylv = intval($myinfo['ulv']);
    $ujob = intval($myinfo['ujob']);
    //!当前副本挑战情况
    $curcopyid = preg_split("/[\s,]+/", $jingyinginfo['curcopyid']);
    $copyinfo = array();
    if (count($curcopyid) > 0) {
        foreach ($curcopyid as $p) {
            $pdetail = preg_split("/[\s|]+/", $p);
            if (count($pdetail) == 2) {
                $copyinfo[$pdetail[0]] = $pdetail[1]; 
            }
        }
    }
    $challengetimes = intval($jingyinginfo['challengetimes']);
    
    //!等级不足或者职业不符
    if (intval($myinfo['vip']) < 1){
        return array(
                0,
                STR_Club_VIP_Not_Enough
        );
    }
    elseif ($mylv + 5 <= intval($monsterinfo['copylv'])){
        return array(
                0,
                STR_LvOff
        );
        // Boss挑战次数不足     
    }elseif ($challengetimes < 1) { //Boss挑战次数不足
        return array(
            0,
            STR_PVP_Boss
        );
    } elseif (!array_key_exists($monsterinfo['copylv'], $copyinfo) || (array_key_exists($monsterinfo['copylv'], $copyinfo) && intval($copyinfo[$monsterinfo['copylv']]) < $mapid)) { //地图未开启
        return array(
            0,
            STR_MapUnopen
        );
    }
    
    //!挑战胜利
    if (true) {
        $addexp += intval($monsterinfo['exp']);
        $addcoin += intval($monsterinfo['coin']);
        $addrune += intval($monsterinfo['rune']);
        $minlv = max(1, min($mylv, intval($monsterinfo['lv']) - 5));
        $maxlv = min(MAXULV, intval($monsterinfo['lv']));
        _addExp($uid, $addexp);
        _addCoin($uid, $addcoin,'pvjingying');
        //!增加符文碎片
        if ($addrune > 0){
            sql_update("insert into uruneskill (uid, fragmentnum) values ($uid, $addrune) on duplicate key update fragmentnum = fragmentnum + $addrune");
        }
        
        for ($i = 1; $i <= 12; ++$i){
            $itemInfo = explode(',', $monsterinfo["drop$i"]);
            if (count($itemInfo) == 3 && $itemInfo[0] != 0 && $itemInfo[1] != 0) {
                $itemid = intval($itemInfo[0]);
                $itemnum = intval($itemInfo[1]);
                $randNum = rand(1, 10000);
                if (intval($itemInfo[2]) >= $randNum){ 
                    $equipCfg = sql_fetch_one("select * from cfg_equip where eid = $itemid");
                    if ($equipCfg){//!增加装备
                        $addequip[] = _doCreateEquipAndGet($uid, $equipCfg, 1, 0);
                    }else{ //!道具
                        _addItem($uid, $itemid, $itemnum,"pvjingying");
                        $additem[] = array("itemid"=>$itemid, "count"=>$itemnum);
                    }
                }
            }
        }
        
        //!更新挑战次数
        sql_update("update ujingying set challengetimes=challengetimes-1 where uid=$uid and challengetimes>0");        
        
        //! 圣诞活动
        if (checkActivityOpenById(activityTypeDef::christmasActivity)){
            $rand = rand(1, 100);
            $rate = $maxlv / $mylv * 15;
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
    $r[6] = 0;
    $r[7] = 0;
    $r[8] = $additem;
    $r[9] = $addrune;
    $res = array();
    $res['log'] = $r;
    $res['type'] = 'quickpvjingying';
    //$res['nowmid'] = 0;
    $res['stat'] = '';
    $res['battletime'] = 0;
    $res['msg'] = $msg;
    $bags = sql_fetch_rows("select * from ubag where uid=$uid");
    $items = sql_fetch_rows("select * from uitem where uid=$uid");
    
    return array(
        1,
        $res,
        $bags,
        $items
    );
}
?>