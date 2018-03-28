<?php
require_once 'config.php';
require_once 'db.php';
date_default_timezone_set('Asia/Shanghai');
$t = date('YmdHis');
$alltablesres = sql_fetch_rows("show tables");
foreach ($alltablesres as $alltable) {
    $tname = $alltable['Tables_in_p11'];
    $tablecreate = sql_fetch_one("show create table $tname");
    if ($tablecreate) {
        _createsql("_struct" . $t, $tablecreate['Create Table']);

    }
    _createsql("_struct" . $t, "truncate table $tname");
}
$struct1 = "INSERT ignore into cuser (uid,loginname,pwd) VALUES (10000,'','')";
$struct2 = "INSERT IGNORE into upartner (partnerid,
`name`,
skill,
partneres,
partnerep,
mainp,
newep,
uid,
oldep,
picindex) VALUES (100000,'','','','','','','','','')";
_createsql("_struct" . $t, $struct1);
_createsql("_struct" . $t, $struct2);
$cfg_tables = array('cfg_chenghao', 'cfg_chargegift', 'cdkey_xiaomi_gift', 'cfg_autoname', 'cfg_autopre', 'cfg_boss', 'cfg_buff', 'cfg_chat_ban_ip', 'cfg_chat_ban_uid', 'cfg_clublv', 'cfg_equip', 'cfg_equipexp', 'cfg_gem', 'cfg_item', 'cfg_keywords', 'cfg_map', 'cfg_monster', 'cfg_name', 'cfg_p', 'cfg_partner', 'cfg_pvpaward', 'cfg_shop', 'cfg_shopbag', 'cfg_skill', 'cfg_upcost', 'cfg_uprate', 'cfg_userlv', 'cfg_vip', 'cfg_tower', 'uactgift', 'cfg_club_skill');
foreach ($cfg_tables as $tablename) {
    $toppayres = sql_fetch_rows("select * from $tablename");
    foreach ($toppayres as $onedata) {
        $keys = array();
        $values = array();
        $updates = array();
        foreach ($onedata as $key => $value) {
            $keys[] = "`" . $key . "`";
            $values[] = "'" . $value . "'";
            $updates[] = "`" . $key . "`" . "=" . "'" . $value . "'";
        }
        $sql = "INSERT into $tablename (" . implode(',', $keys) . ") values (" . implode(',', $values) . ") on DUPLICATE KEY UPDATE " . implode(',', $updates);
        _createsql("_cfgdata" . $t, $sql);
    }
}

function _createsql($t, $sql)
{
    @file_put_contents("log/p11" . $t . ".sql", $sql . ";\n", FILE_APPEND);
    @file_put_contents("log/p11_db.sql", $sql . ";\n", FILE_APPEND);
}

