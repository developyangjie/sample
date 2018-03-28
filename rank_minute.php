<?php

// ----------------------------------
// 独立cron脚本，用来处理游戏事件逻辑：
// ----------------------------------
require_once 'db.php';
require_once 'platformdb.php';
require_once 'battle.php';
require_once 'log.php';
require_once 'my_new.php';
require_once 's_user_new.php';
require_once 'boss.php';
require_once 'club.php';
require_once("CronFramework.php");
date_default_timezone_set('Asia/Shanghai');
require_once 'mail.php';
//require_once 'getui.php';
define('CRONVERSION', '2015-01-12 14:40:17', true);
$scheduleTable = array();
// 10秒
$scheduleTable[10] = array(
);

// 每1分钟
$scheduleTable[60] = array(

);

// 每2分钟
$scheduleTable[120] = array(

);
// 每5分钟
$scheduleTable[300] = array(
    'autoDeleteMail'
);
// 每30分钟
$scheduleTable[1800] = array(
    'topRank'
);

// 5:00:00
$scheduleTable[18000] = array(
    'updateClubData',
    'updateutmap'
);

// 9:00:00
$scheduleTable[32400] = array(
    'initClubBattleData'
);

// 10:00:00
$scheduleTable[36000] = array(
    'updateClubBattleData',
    'updateClubtask'
);

// 12:00:00
$scheduleTable[43200] = array(
    'sendNoonMail'
);

// 团战公告
// 13:03:41
$scheduleTable[47021] = array(

);
// 14:03:41
$scheduleTable[50621] = array(

);

//18:00:00
$scheduleTable[64800] = array(
    'sendNightMail'
);

// 团战公告
// 19:03:41
$scheduleTable[68621] = array(

);

// 公会战公告
// 20:30:00
$scheduleTable[73800] = array(

);
// 开启公会战
// 21:00:00
$scheduleTable[75600] = array(
    'sendMidnightMail'
);

// 20:03:41
$scheduleTable[72221] = array(

);
// 21:04:11
$scheduleTable[75851] = array(

);

// 21:00:31
$scheduleTable[75631] = array(
    'DoDAY'
);
// 23:00:21
$scheduleTable[81821] = array(

);
// 23:59:30
$scheduleTable[86370] = array(
    'updateClubBossBattle'
);
// 23:59:59
$scheduleTable[86439] = array(
    'doResetDonate',
    'doSendRewardAct8',
    'doResetDailyTask',
    'doResetShop',
    'doResetSweep',
    'doResetDraw',
    'doResetFriendGood',
    'doResetClubData',
    'doResetBuyCount',
    'doResetPvpCount',
    'doResetTreasure'
);
cronLog( "数据库：".DB_DATABASE);
$mid = 100000;
startcron();
// 重新计算排行榜
topRank();
cronLog("start ...");
// 定时任务执行
runCron($scheduleTable);
cronLog("end ...");


function startcron()
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    $lt = CRONVERSION;
  //  sql_update("insert into server_status (sid,cronstart,cronlast,`localtime`) values (1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'$lt') on duplicate key update cronstart=UNIX_TIMESTAMP(),`localtime`='$lt'");
}

// 每2分钟更新服务器状态
function doreport()
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    $lt = (intval($localTime['tm_year']) - 100) . "-" . (intval($localTime['tm_mon']) + 1) . "-" . $localTime['tm_mday'] . " " . $localTime['tm_hour'] . ":" . $localTime["tm_min"] . ":" . $localTime["tm_sec"];
  //  sql_update("insert into server_status (sid,cronstart,cronlast,`localtime`) values (1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'$lt') on duplicate key update cronlast=UNIX_TIMESTAMP()");
}

// 更新公会签到信息
function updateClubData()
{
    sql_update("update sysclub set checkincount=0");
    sql_update("update uclub set schoolcount=''");
}

// 更新神魔封印
function updateutmap()
{
    sql_update("delete from utmap");
}

