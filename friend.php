<?php

function _getFriendLimit($ulv, $vip)
{
    $ulv = intval($ulv);
    $vip = intval($vip);
    return max(floor(($ulv - 20) / 4), 0) + 10;
}

//获取要加入好友信息
function getFriendInfo($uid, $params)
{
    $frobot = sql_fetch_one("select * from ufriend where uid=$uid and fuid=140737");
    if(!$frobot){
        $fid = array(140737,0);
        addFriend($uid, $fid);
    }
    
    $uinfo = sql_fetch_one("select ulv,serverid from uinfo where uid = $uid");
    $ulv=$uinfo['ulv'];
    $serverid = $uinfo['serverid'];
    $res = array();
/*    $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,pvetime from uinfo where uid in (140681,140678,140686,140684)");
    if($uinfos){
        foreach ($uinfos as $u){
            $res[] = $u;
        }
    }*/
    $ufidarr = array();
    $ufids = sql_fetch_rows("select fuid from ufriend where uid = $uid");
    $ureqfids = sql_fetch_rows("select fuid from ufriendreq where uid = $uid");
    if($ureqfids){
        $ufids = array_merge($ufids,$ureqfids);
    }
    foreach ($ufids as $ufid){
        $ufidarr[] = $ufid['fuid'];
    }
    $ufidstr = implode(",", $ufidarr);
    if($ufidstr){
        $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv = $ulv + 20 and uid != $uid and uid not in ($ufidstr) and serverid = $serverid order by rand() limit 4");
    }
    else{
        $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv = $ulv + 20 and uid != $uid and serverid = $serverid order by rand() limit 4");
    }
    if($uinfos){
        foreach ($uinfos as $u){
            $res[] = $u;
        }
    }
    if(count($res) < 4){
        $ucount = 4 - count($res);
        if($ufidstr){
            $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv > $ulv and uid != $uid and uid not in ($ufidstr) and serverid = $serverid order by rand() limit $ucount");
        }
        else{
            $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv > $ulv and uid != $uid and serverid = $serverid order by rand() limit $ucount");
        }
        if($uinfos){
            foreach ($uinfos as $u){
                $res[] = $u;
            }
        }
        if(count($res) < 4){
            $ucount = 4 - count($res);
            if($ufidstr){
                $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv = $ulv and uid != $uid and uid not in ($ufidstr) and serverid = $serverid order by rand() limit $ucount");
            }
            else{
                $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv = $ulv and uid != $uid and serverid = $serverid order by rand() limit $ucount");
            }
            if($uinfos){
                foreach ($uinfos as $u){
                    $res[] = $u;
                }
            }
            if(count($res) < 4){
                $ucount = 4 - count($res);
                if($ufidstr){
                    $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv < $ulv and uid != $uid and uid not in ($ufidstr) and serverid = $serverid order by rand() limit $ucount");
                }
                else{
                    $uinfos = sql_fetch_rows("select uid,uname,ulv,vip,logintime from uinfo where ulv < $ulv and uid != $uid and serverid = $serverid order by rand() limit $ucount");
                }
                if($uinfos){
                    foreach ($uinfos as $u){
                        $res[] = $u;
                    }
                }
            }
        }
    }
    for($i = 0; $i < count($res); $i ++){
        $tuid = intval($res[$i]['uid']);
        $logintime = $res[$i]['logintime'];
        $nowtime = time();
        $days=round(($nowtime-$logintime)/3600/24);
        $res[$i]['days'] = $days;
        //判断那个服
        $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
        $upvp_num="upvp_".$serverid;
        $pvpinfo = sql_fetch_one("SELECT u.index,u.zhanli FROM $upvp_num u WHERE u.uid = $tuid");
        $res[$i]['pvp'] = $pvpinfo;
        $partners = sql_fetch_one("select pvpstagepartner,pvpleader,brave from uequip where uid = $tuid");
        $pvpstagepartner = $partners['pvpstagepartner'];
        $leader = intval($partners['pvpleader']);
        $brave = intval($partners['brave']);
        if(!empty($pvpstagepartner)){
            $parterids = sql_fetch_rows("select partnerid,pid from upartner where partnerid in ($pvpstagepartner)");
            $pids = array();
            $pcids = array();
            foreach ($parterids as $partner){
                $pids[] = $partner['partnerid'];
                $pcids[] = $partner['pid'];
            }
            $res[$i]['partnerid'] = $pids;
            $res[$i]['partners'] = $pcids;
        }
        $leaders = sql_fetch_one("select partnerid,pid from upartner where partnerid = $leader");
        $res[$i]['leaderid'] = $leaders['partnerid'];
        $res[$i]['leader'] = $leaders['pid'];
        $braveid = sql_fetch_one("select pid from upartner where partnerid = $brave");
        $res[$i]['brave'] = $braveid;
        $sysclub = sql_fetch_one("select s.cid,s.cname,s.count,s.maxcount,s.clv,s.activity from uclub c inner join sysclub s on c.cid=s.cid where c.uid = $tuid");
        $res[$i]['club'] = $sysclub;
        if($sysclub){
            $cid = $sysclub['cid'];
            $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid and uid = $uid");
            if($clubreq){
                $res[$i]['club']['join'] = 1;
            }
            else{
                $res[$i]['club']['join'] = 0;
            }
        }
    }
    return array(
        1,
        $res
    );
}

