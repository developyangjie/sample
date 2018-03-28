<?php 
require_once 'db.php';
define('PAY_SECRET_KEY', 's2Li9sr0G3xH');
define('PAY_RATE', 1);

function _pay_log($msg) {
    // $filename = 'debug.log';
//     @file_put_contents($filename, date('Y-m-d H:i:s').'|'.$msg.PHP_EOL,FILE_APPEND);
}

function _validate_param($param) {
    $require_fiedls = array('account','gold','order','token','sign');
    foreach ($require_fiedls as $field) {
        if (!isset($param[$field])) {
            return 0;
        }
    }
    return 1;
}

function _validate_sign($account,$gold,$order,$token,$sign) {
    return strtolower(md5($account.'_'.$gold.'_'.$order.'_'.$token.'_'.PAY_SECRET_KEY)) === $sign;
}

function _validate_account($account) {
    $uinfo = sql_fetch_one("select * from cuser where loginname='$account'");
    if ($uinfo) {
        return $uinfo;
    } 
    return false;
}

function _add_order($account,$gold,$order,$token,$sign,$mark) {
    $user = _validate_account($account);
    if ($user === false) {
        return 7;
    }
    _pay_log('Account validate OK:'.json_encode($user));
    $uid = $user['uid'];
    $platform = $user['platform'];
    $ug = $gold * PAY_RATE;
    $ugreal = $ug;
//     if (!in_array($gold, array(100,300,500,1000,3000,5000))) {
//         return 3;
//     }
//     _pay_log('Gold validate OK');
    try {
        $pay_order = sql_fetch_one("select * from pay_txwy where billorder='$order'");
        if ($pay_order) {
            return 1;
        }
        _pay_log('Order repeat check OK');
        $vipInfo = sql_fetch_one("select vip,vippay from uinfo where uid=$uid");
        $vipCharge = intval($vipInfo['vippay']);
        $vipCharge += $ugreal;
        $vipNewLvInfo = sql_fetch_one("select vip from cfg_vip where $vipCharge >=pay order by vip desc limit 1");
        $vipNewLv = intval($vipNewLvInfo['vip']);
        $gift = sql_fetch_one("select paygift from ugift where uid=$uid");
        //     sql_query("BEGIN");
        $res1 = 1;
        $res2 = 1;
        $res3 = 1;
        if ($gold == 30) {
            _pay_log('Month Card Yes!');
            $res1 = sql_insert("insert into ugift (uid,mgift,mstep) values ($uid,UNIX_TIMESTAMP(CURDATE())+86400*30,UNIX_TIMESTAMP(CURDATE())) on duplicate key update mgift=GREATEST(mgift,UNIX_TIMESTAMP(CURDATE())) + 86400*30,mstep=GREATEST(UNIX_TIMESTAMP(CURDATE()),mstep)");
            _pay_log('Update ugift OK'.$res1);
            $res2 = sql_update("update uinfo set pvb=LEAST(pvb+2,5),ug=ug+$ug,vippay=vippay+$ugreal,vip=$vipNewLv where uid=$uid");
            _pay_log('Update uinfo OK'.$res2);
        } else {
            _pay_log('Month Card No!');
            if (!$gift || intval($gift['paygift']) == 0) {
                $ug = $ug * 3;
                $res1 = sql_insert("insert into ugift(uid,paygift) values ($uid,1) on duplicate key update paygift=GREATEST(paygift,1)");
                _pay_log('Update ugift OK'.$res1);
            }
            $res2 = sql_update("update uinfo set ug=ug+$ug,vippay=vippay+$ugreal,vip=$vipNewLv where uid=$uid");
            _pay_log('Update uinfo OK'.$res2);
        }
        $res3 = sql_insert("insert into pay_txwy (billorder,uid,account,platform,gold,ug,status,ts) values ('$order',$uid,'$account','$platform',$gold,$ug,1,UNIX_TIMESTAMP())");
        _pay_log('Insert Order OK'.$res3);
//         if ($res1 && $res2 && $res3) {
//             sql_query("COMMIT");
//             _pay_log('Commit OK');
//             return 1;
//         } else {
//             sql_query("ROLLBACK");
//             _pay_log('Commit Fail');
//             return 0;
//         }
        return 1;
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}



$params = $_GET + $_POST;
$res = _validate_param($params);
if ($res === 1) {
    $account = $params['account'];
    $gold = $params['gold'];
    $order = $params['order'];
    $token = $params['token'];
    $sign = $params['sign'];
    $mark = isset($params['mark']) ? $params['mark'] : null;
    if (_validate_sign($account, $gold, $order, $token, $sign)) {
        _pay_log('Sign validate OK');
        $res = _add_order($account, $gold, $order, $token, $sign, $mark);
    } else {
        _pay_log('Sign validate Fail');
        $res = 5;
    }
}
echo $res;
?>