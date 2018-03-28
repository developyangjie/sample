<?php
require_once 'db.php';
/*
define('CDKEY_HOST', "localhost:3306", true);
define('CDKEY_DATABASE', "sggj_gamebar", true);
define('CDKEY_USERNAME', "sggjgamebar", true);
define('CDKEY_PASSWORD', "JW8YpKN8XpTXNYu", true);
*/
define('CDKEY_HOST', "192.168.1.188:3306", true);
define('CDKEY_DATABASE', "sggj_gamebar", true);
define('CDKEY_USERNAME', "root", true);
define('CDKEY_PASSWORD', "6emYvgIYkt1VS8bjJA", true);

define('STR_DataErr', "数据错误");
define('STR_Dabaikey_Error', "兑换码非法");
define('STR_Dabaikey_Invalid', "兑换码无效");
define('STR_Dabaikey_Create_Erorr', "兑换码生成失败");

sql_connect(CDKEY_HOST, CDKEY_USERNAME, CDKEY_PASSWORD, CDKEY_DATABASE);
/*
 * $str:加密token
 * $times:使用次数
 * $keyType:cdkey类型
 * $count:总数量
 * $daynum:有效天数
 */
function dabaikeyCreater($str, $uid, $loginname)
{
    //$dayInfo = date('ymd');
    //$myStr = $dayInfo . $str . $uid . $loginname . time();
    $cdkey = "";//strtolower(substr(md5($myStr), 0, 12));
    for($i = 0; $i < 6; ++$i){
        $cdkey .= mt_rand(0, 9);
    }
    return $cdkey;
}

/**
 * 接口：获取兑换key
 * @param $uid
 * @param $param ['cdkey']
 * @return array
 */
function againdabaikey($uid, $loginname, $sid)
{
    $dabaikey = dabaikeyCreater("ttsb", $uid, $loginname);
     try { 
        $in = sql_insert("insert into sysdabaikey (dabaikey,sid,ts,uid,loginname) values ('$dabaikey', $sid, UNIX_TIMESTAMP(),$uid, '$loginname')");
        if ($in == 0){
            //!返回结果
            return array(
                    1,
                    $dabaikey
            );
        }
    } catch (Exception $e) {
        //!返回结果
        return array(
            0,
            STR_Dabaikey_Create_Erorr
        );
    }


}

/**
 * 接口：设置兑换key地址
 * @param $uid
 * @param $param ['cdkey']
 * @return array
 */
function dabaikeyAddr($uid, $loginname, $sid, $addr)
{
    $cdkey = strtolower(strval($addr['key']));
    $cdkey = trim($addr['key']);
    if (!ctype_alnum($addr['key'])) {
        return array(
                0,
                STR_Dabaikey_Error
        );
    }

    $keyinfos = sql_fetch_one("SELECT * FROM sysdabaikey WHERE dabaikey='$cdkey' and uid = $uid and loginname='$loginname' and sid=$sid");

    if (!$keyinfos){
        return array(
                0,
                STR_Dabaikey_Invalid
        );
    }
    $name = $addr['name'];
    $tel = $addr['tel'];
    //!设置地址
    sql_update("update sysdabaikey set name='$name', tel='$tel' WHERE dabaikey='$cdkey'");

    //!返回结果
    return array(
            1
    );
}

$res = array(0,STR_DataErr);
$params = $_GET + $_POST;
if (array_key_exists("cmd", $params) && array_key_exists("uid", $params) && array_key_exists("loginname", $params) && array_key_exists("sid", $params)) {
    if ($params['cmd'] == "dabaiKeyAddr"){
        $res = dabaikeyAddr($params['uid'],$params['loginname'],$params['sid'], $params);
    }elseif($params['cmd'] == "dabaiKey"){
        $res = againdabaikey($params['uid'],$params['loginname'],$params['sid']);
    }
}

echo json_encode($res);
?>