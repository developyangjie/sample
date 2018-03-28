<?php

// ----------------------------------
// 独立cron脚本，用来处理游戏事件逻辑：
// ----------------------------------
require_once 'db.php';
require_once 'config.php';
require_once 'battle.php';
require_once 'gvg.php';
require_once 'my_new.php';
require_once 's_user_new.php';
require_once 'boss.php';
require_once 'club.php';
require_once ("CronFramework.php");
date_default_timezone_set('Asia/Shanghai');
require_once 'mail.php';
define('CRONVERSION', '2014-10-13 16:05:54', true);

_restartGvg();

function _restartGvg()
{
    if (! sql_fetch_one("select * from server_gvg where dth=101514")) {
        doGvg();
    }
}

function startcron()
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    $lt = CRONVERSION;
    // $lt =(intval($localTime['tm_year'])-100)."-".(intval($localTime['tm_mon'])+1)."-".$localTime['tm_mday']." ".$localTime['tm_hour'].":".$localTime["tm_min"].":".$localTime["tm_sec"];
    sql_update("insert into server_status (sid,cronstart,cronlast,`localtime`) values (1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'$lt') on duplicate key update cronstart=UNIX_TIMESTAMP(),`localtime`='$lt'");
}

function doreport()
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    $lt = (intval($localTime['tm_year']) - 100) . "-" . (intval($localTime['tm_mon']) + 1) . "-" . $localTime['tm_mday'] . " " . $localTime['tm_hour'] . ":" . $localTime["tm_min"] . ":" . $localTime["tm_sec"];
    sql_update("insert into server_status (sid,cronstart,cronlast,`localtime`) values (1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'$lt') on duplicate key update cronlast=UNIX_TIMESTAMP()");
}

// doGvg();
function doclubboss()
{
    $clubexp = array(
        0,
        3000,
        10000,
        25000,
        64000,
        155000,
        338000,
        665000,
        1200000,
        2019000
    );
    $memberexp = array(
        2000,
        1500,
        1200,
        1000,
        900,
        800,
        700,
        600,
        500,
        400
    );
    for ($j = 1; $j < 10; $j ++) {
        $tempexp = $clubexp[$j];
        // sql_update("update sysclub set clv=clv+1,maxcount=20+2*clv-2 where clv=$j and exp>$tempexp");
    }
    $res = sql_fetch_rows("select * from sysclub where bosstemphp>0 and bossts<UNIX_TIMESTAMP()");
    foreach ($res as $sysclub) {
        $bosslv = intval($sysclub['bosslv']);
        $bosstemphp = intval($sysclub['bosstemphp']);
        $cid = intval($sysclub['cid']);
        $myinfos = sql_fetch_rows("select u.*,e.*,c.buff from uclub c inner join  uinfo u on c.uid=u.uid inner join uequip e on c.uid=e.uid where c.cid=$cid and c.bossts>0 order by bossts");
        $boss = new boss($bosslv, $bosstemphp, $bosstemphp);
        $temphp = $bosstemphp;
        $addexp = floor(($bosslv * $bosslv * 0.4 + 400) / 100) * 100;
        for ($i = 0; $i < count($myinfos) && $temphp > 0; $i ++) {
            $my = new my(5, $myinfos[$i], $myinfos[$i]);
            $uid = intval($myinfos[$i]['uid']);
            $temphp = _cBattle($my, $boss, $cid, $uid);
            if ($temphp <= 0) {
                // 打死了boss;
                // _addItem($uid, 3, 5);
                $mcontent = '经过全体公会成员的努力,你幸运的给了公会BOSS最后一击';
                $addcoin = $addexp * 100;
                _addMail($uid, $mcontent,$mcontent, 0, $addcoin, 0);
                //_addChenghao($uid, 1);
            }
        }
        sql_update("update sysclub set bosstemphp=$temphp,bossts=bossts+300 where cid=$cid");
        cronLog("boss:" . $cid . ":" . $temphp);
    }
    $res2 = sql_fetch_rows("select * from sysclub where bosshp>0 and bosstemphp<=0");
    foreach ($res2 as $bossoverclub) {
        $callts = intval($bossoverclub['callts']);
        $bossts = intval($bossoverclub['bossts']);
        $bosslv = intval($bossoverclub['bosslv']);
        $bosshp = intval($bossoverclub['bosshp']);
        $cid = intval($bossoverclub['cid']);
        $clv = intval($bossoverclub['clv']);
        $exp = intval($bossoverclub['exp']);
        $addexp = floor(($bosslv * $bosslv * 0.4 + 400) / 100) * 100; // sql_fetch_one_cell("select sum(tempscore) from uclub where cid=$cid");
                                                                      // sql_update("update uclub set score=score+LEAST(ceil(tempscore*$addexp/$bosshp),$addexp*0.4),tempscore=0,bossts=0,buff=0 where cid=$cid");
        $members = sql_fetch_rows("select * from uclub where cid=$cid and tempscore>0 order by tempscore desc");
        for ($mn = 0; $mn < count($members); $mn ++) {
            $madds1 = floor($bosslv * 0.2);
            $muid = intval($members[$mn]['uid']);
            if ($mn < 3 && $madds1 > 0) {
                $mcontent = '您在公会魔兽入侵战中获得了额外奖励';
                _addMail($muid, $mcontent,$mcontent, 0, 0, $madds1);
            }
            if ($mn < 10) {
                $maddexp = ceil($memberexp[$mn] / 10000 * $addexp);
            } else {
                $maddexp = ceil(100 / 10000 * $addexp);
            }
            sql_update("update uclub set score=score+$maddexp,totalscore=totalscore+$maddexp,tempscore=0,bossts=0,buff=0 where cid=$cid and uid=$muid");
        }
        
        $lvup = 0;
        if ($callts > 0 && $bossts > 0) {
            if (($bossts - $callts) < 1860) {
                $lvup = 10;
            } elseif (($bossts - $callts) < 3660) {
                $lvup = 5;
            }
        }
        $clvup = 0;
        if ($clv < 10 && ($exp + $addexp) > $clubexp[$clv]) {
            $clvup = 1;
        }
        sql_update("update sysclub set clv=clv+$clvup,maxcount=20+2*clv-2,bosslv=bosslv+$lvup,bosshp=0,bosstemphp=0,bossts=0,callts=0,exp=exp+$addexp where cid=$cid");
        cronLog("bossover:" . $cid . ":" . $lvup);
    }
    $res3 = sql_fetch_rows("select * from sysclub where callts>0 and callts<(UNIX_TIMESTAMP()-14430)");
    foreach ($res3 as $bossout) {
        $callts = intval($bossout['callts']);
        $bossts = intval($bossout['bossts']);
        $bosslv = intval($bossout['bosslv']);
        $bosshp = intval($bossout['bosshp']);
        $cid = intval($bossout['cid']);
        $clv = intval($bossout['clv']);
        $exp = intval($bossout['exp']);
        $addexp = floor(($bosslv * $bosslv * 0.4 + 400) / 100) * 50; // sql_fetch_one_cell("select sum(tempscore) from uclub where cid=$cid");
                                                                     // sql_update("update uclub set score=score+LEAST(ceil(tempscore*$addexp/$bosshp),$addexp*0.4),tempscore=0,bossts=0,buff=0 where cid=$cid");
        $members = sql_fetch_rows("select * from uclub where cid=$cid and tempscore>0 order by tempscore desc");
        for ($mn = 0; $mn < count($members); $mn ++) {
            $muid = intval($members[$mn]['uid']);
            if ($mn < 10) {
                $maddexp = ceil($memberexp[$mn] / 10000 * $addexp);
            } else {
                $maddexp = ceil(100 / 10000 * $addexp);
            }
            sql_update("update uclub set score=score+$maddexp,totalscore=totalscore+$maddexp,tempscore=0,bossts=0,buff=0 where cid=$cid and uid=$muid");
        }
        
        $lvup = - 10;
        // if ($callts>0&&$bossts>0&&($bossts-$callts)<7200) {
        // $lvup=1;
        // }
        $clvup = 0;
        if ($clv < 10 && ($exp + $addexp) > $clubexp[$clv]) {
            $clvup = 1;
        }
        sql_update("update sysclub set clv=clv+$clvup,maxcount=20+2*clv-2,bosslv=GREATEST(bosslv+$lvup,40),bosshp=0,bosstemphp=0,bossts=0,callts=0,exp=exp+$addexp where cid=$cid");
        cronLog("bossout:" . $cid . ":" . $lvup);
    }
}