//查找好友
function findFriendInfo($uid, $params)
{
	$serverid = intval(sql_fetch_one_cell("select serverid from uinfo where uid = $uid"));
	
    $muid = intval($params[0]);
    $uinfo = sql_fetch_one("select uid,uname,ulv,vip,logintime from uinfo where uid = $muid and serverid=$serverid");
    if($uinfo){
        $logintime = $uinfo['logintime'];
        $nowtime = time();
        $days=round(($nowtime-$logintime)/3600/24);
        $uinfo['days'] = $days;
        //判断那个服
        $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
        $upvp_num="upvp_".$serverid;
        $pvpinfo = sql_fetch_one("SELECT u.index,u.zhanli FROM $upvp_num u WHERE u.uid = $muid");
        $uinfo['pvp'] = $pvpinfo;
        $partners = sql_fetch_one("select pvpstagepartner,pvpleader,brave from uequip where uid = $muid");
        $pvpstagepartner = $partners['pvpstagepartner'];
        $leader = intval($partners['pvpleader']);
        $brave = intval($partners['brave']);
        if(!empty($pvpstagepartner)){
            $parterids = sql_fetch_rows("select partnerid,pid from upartner where partnerid in ($pvpstagepartner)");
            $pids = array();
            $pcids = array();
            foreach ($parterids as $partner){
                $pids[] = $partner['partnerid'];
                $pcids[] = $partner['pid'];
            }
            $uinfo['partnerid'] = $pids;
            $uinfo['partners'] = $pcids;
        }
        $leaders = sql_fetch_one("select partnerid,pid from upartner where partnerid = $leader");
        $uinfo['leaderid'] = $leaders['partnerid'];
        $uinfo['leader'] = $leaders['pid'];
        $braveid = sql_fetch_one("select pid from upartner where partnerid = $brave");
        $uinfo['brave'] = $braveid;
        $sysclub = sql_fetch_one("select s.cid,s.cname,s.count,s.maxcount,s.clv,s.activity from uclub c inner join sysclub s on c.cid=s.cid where c.uid = $muid");
        $uinfo['club'] = $sysclub;
        if($sysclub){
            $cid = $sysclub['cid'];
            $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid and uid = $uid");
            if($clubreq){
                $uinfo['club']['join'] = 1;
            }
            else{
                $uinfo['club']['join'] = 0;
            }
        }
        return array(
            1,
            $uinfo
        );
    }
    else{
        return array(
            0,
            STR_ID_Error
        );
    }
}