function initClubBattleData()
{
    $week = date("w");
    if($week == 6){
      //  sql_update("update sysclubwar set time = 0, actstate = 0, wincid = 0");
        sql_update("delete from sysclubwar");
        sql_update("update sysclubwarhole set cid = 0, uid = 0, stagepartner = '', leader = 0, girl = 0, time = 0, ts = 0, goods = '', goodtime = 0");
    }
}

function updateClubtask()
{
	$week = date("w");
	if($week == 6){
		$cid_arr=sql_fetch_one("select cid from sysclub");
		foreach ($cid_arr as $cid)
		{
			_randomclubtask($cid);	
		}	
		sql_update("delete from uclubtask");
	}
}

// 更新攻城掠地信息
function updateClubBattleData()
{
    $week = date("w");
    if($week == 6){  //活动开始
        $rank = sql_fetch_rows("select cid,zhanli from sysclub order by zhanli");
        for($i = 0; $i < count($rank); $i ++){
            $cid = 0;
            $cid = intval($rank[$i]['cid']);
            if($i % 2 == 0){
                $index = $i + 1;
                sql_insert("insert into sysclubwar (id,time,cid1) values ($index,UNIX_TIMESTAMP() + 172800 ,$cid) on duplicate key update time = UNIX_TIMESTAMP() + 172800, cid1 = $cid");
            }
            if($i % 2 == 1){
                sql_insert("insert into sysclubwar (id,cid2) values ($i,$cid) on duplicate key update cid2 = $cid");
            }
            for($j = 1; $j <= 11; $j ++){
                sql_insert("insert ignore into sysclubwarhole (id,holeid) values ($index,$j)");
            }
        }
        sql_update("update sysclubwar set actstate = 1");
    }
    if($week == 1){ //结算
        $clubwar = sql_fetch_rows("select * from sysclubwar");
        foreach($clubwar as $value){
            $id = $value['id'];
            $cid1 = $value['cid1'];
            $cid2 = $value['cid2'];
            $score1 = 0;
            $score2 = 0;
            $nowtime = time();
            $holeinfo1 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid1");
            foreach($holeinfo1 as $hole1){
                $cid1 = intval($hole1['cid']);
                $time = intval($hole1['time']);
                $mintime = intval(($nowtime - $time) / 60 / intval($hole1['needtime']));
                if($mintime > intval($hole1['limit'])){
                    $mintime = intval($hole1['limit']);
                }
                $score1 += intval($hole1['occupyscore']) + $mintime * intval($hole1['score']);
            }
            $holeinfo2 = sql_fetch_rows("select s.cid,s.time,c.* from sysclubwarhole s inner join cfg_clubwarscore c on s.holeid = c.id inner join sysclubwar w on w.id = s.id where s.cid = $cid2");
            foreach($holeinfo2 as $hole2){
                $cid2 = intval($hole2['cid']);
                $time = intval($hole2['time']);
                $mintime = intval(($nowtime - $time) / 60 / intval($hole2['needtime']));
                if($mintime > intval($hole2['limit'])){
                    $mintime = intval($hole2['limit']);
                }
                $score2 += intval($hole2['occupyscore']) + $mintime * intval($hole2['score']);
            }
            if($score1 > $score2){
                sql_update("update `sysclubwar` set wincid = $cid1 where id = $id");
            }
            elseif($score1 < $score2){
                sql_update("update `sysclubwar` set wincid = $cid2 where id = $id");
            }
        }
        sql_update("update sysclubwar set actstate = 2");
    }
}

//更新公会boss战数据
function updateClubBossBattle()
{
    $week = date("w");
    if($week == 6){
        sql_update("update sysclub set bosshp=''");
        sql_update("update uclub set schoolreward=''");
    }
}


function DoMail()
{
    sql_update("update uinfo set mail=1");
}

/**
 * 竞技场排名奖励
 */
