<?php
require_once 'db.php';
require_once 'platform/qh360_verify.php';
$paramStr = $_POST['param'];
file_put_contents('postLog', $paramStr . "\n", FILE_APPEND);
$param = json_decode($paramStr);
$authCode = $param[0];
$ret = null;
$accessInfo = _checkLoginQh360($authCode);
if (array_key_exists('access_token', $accessInfo)) {
    $access_token = $accessInfo['access_token'];
    if ($access_token) {
        $userinfo = null;//sql_fetch_one_cell("select uid from cuser where loginname='$loginname' and pwd='$pwd'");
        if ($userinfo) {//存在
            return array(1, _getUidKey($uid), intval($userinfo['uid']), $userinfo['loginname'], $access_token, "xxxx");
        } else {//不存在去取用户的信息
            $uinfo = _getLoginInfoQh360($access_token);
            if (array_key_exists("id", $uinfo)) {
                sql_connect();
                $qh360Id = intval($uinfo['id']);
                sql_update("insert into login_qh360(qhid,access_token,createTs,lastTs)values($qh360Id,'$access_token',unix_timestamp(),unix_timestamp()) on duplicate key update access_token='$access_token',lastTs=unix_timestamp()");
                $login_name = $uinfo['name'];
                $expires_in = $accessInfo['expires_in'];
                $refresh_token = $accessInfo['refresh_token'];
                $ret = array(1, $qh360Id, $access_token, $login_name, $expires_in, $refresh_token);
            } else {
                $errcode = $accessInfo['error_code'];
                $ret = array(5, "平臺獲取用戶資訊錯誤:" . $errcode);
            }
        }
    } else {
        $ret = array(0, "平臺驗證錯誤!");
    }
} else {
    $errcode = $accessInfo['error_code'];
    $ret = array(5, "平臺登入驗證錯誤:" . $errcode);
}
echo json_encode($ret);
file_put_contents('postLog', json_encode($ret) . "\n", FILE_APPEND);
?>