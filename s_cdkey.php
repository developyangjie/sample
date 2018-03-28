<?php
define('CDKEY_HOST', "db.ttgj:3306", true);
define('CDKEY_DATABASE', "p11cdkey", true);
define('CDKEY_USERNAME', "p11cdkey", true);
define('CDKEY_PASSWORD', "yeswecan", true);
//function getCdKeyGift($uid,$param) {
//	$cdkeyType	=intval($param[0]);
//	$cdkey		=strval($param[1]);
//	if(!ctype_alnum($cdkey)){
//		return array(1,"非法的兌換碼!");
//	}
//	$giftInfo=_getGift($cdkeyType);
//	$giftTable=$giftInfo[0];
//	$gitfs=$giftInfo[1];
//	if(!$giftTable){
//		return array(2,"禮包不存在!");
//	}
//	$cdkeyInfo=sql_fetch_one("select kid,uid from $giftTable where cdkey='$cdkey' and (endTs=0 or endTs>unix_timestamp())");
//	if(!$cdkeyInfo){
//		return array(3,"兌換碼錯誤!");
//	}
//	$keyUid=intval($cdkeyInfo['uid']);
//	if($keyUid>0){
//		return array(4,"該兌換碼已經使用過!");
//	}
//	$hasUsed=sql_fetch_one("select kid from cdkey_template where uid=$uid");
//	if($hasUsed) return array(5,"不能重複領取禮包!");
//
//	$kid=intval($cdkeyInfo['kid']);
//	sql_update("update $giftTable set uid=$uid,gotTs=unix_timestamp() where kid=$kid");
//
//	$gotAr=array();
//	foreach ($gitfs as $cid => $count) {
//		$gotAr[]=_addAward($uid, $cid, $count,0);
//	}
//	return array(0,$gotAr);
//}
//function _getGift($cdkeyType) {
//	$giftTable;
//	$gifts;
//	switch ($cdkeyType){
//		case 1:
//			$giftTable="cdkey_template";
//			$gifts=array('1'=>10000,'101'=>1);
//			break;
//		case 2:
//			break;
//	}
//	return array($giftTable,$gifts);
//}
function _getJiaqunGift($uid)
{
    $gift_xiaomi = sql_fetch_one("select * from ugift_xiaomi1 where uid=$uid");
    $hasGot = 0;
    if (!$gift_xiaomi) {
        sql_insert("insert into ugift_xiaomi1 (uid) values ($uid)");
    } else {
        $hasGot = intval($gift_xiaomi['jqlb']);
        if ($hasGot) {
            return array(0, "你已領取過加群禮包了!");
        }
    }
    //$giftInfo=sql_fetch_one("select * from cdkey_xiaomi_gift where type=$type and giftType=$giftType");
    $ug = 300;
    $coin = 0;
    $s1 = 0;

    $giftInfo['ug'] = $ug;
    $giftInfo['coin'] = $coin;
    $giftInfo['s1'] = $s1;
    sql_update("update ugift_xiaomi1 set jqlb=1 where uid=$uid");
    _getGiftXiaomi($uid, $ug, $coin, $s1, 0, 0);
    return array(1, $giftInfo);
}

/**
 * 接口：获取CDKey兑换奖励
 * @param $uid
 * @param $param ['platform','cdkey']
 * @return array
 */
function getCdKeyGift($uid, $param)
{
    $platform = $param[0];
    $cdkey = strtolower(strval($param[1]));
    $cdkey = trim($cdkey);
    if (!ctype_alnum($cdkey)) {
        return array(0, "非法的兌換碼!");
    }
    if ($cdkey == 'jqlb') {
        //加群奖励;
        return _getJiaqunGift($uid);
    }
    $cdkeytype = substr($cdkey, 0, 3);
    if (strlen($cdkey) == 16) {
        $gifttype = substr($cdkey, 3, 1);
    } else {
        $gifttype = substr($cdkey, 3, 2);
    }

    if ($cdkeytype == 'jda') {
        return _getCdkeyGiftNew($uid, $cdkey, $gifttype);
    }
    //聊天室奖励,cdkey 开头为'lt'
    $cdkeytype2 = substr($cdkeytype, 0, 2);
    if ($cdkeytype2 == 'lt') {
        return _getChatroomGift($uid, $cdkey);
    }

    switch ($platform) {
        case "local":
        case "xiaomi":
        case "qh360":
            return _getXiaomiGift($uid, $cdkey);
            break;
    }
    return array(0, "兌換平臺錯誤!");
}