function DoDAY()
{
    cronLog("do...");
    sql_connect();
    $doubleaward = 1;
    $pvpawardarr = sql_fetch_rows("select * from cfg_pvprankaward order by rbegin desc;");
    $firstRankUid = 0;
    for ($i = 0; $i < count($pvpawardarr); $i++) {
        $awardarr = $pvpawardarr[$i];
        $minindex = intval($awardarr['rbegin']);
        $maxindex = intval($awardarr['rend']);
        $ug = intval($awardarr['ug']) * $doubleaward;
        $honor = 0;
        if(intval($awardarr['itemid']) == 7){
            $honor = intval($awardarr['itemamount']) * $doubleaward;
        }
        else{
            $itemid = intval($awardarr['itemid']);
            $count = intval($awardarr['itemamount']) * $doubleaward;
        }
        
        $mtitle = STR_PVP_AWARD_MAIL;
        $mcontent = sprintf("CONCAT('%s 日竞技场奖励：恭喜您获得竞技排行第',`index`,'名') as mcontent", date("Y-m-d"));
        if ($doubleaward > 1) {
            $mcontent = sprintf("CONCAT('%s 日竞技场奖励[%d 倍]：恭喜您获得竞技排行第',`index`,'名') as mcontent", date("Y-m-d"), $doubleaward);
        }
        $sql = "INSERT INTO umail (uid,mtitle,mcontent,ug,honor,itemid,count,ts,system) SELECT uid,'$mtitle' as mtitle,$mcontent,$ug as ug ,$honor as honor,$itemid as itemid,$count as `count`,UNIX_TIMESTAMP() as `ts`,1 as `system` from upvp_1 where `index`>=$minindex and `index`<=$maxindex";
        $ret = sql_update($sql);
        cronLog($minindex . "-" . $maxindex . "UG:" . $ug . "|US:" . $count . "|" . $ret);
        // 第一名
        if ($maxindex == 1) {
            $uinfo = sql_fetch_one("select * from upvp_1 where `index`=1");
            $firstRankUid = intval($uinfo['uid']);
            // 竞技之王称号
           // _addChenghao($firstRankUid, 7);
        }
        // 前20名
        if ($maxindex == 20) {
            $uinfos = sql_fetch_rows("select * from upvp_1 where `index`<=20 and not `index`=1");
            $len = count($uinfos);
            for ($j = 0; $j < $len; $j++) {
                $uid2 = $uinfos[$j]['uid'];
                // 竞技精英称号
             //   _addChenghao($uid2, 2);
            }
        }
    }
    // 系统公告
    if ($firstRankUid > 0) {
        $uinfo = sql_fetch_one("select * from uinfo where uid=$firstRankUid");
        _addSysMsg(sprintf(STR_PVP_SysMsg1,$uinfo['uname']));
    }
    // 邮件提醒
    DoMail();
    return;
}

function autoDeleteMail()
{
    $mail = sql_fetch_one("select *, UNIX_TIMESTAMP() as nts,UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE()) as checkts from umail limit 1");
    if ($mail) {
        $cts = intval($mail['checkts']);
        // 13:30~15:30, 19:30~21:30不删邮件
        if (($cts > 13.5 * 3600 && $cts < 15.5 * 3600) || ($cts > 19.5 * 3600 && $cts < 21.5 * 3600)) {
            cronLog("auto delete mail return");
            return;
        }
        $res = sql_update("delete from umail where ts < (UNIX_TIMESTAMP() - 86400*7)");
        cronLog("auto delete $res mails ok");
    }
}

function doResetDonate() {
    cronLog("reset club donate");
    sql_update("update uclub set donate=0");
    cronLog("reset club donate end");
}

function doSendRewardAct8() {

}

