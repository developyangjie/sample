<?php
//
//
//                            _ooOoo_
//                           o8888888o
//                           88" . "88
//                           (| -_- |)
//                            O\ = /O
//                        ____/`---'\____
//                      .   ' \\| |// `.
//                       / \\||| : |||// \
//                     / _||||| -:- |||||- \
//                       | | \\\ - /// | |
//                     | \_| ''\---/'' | |
//                      \ .-\__ `-` ___/-. /
//                   ___`. .' /--.--\ `. . __
//                ."" '< `.___\_<|>_/___.' >'"".
//               | | : `- \`.;`\ _ /`;.`/ - ` : | |
//                 \ \ `-. \_ __\ /__ _/ .-` / /
//         ======`-.____`-.___\_____/___.-`____.-'======
//                            `=---='
//                          信佛祖，无BUG
//         .............................................
// $res = array(0,'服务器正在维护中，预计维护时间为0:00~2:00，给您带来的不便尽请谅解，游戏维护结束后，将给予您维护补偿，感谢您对三国挂机的支持！');
// if (isset($_POST['callback'])) {
//     echo $_POST['callback'].'('.json_encode($res).')';
// } else {
//     echo json_encode($res);
// }
// exit();


require_once 'cmd.php';
header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('Asia/Shanghai');
$ret;
$alllog=array();
$newulv = 0;
$newzhanli = 0;

function log_newulv($ulv) {
    global $newulv;
    $newulv = $ulv;
}

function log_newzhanli($zhanli) {
    global $newzhanli;
    $newzhanli = $zhanli;
}

function log_log($type,$msg) {
    global $alllog;
    if (is_array($msg)) {
        $msg = implode('|', $msg);
    }
    $alllog[] = array($type,$msg);
}
$ver = $_POST['ver'];
$cmd = $_POST['cmd'];
$func = $_POST['func'];
$sid = $_POST['sid'];
$paramstr = $_POST['param'];
// $paramstr = substr($paramstr, 3);
$paramstr = str_replace('-', '+', $paramstr);
$paramstr = str_replace('*', '/', $paramstr);
$paramstr = str_replace('!', '=', $paramstr);
$decode = base64_decode($paramstr);
//file_put_contents("login.txt", $paramstr.":".$decode."\n", FILE_APPEND);
$param = json_decode($decode);

// $ver = $_POST['ver'];
// $cmd = $_POST['cmd'];
// $func = $_POST['func'];
// $param = json_decode($_POST['param'], true);
$vers = explode(".", $ver);
$baseVer = intval($vers[0]) * 10000 + intval($vers[1]) * 100 + intval($vers[2]);
$starttime = microtime(true);
if ($baseVer < 10000) {
    $ret = array(
        2,
        "版本错误"
    );
} else {
    if (! is_array($param)) {
        $param = array();
    }
    $param[] = $baseVer;
    if ($cmd == "g") {
        $uid = 'g';
        $param = array_merge(array(
            $func
        ), $param);
        $ret = cmd::g($param);
    } else {
//         $uid = intval($_POST['uid']);
        $uid = intval($_POST['uid']);
        $uidkey = null;
        if (array_key_exists("uidkey", $_POST)) {
//         if (array_key_exists("uidkey", $_POST)) {
            $uidkey = $_POST['uidkey'];
//             $uidkey = $_POST['uidkey'];
            $signkey = _readUidKey($uid,$sid);
//             $signkey2 = md5(DB_DATABASE . $uid);
            if ((! $signkey || $signkey != $uidkey) &&$func!='login') {
                $ret = array(
                    3,
                    "登录已过期或从其它地方登录，请重新登录",
                );
            } else {

            	 	          	
                $param = array_merge(array(
                    $uid,
                    $func,
                	$sid
                ), $param);

                $ret = cmd::c($param);
                //判断是否封号
                //是否封号

                if($func!='login')
                {
                	$uinfo = sql_fetch_one("SELECT forbid,forbidtime,forbidreason FROM uinfo WHERE uid=$uid");
                	if($uinfo['forbid']==1&&$uinfo['forbidtime']>time())
                	{
                		$date=date("Y-m-d h:i:s", $uinfo['forbidtime']);
                		$ret= array(
                				4,
                				'您被检测到在游戏内'.$uinfo['forbidreason']."m被封停至".$date.",有异议可以咨询QQ:2150533667!"
                		);
                	}
                }
                
                
                $endtime = microtime(true);
                $lasttime = (int) (($endtime - $starttime) * 1000);
                $theparam = array($ver,$lasttime);
                $theparam = array_merge($theparam,$param);
                $retstr = '~1';
                if (is_array($ret)) {
                    if (is_array($ret[1])) {
                        if ($ret[1][0] == 0) {
                            $retstr = "~0[".$ret[1][1]."]";
                        }
                    } elseif ($ret[1] == 0) {
                        $retstr = "~0";
                    }
                }
                $theparam[] = $retstr;
                log_log('cmd', $theparam);
            }
        } else {
            $ret = array(
                2,
                "数据错误"
            );
        }
    }
}

// if (isset($_POST['callback'])) {
//     echo $_POST['callback'] . '(' . json_encode($ret) . ')';
// } else {
//     echo json_encode($ret);
// }
function varcheck($version){
	_sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
	$serverid=intval(SERVER_ID);
    return 	$ver = _sql_fetch_one_cell("select version from server_list where id=$serverid");
}

if (isset($_POST['callback'])) {

    echo $_POST['callback'] . '(' . json_encode($ret) . ')';
} else {
	$upvar=varcheck($baseVer);
	array_push($ret, $upvar);
    echo json_encode($ret);
}

// 排行榜
// if (isset($uid) && $uid != 'g') {
//     if ($newulv > 0 || $newzhanli > 0) {
//         require_once 'platform/gamebar_verify.php';
//         $snsinfo = _sql_fetch_one("select * from cuser where uid=$uid limit 1");
//         if ($snsinfo) {
//             $openid = $snsinfo['loginname'];
//             $openkey = $snsinfo['pwd'];
//             $sdk = new WBAuthSDK();
//             if ($newulv > 0) {
//                 $tmp = $sdk->set_achievement($openid, $openkey, 'key1', $newulv);
//                 log_log('setachievement', $tmp);
//             }
//             if ($newzhanli > 0) {
//                 $tmp = $sdk->set_achievement($openid, $openkey, 'level', $newzhanli);
//                 log_log('setachievement', $tmp);
//             }
//         }
//     }
// }
// 处理日志
if (!empty($alllog)) {
    foreach ($alllog as $thelog) {
        $filename = '../log/'.date('Ymd').'.'.DB_DATABASE.'.'.$thelog[0].'log.log';
        @file_put_contents($filename, date('Y-m-d H:i:s').'|'.$thelog[1].PHP_EOL,FILE_APPEND);        
    }
}
exit();
?>