/**
 * 接口：好友列表
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function friendList($uid, $params)
{
    $friends = sql_fetch_rows("select u.uid,u.uname,u.ulv,u.vip,u.zhanli,u.logintime from ufriend f inner join uinfo u on f.fuid=u.uid where f.uid=$uid");
    if (! $friends) {
        return array(
            1,
            array()
        );
    }
    for($i = 0; $i < count($friends); $i ++){
        $tuid = intval($friends[$i]['uid']);
        $logintime = $friends[$i]['logintime'];
        $nowtime = time();
        $days=round(($nowtime-$logintime)/3600/24);
        $good = _isFriendGood($uid,$tuid);
        $friends[$i]['good'] = $good;
        $friends[$i]['days'] = $days;
        //判断那个服
        $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
        $upvp_num="upvp_".$serverid;
        $pvpinfo = sql_fetch_one("SELECT u.index,u.zhanli FROM  $upvp_num u WHERE u.uid = $tuid");
        $friends[$i]['pvp'] = $pvpinfo;
        $partners = sql_fetch_one("select pvpstagepartner,pvpleader,brave from uequip where uid = $tuid");
        $pvpstagepartner = $partners['pvpstagepartner'];
        $leader = intval($partners['pvpleader']);
        $brave = intval($partners['brave']);
        if(!empty($pvpstagepartner)){
            $parterids = sql_fetch_rows("select partnerid,pid from upartner where partnerid in ($pvpstagepartner)");
            $pids = array();
            $pcids = array();
            foreach ($parterids as $partner){
                $pids[] = $partner['partnerid'];
                $pcids[] = $partner['pid'];
            }
            $friends[$i]['partnerid'] = $pids;
            $friends[$i]['partners'] = $pcids;
        }
        $leaders = sql_fetch_one("select partnerid,pid from upartner where partnerid = $leader");
        $friends[$i]['leaderid'] = $leaders['partnerid'];
        $friends[$i]['leader'] = $leaders['pid'];
        $braveid = sql_fetch_one("select pid from upartner where partnerid = $brave");
        $friends[$i]['brave'] = $braveid;
        $sysclub = sql_fetch_one("select s.cid,s.cname,s.count,s.maxcount,s.clv,s.activity from uclub c inner join sysclub s on c.cid=s.cid where c.uid = $tuid");
        $friends[$i]['club'] = $sysclub;
        if($sysclub){
            $cid = $sysclub['cid'];
            $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid and uid = $uid");
            if($clubreq){
                $friends[$i]['club']['join'] = 1;
            }
            else{
                $friends[$i]['club']['join'] = 0;
            }
        }
    }
    return array(
        1,
        $friends
    );
}

/**
 * 接口：好友请求列表
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function friendReqlist($uid, $params)
{
    sql_update("delete from ufriendreq where uid=$uid and ts<UNIX_TIMESTAMP()-86400*7");
    $friends = sql_fetch_rows("select u.uid,u.uname,u.ulv,u.zhanli,u.logintime,f.ts,u.vip from ufriendreq f inner join uinfo u on f.fuid=u.uid where f.uid=$uid");
    if (! $friends) {
        return array(
            1,
            array()
        );
    }
    for($i = 0; $i < count($friends); $i ++){
        $tuid = intval($friends[$i]['uid']);
        $logintime = $friends[$i]['logintime'];
        $nowtime = time();
        $days=round(($nowtime-$logintime)/3600/24);
        $friends[$i]['days'] = $days;
        //判断那个服
        $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
        $upvp_num="upvp_".$serverid;
        $pvpinfo = sql_fetch_one("SELECT u.index,u.zhanli FROM $upvp_num u WHERE u.uid = $tuid");
        $friends[$i]['pvp'] = $pvpinfo;
        $partners = sql_fetch_one("select pvpstagepartner,pvpleader,brave from uequip where uid = $tuid");
        $pvpstagepartner = $partners['pvpstagepartner'];
        $leader = intval($partners['pvpleader']);
        $brave = intval($partners['brave']);
        if(!empty($pvpstagepartner)){
            $parterids = sql_fetch_rows("select partnerid,pid from upartner where partnerid in ($pvpstagepartner)");
            $pids = array();
            $pcids = array();
            foreach ($parterids as $partner){
                $pids[] = $partner['partnerid'];
                $pcids[] = $partner['pid'];
            }
            $friends[$i]['partnerid'] = $pids;
            $friends[$i]['partners'] = $pcids;
        }
        $leaders= sql_fetch_one("select partnerid,pid from upartner where partnerid = $leader");
        $friends[$i]['leaderid'] = $leaders['partner'];
        $friends[$i]['leader'] = $leaders['pid'];
        $braveid = sql_fetch_one("select pid from upartner where partnerid = $brave");
        $friends[$i]['brave'] = $braveid;
        $sysclub = sql_fetch_one("select s.cid,s.cname,s.count,s.maxcount,s.clv,s.activity from uclub c inner join sysclub s on c.cid=s.cid where c.uid = $tuid");
        $friends[$i]['club'] = $sysclub;
        if($sysclub){
            $cid = $sysclub['cid'];
            $clubreq = sql_fetch_one("select * from sysclubreq where cid = $cid and uid = $uid");
            if($clubreq){
                $friends[$i]['club']['join'] = 1;
            }
            else{
                $friends[$i]['club']['join'] = 0;
            }
        }
    }
    return array(
        1,
        $friends
    );
}

//给好友点赞
function addFriendGood($uid, $params)
{
    $fuid = intval($params[0]);
    $friend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if (! $friend) {
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    $good = intval($friend['good']);
    $isget = intval($friend['isget']);
    if($good == 0 && $isget == 0){
        sql_update("update ufriend set good = 1 where uid=$uid and fuid=$fuid");
        _addBreadNum($uid, 1);
        return array(
            1,
            1
        );
    }
    _updateUTaskProcess($uid, 1011);
    return array(
        0,
        FRIEND_IN_GOOD_EXIST
    );
}

//是否点赞
function _isFriendGood($uid,$fuid)
{
    $friend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    $mfriend = sql_fetch_one("select * from ufriend where uid=$fuid and fuid=$uid");
    if($friend && $mfriend){
        $good = intval($friend['good']);
        $isget = intval($friend['isget']);
        $mgood = intval($mfriend['good']);
        if($good == 0 && $mgood == 0){
            return 0;     //无领取
        }
        elseif($good == 1 && $mgood == 0){
            return 1;     //自己已领取
        }
        elseif($good == 0 && $mgood == 1){
            return 2;     //好友已领取
        }
        elseif($good == 1 && $mgood == 1 && $isget == 0){
            return 3;     //自己可领取
        }
        elseif($good == 1 && $mgood == 1 && $isget > 0){
            return 4;     //自己已领取
        }
    }
    else{
        return 0;
    }
}

//获取点赞礼物
function getFriendGoodReward($uid,$params)
{
    $fuid = intval($params[0]);
    $friend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if (! $friend) {
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    $mfriend = sql_fetch_one("select * from ufriend where uid=$fuid and fuid=$uid");
    if($friend && $mfriend){
        $good = intval($friend['good']);
        $mgood = intval($mfriend['good']);
        $isget = intval($friend['isget']);
        if($good == 0){
            return array(
                0,
                FRIEND_NOT_GOOD
            );
        }
        elseif($good == 1 && $mgood == 1 && ($isget == 0 || $isget == 1)){
            sql_update("update ufriend set isget = 2 where uid=$uid and fuid=$fuid");
            _addItem($uid, 2001, 1, '获取点赞奖励');
            return array(
                1,
                array(10,1)
            );
        }
    }
    return array(
        0,
        FRIEND_NOT_GOOD_REWARD
    );
}

/**
 * 接口：添加好友
 *
 * @param
 *            $uid
 * @param $params ['fuid']            
 * @return array
 */