// doGvg();
// _autocreateteam();
function _autocreateteam()
{
    $lv = rand(19, 29);
    $uids = sql_fetch_rows("select * from uinfo where ug>100 and ulv>$lv order by rand() limit 10");
    foreach ($uids as $v) {
        $uid = $v['uid'];
        $params = array();
        createGvg($uid, $params);
    }
}

function doGvg()
{
    cronLog("start gvg");
    $teams = intval(sql_fetch_one_cell("select count(*) from sysgvg where isover=0"));
    for ($j2 = 0; $j2 < 20; $j2 ++) {
        if ($teams <= pow(2, $j2)) {
            $limit = pow(2, $j2) - $teams;
            sql_update("update sysgvg set bout=bout+1 order by rand() limit $limit");
            $j2 = 20;
            break;
        }
    }
    $sleepts = rand(1000, 1300);
    sql_update("insert into server_gvg (dth,ts,teams,bout,nextts) VALUES (FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H'),UNIX_TIMESTAMP(),$teams,0,UNIX_TIMESTAMP()+$sleepts)");
    cronLog("start ok:" . $teams);
    $res = sql_fetch_rows("select * from ugvg where gid=0");
    foreach ($res as $re) {
        $ruid = $re['uid'];
        $mcontent = "很遺憾在團戰開始時, 您沒有成功匹配到隊伍, 無法參加本次團戰。期待您的下次表現";
        _addMail($ruid, $mcontent,$mcontent, 0, 0, 0);
    }
    cronLog("mail to players ok");
}

function doGvgbout()
{
    $starttime = microtime(true);
    $sinfo = sql_fetch_one("select * from server_gvg where dth=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H') and nextts<UNIX_TIMESTAMP() and isover=0");
    if ($sinfo) {
        cronLog("gvgbout:" . $sinfo['dth'] . ":" . $sinfo['bout']);
        $bout = intval($sinfo['bout']);
        $teams = intval(sql_fetch_one_cell("select count(*) from sysgvg where bout=$bout and isover=0"));
        $bt = ceil($teams / 2);
        if ($bt == 0) {
            // 可以去发奖了
            sql_update("update server_gvg set isover=1 where dth=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H')");
            return;
        }
        if ($teams == 1) {
            // 冠军;
            $gid1 = intval(sql_fetch_one_cell("select gid from sysgvg where isover=0"));
            sql_update("update sysgvg set isover=1 where gid=$gid1");
            _gvgover($gid1, $bout, 1);
            sql_update("delete from sysgvg");
            sql_update("delete from ugvg");
            sql_update("update server_gvg set isover=1 where dth=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H')");
            return;
        }
        for ($j = 0; $j < $bt; $j ++) {
            $twog = sql_fetch_rows("select gid,gname from sysgvg where bout=$bout and isover=0 order by rand() limit 2");
            if (! $twog || count($twog) == 0) {
                // 本回合结束;
                break;
            }
            if (count($twog) == 1) {
                $gid1 = $twog[0]['gid'];
                sql_update("update sysgvg set bout=bout+1 where gid=$gid1");
                break;
            }
            global $conn;
            if (rand(1, 10000) < 5000) {
                // $gname1=mysql_escape_string($twog[0]['gname']);
                // $gname2=mysql_escape_string($twog[1]['gname']);
                $gname1 = $conn->escape_string($twog[0]['gname']);
                $gname2 = $conn->escape_string($twog[1]['gname']);
                $gid1 = $twog[0]['gid'];
                $gid2 = $twog[1]['gid'];
            } else {
                $gid2 = $twog[0]['gid'];
                $gid1 = $twog[1]['gid'];
                // $gname2=mysql_escape_string($twog[0]['gname']);
                // $gname1=mysql_escape_string($twog[1]['gname']);
                $gname2 = $conn->escape_string($twog[0]['gname']);
                $gname1 = $conn->escape_string($twog[1]['gname']);
            }
            $res = _doGvg($gid1, $gid2);
            $ret1 = 0;
            $ret2 = 1;
            if ($res[0]) {
                $ret1 = 1;
                $ret2 = 0;
                $gvglogarr = array();
                $gvglogarr['win'] = $gid1;
                $gvglogarr['log'][$gid1] = $res[1];
                $gvglogarr['log'][$gid2] = $res[2];
                $gvglog = json_encode($gvglogarr);
            } else {
                $gvglogarr = array();
                $gvglogarr['win'] = $gid2;
                $gvglogarr['log'][$gid1] = $res[1];
                $gvglogarr['log'][$gid2] = $res[2];
                $gvglog = json_encode($gvglogarr);
            }
            $logid = sql_insert("insert into log_gvg (log) values ('$gvglog')");
            sql_update("insert into gvglog (gid,armgid,bout,result,logid,gname,armname) values ($gid1,$gid2,$bout,$ret1,$logid,'$gname1','$gname2')");
            sql_update("insert into gvglog (gid,armgid,bout,result,logid,gname,armname) values ($gid2,$gid1,$bout,$ret2,$logid,'$gname2','$gname1')");
            if ($ret1) {
                sql_update("update sysgvg set bout=bout+1 where gid=$gid1");
                sql_update("update sysgvg set isover=1 where gid=$gid2");
                // 发奖gid2
                _gvgover($gid2, $bout);
            } else {
                sql_update("update sysgvg set bout=bout+1 where gid=$gid2");
                sql_update("update sysgvg set isover=1 where gid=$gid1");
                ;
                // 发奖gid1
                _gvgover($gid1, $bout);
            }
        }
        $sleepts = 60;
        if ($bout == 0) {
            $sleepts = rand(250, 350);
        } elseif ($bout == 1) {
            $sleepts = rand(100, 150);
        } else {
            $sleepts = rand(40, 100);
        }
        sql_update("update server_gvg set bout=bout+1,nextts=UNIX_TIMESTAMP()+$sleepts where dth=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H')");
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        cronLog("bout:" . ($bout + 1) . " over cost:" . $lasttime);
        // 判断如果是最后一轮打完了,那么运行cvcover
    }
}