function _createrobot()
{
    $time = time();
    $acc = 100000;
    for($num = 1; $num <= 100; $num++ ){
        $uid = $acc + $num;
        $accstr = "w"."$num";
        $ret = sql_update("INSERT INTO `cuser` (`uid`,`loginname`, `pwd`, `ts`, `platform`) VALUES($uid, '$accstr', '1111', $time, 'local')");
        if($ret){
            $girlres = addGirl($uid, 0);
            $name1 = sql_fetch_one("select * from cfg_autopre order by rand() limit 1");
            $name2 = sql_fetch_one("select * from cfg_autoname order by rand() limit 1");
            $uname = $name1['name'] . $name2['name'];
            $ucoin = 20000;
            $ug = 0;
            $ulv = 1;
            $unlv = 1;
            $uexp = 0;
            $ujob = 0;
            $step = 1;
            $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
            $offset = 108000;
            if ($h < 6) {
                $offset = 21600;
            }
            sql_insert("INSERT INTO uinfo(uid,uname, ucoin, ulv,uexp,uptime,umid,ug,ujob,refreshtime,loginday,pvp,winrate,partnerskill,logintime) VALUES
            ('$uid', '$uname', '$ucoin', '$ulv','$uexp',unix_timestamp(),1,'$ug','$ujob',UNIX_TIMESTAMP(CURDATE())+$offset,1,5,90,1,unix_timestamp())");
            $Bclass = array(104,105,106,107,204,304,404,405,406,407,408,409,504,604,605,606,607);
            $Aclass = array(101,201,202,301,302,401,402,501,502,601,602);
            $Sclass = array(103,203,303,403,503,603);
            $pid = 0;
            $addp = array();
            $cpids = array();
            if($num <= 5){
                $rand1 = rand(0, count($Sclass) - 1);
                $rand2 = rand(0, count($Sclass) - 1);
                $rand3 = rand(0, count($Sclass) - 1);
                $cpids[] = $Sclass[$rand1];
                $cpids[] = $Sclass[$rand2];
                $cpids[] = $Sclass[$rand3];
            }
            elseif($num >= 6 && $num <= 15){
                $rand1 = rand(0, count($Sclass) - 1);
                $rand2 = rand(0, count($Sclass) - 1);
                $rand3 = rand(0, count($Aclass) - 1);
                $cpids[] = $Sclass[$rand1];
                $cpids[] = $Sclass[$rand2];
                $cpids[] = $Aclass[$rand3];
            }
            elseif($num >= 16 && $num <= 40){
                $rand1 = rand(0, count($Sclass) - 1);
                $rand2 = rand(0, count($Aclass) - 1);
                $rand3 = rand(0, count($Aclass) - 1);
                $cpids[] = $Sclass[$rand1];
                $cpids[] = $Aclass[$rand2];
                $cpids[] = $Aclass[$rand3];
            }
            elseif($num >= 41 && $num <= 50){
                $rand1 = rand(0, count($Aclass) - 1);
                $rand2 = rand(0, count($Aclass) - 1);
                $rand3 = rand(0, count($Aclass) - 1);
                $cpids[] = $Aclass[$rand1];
                $cpids[] = $Aclass[$rand2];
                $cpids[] = $Aclass[$rand3];
            }
            elseif($num >= 51 && $num <= 60){
                $rand1 = rand(0, count($Aclass) - 1);
                $rand2 = rand(0, count($Aclass) - 1);
                $rand3 = rand(0, count($Bclass) - 1);
                $cpids[] = $Aclass[$rand1];
                $cpids[] = $Aclass[$rand2];
                $cpids[] = $Bclass[$rand3];
            }
            elseif($num >= 61 && $num <= 70){
                $rand1 = rand(0, count($Aclass) - 1);
                $rand2 = rand(0, count($Aclass) - 1);
                $rand3 = rand(0, count($Bclass) - 1);
                $cpids[] = $Aclass[$rand1];
                $cpids[] = $Aclass[$rand2];
                $cpids[] = $Bclass[$rand3];
            }
            elseif($num >= 71 && $num <= 80){
                $rand1 = rand(0, count($Aclass) - 1);
                $rand2 = rand(0, count($Bclass) - 1);
                $rand3 = rand(0, count($Bclass) - 1);
                $cpids[] = $Aclass[$rand1];
                $cpids[] = $Bclass[$rand2];
                $cpids[] = $Bclass[$rand3];
            }
            elseif($num >= 81 && $num <= 100){
                $rand1 = rand(0, count($Bclass) - 1);
                $rand2 = rand(0, count($Bclass) - 1);
                $rand3 = rand(0, count($Bclass) - 1);
                $cpids[] = $Bclass[$rand1];
                $cpids[] = $Bclass[$rand2];
                $cpids[] = $Bclass[$rand3];
            }
            foreach ($cpids as $cpid){
                $addp[] = _createPartner($uid, $cpid);
            }
            $addpstr = implode(",", $addp);
            $leader = intval($addp[0]);
            sql_insert("INSERT INTO `uequip` (`uid`, `stagepartner`, `pvestagepartner`, `pvpstagepartner`, `leader`, `pveleader`, `pvpleader`, `brave`, `girl`, `pvegirl`, `pvpgirl`, `fuid`) VALUES
            ($uid, '', '$addpstr', '$addpstr', 0, $leader, $leader, $leader, 1, 1, 1, 0)");
            sql_insert("INSERT INTO `upvp` (`index`, `uid`, `win`, `lose`, `bestindex`, `pktime`, `zhanli`, `match`) VALUES
            ($num, $uid, 1, 0, $num, $time, 0, NULL)");
            $myequip = sql_fetch_one("select * from uequip where uid=$uid");
            $myinfo = sql_fetch_one("select * from uinfo where uid=$uid");
            $my = new my(1, $myinfo, $myequip);
            $zhanli = $my->zhanli;
            $myinfo['zhanli'] = $zhanli;
            sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
            sql_update("update upvp set zhanli=$zhanli where uid=$uid");
        }
    }
}