function addFriend($uid, $params)
{
    $fuid = intval($params[0]);
    $friend = sql_fetch_one("select * from ufriendreq where uid=$uid and fuid=$fuid");
    if (! $friend) {
        return array(
            0,
            FRIEND_NOT_IN_REQLIST
        );
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $limit = 50;
    $count = intval(sql_fetch_one_cell("select count(*) from ufriend where uid=$uid"));
    if ($count >= $limit) {
        return array(
            0,
            FRIEND_LIMIT
        );
    }
    // TODO 标记/删除申请
    if (! sql_update("delete from ufriendreq where uid=$uid and fuid=$fuid")) {
        return array(
            0,
            FRIEND_NOT_IN_REQLIST
        );
    }
    // 增加双方好友
    sql_update("insert ignore into ufriend (uid,fuid) values ($uid,$fuid)");
    sql_update("insert ignore into ufriend (uid,fuid) values ($fuid,$uid)");
    return array(
        1
    );
}

/**
 * 接口：向好友发送邮件
 *
 * @param
 *            $uid
 * @param $params ['fuid','content']            
 * @return array
 */
function mailToFriend($uid, $params)
{
    $fuid = intval($params[0]);
    $mcontent = _filterstr($params[1]);
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $uname = _filterstr($uinfo["uname"]);
    $mtitle = sprintf("%s", $uname);
    sql_update("insert ignore into umail (uid,mtitle,mcontent,mtype,count,ts) values ($fuid,'$mtitle','$mcontent',3,$uid,UNIX_TIMESTAMP())");
    sql_update("update uinfo set mail=1 where uid=$fuid");
    return array(
        1
    );
}

/**
 * 接口：删除好友
 *
 * @param
 *            $uid
 * @param $params ['fuid']            
 * @return array
 */
function delFriend($uid, $params)
{
    $fuid = intval($params[0]);
    $friend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if (!$friend) {
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    // TODO 删除双方
    sql_update("delete from ufriend where (uid=$uid and fuid=$fuid) or (uid=$fuid and fuid=$uid)");
    return array(
        1
    );
}

/**
 * 接口：请求加为好友
 *
 * @param
 *            $uid
 * @param $params ['fuid']            
 * @return array
 */
function reqFriend($uid, $params)
{
    $fuid = intval($params[0]);
    if ($fuid == $uid) {
        return array(
            0,
            FRIEND_REQ_YOURSELF
        );
    }
    $friend = sql_fetch_one("select * from ufriendreq where uid=$fuid and fuid=$uid");
    if ($friend) {
        return array(
            0,
            FRIEND_RE_REQ
        );
    }
    $friend2 = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if ($friend2) {
        return array(
            0,
            FRIEND_OLD
        );
    }
    $count = intval(sql_fetch_one_cell("select count(*) from ufriend where uid=$fuid"));
    if ($count >= 50) {
        return array(
            0,
            FRIEND_Count_Limit
        );
    }
    sql_update("insert ignore into ufriendreq (uid,fuid,ts) values ($fuid,$uid,UNIX_TIMESTAMP())");
    return array(
        1
    );
}

/**
 * 接口：取消好友请求
 *
 * @param
 *            $uid
 * @param $params ['fuid']            
 * @return array
 */
function delReqFriend($uid, $params)
{
    $fuid = intval($params[0]);
    if (! sql_update("delete from ufriendreq where uid=$uid and fuid=$fuid")) {
        return array(
            0,
            FRIEND_NOT_IN_REQLIST
        );
    }
    return array(
        1
    );
}

//拜访好友
function getFriendPartner($uid, $params)
{
    $fuid = $params[0];
    $friend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if (! $friend) {
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    $res = sql_fetch_rows("select * from upartner where uid=$fuid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$fuid");
    $ulv = intval($uinfo['ulv']);
        
    $myequip = sql_fetch_one("select * from uequip where uid=$fuid");
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
        if (!empty($res)) {
            for($i = 0; $i < count($res); $i ++) {
                $pinfo = $res[$i];
                $otheradd = array();
        
                if (in_array(intval($pinfo['partnerid']), $pids)){
                    $otheradd = $combineadd;
                }
                $partner = new my(4, $pinfo, $ulv, $otheradd);
        
                $res[$i]['ppppp'] = $partner->format_to_array();
                $starlv = intval($pinfo['starlv']);
                $res[$i]['pinfo'][] = getPartnerInfoByStar($fuid,intval($pinfo['partnerid']),$starlv);
                if($starlv < 10){
                    $res[$i]['pinfo'][] = getPartnerInfoByStar($fuid,intval($pinfo['partnerid']),$starlv + 1);
                }
        
                unset($res[$i]['uid']);
                //unset($res[$i]['skill']);
                unset($res[$i]['partnerbase']);
                unset($res[$i]['upep']);
                //unset($res[$i]['starlv']);
                //unset($res[$i]['starexp']);
            }
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


//生成邀请码
function getFriendCode($uid, $params)
{
    $code = sql_fetch_one_cell("select code from uinfo where uid = $uid");
    if(!$code){
        $time = time();
        $str = $uid.$time;
        $code = strtolower(substr(md5($str), 0, 12));
        sql_update("update uinfo set code = '$code' where uid = $uid");
    }
    $count = intval(sql_fetch_one_cell("select count(*) from uinfo where fcode = '$code'"));
    return array(
        1,
        $code,
        $count
    );
}

//输入邀请码
function inputFriendCode($uid, $params)
{
    $code = $params[0];
    $isexist = sql_fetch_one("select * from uinfo where code = '$code'");
    if(!$isexist){
        return array(
            0,
            FRIEND_FCODE_IS_NOT_EXIST
        );
    }  
    $fcode = sql_fetch_one_cell("select fcode from uinfo where uid = $uid");
    if(!empty($fcode)){
        return array(
            0,
            FRIEND_FCODE_ISEXIST
        );
    }
    $mycode = sql_fetch_one_cell("select code from uinfo where uid = $uid");
    if(!empty($code) && strcmp($mycode,$code) == 0){
        return array(
            0,
            FRIEND_FCODE_IS_MYSELF
        );
    }
    sql_update("update uinfo set fcode = '$code' where uid = $uid");
    $fcount = intval(sql_fetch_one_cell("select count(*) from uinfo where fcode = '$code'"));
    $fuid = intval(sql_fetch_one_cell("select uid from uinfo where code = '$code'"));
    if($fcount >= 5 && $fcount < 10){
        _addMail($fuid,"成功邀请好友5人", "成功邀请好友5人奖励", 100, 0, 0);
    }
    elseif($fcount >= 10 && $fcount < 15){
        _addMail($fuid,"成功邀请好友10人", "成功邀请好友10人奖励", 200, 0, 0);
    }
    elseif($fcount >= 15){
        _addMail($fuid,"成功邀请好友15人", "成功邀请好友15人奖励", 500, 0, 0);
    }
    return array(
        1
    );
}

//获取好友任务奖励
function getFriendTask($uid, $params)
{
    $cfgreward = sql_fetch_rows("select * from cfg_friendtask");
    $rdata = '';
    for($i = 1; $i <= count($cfgreward); $i ++){
        if(empty($rdata)){
            $rdata = "$i"."|"."0";
        }
        else{
            $rdata = "$rdata".","."$i"."|"."0";
        }
    }
    $ufreward = sql_fetch_one("select * from ufriendreward where uid = $uid");
    if(!$ufreward){
        sql_update("insert ignore into ufriendreward (uid,reward,getreward) values ($uid,'$rdata','$rdata')");
    }
    $ufreward = sql_fetch_one("select * from ufriendreward where uid = $uid");
    $rewardstr = $ufreward['reward'];
    $getrewardstr = $ufreward['getreward'];
    $code = sql_fetch_one_cell("select code from uinfo where uid = $uid");
    if(!$code){
        return array(
            1,
            $rewardstr,
            $getrewardstr
        );
    }
    $reward = array();
    $rewardarr = explode(",", $rewardstr);
    foreach ($rewardarr as $rv){
        $reward[] = explode("|", $rv);
    }
    $fcount = sql_fetch_one_cell("select count(*) from uinfo where fcode = '$code'");
    $flevel = sql_fetch_one_cell("select u.ulv from uinfo u inner join ufriend f on u.uid=f.uid where f.uid = $uid order by u.ulv desc");
    $fug = sql_fetch_one_cell("select u.ug from uinfo u inner join ufriend f on u.uid=f.uid where f.uid = $uid order by u.ug desc");
    $friends = sql_fetch_rows("select * from ufriend where uid=$uid");
    $upartnernum = 0;
    foreach ($friends as $friend){
        $fuid = intval($friend['fuid']);
        $partners = sql_fetch_rows("select pid from upartner where uid=$fuid");
        $pids = array();
        foreach ($partners as $p){
            $pid = $p['pid'];
            if(!in_array($pid, $pids)){
                $pids[] = $pid;
            }
        }
        if(count($pids) > $upartnernum){
            $upartnernum = count($pids);
        }
    }
    for($c = 0; $c < count($cfgreward); $c ++){
        if($c < 3){
            if($reward[$c][1] == 0 && $cfgreward[$c]['needtimes'] <= $fcount){
                $reward[$c][1] = 1;
            }
        }
        elseif ($c >= 3 && $c < 13){
            if($reward[$c][1] == 0 && $cfgreward[$c]['needtimes'] <= $flevel){
                $reward[$c][1] = 1;
            }
        }
        elseif ($c >= 13 && $c < 21){
            if($reward[$c][1] == 0 && $cfgreward[$c]['needtimes'] <= $fug){
                $reward[$c][1] = 1;
            }
        }
        elseif ($c >= 21){
            if($reward[$c][1] == 0 && $cfgreward[$c]['needtimes'] <= $upartnernum){
                $reward[$c][1] = 1;
            }
        }
    }
    $rewardv = array();
    foreach($reward as $re){
        $rewardv[] = implode("|", $re);
    }
    $rewardstr = implode(",", $rewardv);
    sql_update("update ufriendreward set reward = '$rewardstr' where uid = $uid");
    return array(
        1,
        $rewardstr,
        $getrewardstr,
        $fcount,
        $flevel,
        $fug,
        $upartnernum
    );
}

//获取好友任务奖励
function getFriendReward($uid, $params)
{
    $id = $params[0];
    $cfgreward = sql_fetch_rows("select * from cfg_friendtask");
    if($id > count($cfgreward)){
        return array(
            0,
            STR_DataErr
        );
    }
    $fcount = 0;
    $code = sql_fetch_one_cell("select code from uinfo where uid = $uid");
    if($code){
        $fcount = sql_fetch_one_cell("select count(*) from uinfo where fcode = '$code'");
    }
    $ufreward = sql_fetch_one("select * from ufriendreward where uid = $uid");
    $rewardstr = $ufreward['reward'];
    $getrewardstr = $ufreward['getreward'];
    $reward = array();
    $rewardarr = explode(",", $rewardstr);
    foreach ($rewardarr as $rv){
        $reward[] = explode("|", $rv);
    }
    $getreward = array();
    $getrewardarr = explode(",", $getrewardstr);
    foreach ($getrewardarr as $grv){
        $getreward[] = explode("|", $grv);
    }
    $items = array();
    if($reward[$id-1][1] == 1 && $getreward[$id-1][1] == 0){
        $cfgrewardstr = $cfgreward[$id-1]['reward'];
        $items[] = explode("|", $cfgrewardstr);
        if(count($items) > 0){
            foreach ($items as $item){
                if($item[0] == 1){
                    _addCoin($uid, intval($item[1]),'好友任务奖励');
                }
            }
            $getreward[$id-1][1] = 1;
            $getrewardv = array();
            foreach($getreward as $gv){
                $getrewardv[] = implode("|", $gv);
            }
            $getrewardstr = implode(",", $getrewardv);
            sql_update("update ufriendreward set getreward = '$getrewardstr' where uid = $uid");
        }
    }
    return array(
        1,
        $rewardstr,
        $getrewardstr,
        $items
    );
}

//获取好友代表勇者信息
function getFriendBrave($uid, $params)
{
    $finfos = sql_fetch_rows("select e.brave,UNIX_TIMESTAMP()-f.time as time,u.uname,p.* from ufriend f inner join uequip e on f.fuid=e.uid inner join uinfo u on f.fuid=u.uid inner join upartner p on p.partnerid=e.brave where f.uid = $uid");
    $brave = 0;
    $fid = intval(sql_fetch_one_cell("select fuid from uequip where uid = $uid"));
    if($fid > 0){
        $brave = intval(sql_fetch_one_cell("select f.brave from uequip e inner join ufriend f on e.fuid=f.fuid and e.uid=f.uid where e.uid=$uid"));
    }
    return array(
        1,
        $finfos,
        $brave
    );
}

//设置好友代表勇者信息
function setFriendBrave($uid, $params)
{
    $fuid = $params[0];
    $brave = intval(sql_fetch_one_cell("select brave from uequip where uid=$fuid"));
    if($brave == 0){
        return array(
            0,
            FRIEND_BRAVE_NOT_EXIST
        );
    }
    $nowtime = time();
    $ufriend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if(!$ufriend){
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    if($nowtime < (intval($ufriend['time']) + 180)){
        return array(
            0,
            FRIEND_BRAVE_TIME
        );
    }
    $fbrave = intval($ufriend['brave']);
    $fid = intval($ufriend['fuid']);
    if($fbrave > 0){
        if($nowtime > (intval($ufriend['time']) + 0)){ //86400
            sql_update("update ufriend set brave = 0 where uid=$uid and fuid=$fuid");
        }
        sql_update("update uequip set fuid = 0 where uid=$uid");
    }
    else{
        sql_update("update ufriend set brave = $brave where uid=$uid and fuid=$fuid");
        sql_update("update uequip set fuid = $fuid where uid=$uid");
    }
    if($fbrave > 0){
        return array(
            1,
            0
        );
    }
    else{
        return array(
            1,
            $brave
        );
    }
}

//获取好有PVE阵容信息
function getFriendStagepartner($uid, $params)
{
	//取出好友pve阵容，CD，好友Uid
 	$finfos = sql_fetch_rows("select e.pvestagepartner as psp,UNIX_TIMESTAMP()-f.time as time,f.fuid as fid,u.uname as name,u.zhanli as zhanli from ufriend f inner join uequip e on f.fuid=e.uid inner join uinfo u on f.fuid=u.uid where f.uid = $uid");
	
	for($i=0;count($finfos)>$i;$i++){	
		//wxltest
    	$finfos[$i]['time']=0;
    	//wxltest
		
		$fuid=$finfos[$i]['fid'];
		$partner_arr=explode(',',$finfos[$i]['psp']);
		//遍历pve阵容数组		 
		foreach ($partner_arr as $key=>$value){
			$partnerdete=sql_fetch_one("select * from upartner where partnerid=$value and uid=$fuid");
//			$partnerdete = sql_fetch_rows("select e.brave,UNIX_TIMESTAMP()-f.time as time,u.uname,p.* from ufriend f inner join uequip e on f.fuid=e.uid inner join uinfo u on f.fuid=u.uid inner join upartner p on p.partnerid=e.brave where f.uid = $uid");
			array_push($finfos[$i],$partnerdete);
		}
	}

    return array(
        1,
        $finfos,
    );
}

//设置好友PVE阵容信息
function setFriendStagepartner($uid, $params)
{
    $fuid = $params[0];
    $pvestagepartner = intval(sql_fetch_one_cell("select pvestagepartner from uequip where uid=$fuid"));
    $pvestagepartner_arr=explode(pvestagepartner, ',');
    
    if(pvestagepartner == ''){
        return array(
            0,
            FRIEND_STAGEPARTNER_NOT_EXIST
        );
    }
    $nowtime = time();
    //好友是否存在
    $ufriend = sql_fetch_one("select * from ufriend where uid=$uid and fuid=$fuid");
    if(!$ufriend){
        return array(
            0,
            FRIEND_NOT_IN_LIST
        );
    }
    if($nowtime < (intval($ufriend['time']) + 5)){
        return array(
            0,
            FRIEND_BRAVE_TIME
        );
    }
    $fstagepartner = intval($ufriend['stagepartner']);
    $fid = intval($ufriend['fuid']);
    if($fstagepartner != ''){
        if($nowtime > (intval($ufriend['worldtime']) + 0)){ //86400
            sql_update("update ufriend set stagepartner = '' where uid=$uid and fuid=$fuid");
        }
//        sql_update("update uequip set fuid = 0 where uid=$uid");
    }
    else{
        sql_update("update ufriend set stagepartner =$fstagepartner,time = UNIX_TIMESTAMP() where uid=$uid and fuid=$fuid");
//        sql_update("update uequip set fuid = $fuid where uid=$uid");
    }
    if($fstagepartner != ''){
        return array(
            1,
            0
        );
    }
    else{
        return array(
            1,
           $fstagepartner
        );
    }
}
?>