function _gvgover($gid, $bout, $isguanjun = 0)
{
    if ($isguanjun) {
        sql_update("insert into umail (uid,mcontent,count,mtype,ts) select uid,concat('冠軍戰報!你所在小隊在', FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰中經過',($bout+1),'輪戰鬥獲得了冠軍!下一場再接再厲!'),$gid,1,UNIX_TIMESTAMP() from ugvg where gid=$gid");
    } else {
        sql_update("insert into umail (uid,mcontent,count,mtype,ts) select uid,concat('最新戰報!你所在小隊在', FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰中經過了',($bout+1),'輪戰鬥,被擊敗!下一場請繼續努力!'),$gid,1,UNIX_TIMESTAMP() from ugvg where gid=$gid");
    }
    sql_update("insert into umail (uid,mcontent,ucoin,mtype,ts,system) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰隊員獎勵'),1000*u.ulv*($bout+1),0,UNIX_TIMESTAMP(),1 from ugvg g inner join uinfo u on g.uid=u.uid where g.gid=$gid");
    
    if ($bout > 0) {
        sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,mtype,ts,system) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰隊長額外獎勵'),2000*u.ulv,12,$bout,0,UNIX_TIMESTAMP(),1 from sysgvg  g inner join uinfo u on g.uid=u.uid where g.gid=$gid");
    } else {
        sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,mtype,ts,system) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰隊長額外獎勵'),2000*u.ulv,0,$bout,0,UNIX_TIMESTAMP(),1 from sysgvg  g inner join uinfo u on g.uid=u.uid where g.gid=$gid");
    }
    if ($isguanjun) {
        $gvgts = GVG_OPENTS;
        $today = sql_fetch_one("select DAYOFWEEK(CURDATE()) as d, YEARWEEK(CURDATE())-YEARWEEK(FROM_UNIXTIME($gvgts)) as w");
        $week = intval($today['w']) + 1;
        $day = intval($today['d']);
        if ($day == 2 || $day == 5) {
            $c = 1;
        } elseif ($day == 3 || $day == 6) {
            $c = 2;
        } elseif ($day == 4 || $day == 7) {
            $c = 3;
        } else {
            $c = 4;
        }
        $id = min(4 + floor(pow($week * 7, 1 / 3)), 15);
        $itemid = $c * 100 + $id;
        $mcontent1 = "團戰冠軍隊員特別獎勵";
        $mcontent2 = "團戰冠軍隊長特別獎勵";
        sql_update("insert into umail (uid,mcontent,itemid,count,mtype,ts,system) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(),	'%Y-%m-%d %H:00'),'$mcontent1'),$itemid-2,1,0,UNIX_TIMESTAMP(),1 from ugvg g  where g.gid=$gid");
        sql_update("insert into umail (uid,mcontent,itemid,count,mtype,ts,system) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(),	'%Y-%m-%d %H:00'),'$mcontent2'),$itemid,1,0,UNIX_TIMESTAMP(),1 from sysgvg g where g.gid=$gid");
        // sql_update("insert into umail (uid,mcontent,itemid,count,mtype,ts) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰冠軍隊員特別獎勵'),12,1,0,UNIX_TIMESTAMP() from ugvg g where g.gid=$gid");
        // sql_update("insert into umail (uid,mcontent,itemid,count,mtype,ts) select g.uid,concat( FROM_UNIXTIME(UNIX_TIMESTAMP(), '%Y-%m-%d %H:00'),'團戰冠軍隊長特別獎勵'),13,1,0,UNIX_TIMESTAMP() from sysgvg g where g.gid=$gid");
        $leader = sql_fetch_one("select * from sysgvg where gid=$gid");
        $uid = $leader['uid'];
        //_addChenghao($uid, 8);
        $members = sql_fetch_rows("select * from ugvg where gid=$gid and uid!=$uid");
        for ($i = 0; $i < count($members); $i ++) {
            $muid = $members[$i]['uid'];
            //_addChenghao($muid, 3);
        }
    }
}

function autoGvg()
{
    // 随机报名
    cronLog("gvg");
    // sql_update(" insert ignore into ugvg (uid) select uid from uinfo order by rand() limit 500");
    // 统计队伍
    $res = sql_fetch_rows("select gid,count(*) as count from ugvg where gid!=0 group by gid order by rand()");
    foreach ($res as $value) {
        $gid = $value['gid'];
        $count = $value['count'];
        if ($count < 10) {
            $limit = 10 - $count;
            $ret = sql_update("update ugvg set gid=$gid where gid=0 order by rand() limit $limit");
            cronLog("g:" . $gid . ":add" . $ret);
        }
    }
}

