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
date_default_timezone_set('Asia/Shanghai');
require_once 'mail.php';
define('CRONVERSION', '2014-10-27 17:07:57', true);

/**
 * 开启工会战
 */
function startcvc()
{
    cronLog("start cvc");
    $servercvc = intval(sql_fetch_one_cell("select DAYOFWEEK(CURDATE()) as w"));
    // 礼拜二开打
    if ($servercvc != 3) {
        return;
    }
    // 已有信息，则忽略
    if (sql_fetch_one("select * from server_cvc where `week`=YEARWEEK(CURDATE())")) { // YEARWEEK(CURDATE()) like 201444
        return;
    }
    // 找出所有队伍
    $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and gid=0 and week=YEARWEEK(CURDATE())"));
    if ($teams < 16) {
        sql_update("insert into cvcbattle (cid,bout,`name`,isover,`week`,gid)
				(select cid as cid,0 as bout,cname as `name`,0 as isover,YEARWEEK(CURDATE()) as `week`,0 as gid
				from sysclub WHERE cid not in (select cid from cvcbattle where `week`=YEARWEEK(CURDATE())) and clv>=2)");
        cronLog("auto join cvcbattle ok and start cvc...");
        $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and gid=0 and week=YEARWEEK(CURDATE())"));
        if ($teams < 16) {
            return;
        }
    }
    $sleepts = rand(1000, 1300) * 0 - 1;
    sql_update("insert into server_cvc (week,ts,teams,bout,nextts) VALUES (YEARWEEK(CURDATE()),UNIX_TIMESTAMP(),$teams,0,UNIX_TIMESTAMP()+$sleepts)");
    cronLog("start ok:" . $teams);
    // 周日开始公会战参加限制
    sql_update("update uclub set battlecid=cid where DAYOFWEEK(CURDATE())=3");
    cronLog("set battlecid ok");
    _addSysMsg('万众瞩目的公会逐鹿中原争霸战预赛已经开始了，哪些公会可以进入16强呢，让我们拭目以待！');
}

/**
 * 工会战回合
 */
function docvcbout()
{
    // 每次击杀增加的贡献
    $addscore = array(
        40,
        50,
        60,
        80,
        100
    );
    $starttime = microtime(true);
    $sinfo = sql_fetch_one("select *,DAYOFWEEK(CURDATE()) as w from server_cvc where week=YEARWEEK(CURDATE()) and nextts<UNIX_TIMESTAMP() and isover=0");
    if ($sinfo) {
        cronLog("cvcbout:" . $sinfo['week'] . ":" . $sinfo['bout']);
        $bout = intval($sinfo['bout']);
        $w = intval($sinfo['w']);
        // 加个保险
        if ($bout >= ($w - 2)) {
            return;
        }
        cronLog("bout:" . $bout);
        $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and week=YEARWEEK(CURDATE())"));
        $needover = $teams - pow(2, (4 - $bout));
        cronLog("to check ok:" . $teams . "|" . $bout . "|" . $needover);

        $isbout = 0;
        if ($bout > 0) {
            $isbout = 1;
        }
        $winteams = array();
        for ($j = 1; $j <= $needover; $j++) {
            if ($isbout) {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout and gid=$j and week=YEARWEEK(CURDATE())");
            } else {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout and week=YEARWEEK(CURDATE()) order by rand() limit 2");
            }
            if (!$twog || count($twog) <= 1) {
                // 本回合结束;
                break;
            }
            global $conn;
            if (rand(1, 10000) < 5000) {
                $cname1 = $conn->escape_string($twog[0]['name']);
                $cname2 = $conn->escape_string($twog[1]['name']);
                $cbid1 = $twog[0]['cbid'];
                $cbid2 = $twog[1]['cbid'];
                $cid1 = $twog[0]['cid'];
                $cid2 = $twog[1]['cid'];
            } else {
                $cbid2 = $twog[0]['cbid'];
                $cbid1 = $twog[1]['cbid'];
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
                if ($bout < 1) {
                    _cvcoverMail($cid2, $bout, $cname2, $cname1);
                } else {
                    $winteams[] = $cname1;
                }
            } else {
                sql_update("update cvcbattle set bout=bout+$isbout where cbid=$cbid2 and week=YEARWEEK(CURDATE())");
                sql_update("update cvcbattle set isover=1 where cbid=$cbid1 and week=YEARWEEK(CURDATE())");
                if ($bout < 1) {
                    _cvcoverMail($cid1, $bout, $cname1, $cname2);
                } else {
                    $winteams[] = $cname2;
                }
            }
            // 每轮结束发击杀奖励,直接加
            if ($bout < 5 && $addscore[$bout] && $addscore[$bout] > 0) {
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
        cronLog("bout reward start");
        _cvcRewardBout($bout);
        cronLog("bout reward ok");
        $gcount = pow(2, 5 - $bout);
        sql_update("update cvcbattle set gid=0 where isover=0 and week=YEARWEEK(CURDATE())");
        for ($i = 1; $i <= $gcount; $i++) {
            sql_update("update cvcbattle set gid=$i,bout=GREATEST(1,bout) where isover=0 and gid=0 and week=YEARWEEK(CURDATE()) order by rand() limit 2");
        }
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
        $lasttime = (int)(($endtime - $starttime) * 1000);
        cronLog("bout:" . ($bout + 1) . " over cost:" . $lasttime);
        // 推送当日战斗详情
        $sysmsg = '';
        if ($bout == 0) {
            $tmpBout = $bout + 1;
            $wins = sql_fetch_rows("select name from cvcbattle where isover=0 and bout=$tmpBout");
            foreach ($wins as $win) {
                $winteams[] = $win['name'];
            }
        }
        if ($bout != 4) {
            $sysmsg = '经过激烈的战斗，公会逐鹿中原争霸战'._getCvcBoutName($bout).'已经决出胜负，晋级'._getCvcBoutName($bout + 1).'的公会是：'.implode(',', $winteams);
        } else {
            $sysmsg = '经过一周的激烈较量，'.$winteams[0].' 公会获得本周公会逐鹿中原争霸战的最强霸主!';
        }
        _addSysMsg($sysmsg);
        if ($teams == 2) {
            // 冠军
            $gid1 = intval(sql_fetch_one_cell("select cid from cvcbattle where isover=0 and week=YEARWEEK(CURDATE())"));
            $bout = 5;
            _cvcRewardBout($bout);
            sql_update("update server_cvc set isover=1 where week=YEARWEEK(CURDATE())");
            return;
        }
    }
}

function _cvcoverMail($cid, $bout, $cname1, $cname2)
{
    $pow = pow(2, 5 - $bout);
    $mcontent = _getCvcBoutName($bout);
    $tmp = str_replace('#name1#', $cname1, STR_Club_MatchLoss);
    $tmp = str_replace('#match#', $mcontent, $tmp);
    $mcontent = str_replace('#name2#', $cname2, $tmp);
    $members = sql_fetch_rows("select * from uclub where cid=$cid");
    foreach ($members as $m) {
        $uid = intval($m['uid']);
        _addMail($uid, $mcontent,$mcontent, 0, 0, 0);
    }
    // 向公会推送失败消息
    _addSysMsg($mcontent,$cid);
}

function _getCvcBoutName($bout) {
    $name = '';
    $pow = pow(2, 5 - $bout);
    if ($bout == 0) {
        $name = STR_Club_Match1;
    } elseif ($bout == 4) {
        $name = STR_Club_Match2;
    } elseif ($bout == 3) {
        $name = STR_Club_Match3;
    } else {
        $name = $pow . STR_Club_Match4;
    }
    return $name;
}

function _cvcreward($bout, $type)
{
    $coinreward1 = array(
        3000,
        4500,
        6000,
        8000,
        10000,
        10000
    );
    $coinreward2 = array(
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
        1,
        2
    );
    $bossreward2 = array(
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
    $pow = pow(2, 5 - $bout);
    $rewardarr = array(
        0 => array(
            "bout" => 0,
            "coin" => 2000,
            "boss1" => 1,
            "boss2" => 0,
            "honor1" => 200,
            "honor2" => 50
        ),
        1 => array(
            "bout" => 1,
            "coin" => 2000,
            "boss1" => 1,
            "boss2" => 0,
            "honor1" => 300,
            "honor2" => 50
        ),
        2 => array(
            "bout" => 2,
            "coin" => 2000,
            "boss1" => 2,
            "boss2" => 0,
            "honor1" => 300,
            "honor2" => 100
        ),
        3 => array(
            "bout" => 3,
            "coin" => 2000,
            "boss1" => 2,
            "boss2" => 0,
            "honor1" => 400,
            "honor2" => 100
        ),
        4 => array(
            "bout" => 4,
            "coin" => 4000,
            "boss1" => 2,
            "boss2" => 1,
            "honor1" => 400,
            "honor2" => 100
        ),
        5 => array(
            "bout" => 5,
            "coin" => 4000,
            "boss1" => 4,
            "boss2" => 1,
            "honor1" => 500,
            "honor2" => 100
        )
    );
    if ($bout == 0) {
        if (!$isover) {
            // 预赛没挂,进了64强
            sql_update("update uclub set score=score+50,totalscore=totalscore+50 where cid=$cid and (battlecid=cid or battlecid=0)");
        } else {
            // 预赛挂了
            sql_update("update uclub set score=score+30,totalscore=totalscore+30 where cid=$cid and (battlecid=cid or battlecid=0)");
        }
    }
    if ($rewardarr[$bout]) {
        $reward = $rewardarr[$bout];
        $members = sql_fetch_rows("select c.*,u.ulv from uclub c inner join uinfo u on c.uid=u.uid where c.cid=$cid and (c.battlecid=c.cid or c.battlecid=0)");
        if ($bout == 0) {
            if (!$isover) {
                $mcontent = STR_Club_BossInf1;
            } else {
                $mcontent = STR_Club_BossInf2;
            }
        } elseif ($bout == 5) {
            $mcontent = STR_Club_BossInf3;
        } elseif ($bout == 4) {
            if (!$isover) {
                $mcontent = STR_Club_BossInf4;
            } else {
                $mcontent = STR_Club_BossInf5;
            }
        } elseif ($bout == 3) {
            if (!$isover) {
                $mcontent = STR_Club_BossInf6;
            } else {
                $mcontent = STR_Club_BossInf7;
            }
        } else {
            if (!$isover) {
                $mcontent = str_replace('#round#', $pow, STR_Club_BossInf8);
            } else {
                $mcontent = str_replace('#round#', $pow, STR_Club_BossInf9);
            }
        }
        // 向公会推送胜利消息
        _addSysMsg($mcontent,$cid);
        foreach ($members as $member) {
            $uid = intval($member['uid']);
            $ulv = intval($member['ulv']);
            $coin = intval($reward['coin']) * $ulv;
            // 会长奖励
            if (intval($member['state']) == 1000) {
                $boss = intval($reward['boss1']);
                $honor = intval($reward['honor1']);
                sql_update("insert into umail (uid,mcontent,ucoin,honor,ts,system) values ($uid,'$mcontent',$coin,7,$honor,UNIX_TIMESTAMP(),1)");
                if ($boss > 0) {
                    sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',0,11,$boss,UNIX_TIMESTAMP(),1)");
                }
                // 天下至尊称号
                if ($bout == 5) {
                    //_addChenghao($uid, 9);
                }
            }
            // 会员奖励
            else {
                $boss = intval($reward['boss2']);
                $honor = intval($reward['honor2']);
                sql_update("insert into umail (uid,mcontent,ucoin,honor,ts,system) values ($uid,'$mcontent',$coin,$honor,UNIX_TIMESTAMP(),1)");
                if ($boss > 0) {
                    sql_update("insert into umail (uid,mcontent,ucoin,itemid,count,ts,system) values ($uid,'$mcontent',0,11,$boss,UNIX_TIMESTAMP(),1)");
                }
                // 战友情深称号
                if ($bout == 5) {
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
    $pow = pow(2, 5 - $bout);
    if ($bout <= 0) {
        return;
    }
    if ($bout == 5) {
        $mcontent = STR_Club_BossInf3;
        $club = sql_fetch_one("select * from sysclub where cid=$cid");
        $uid = $club['uid'];
        //_addChenghao($uid, 9);
        $members = sql_fetch_rows("select * from uclub where cid=$cid and uid!=$uid");
        for ($i = 0; $i < count($members); $i++) {
            $muid = $members[$i]['uid'];
            //_addChenghao($muid, 4);
        }
    } elseif ($bout == 4) {
        $mcontent = STR_Club_BossInf5;
    } else {
        $mcontent = str_replace('#round#', $pow, STR_Club_BossInf10);
    }
    $uinfo = sql_fetch_rows("select c.*,u.ulv from uclub c inner join uinfo u on c.uid=u.uid where cid=$cid and cid>0");
    for ($i = 0; $i < count($uinfo); $i++) {
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
            log_log('coin',$uid . "," . $ucoin . ",cvcReward,1");
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

function cvcMsg()
{
    $w = intval(sql_fetch_one_cell("select DAYOFWEEK(CURDATE()) as w"));
    if ($w > 2) {
        if ($w == 3) {
            $content = "公会战报名还有30分钟就要截止啦，还没有报名的公会抓紧咯~";
        } elseif ($w <= 6) {
            $content = "30分钟后本轮公会战即将开始，请参战的英雄及时调整装备技能，发挥全力奋战到底！";
        } elseif ($w == 7) {
            $cvcinfo = sql_fetch_rows("select * from cvcbattle where bout=4 and isover=0");
            if ($cvcinfo && count($cvcinfo) == 2) {
                $cid1 = $cvcinfo[0]['name'];
                $cid2 = $cvcinfo[1]['name'];
                $content = sprintf("万众瞩目的公会战决战即将开打，%s 公会和%s 公会将擦出怎样激烈的火花呢？让我们拭目以待！", $cid1, $cid2);
            } else {
                $content = "30分钟后本轮公会战即将开始，请参战的英雄及时调整装备技能，发挥全力奋战到底！";
            }
        }
        _addSysMsg($content,0);
    }
}

function cronLog($msg)
{
    $time = date("Ymd H:i:s");
    echo "[$time]" . $msg . "\n";
}

$funcname = $_SERVER["argv"][1];
if (function_exists($funcname)) {
    $funcname();
}
?>