function getNewType($type, $giftType)
{
    $giftType = intval($giftType);
    $newType = 0;
    switch ($type) {
        case 1:
            if ($giftType <= 4 && $giftType >= 1) {
                $newType = $giftType;
            }
            if ($giftType > 4) {
                $newType = $giftType - 4;
            }
            break;
        default:
            break;
    }
    return $newType;
}

function _getCdkeyGiftNew($uid, $cdkey, $gifttype)
{
    $newType = getNewType(1, $gifttype);
    //step1 判断我是不是领过这种类型礼包了
    $gift_xiaomi = sql_fetch_one("select * from ugift_xiaomi1 where uid=$uid");
    $hasGot = 0;
    if (!$gift_xiaomi) {
        sql_insert("insert into ugift_xiaomi1 (uid) values ($uid)");
    } else {
        $hasGot = intval($gift_xiaomi['type' . $newType]);
        if ($hasGot) {
            return array(0, "你已領取過該類型禮包了!");
        }
    }
    //step2 直接领取礼包
    sql_connect(CDKEY_HOST, CDKEY_USERNAME, CDKEY_PASSWORD, CDKEY_DATABASE);
    $ret = sql_update("update cdkey_all set uid=$uid,ts=unix_timestamp() where cdkey='$cdkey' and uid=0");
    if ($ret == 0) {
        return array(0, "兌換碼錯誤或已經被領取過!");
    }
    //step3 加奖励 &切会本业务服务器
    sql_connect();
    $giftInfo = sql_fetch_one("select * from cdkey_xiaomi_gift where type=1 and giftType=$gifttype");
    $ug = 0;
    $coin = 0;
    $s1 = 0;
    $itemid = 0;
    $count = 0;
    if (!$giftInfo) {
        return array(0, "禮包獎勵不存在!");
    }
    if ($giftInfo['ug']) {
        $ug = $giftInfo['ug'];
    }
    if ($giftInfo['coin']) {
        $coin = $giftInfo['coin'];
    }
    if ($giftInfo['s1']) {
        $s1 = $giftInfo['s1'];
    }
    if ($giftInfo['itemid']) {
        $itemid = $giftInfo['itemid'];
    }
    if ($giftInfo['count']) {
        $count = $giftInfo['count'];
    }
    if (!sql_update("update ugift_xiaomi1 set type$newType=1 where uid=$uid and type$newType=0")) {
        return array(0, "你已領取過該類型禮包");
    }
    _getGiftXiaomi($uid, $ug, $coin, $s1, $itemid, $count);
    return array(1, $giftInfo);
}