function DoMail()
{
    // $mid=0;
    sql_update("update uinfo set mail=1");
}

// 5秒一次统计排名，把排名信息插入到排名表中，实际上是一个只读类型的脚本：
// 排名是java实际做到redis里面的；
function DoDAY()
{
    cronLog("do...");
    sql_connect();
    $serveract = sql_fetch_one("select * from server_act where said=1");
    $doubleaward = 1;
    if ($serveract) {
        if (intval($serveract['actvalue']) > 1) {
            $doubleaward = intval($serveract['actvalue']);
        }
    }
    // $res=sql_fetch_rows($sql);
    // cronLog(var_export($res,TRUE));
    // 1 1 550 18
    $pvpawardarr = array(
        array(
            1,
            1,
            550,
            7,
            1000
        ),
        array(
            2,
            2,
            500,
            7,
            600
        ),
        array(
            3,
            3,
            450,
            7,
            500
        ),
        array(
            4,
            4,
            400,
            7,
            450
        ),
        array(
            5,
            5,
            350,
            7,
            400
        ),
        array(
            6,
            6,
            340,
            7,
            380
        ),
        array(
            7,
            7,
            330,
            7,
            360
        ),
        array(
            8,
            8,
            320,
            7,
            340
        ),
        array(
            9,
            9,
            310,
            7,
            320
        ),
        array(
            10,
            10,
            300,
            7,
            300
        ),
        array(
            11,
            20,
            250,
            7,
            250
        ),
        array(
            21,
            30,
            200,
            7,
            200
        ),
        array(
            31,
            40,
            150,
            7,
            180
        ),
        array(
            41,
            50,
            130,
            7,
            160
        ),
        array(
            51,
            70,
            110,
            7,
            150
        ),
        array(
            71,
            100,
            100,
            7,
            140
        ),
        array(
            101,
            200,
            90,
            7,
            130
        ),
        array(
            201,
            300,
            80,
            7,
            120
        ),
        array(
            301,
            400,
            70,
            7,
            110
        ),
        array(
            401,
            500,
            60,
            7,
            100
        ),
        array(
            501,
            700,
            55,
            7,
            90
        ),
        array(
            701,
            1000,
            55,
            7,
            80
        ),
        array(
            1001,
            2000,
            50,
            7,
            70
        ),
        array(
            2001,
            3000,
            50,
            7,
            60
        ),
        array(
            3001,
            4000,
            50,
            7,
            50
        ),
        array(
            4001,
            5000,
            50,
            7,
            40
        ),
        array(
            5001,
            7000,
            45,
            7,
            30
        ),
        array(
            7001,
            10000,
            45,
            7,
            20
        ),
        array(
            10001,
            20000,
            40,
            7,
            10
        ),
        array(
            20001,
            30000,
            40,
            0,
            0
        ),
        array(
            30001,
            40000,
            35,
            0,
            0
        ),
        array(
            40001,
            50000,
            30,
            0,
            0
        ),
        array(
            50001,
            70000,
            25,
            0,
            0
        ),
        array(
            70001,
            100000,
            20,
            0,
            0
        )
    );
    $firstRankUid = 0;
    for ($i = 0; $i < count($pvpawardarr); $i ++) {
        $awardarr = $pvpawardarr[$i];
        $minindex = intval($awardarr[0]);
        $maxindex = intval($awardarr[1]);
        $ug = intval($awardarr[2]) * $doubleaward;
        $itemid = intval($awardarr[3]);
        $count = intval($awardarr[4]) * $doubleaward;
        if ($maxindex == 1) {
            $uinfo = sql_fetch_one("select * from upvp where `index`=1");
            $firstRankUid = intval($uinfo['uid']);
        }
        if ($maxindex == 20) {
            $uinfos = sql_fetch_rows("select * from upvp where `index`<=20 and not `index`=1");
        }
        $mtitle = "競技排行獎勵";
        $mcontent = sprintf("CONCAT('%s 日競技獎勵:恭喜你獲得競技排行第',`index`,'名') as mcontent", date("Y-m-d"));
        if ($doubleaward > 1) {
            $mcontent = sprintf("CONCAT('%s 日競技獎勵[%d 倍]:恭喜你獲得競技排行第',`index`,'名') as mcontent", date("Y-m-d"), $doubleaward);
        }
        
        $sql = "INSERT INTO umail (uid,mtitle,mcontent,ug,itemid,count,ts,system) SELECT uid,'$mtitle' as mtitle,$mcontent,$ug as ug ,$itemid as itemid,$count as `count`,UNIX_TIMESTAMP() as `ts`,1 as system from upvp where `index`>=$minindex and `index`<=$maxindex";
        // $ret=sql_update("INSERT INTO umail (uid,mtitle,mcontent,ug,itemid,count,ts) SELECT uid,'競技排行獎勵' as mtitle,CONCAT('恭喜你獲得競技排行第',`index`,'名') as mcontent,$ug as ug ,$itemid as itemid,$count as `count`,UNIX_TIMESTAMP() as `ts` from upvp where `index`>=$minindex and `index`<=$maxindex");
        // cronLog($sql);
        $ret = sql_update($sql);
        cronLog($minindex . "-" . $maxindex . "UG:" . $ug . "|US:" . $count . "|" . $ret);
        if ($maxindex == 1) {
            $uid = $uinfo['uid'];
            //_addChenghao($uid, 7);
        }
        if ($maxindex == 20) {
            for ($j = 0; $j < count($uinfos); $j ++) {
                $uid2 = $uinfos[$j]['uid'];
                //_addChenghao($uid2, 2);
            }
        }
    }
    // 系统公告
    if ($firstRankUid > 0) {
        $uinfo = sql_fetch_one("select * from uinfo where uid=$firstRankUid");
        $content = sprintf(' { RTE("系統：", 25,cc.c3b(255,0,0)), RTE("一整日的交戰之後，最終 ", 25,cc.c3b(0,183,0)),RTE("%s", 25,cc.c3b(255,255,255)),RTE(" 成為了今日競技場鬥士之中的最強王者，明天又會有怎樣的挑戰需要他面對呢？", 25,cc.c3b(0,183,0)) } ', $uinfo['uname']);
        _chatSendMsgOnly($content);
    }
    
    DoMail();
    return;
}

