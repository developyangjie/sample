<?php

/**
 * 接口：获取邮件列表
 * @param $uid
 * @param $params []
 * @return array
 */
function getMail($uid, $params)
{
    $res = sql_fetch_rows("select *,ts+604800 as endtime from umail where uid=$uid and (ts+604800) >= UNIX_TIMESTAMP() and isdel = 0 order by mid desc");
    sql_update("update uinfo set mail=0 where uid=$uid");
    $uinfo=sql_fetch_one("select ug,vip,vippay from uinfo where uid=$uid");
    return array(
        1,
        $res,
    	$uinfo
    );
}

/**
 * 接口：删除邮件
 * 
 * @param
 *            $uid
 * @param $params ['mid']            
 * @return array
 */
function delMail($uid, $params)
{
    $mid = intval($params[0]);
    $ret = sql_update("update umail set isdel=1, isget=1 where mid=$mid and uid=$uid");
    $mailcnt = intval(sql_fetch_one_cell("select count(*) from umail where uid=$uid and isdel=0"));
    if ($mailcnt <= 0) {
        sql_update("update uinfo set mail=0 where uid=$uid");
    }
    return array(
        $ret
    );
}

/**
 * 接口：获取邮件奖励
 * 
 * @param
 *            $uid
 * @param $params ['mid']            
 * @return array
 */
function getMailReward($uid, $params)
{
    $mid = intval($params[0]);
    $mail = sql_fetch_one("select * from umail where mid=$mid and uid=$uid and isget=0");
    if (! $mail) {
        return array(
            0,
            STR_Mail_NotExist
        );
    }
    $mtype = intval($mail['mtype']);
    $ug = intval($mail['ug']);
    $ucoin = intval($mail['ucoin']);
    $honor = intval($mail['honor']);
    $itemid = intval($mail['itemid']);
    $count = intval($mail['count']);
    $itemid1 = intval($mail['itemid1']);
    $count1 = intval($mail['count1']);
    $partners = $mail['partners'];
    $retp = array();
    $equip = array(
        0
    );
    $items = array();
    if ($ug > 0 || $ucoin > 0 || $honor > 0 || $itemid > 0||partners!='') {
        $ret = sql_update("update umail set isdel=1,isget=1 where mid=$mid and uid=$uid");
        if (! $ret) {
            return array(
                0,
                STR_Mail_Received
            );
        }
        $mailcnt = intval(sql_fetch_one_cell("select count(*) from umail where uid=$uid and isget=0"));
        $mail = 1;
        if ($mailcnt <= 0) {
            $mail = 0;
        }
        sql_update("update uinfo set ug=ug+$ug,ucoin=ucoin+$ucoin,mail=$mail where uid=$uid");
        if ($honor > 0) {
            _addHonor($uid, $honor);
        }
        if ($itemid > 0 && $count > 0) {
            _addItem($uid, $itemid, $count,'邮件奖励');
            $item = array();
            $item['itemid'] = $itemid;
            $item['count'] = $count;
            $items[] = $item;
        }
        
        if ($itemid1 > 0 && $count1 > 0) {
            _addItem($uid, $itemid1, $count1,'邮件奖励');
            $item = array();
            $item['itemid'] = $itemid1;
            $item['count'] = $count1;
            $items[] = $item;
        }
        if($partners){
            $pids = explode(",", $partners);
            if(count($pids) > 0){
                foreach ($pids as $p){
                    $addid[] = _createPartner($uid, $p, 1);
                }
            }
            if(count($addid) > 0){
                $retp[] = getPartnerbyPids($uid, $addid);
            }
        }
    } else {
     //   sql_update("update umail set isdel=1,isget=1 where mid=$mid and uid=$uid");
        $mailcnt = intval(sql_fetch_one_cell("select count(*) from umail where uid=$uid and isget=0"));
        if ($mailcnt <= 0) {
            sql_update("update uinfo set mail=0 where uid=$uid");
        }
    }
    return array(
        1,
        $ug,
        $ucoin,
        $retp,
        $equip,
        $items,
        $honor
    );
}

function _addMail($uid,$mtitle, $mcontent, $ug, $ucoin, $honor, $itemid = 0, $itemcount = 0, $itemid1 = 0, $itemcount1 = 0, $partners = '')
{
    if($ug > 0 || $ucoin > 0 || $honor > 0 || $itemid > 0 || $itemcount > 0 || $itemid1 > 0 || $itemcount1 > 0){
        sql_update("insert into umail (uid,mtitle,mcontent,ug,ucoin,honor,ts,itemid,count,itemid1,count1,partners,system) values ($uid,'$mtitle','$mcontent',$ug,$ucoin,$honor,UNIX_TIMESTAMP(),$itemid,$itemcount,$itemid1,$itemcount1,'$partners',1)");
        sql_update("update uinfo set mail=1 where uid=$uid");
    }
    else{
        sql_update("insert into umail (uid,mtitle,mcontent,ug,ucoin,honor,ts,itemid,count,itemid1,count1,partners,system) values ($uid,'$mtitle','$mcontent',$ug,$ucoin,$honor,UNIX_TIMESTAMP(),$itemid,$itemcount,$itemid1,$itemcount1,'$partners',0)");
        sql_update("update uinfo set mail=1 where uid=$uid");
    }
}

/**
 * 接口：设置邮件为已读
 *
 * @param
 *            $uid
 * @param $params ['mid']
 * @return array
 */
function setReadMail($uid,$params)
{
    $mid = intval($params[0]);
    sql_update("update umail set isget=1 where mid=$mid");
    return array(
        1
    );
}

/**
 * 接口：一键获取邮件奖励
 *
 * @param
 *            $uid
 * @param $params 
 * @return array
 */
function oneKeyGetReadMail($uid,$params)
{
    $ret = array();
    $mailinfos = sql_fetch_rows("select * from umail where uid=$uid and (ts+604800) >= UNIX_TIMESTAMP() and isget=0 and system=1");
    if(!$mailinfos){
        return array(
            0,
            STR_Not_Mail_Received
        );
    }
    foreach ($mailinfos as $mail) {
        $mailArr = array(intval($mail['mid']));
        $ret[] = getMailReward($uid,$mailArr);
    } 
    sql_update("update umail set isdel=1,isget=1 where uid=$uid and system=1");
    return array(
        1,
        $ret
    );
}
