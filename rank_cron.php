<?php

// ----------------------------------
// 独立cron脚本，用来处理游戏事件逻辑：
// ----------------------------------
require_once("config.php");
require_once("db.php");
require_once("CronFramework.php");
require_once 's_user_new.php';
// DoDAY();

docvcbout();

/**
 * 竞技场排名奖励
 */
function DoDAY()
{
    cronLog("do...");
    sql_connect();
    // 是否双倍奖励
    $doubleaward = 1;
//    $serveract = sql_fetch_one("select * from server_act where said=1");
//    if ($serveract) {
//        if (intval($serveract['actvalue']) > 1) {
//            $doubleaward = intval($serveract['actvalue']);
//        }
//    }
    // PVP奖励
    $pvpawardarr = array(
        // array(排名起始,排名结束,钻石奖励,奖励道具ID,奖励道具数量)
        array(1, 1, 550, 7, 1000),
        array(2, 2, 500, 7, 600),
        array(3, 3, 450, 7, 500),
        array(4, 4, 400, 7, 450),
        array(5, 5, 350, 7, 400),
        array(6, 6, 340, 7, 380),
        array(7, 7, 330, 7, 360),
        array(8, 8, 320, 7, 340),
        array(9, 9, 310, 7, 320),
        array(10, 10, 300, 7, 300),
        array(11, 20, 250, 7, 250),
        array(21, 30, 200, 7, 200),
        array(31, 40, 150, 7, 180),
        array(41, 50, 130, 7, 160),
        array(51, 70, 110, 7, 150),
        array(71, 100, 100, 7, 140),
        array(101, 200, 90, 7, 130),
        array(201, 300, 80, 7, 120),
        array(301, 400, 70, 7, 110),
        array(401, 500, 60, 7, 100),
        array(501, 700, 55, 7, 90),
        array(701, 1000, 55, 7, 80),
        array(1001, 2000, 50, 7, 70),
        array(2001, 3000, 50, 7, 60),
        array(3001, 4000, 50, 7, 50),
        array(4001, 5000, 50, 7, 40),
        array(5001, 7000, 45, 7, 30),
        array(7001, 10000, 45, 7, 20),
        array(10001, 20000, 40, 7, 10),
        array(20001, 30000, 40, 0, 0),
        array(30001, 40000, 35, 0, 0),
        array(40001, 50000, 30, 0, 0),
        array(50001, 70000, 25, 0, 0),
        array(70001, 100000, 20, 0, 0)
    );
    $firstRankUid = 0;
    for ($i = 0; $i < count($pvpawardarr); $i++) {
        $awardarr = $pvpawardarr[$i];
        $minindex = intval($awardarr[0]);
        $maxindex = intval($awardarr[1]);
        $ug = intval($awardarr[2]) * $doubleaward;
        $itemid = intval($awardarr[3]);
        $count = intval($awardarr[4]) * $doubleaward;
        $mtitle = "竞技场奖励";
        $mcontent = sprintf("CONCAT('%s 日竞技场奖励：恭喜您获得竞技排行第',`index`,'名') as mcontent", date("Y-m-d"));
        if ($doubleaward > 1) {
            $mcontent = sprintf("CONCAT('%s 日竞技场奖励[%d 倍]：恭喜您获得竞技排行第',`index`,'名') as mcontent", date("Y-m-d"), $doubleaward);
        }
        // 发送奖励邮件
        $sql = "INSERT INTO umail (uid,mtitle,mcontent,ug,itemid,count,ts,system) SELECT uid,'$mtitle' as mtitle,$mcontent,$ug as ug ,$itemid as itemid,$count as `count`,UNIX_TIMESTAMP() as `ts`,1 as system from upvp where `index`>=$minindex and `index`<=$maxindex";
        $ret = sql_update($sql);

        cronLog($minindex . "-" . $maxindex . "UG:" . $ug . "|US:" . $count . "|" . $ret);

        // 如果是第一名
        if ($maxindex == 1) {
            $uinfo = sql_fetch_one("select * from upvp where `index`=1");
            $firstRankUid = intval($uinfo['uid']);
            // 竞技之王称号
          //  _addChenghao($firstRankUid,7);
        }
        // 前20名
        if ($maxindex == 20) {
            $uinfos = sql_fetch_rows("select * from upvp where `index`<=20 and not `index`=1");
            $len = count($uinfos);
            for ($j = 0; $j < $len; $j++) {
                $uid2 = $uinfos[$j]['uid'];
                // 竞技精英称号
            //    _addChenghao($uid2, 2);
            }
        }
    }
    // 发送聊天消息
    /*
    if ($firstRankUid > 0) {
        $uinfo = sql_fetch_one("select * from uinfo where uid=$firstRankUid");
        $content = sprintf(' { RTE("系統：", 25,cc.c3b(255,0,0)), RTE("一整日的交戰之後，最終 ", 25,cc.c3b(0,183,0)),RTE("%s", 25,cc.c3b(255,255,255)),RTE(" 成為了今日競技場鬥士之中的最強王者，明天又會有怎樣的挑戰需要他面對呢？", 25,cc.c3b(0,183,0)) } ', $uinfo['uname']);
        _chatSendMsgOnly($content);
    }
    */
    // 邮件提醒
    DoMail();
    return;
}

function DoMail()
{
    sql_update("update uinfo set mail=1");
}

?>