function _getXiaomiGift($uid, $cdkey)
{
    $cdkey = trim($cdkey);
    if (!ctype_alnum($cdkey)) {
        return array(0, "非法的兌換碼!");
    }
    $info = sql_fetch_one("select * from cdkey_xiaomi where cdkey='$cdkey'");
    if (!$info) {
        _errorlog("cdkey:" . $cdkey);
        return array(0, "兌換碼不存在!");
    }
    //	$kid		=intval($info['kid']);
    $type = intval($info['type']);
    $giftType = intval($info['giftType']);
    $giftuid = intval($info['uid']);
    if ($giftuid > 0) return array(0, "該禮包已經被領取過!");

    $newType = getNewType($type, $giftType);

    $gift_xiaomi = sql_fetch_one("select * from ugift_xiaomi1 where uid=$uid");
    $hasGot = 0;
    if (!$gift_xiaomi) {
        sql_insert("insert into ugift_xiaomi1 (uid) values ($uid)");
    } else {
        $hasGot = intval($gift_xiaomi['type' . $newType]);
        if ($hasGot) {
            return array(0, "你已領取過該類型禮包了!");
        }
    }
    $giftInfo = sql_fetch_one("select * from cdkey_xiaomi_gift where type=$type and giftType=$giftType");
    $ug = 0;
    $coin = 0;
    $s1 = 0;
    $itemid = 0;
    $count = 0;
    if (!$giftInfo) {
        return array(0, "禮包獎勵不存在!");
    }
    if ($giftInfo['ug']) {
        $ug = $giftInfo['ug'];
    }
    if ($giftInfo['coin']) {
        $coin = $giftInfo['coin'];
    }
    if ($giftInfo['s1']) {
        $s1 = $giftInfo['s1'];
    }
    if ($giftInfo['itemid']) {
        $itemid = $giftInfo['itemid'];
    }
    if ($giftInfo['count']) {
        $count = $giftInfo['count'];
    }

    if (!sql_update("update ugift_xiaomi1 set type$newType=1 where uid=$uid and type$newType=0")) {
        return array(0, "你已領取過該類型禮包");
    }
    if (!sql_update("update cdkey_xiaomi set uid=$uid,ts=unix_timestamp() where cdkey='$cdkey' and uid=0")) {
        return array(0, "該禮包已經被領取");
    }

    _getGiftXiaomi($uid, $ug, $coin, $s1, $itemid, $count);
    return array(1, $giftInfo);
}

function _getChatroomGift($uid, $cdkey)
{
    sql_connect(CDKEY_HOST, CDKEY_USERNAME, CDKEY_PASSWORD, CDKEY_DATABASE);
    $ltGif = sql_fetch_one("select * from cdkey_lt where cdkey='$cdkey'");
    if (!$ltGif) {
        return array(0, "禮包獎勵不存在!");
    }
    $ltuid = intval($ltGif['uid']);
    if ($ltuid != 0) {
        return array(0, "該禮包已經被領取");
    }

    sql_connect();
    $gift_xiaomi = sql_fetch_one("select * from ugift_xiaomi1 where uid=$uid");
    $hasGot = 0;
    if (!$gift_xiaomi) {
        sql_insert("insert into ugift_xiaomi1 (uid) values ($uid)");
    } else {
        $hasGot = intval($gift_xiaomi['type10']);
        if ($hasGot) {
            return array(0, "你已領取過聊天室禮包了!");
        }
    }

    sql_connect(CDKEY_HOST, CDKEY_USERNAME, CDKEY_PASSWORD, CDKEY_DATABASE);
    sql_update("update cdkey_lt set uid=$uid,ts=unix_timestamp()  where cdkey='$cdkey' and uid=0");
    sql_connect();

    sql_update("update ugift_xiaomi1 set type10=1 where uid=$uid");

    $ug = $ltGif['ug'];
    $coin = $ltGif['coin'];
    if ($ug == 0 && $coin == 0) {
        $ug = 50;
    }
    sql_update("update uinfo set ug=ug+$ug,ucoin=ucoin+$coin where uid=$uid");

    $giftInfo['ug'] = $ug;
    $giftInfo['coin'] = $coin;
    //$giftInfo['s1']=0;
    $giftInfo['itemid'] = 0;
    $giftInfo['count'] = 0;

    return array(1, $giftInfo);

}

function _getGiftXiaomi($uid, $ug, $coin, $s1, $itemid, $count)
{
    if ($ug < 0) {
        $ug = 0;
    }
    if ($coin < 0) {
        $coin = 0;
    }
    if ($s1 < 0) {
        $s1 = 0;
    }
    sql_update("update uinfo set ug=ug+$ug,ucoin=ucoin+$coin where uid=$uid");
    _coinlog($uid . "," . $coin . ",cdkey,1");
    _glog($uid . "," . $ug . ",addugcdkey");
    if ($s1 > 0) {
        _addJinghua($uid, $s1);
    }
    if ($itemid > 0 && $count > 0) {
        _addItem($uid, $itemid, $count);
        _itemlog($uid . ",cdkey," . $itemid . "," . $count . ",1");
    }
}

?>