function startcvc()
{
    cronLog("start cvc");
    $servercvc = intval(sql_fetch_one_cell("select DAYOFWEEK(CURDATE()) as w"));
    if ($servercvc != 1) {
        return;
    }
    if (sql_fetch_one("select * from server_cvc where `week`=YEARWEEK(CURDATE())")) {
        return;
    }
    $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and gid=0 and week=YEARWEEK(CURDATE())"));
    if ($teams < 64) {
        // over buda;
        // auto join cvcbattle
        sql_update("insert into cvcbattle (cid,bout,`name`,isover,`week`,gid)
				(select cid as cid,0 as bout,cname as `name`,0 as isover,YEARWEEK(CURDATE()) as `week`,0 as gid
				from sysclub WHERE cid not in (select cid from cvcbattle where `week`=YEARWEEK(CURDATE())) and clv>=3)");
        cronLog("auto join cvcbattle ok and start cvc...");
        $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and gid=0 and week=YEARWEEK(CURDATE())"));
        if ($teams < 64) {
            return;
        }
    }
    // for ($j2 = 0; $j2 < 20; $j2++) {
    // if ($teams<=pow(2, $j2)) {
    // $limit=pow(2, $j2)-$teams;
    // sql_update("update sysgvg set bout=bout+1 order by rand() limit $limit");
    // $j2=20;
    // break;
    // }
    // }
    $sleepts = rand(1000, 1300) * 0 - 1;
    sql_update("insert into server_cvc (week,ts,teams,bout,nextts) VALUES (YEARWEEK(CURDATE()),UNIX_TIMESTAMP(),$teams,0,UNIX_TIMESTAMP()+$sleepts)");
    cronLog("start ok:" . $teams);
    // 周日开始公会战参加限制
    sql_update("update uclub set battlecid=cid where DAYOFWEEK(CURDATE())=1");
    cronLog("set battlecid ok");
}

function docvcbout($w)
{
    $addscore = array(
        0,
        30,
        40,
        50,
        60,
        80,
        100
    ); // 每次击杀增加的贡献
    $starttime = microtime(true);
    $sinfo = sql_fetch_one("select *,DAYOFWEEK(CURDATE()) as w from server_cvc where week=YEARWEEK(CURDATE()) and nextts<UNIX_TIMESTAMP() and isover=0");
    if ($sinfo) {
        cronLog("cvcbout:" . $sinfo['week'] . ":" . $sinfo['bout']);
        $bout = intval($sinfo['bout']);
        // $w=intval($sinfo['w']);
        // 加个保险
        if ($bout >= $w) {
            return;
        }
        cronLog("bout:" . $bout);
        $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and week=YEARWEEK(CURDATE())"));
        // $bt=ceil($teams/2);
        $needover = $teams - pow(2, (6 - $bout));
        cronLog("to check ok:" . $teams . "|" . $bout . "|" . $needover);
        
        $isbout = 0;
        if ($bout > 0) {
            $isbout = 1;
        }
        for ($j = 1; $j <= $needover; $j ++) {
            if ($isbout) {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout and gid=$j and week=YEARWEEK(CURDATE())");
            } else {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout and week=YEARWEEK(CURDATE()) order by rand() limit 2");
            }
            if (! $twog || count($twog) <= 1) {
                // 本回合结束;
                break;
            }
            global $conn;
            if (rand(1, 10000) < 5000) {
                // $cname1=mysql_escape_string($twog[0]['name']);
                // $cname2=mysql_escape_string($twog[1]['name']);
                $cname1 = $conn->escape_string($twog[0]['name']);
                $cname2 = $conn->escape_string($twog[1]['name']);
                $cbid1 = $twog[0]['cbid'];
                $cbid2 = $twog[1]['cbid'];
                $cid1 = $twog[0]['cid'];
                $cid2 = $twog[1]['cid'];
            } else {
                $cbid2 = $twog[0]['cbid'];
                $cbid1 = $twog[1]['cbid'];
                // $cname2=mysql_escape_string($twog[0]['name']);
                // $cname1=mysql_escape_string($twog[1]['name']);
                $cname2 = $conn->escape_string($twog[0]['name']);
                $cname1 = $conn->escape_string($twog[1]['name']);
                $cid2 = $twog[0]['cid'];
                $cid1 = $twog[1]['cid'];
            }
            $res = _docvc($cid1, $cid2);
            $ret1 = 0;
            $ret2 = 1;
            if ($res[0]) {
                $ret1 = 1;
                $ret2 = 0;
                $cvclogarr = array();
                $cvclogarr['win'] = $cbid1;
                $cvclogarr['log'][$cbid1] = $res[1];
                $cvclogarr['log'][$cbid2] = $res[2];
                $cvclogarr['killlog'] = $res[4];
                $cvclog = json_encode($cvclogarr);
            } else {
                $cvclogarr = array();
                $cvclogarr['win'] = $cbid2;
                $cvclogarr['log'][$cbid1] = $res[1];
                $cvclogarr['log'][$cbid2] = $res[2];
                $cvclogarr['killlog'] = $res[4];
                $cvclog = json_encode($cvclogarr);
            }
            if ($isbout) {
                cronLog("insert cvclog");
                $logid = sql_insert("insert into log_cvc (log) values ('$cvclog')");
                sql_update("insert into cvclog (cbid,armcbid,bout,result,logid,cname,armname,week) values ($cbid1,$cbid2,$bout,$ret1,$logid,'$cname1','$cname2',YEARWEEK(CURDATE()))");
                sql_update("insert into cvclog (cbid,armcbid,bout,result,logid,cname,armname,week) values ($cbid2,$cbid1,$bout,$ret2,$logid,'$cname2','$cname1',YEARWEEK(CURDATE()))");
            }
            if ($ret1) {
                sql_update("update cvcbattle set bout=bout+$isbout where cbid=$cbid1 and week=YEARWEEK(CURDATE())");
                sql_update("update cvcbattle set isover=1 where cbid=$cbid2 and week=YEARWEEK(CURDATE())");
                // 发奖gid2
                // _cvcover($cid2, $bout);
                if ($bout < 1) {
                    _cvcoverMail($cid2, $bout, $cname2, $cname1);
                }
            } else {
                sql_update("update cvcbattle set bout=bout+$isbout where cbid=$cbid2 and week=YEARWEEK(CURDATE())");
                sql_update("update cvcbattle set isover=1 where cbid=$cbid1 and week=YEARWEEK(CURDATE())");
                // 发奖gid1
                // _cvcover($cid1, $bout);
                if ($bout < 1) {
                    _cvcoverMail($cid1, $bout, $cname1, $cname2);
                }
            }
            // 每轮结束发击杀奖励,直接加
            if ($bout < 7 && $addscore[$bout] && $addscore[$bout] > 0) {
                cronLog("kill reward start");
                $add = $addscore[$bout];
                foreach ($res[1] as $t1) {
                    $tuid = intval($t1["uid"]);
                    $tkilladd = intval($t1["kill"]) * $add;
                    sql_update("update uclub set score=score+$tkilladd,totalscore=totalscore+$tkilladd where uid=$tuid and (battlecid=cid or battlecid=0)");
                }
                foreach ($res[2] as $t2) {
                    $tuid = intval($t2["uid"]);
                    $tkilladd = intval($t2["kill"]) * $add;
                    sql_update("update uclub set score=score+$tkilladd,totalscore=totalscore+$tkilladd where uid=$tuid and (battlecid=cid or battlecid=0)");
                }
                cronLog("kill reward ok");
            }
        }
        // if ($bout==0) {
        // 分组;
        cronLog("bout reward start");
        _cvcRewardBout($bout);
        cronLog("bout reward ok");
        $gcount = pow(2, 5 - $bout);
        sql_update("update cvcbattle set gid=0 where isover=0 and week=YEARWEEK(CURDATE())");
        for ($i = 1; $i <= $gcount; $i ++) {
            sql_update("update cvcbattle set gid=$i,bout=GREATEST(1,bout) where isover=0 and gid=0 and week=YEARWEEK(CURDATE()) order by rand() limit 2");
        }
        // }
        $sleepts = 60;
        if ($bout == 0) {
            $sleepts = rand(250, 350);
        } elseif ($bout == 1) {
            $sleepts = rand(100, 150);
        } else {
            $sleepts = rand(40, 100);
        }
        $sleepts = 0;
        sql_update("update server_cvc set bout=bout+1,nextts=UNIX_TIMESTAMP()+$sleepts where week=YEARWEEK(CURDATE())");
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        cronLog("bout:" . ($bout + 1) . " over cost:" . $lasttime);
        if ($teams == 2) {
            // 冠军;
            $gid1 = intval(sql_fetch_one_cell("select cid from cvcbattle where isover=0 and week=YEARWEEK(CURDATE())"));
            // sql_update("update cvcbattle set isover=1 where gid=$gid1");
            $bout = 7;
            // _cvcover($gid1, $bout);
            _cvcRewardBout($bout);
            // sql_update("delete from sysgvg");
            // sql_update("delete from cvcbattle where week=YEARWEEK(CURDATE())");
            sql_update("update server_cvc set isover=1 where week=YEARWEEK(CURDATE())");
            
            return;
        }
    }
}

function _cvcoverMail($cid, $bout, $cname1, $cname2)
{
    $pow = pow(2, 7 - $bout);
    if ($bout == 0) {
        $mcontent = "預選賽";
    } elseif ($bout == 6) {
        $mcontent = "決賽";
    } elseif ($bout == 5) {
        $mcontent = "半決賽";
    } else {
        $mcontent = $pow . "強賽";
    }
    $mcontent = "您的公會 " . $cname1 . " 在" . $mcontent . "中被 " . $cname2 . " 公會淘汰,請再接再厲";
    
    $members = sql_fetch_rows("select * from uclub where cid=$cid");
    foreach ($members as $m) {
        $uid = intval($m['uid']);
        _addMail($uid, $mcontent,$mcontent, 0, 0, 0);
    }
}

function _cvcreward($bout, $type)
{
    $coinreward1 = array(
        1000,
        2000,
        3000,
        4500,
        6000,
        8000,
        10000,
        10000
    );
    $coinreward2 = array(
        3000,
        6000,
        9000,
        13000,
        17000,
        22000,
        28000,
        28000
    );
    $bossreward1 = array(
        0,
        0,
        0,
        0,
        0,
        0,
        1,
        2
    );
    $bossreward2 = array(
        0,
        0,
        0,
        1,
        2,
        3,
        5,
        13
    );
    $coin = 0;
    $boss = 0;
    if ($type == 1) {
        $coin = $coinreward1[$bout];
        $boss = $bossreward1[$bout];
    } elseif ($type == 2) {
        $coin = $coinreward2[$bout];
        $boss = $bossreward2[$bout];
    }
    return array(
        $coin,
        $boss
    );
}

function _cvcRewardClub($cid, $bout, $isover)
{
    $pow = pow(2, 7 - $bout);
    $rewardarr = array(
        0 => array(
            "bout" => 0,
            "coin" => 2000,
            "boss1" => 0,
            "boss2" => 0,
            "honor1" => 200,
            "honor2" => 50
        ),
        1 => array(
            "bout" => 1,
            "coin" => 2000,
            "boss1" => 1,
            "boss2" => 0,
            "honor1" => 200,
            "honor2" => 50
        ),
        2 => array(
            "bout" => 2,
            "coin" => 2000,
            "boss1" => 1,
            "boss2" => 0,
            "honor1" => 200,
            "honor2" => 50
        ),
        3 => array(
            "bout" => 3,
            "coin" => 2000,
            "boss1" => 1,
            "boss2" => 0,
            "honor1" => 300,
            "honor2" => 50
        ),
        4 => array(
            "bout" => 4,
            "coin" => 2000,
            "boss1" => 2,
            "boss2" => 0,
            "honor1" => 300,
            "honor2" => 100
        ),
        5 => array(
            "bout" => 5,
            "coin" => 2000,
            "boss1" => 2,
            "boss2" => 0,
            "honor1" => 400,
            "honor2" => 100
        ),
        6 => array(
            "bout" => 6,
            "coin" => 4000,
            "boss1" => 2,
            "boss2" => 1,
            "honor1" => 400,
            "honor2" => 100
        ),
        7 => array(
            "bout" => 7,
            "coin" => 4000,
            "boss1" => 4,
            "boss2" => 1,
            "honor1" => 500,
            "honor2" => 100
        )
    );
    if ($bout == 0) {
        if (! $isover) { // 预赛没挂,进了64强
            sql_update("update uclub set score=score+50,totalscore=totalscore+50 where cid=$cid and (battlecid=cid or battlecid=0)");
        } else { // 预赛挂了
            sql_update("update uclub set score=score+30,totalscore=totalscore+30 where cid=$cid and (battlecid=cid or battlecid=0)");
        }
    }
    if ($rewardarr[$bout]) {
        $reward = $rewardarr[$bout];
        $members = sql_fetch_rows("select c.*,u.ulv from uclub c inner join uinfo u on c.uid=u.uid where c.cid=$cid and (c.battlecid=c.cid or c.battlecid=0)");
        if ($bout == 0) {
            if (! $isover) {
                $mcontent = "恭喜你的公會在公會爭霸戰預選賽中獲勝!";
            } else {
                $mcontent = "你在公會爭霸戰預選賽中落敗,請再接再厲";
            }
        } elseif ($bout == 7) {
            $mcontent = "恭喜你的公會在本周公會爭霸戰中獲得冠軍!";
        } elseif ($bout == 6) {
            if (! $isover) {
                $mcontent = "恭喜你的公會在公會爭霸戰決賽中獲勝!";
            } else {
                $mcontent = "恭喜你的公會在公會爭霸戰決賽中獲得亞軍";
            }
        } elseif ($bout == 5) {
            if (! $isover) {
                $mcontent = "恭喜你的公會在公會爭霸戰半決賽中獲勝!";
            } else {
                $mcontent = "你的公會在公會爭霸戰半決賽中落敗,請再接再厲";
            }
        } else {
            if (! $isover) {
                $mcontent = "恭喜你的公會在公會爭霸戰" . $pow . "強賽中獲勝!";
            } else {
                $mcontent = "你的公會在公會爭霸戰" . $pow . "強賽中落敗,請再接再厲";
            }
        }
        foreach ($members as $member) {
            $uid = intval($member['uid']);
            $ulv = intval($member['ulv']);
            $coin = intval($reward['coin']) * $ulv;
            if (intval($member['state']) == 1000) { // 会长奖励
                $boss = intval($reward['boss1']);
                $honor = intval($reward['honor1']);
                sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',$coin,7,$honor,UNIX_TIMESTAMP(),1)");
                sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',0,11,$boss,UNIX_TIMESTAMP(),1)");
                if ($bout == 7) { // 称号
                    //_addChenghao($uid, 9);
                }
            } else { // 会员奖励
                $boss = intval($reward['boss2']);
                $honor = intval($reward['honor2']);
                sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',$coin,7,$honor,UNIX_TIMESTAMP(),1)");
                if ($boss > 0) {
                    sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',0,11,$boss,UNIX_TIMESTAMP(),1)");
                }
                if ($bout == 7) { // 称号
                    //_addChenghao($uid, 4);
                }
            }
            sql_update("update uinfo set mail=1 where uid=$uid");
        }
    }
}

function _cvcRewardBout($bout)
{
    $clubs = sql_fetch_rows("select * from cvcbattle where bout>=$bout and `week`=YEARWEEK(CURDATE())");
    foreach ($clubs as $club) {
        $cid = intval($club['cid']);
        cronLog("cvc reward bout:" . $bout . "|cid:" . $cid . " start");
        $isover = intval($club['isover']);
        _cvcRewardClub($cid, $bout, $isover);
        cronLog("--cvc reward bout:" . $bout . "|cid:" . $cid . " ok--");
    }
}

function _cvcover($cid, $bout)
{
    $pow = pow(2, 7 - $bout);
    if ($bout <= 0) {
        return;
    }
    if ($bout == 7) {
        $mcontent = "恭喜你的公會在公會爭霸賽中獲得了冠軍";
        $club = sql_fetch_one("select * from sysclub where cid=$cid");
        $uid = $club['uid'];
        //_addChenghao($uid, 9);
        $members = sql_fetch_rows("select * from uclub where cid=$cid and uid!=$uid");
        for ($i = 0; $i < count($members); $i ++) {
            $muid = $members[$i]['uid'];
            //_addChenghao($muid, 4);
        }
    } elseif ($bout == 6) {
        $mcontent = "恭喜你的公會在公會爭霸賽中獲得了亞軍";
    } else {
        $mcontent = "恭喜你的公會在公會爭霸賽中進入了" . $pow . "強";
    }
    $uinfo = sql_fetch_rows("select c.*,u.ulv from uclub c inner join uinfo u on c.uid=u.uid where cid=$cid and cid>0");
    for ($i = 0; $i < count($uinfo); $i ++) {
        $uid = intval($uinfo[$i]['uid']);
        $type = 1;
        if ($uinfo[$i]['state'] == 1000) {
            $type = 2;
        }
        $reward = _cvcreward($bout, $type);
        $ucoin = intval($uinfo[$i]['ulv'] * $reward[0]);
        $boss = intval($reward[1]);
        if ($ucoin != 0 || $boss != 0) {
            sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',$ucoin,11,$boss,UNIX_TIMESTAMP(),1)");
            sql_update("update uinfo set mail=1 where uid=$uid");
            _coinlog($uid . "," . $ucoin . ",cvcReward,1");
        }
    }
    $exp = $bout * 100;
    sql_update("update sysclub set exp=exp+$exp where cid=$cid");
}

function autoDeleteMail()
{
    global $mid;
    $mail = sql_fetch_one("select *,UNIX_TIMESTAMP() as nts,UNIX_TIMESTAMP()-UNIX_TIMESTAMP(CURDATE()) as checkts from umail where mid>$mid order by mid limit 1");
    if ($mail) {
        $cts = intval($mail['checkts']);
        // 13:30~15:30, 19:30~21:30不删邮件
        if (($cts > 13.5 * 3600 && $cts < 15.5 * 3600) || ($cts > 19.5 * 3600 && $cts < 21.5 * 3600)) {
            cronLog("auto delete mail return");
            return;
        }
        $ret = 0;
        if ((intval($mail['nts']) > intval($mail['ts']) + 86400 * 7)) {
            $ret = sql_update("delete from umail where mid<$mid  and ts<UNIX_TIMESTAMP()-86400*7 limit 10000");
            cronLog("auto delete mail mid < " . $mid . " delete " . $ret);
            if (intval($mail['mid']) > $mid + 100000) {
                $mid = intval($mail['mid']) + 10000;
            } else {
                $mid += 10000;
            }
        }
    } else {
        $mid = 100000;
    }
}

function doDeleteCvcbattle()
{
    $res = sql_fetch_rows("select CONCAT(`cbid`,',',`cid`,',',`bout`,',',`name`,',',`isover`,',',`week`,',',`gid`) from cvcbattle where DATE_FORMAT(CURDATE(),'%w')=6 and week<=YEARWEEK(CURDATE())");
    if ($res && count($res) > 0) {
        cronLog("delete cvcbattle begin......");
        cronLog("cbid,cid,bout,name,isover,week,gid");
        cronLog(var_export($res, true));
        // cvcbattle 本周
        sql_update("delete from cvcbattle where DATE_FORMAT(CURDATE(),'%w')=6 and week<=YEARWEEK(CURDATE()) and bout=0");
        cronLog("......delete cvcbattle end");
        // left bout>1
        sql_update("update cvcbattle SET `week`=YEARWEEK(CURDATE())+1,bout=0,gid=0,isover=0");
        
        // cvclog 两周前
        sql_update("delete from cvclog where DATE_FORMAT(CURDATE(),'%w')=6 and week<YEARWEEK(CURDATE())-1");
        // log_cvc 两周前
        // sql_update("delete from log_cvc where DATE_FORMAT(CURDATE(),'%w')=6 and ts>0 and ts<UNIX_TIMESTAMP-14*86400");
    }
}

// 将本周bosscid清零
function doClearClubBosscid()
{
    $res = intval(sql_fetch_one_cell("select DATE_FORMAT(CURDATE(),'%w')"));
    if ($res == 0) {
        cronLog("reset club bosscid");
        sql_update("update uclub set bosscid=0");
        cronLog("reset club bosscid end");
    }
}

function gvgMsg()
{
    $content = "激動人心的多人團戰還有1小時就要正式開啟了，小夥伴們趕緊報名參戰吧！也可以組成自己的隊伍帶隊廝殺哦！";
    _chatSendMsgOnly(' { RTE("系統：", 25,cc.c3b(255,0,0)), RTE("' . $content . '", 25,cc.c3b(0,183,0)) } ');
}

function cvcMsg()
{
    $w = intval(sql_fetch_one_cell("select DAYOFWEEK(CURDATE()) as w"));
    if ($w == 1) {
        $content = "公會戰報名還有30分鐘就要截止啦，還沒有報名的公會抓緊咯~";
    } elseif ($w > 1 && $w < 7) {
        $content = "30分鐘後本輪公會戰即將開始，請參戰的勇士及時調整裝備技能，發揮全力奮戰到底！";
    } else {
        $cvcinfo = sql_fetch_one("select * from cvcbattle where bout=6 and isover=0");
        if ($cvcinfo && count($cvcinfo) == 2) {
            $cid1 = $cvcinfo[0]['name'];
            $cid2 = $cvcinfo[1]['name'];
            $content = sprintf("萬眾矚目的公會戰決戰即將開打，%s公會和%s公會將擦出怎樣激烈的火花呢？讓我們拭目以待！", $cid1, $cid2);
        } else {
            $content = "30分鐘後本輪公會戰即將開始，請參戰的勇士及時調整裝備技能，發揮全力奮戰到底！";
        }
    }
    _chatSendMsgOnly(' { RTE("系統：", 25,cc.c3b(255,0,0)), RTE("' . $content . '", 25,cc.c3b(0,183,0)) } ');
}

function topRank()
{
    sql_connect();
    sql_update("delete from urank where type='zhanli'");
    sql_update("insert into urank (type,uid,cname,uname,ujob,sex,ulv,zhanli,sig) (SELECT 'zhanli' as type,u.uid as uid,c.cname as cname,u.uname as uname,u.ujob as ujob,u.sex as sex,u.ulv as ulv,u.zhanli as zhanli,u.sig as sig FROM uinfo u left JOIN sysclub c on c.uid=u.uid GROUP BY uid ORDER BY u.zhanli desc LIMIT 20)");
    sql_update("delete from urank where type='zhanshi'");
    sql_update("insert into urank (type,uid,cname,uname,ujob,sex,ulv,zhanli,sig) (SELECT 'zhanshi' as type,u.uid as uid,c.cname as cname,u.uname as uname,u.ujob as ujob,u.sex as sex,u.ulv as ulv,u.zhanli as zhanli,u.sig as sig FROM uinfo u left JOIN sysclub c on c.uid=u.uid  where ujob=1 GROUP BY uid ORDER BY u.zhanli desc LIMIT 20)");
    sql_update("delete from urank where type='lieren'");
    sql_update("insert into urank (type,uid,cname,uname,ujob,sex,ulv,zhanli,sig) (SELECT 'lieren' as type,u.uid as uid,c.cname as cname,u.uname as uname,u.ujob as ujob,u.sex as sex,u.ulv as ulv,u.zhanli as zhanli,u.sig as sig FROM uinfo u left JOIN sysclub c on c.uid=u.uid  where ujob=2 GROUP BY uid ORDER BY u.zhanli desc LIMIT 20)");
    sql_update("delete from urank where type='fashi'");
    sql_update("insert into urank (type,uid,cname,uname,ujob,sex,ulv,zhanli,sig) (SELECT 'fashi' as type,u.uid as uid,c.cname as cname,u.uname as uname,u.ujob as ujob,u.sex as sex,u.ulv as ulv,u.zhanli as zhanli,u.sig as sig FROM uinfo u left JOIN sysclub c on c.uid=u.uid  where ujob=3 GROUP BY uid ORDER BY u.zhanli desc LIMIT 20)");
    sql_update("delete from urank where type='juedoushi'");
    sql_update("insert into urank (type,uid,`index`,uname,ujob,sex,ulv,zhanli,sig) (SELECT 'juedoushi' as type,u.uid as uid,p.`index` as `index`,u.uname as uname,u.ujob as ujob,u.sex as sex,u.ulv as ulv,u.zhanli as zhanli,u.sig as sig FROM upvp  p left JOIN uinfo u on u.uid=p.uid GROUP BY uid ORDER BY p.`index` LIMIT 20)");
    sql_update("delete from urank where type='club'");
    sql_update("insert into urank (type,uid,cid,cname,uname,ujob,sex,clv,exp) (SELECT 'club' as type,u.uid as uid,c.cid as cid,c.cname as cname,u.uname as uname,u.ujob as ujob,u.sex as sex,c.clv as clv,c.exp as exp FROM sysclub c left JOIN uinfo u on c.uid=u.uid GROUP BY uid ORDER BY c.exp desc LIMIT 20)");
}

?>