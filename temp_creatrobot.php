<?php
include_once 'config.php';
include_once 'db.php';
//echo  "ok";return ;
//sql_update("Truncate table upvp");
//sql_update("delete from cuser where uid<10000");
//sql_update("delete from uinfo where uid<10000");
//sql_update("delete from uequip where uid<10000");
$filepath = $_SERVER['PHP_SELF'];
$toppayres = explode('/', $filepath);
if (count($toppayres) <= 1) {
    echo "error";
    return;
}
// $dbstr = "p11" . $res[count($res) - 2];
// if ($dbstr != DB_DATABASE) {
//     echo $dbstr;
//     return;
// }
// echo $dbstr;
$count = 3000;
$strength = 2010;
$maxlv = 70;
$sub = 1;
sql_update("insert ignore into upartner (uid,`name`,skill,partnerbase,upep,mainp,picindex) select '1' as `uid`,`name`,skill,partnerbase,upep,mainp,picindex from cfg_partner");
sql_update("delete from cuser where uid<=$count");
sql_update("delete from uinfo where uid<=$count");
sql_update("delete from uequip where uid<=$count");
$allskill = sql_fetch_rows("select sid,sjob,slv,0 as selected  from cfg_skill");
$lvlimit = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50);
$randname = sql_fetch_rows("select * from cfg_autoname order by rand() limit 1000");
$randpre = sql_fetch_rows("select * from cfg_autopre  order by rand() limit 300");
$countname = count($randname);
$countpre = count($randpre);

for ($i = 1; $i <= $count; $i++) {
    //创建用户
    $uid = $i;
    sql_update("INSERT IGNORE INTO cuser
					    		(uid,loginname,pwd) 
								VALUES
								($uid,'97934robot$i','')");
    //x:实力系数
    $x = (50 / (sqrt($i + 17)) + 7.5);
    $ulv = floor($x);
    $ujob = rand(1, 6);

    $sex = floor(($ujob - 1) / 3) + 1;
    $ujob = $ujob % 3;
    if ($ujob == 0) {
        $ujob = 3;
    }

    $minatk = floor(0.01 * $x * $x * $x + 0.1 * $x * $x + 0.5 * $x + 3.8);
    $maxatk = 2 * $minatk;
    $protect = floor(0.008 * $x * $x * $x + 0.15 * $x * $x + 0.5 * $x + 2.4);
    $dehit = floor(0.1 * $x * $x + 2 * $x + 1);
    $def = 6 * $dehit;
    $mdef = 6 * $dehit;
    $hit = floor(3.5 * $dehit);
    $decri = floor(3.5 * $dehit);
    $cri = 7 * $dehit;
    $hp = floor(35 * $dehit + 4 * $x * $x);
    $mp = floor($x * 30);
    $addmp = floor(sqrt(3.5 * $dehit));
    switch ($ujob) {
        case 1:
            $protect *= 2;
            break;
        case 2:
            $cri *= 2;
            break;
        case 3:
            $mp *= 2;
            break;
        default:
            break;
    }

    $uname = "";
    if (rand(1, 10000) > 1000) {
        //$uname=$uname.$randpre[rand(0,$countpre-1)]['name'];
    }
    $uname = $uname . $randname[rand(0, $countname - 1)]['name'].'(NPC)';

    $zhanli = intval((($minatk + $maxatk) * (70 + $ulv) / 70) + $hp / 10 + $mp / 10 + $protect + $def + $mdef + $hit + $dehit * 5 + $cri + $decri);
    $ep = "5|" . $protect . ",6|" . $mdef . ",7|" . $hit . ",8|" . $dehit . ",9|" . $decri . ",10|" . $minatk . ",11|" . $maxatk . ",12|" . $cri . ",26|" . $hp . ",27|" . $mp . ",31|" . $def . ",32|" . $addmp;

    sql_update("INSERT IGNORE INTO uinfo
					    		(uid,uname, ucoin, ulv,uexp,upower,uptime,umid,ug,ujob,step,zhanli,sex,sig) 
								VALUES
								('$uid','$uname', '0', '$ulv','0','0',unix_timestamp(),1,'0','$ujob','0',$zhanli,$sex,'战胜我，我的位置就是你的!')");
    //技能
    $up = 0;
    if ($ulv > 5) {
        $up = 1;
    }
    if ($ulv > 15) {
        $up = 2;
    }
    if ($ulv > 25) {
        $up = 3;
    }
    if ($ulv > 35) {
        $up = 4;
    }
    $skill = "";
    $skillnum = rand(0, $up);
    $temp = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
    for ($j = 0; $j < count($lvlimit); $j++) {
        if ($lvlimit[$j] > $ulv) {
            break;
        }
        $temp[$j] = 0;
    }
    shuffle($temp);
    $flag = false;
    for ($j = 0; $j < count($temp) && $skillnum > 0; $j++) {
        if ($temp[$j] == 0) {
            if ($flag) {
                $skill = $skill . ",";
            } else {
                $flag = true;
            }
            $skill = $skill . "" . $ujob . ($j < 9 ? "0" : "") . ($j + 1);
            $skillnum--;
        }
    }
    //创建装备,技能
    sql_update("insert into uequip (uid,es,ep,skill) values ('$uid','0','$ep','$skill') ON DUPLICATE KEY UPDATE es='0',ep='$ep',skill='$skill'");
    //插入pvp
    sql_update("insert into upvp (uid,zhanli) values ($uid,$zhanli) ");
}
echo "create robot end...".chr(10);
?>