function doResetDailyTask() {
    cronLog("reset daily task");
    sql_update("update udailytask set ts=UNIX_TIMESTAMP() + 2,process = 0,isGet=0");
	//刷新月卡
	$date=sql_fetch_rows("select * from umonthcard");
	foreach ($date as $value)
	{
		$uid=intval($value['uid']);
		$nowtime = time();
		$nowday = strtotime(date("Y-m-d", $nowtime));
		if($value['time']>=$nowday)
		{
			sql_update("update udailytask set ts=UNIX_TIMESTAMP() + 2,process = 1,isGet=0 where uid=$uid and tid=1019");
		}
	}
    cronLog("reset daily task end");
}

//商店购买次数刷新
function doResetShop() {
    cronLog("reset shop buycount");
    sql_update("update ushop set buys=''");
    sql_update("update uequipshop set buys='',reset = 0");
    sql_update("update upartnershop set buys='',reset = 0");
    sql_update("update uvipshop set buys=''");
    sql_update("update uhonorshop set buys=''");
    sql_update("update uclub set buys=''");
    sql_update("update ufragmentshop set buys='',reset = 0");
    cronLog("reset shop buycount end");
}

//扫荡次数刷新
function doResetSweep() {
    cronLog("reset Sweep count");
    sql_update("update upve set sweepinfo='',emapnum='',buyemapnum=''");
    cronLog("reset Sweep count end");
}

//抽卡次数刷新
function doResetDraw() {
    cronLog("reset Draw free count");
    sql_update("update udraw set free=0 where drawid != 2");
    cronLog("reset Draw free count end");
}

//清除好友点赞信息
function doResetFriendGood() {
    cronLog("reset friend good");
    sql_update("update `ufriend` set good = 0, isget = 0");
    cronLog("reset friend good end");
}

//清除公会信息
function doResetClubData() {
    cronLog("reset club");
    $week = date("w");
    if($week==6)
    {
    	$cid_arr=sql_fetch_rows("select cid from sysclub");
    	foreach ($cid_arr as $value)
    	{
    		_randomclubtask($value['cid']);
    	}
    	
    }
    sql_update("update sysclub set checkincount = 0, checkinuid = ''");
    sql_update("update uclub set cinum = 0,citype = 0");
    cronLog("reset club end");
}

//清除购买信息
function doResetBuyCount() {
    cronLog("reset buynum");
    sql_update("update uinfo set buycoin=0");
    sql_update("update uinfo set buybread=0");
    sql_update("update uinfo set buykey=0");
    sql_update("update uinfo set vipreward=0");
    cronLog("reset buynum end");
}

//重置竞技场次数
function doResetPvpCount(){
    cronLog("reset pvp");
    sql_update("update uinfo set pvpreset = 0");
    sql_update("update upartnerarena_1 set count = 5");
    cronLog("reset pvp end");
}

function doResetTreasure(){
    cronLog("reset treasure");
    sql_update("update uinfo set treasure = 0");
    cronLog("reset treasure end");
}

function topRank()
{
    try {

    } catch (Exception $e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}

function sendNoonMail()
{
    $title="中午面包";
    $text="莎莎亲手烘好的面包出炉啦，凉了就不好了";
    $uids = sql_fetch_rows("select uid from uinfo");
    foreach ($uids as $v){
        $uid = $v['uid'];
        _addMail($uid, $title, $text, 0, 0, 0, 102, 1);
    }
    pushMessageToApp($title, $text);
}

function sendNightMail()
{
    $title="晚上面包";
    $text="晚餐前吃点莎莎亲手做的面包吧";
    $uids = sql_fetch_rows("select uid from uinfo");
    foreach ($uids as $v){
        $uid = $v['uid'];
        _addMail($uid, $title, $text, 0, 0, 0, 102, 1);
    }
    pushMessageToApp($title, $text);
}

function sendMidnightMail()
{
    $title="夜宵面包";
    $text="肚子又饿了，陪莎莎一起吃点夜宵吧";
    $uids = sql_fetch_rows("select uid from uinfo");
    foreach ($uids as $v){
        $uid = $v['uid'];
        _addMail($uid, $title, $text, 0, 0, 0, 102, 1);
    }
    pushMessageToApp($title, $text);
}

?>