<?php
require_once 'db.php';
require_once 'config.php';
require_once 'battle.php';
require_once 'gvg.php';
require_once 'my_new.php';
require_once 's_user_new.php';
require_once 'club.php';
require_once 'CronFramework.php';
startcvc();//开启
docvcbout();//淘汰赛=>64
//docvcbout();//=>32
//docvcbout();//=>16
//docvcbout();//=>8
//docvcbout();//=>4
//docvcbout();//=>2
//docvcbout();//=>1


function _teampjoincvc()
{
    ;//wefgnsdf
    //tsetesodfsdndk
    ///sdfsdfsd
    //sdfsfsd
    //2014.08.09
    //2014年8月9日 15:17:25
    //2014年8月9日 15:17:47
    //2014年8月11日11:18:41
    //2014年8月11日11:20:16
}

function startcvc()
{
    cronLog("start cvc");
    $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0 and gid=0 and week=YEARWEEK(CURDATE())"));
    if ($teams < 64) {
        //over buda;
        return;
    }
    //	for ($j2 = 0; $j2 < 20; $j2++) {
    //		if ($teams<=pow(2, $j2)) {
    //			$limit=pow(2, $j2)-$teams;
    //			sql_update("update sysgvg set bout=bout+1 order by rand() limit $limit");
    //			$j2=20;
    //			break;
    //		}
    //	}
    $sleepts = rand(1000, 1300) * 0 - 1;
    sql_update("insert into server_cvc (week,ts,teams,bout,nextts) VALUES (YEARWEEK(CURDATE()),UNIX_TIMESTAMP(),$teams,0,UNIX_TIMESTAMP()+$sleepts)");
    cronLog("start ok:" . $teams);
}

function docvcbout()
{
    $starttime = microtime(true);
    $sinfo = sql_fetch_one("select * from server_cvc where week=YEARWEEK(CURDATE()) and nextts<UNIX_TIMESTAMP() and isover=0");
    if ($sinfo) {
        cronLog("cvcbout:" . $sinfo['week'] . ":" . $sinfo['bout']);
        $bout = intval($sinfo['bout']);
        cronLog("bout:" . $bout);
        $teams = intval(sql_fetch_one_cell("select count(*) from cvcbattle where isover=0"));
        //$bt=ceil($teams/2);
        $needover = $teams - pow(2, (6 - $bout));
        cronLog("to check ok:" . $teams . "|" . $bout . "|" . $needover);
        //		if ($bt==0) {
        //			//可以去发奖了
        //			sql_update("update server_cvc set isover=1 where dth=FROM_UNIXTIME(UNIX_TIMESTAMP(),'%m%d%H')");
        //			return ;
        //		}
        if ($teams == 1) {
            //冠军;
            $gid1 = intval(sql_fetch_one_cell("select cid from cvcbattle where isover=0"));
//			sql_update("update cvcbattle set isover=1 where gid=$gid1");
            _cvcover($gid1, $bout);
            //sql_update("delete from sysgvg");
            sql_update("delete from cvcbattle");
            sql_update("update server_cvc set isover=1 where week=YEARWEEK(CURDATE())");

            return;
        }
        $isbout = 0;
        if ($bout > 0) {
            $isbout = 1;
        }
        for ($j = 1; $j <= $needover; $j++) {
            if ($isbout) {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout and gid=$j ");
            } else {
                $twog = sql_fetch_rows("select cbid,cid,name from cvcbattle where isover=0 and bout=$bout order by rand() limit 2");
            }
            if (!$twog || count($twog) <= 1) {
                //本回合结束;
                break;
            }
            if (rand(1, 10000) < 5000) {
                $cname1 = $twog[0]['name'];
                $cname2 = $twog[1]['name'];
                $cbid1 = $twog[0]['cbid'];
                $cbid2 = $twog[1]['cbid'];
                $cid1 = $twog[0]['cid'];
                $cid2 = $twog[1]['cid'];
            } else {
                $cbid2 = $twog[0]['cbid'];
                $cbid1 = $twog[1]['cbid'];
                $cname2 = $twog[0]['name'];
                $cname1 = $twog[1]['name'];
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
                sql_update("update cvcbattle set bout=bout+$isbout where cbid=$cbid1");
                sql_update("update cvcbattle set isover=1 where cbid=$cbid2");
                //发奖gid2
                _cvcover($cid2, $bout);
            } else {
                sql_update("update cvcbattle set bout=bout+$isbout where cbid=$cbid2");
                sql_update("update cvcbattle set isover=1 where cbid=$cbid1");
                //发奖gid1
                _cvcover($cid1, $bout);
            }
        }
        //if ($bout==0) {
        //分组;
        $gcount = pow(2, 5 - $bout);
        sql_update("update cvcbattle set gid=0 where isover=0");
        for ($i = 1; $i <= $gcount; $i++) {
            sql_update("update cvcbattle set gid=$i,bout=GREATEST(1,bout) where isover=0 and gid=0 order by rand() limit 2");
        }
        //}
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
    }
}

function _cvcreward($bout, $type)
{
    $coinreward1 = array(1000, 2000, 3000, 4500, 6000, 8000, 10000, 10000);
    $coinreward2 = array(3000, 6000, 9000, 13000, 17000, 22000, 28000, 28000);
    $bossreward1 = array(0, 0, 0, 0, 0, 0, 1, 2);
    $bossreward2 = array(0, 0, 0, 1, 2, 3, 5, 13);
    $coin = 0;
    $boss = 0;
    if ($type == 1) {
        $coin = $coinreward1[$bout];
        $boss = $bossreward1[$bout];
    } elseif ($type == 2) {
        $coin = $coinreward2[$bout];
        $boss = $bossreward2[$bout];
    }
    return array($coin, $boss);
}

function _cvcover($cid, $bout)
{
    if ($bout <= 0) {
        return;
    }
    if ($bout == 7) {
        $mcontent = "恭喜你的公會在公會爭霸賽中獲得了冠軍";
    } elseif ($bout == 6) {
        $mcontent = "恭喜你的公會在公會爭霸賽中獲得了亞軍";
    } else {
        $mcontent = "恭喜你的公會在公會爭霸賽中進入了" . pow(2, 7 - $bout) . "強";
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
        }
    }
    $exp = $bout * 100;
    sql_update("update sysclub set exp=exp+$exp where cid=$cid");
}
