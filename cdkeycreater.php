<?php
require_once 'db.php';
define('CDKEY_HOST', "localhost:3306", true);
define('CDKEY_DATABASE', "sggj_gamebar", true);
define('CDKEY_USERNAME', "sggjgamebar", true);
define('CDKEY_PASSWORD', "JW8YpKN8XpTXNYu", true);

define('STR_DataErr', "数据错误");
define('STR_Cdkey_Error', "兑换码非法");
define('STR_Cdkey_Invalid', "兑换码无效或已过期");
define('STR_Cdkey_Used', "兑换码已使用");
define('STR_Cdkey_Type_Same', "相同类型的兑换码已使用过");

sql_connect(CDKEY_HOST, CDKEY_USERNAME, CDKEY_PASSWORD, CDKEY_DATABASE);
$token = 'ttsb';

//keyCreater($token, 1, '01', 10000, 5);
//echo success;
//exist();

/*
 * $str:加密token
 * $times:使用次数
 * $keyType:cdkey类型
 * $count:总数量
 * $daynum:有效天数
 */
function keyCreater($str, $alltimes, $keyType, $count, $daynum)
{
    $dayInfo = date('ymd');
    $endday = strtotime(date('Y-m-d')) + $daynum * 86400;
    for ($i = 0; $i < $count; $i++) {
        $myStr = $dayInfo . $str . $alltimes . $keyType . $i;
        $cdkey = strtolower('sy' . $keyType . substr(md5($myStr), 0, 8));
        sql_insert("insert into syscdkey (cdkey,alltimes,keytype,ts,uid,usetimes,dt,et) values ('$cdkey',$alltimes,'$keyType',0,0,0,$dayInfo,$endday)");
    }
}

/**
 * 接口：获取CDKey兑换奖励
 * @param $uid
 * @param $param ['cdkey']
 * @return array
 */
function checkCdkey($uid, $loginname, $cdkey)
{
    $reward = array();
    $reward['01'] = array(
            //!奖励ID，数量
            array(2, 100)    //!元宝
    );
    $cdkey = strtolower(strval($cdkey));
    $cdkey = trim($cdkey);
    if (!ctype_alnum($cdkey)) {
        return array(
                0,
                STR_Cdkey_Error
        );
    }
    
    $keyinfos = sql_fetch_one("SELECT * FROM syscdkey WHERE cdkey='$cdkey' and et > UNIX_TIMESTAMP(CURDATE())");

    if (!$keyinfos){
        return array(
                0,
                STR_Cdkey_Invalid
        );
    }elseif(intval($keyinfos['alltimes']) <= intval($keyinfos['usetimes'])){
        return array(
                0,
                STR_Cdkey_Used
        );
    }
    
    //!已经其它角色使用
    $olduid = intval($keyinfos['uid']);
    if ($olduid != 0 && !($olduid == $uid && $loginname == $keyinfos['loginname'])){
        return array(
                0,
                STR_Cdkey_Used
        );
    }
    
    //!相同类型的key已经使用过
    $rewardold = sql_fetch_one("SELECT * FROM syscdkey WHERE uid=$uid and loginname='$loginname'");
    if ($rewardold && $rewardold['cdkey'] != $cdkey && $rewardold['keytype'] == $keyinfos['keytype']){
        return array(
                0,
                STR_Cdkey_Type_Same
        );
    }

    //!扣除
    sql_update("update syscdkey set loginname='$loginname', uid=$uid, ts=UNIX_TIMESTAMP(), usetimes=usetimes+1 where cdkey='$cdkey'");

    //!返回结果
    return array(
            1,
            $reward[$keyinfos['keytype']]
    );
}

$res = array(0,STR_DataErr);
$params = $_GET + $_POST;
if (array_key_exists("uid", $params) && array_key_exists("loginname", $params) && array_key_exists("cdkey", $params)) {
    $res = checkCdkey($params['uid'],$params['loginname'],$params['cdkey']);
}

echo json_encode($res);
?>