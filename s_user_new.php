<?php
include_once 'language.php';
include_once 'my_new.php';
include_once 'pve.php';
include_once 'pvp.php';
include_once 'equip.php';
include_once 'battle.php';
require_once 's_login.php';
include_once 'gift.php';
include_once 'friend.php';
include_once 'activityNew.php';
include_once 'shop.php';
include_once 'dailyTask.php';
include_once 'worldboss.php';
include_once 'tool.php';
include_once 'battleCheck.php';
// include_once '8xiawanlogins.php';
// include_once 'egret_pay_polling.php';
// include_once '8xiawan_pay_polling.php';


define('MAXMAPNUM', 80);
define('MAXULV', 160);



/**
 * 接口：注销
 *
 * @param
 *            $uid
 * @param
 *            $params
 * @return array
 */
function logout($uid, $params)
{
    $starttime = microtime(true);
    $endtime = microtime(true);
    $res = _delUidKey($uid);
    $datestr = date('Y-m-d h:i:s',time());
    $suffix = rand(10, 99);
    $requestid = date('Ymdhis',time());
    $requestid = $uid.$requestid.$suffix;
    $lasttime = (int) (($endtime - $starttime) * 1000);
    $content = 'PlayerLogout|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'PlayerRegister'.'|'.$requestid.'|'.SERVER_ID;
    _createlogfile('hylr',$content);
    return array(
        1,
        array($res)
    );
}

/**
 * 接口：获取公告
 *
 * @param
 *            $params
 * @return array
 */
function getNews($uid,$params)
{
    $newsinfo = sql_fetch_one("select news from server_news where startts <= UNIX_TIMESTAMP() and (endts > UNIX_TIMESTAMP() or endts = 0) order by startts desc limit 1");
    if ($newsinfo) {
        return array(
            1,
            $newsinfo['news']
        );
    } else {
        return _news();
    }
}


function _getUidKey($uid,$sid)
{
    require_once 'memcache.php';
    $key = md5(DB_DATABASE . $uid . time());
    $res = mem_instance()->set('user_key:' . $uid.':'.$sid, $key);
    mem_instance()->expire('user_key:' . $uid.':'.$sid, 86400);
    if ($res) {
        return $key;
    }
    return '';
}

function _readUidKey($uid,$sid)
{
    require_once 'memcache.php';
    $res = mem_instance()->get('user_key:' . $uid.':'.$sid);
    if ($res) {
        return $res;
    }
    return '';
}

function _delUidKey($uid,$sid) {
    require_once 'memcache.php';
    $res = mem_instance()->delete('user_key:' . $uid.':'.$sid);
    return $res;
}


/**
 * 接口：登录
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function login($uid, $params)
{
    $starttime = microtime(true);
	//这次的uid是平台uid，并非游戏uid
	$cuid=$uid;
    $serverid = $params[0];
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts FROM uinfo WHERE cuid=$cuid and serverid= $serverid");
    
    if ($uinfo == null) {
        $ucoin = 20000;
        $ug = 0;
        $ulv = 1;
        $uexp = 0;
        $ujob = 0;
        $keynum=10;
        $h = intval(sql_fetch_one_cell("SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(),'%H') as h"));
        $offset = 108000;
        if ($h < 6) {
            $offset = 21600;
        }       
        sql_insert("INSERT INTO uinfo(cuid,serverid, ucoin, ulv,uexp,uptime,ug,ujob,refreshtime,loginday,pvp,logintime) 
								VALUES($cuid,$serverid, '$ucoin', '$ulv','$uexp',unix_timestamp(),'$ug','$ujob',UNIX_TIMESTAMP(CURDATE())+$offset,1,5,unix_timestamp())");
        //给uid赋值
        $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts FROM uinfo WHERE cuid=$cuid and serverid= $serverid");
        $uid=$uinfo['uid'];
        //插入每日任务
        $task_arr=sql_fetch_rows("select * from cfg_dailytask");
        foreach ($task_arr as $value)
        {
        	$taskid=$value['id'];
        	sql_insert("insert into udailytask(uid,tid,process,isGet,ts) values($uid,$taskid,0,0,UNIX_TIMESTAMP())");
        }
        //插入成长任务
        _updateGrowTaskProcess($uid,1);
        _updateGrowTaskProcess($uid,100);
        _updateGrowTaskProcess($uid,171);
        _updateGrowTaskProcess($uid,191);
        //!直接给武将
        _createPartner($uid, 201, 0,false);
        _createPartner($uid, 604, 0,false);
        //_addItem($uid, 401, 1);
        $girlres = addGirl($uid, 0);
        if($girlres[0] == 1){
            sql_update("insert into uequip (uid,girl,pvegirl,pvpgirl) values ($uid, 1, 1, 1) ON DUPLICATE KEY UPDATE girl = 1,pvegirl = 1,pvpgirl = 1");
        }
        $rlogparams = array($cuid,$serverid,$uid,0);
        registerlog_uid($rlogparams);
        sql_insert("insert into udrawforpay(uid,pay) values($uid,0)");
    }
    //给uid赋值
    $uid=$uinfo['uid'];
    //重置pvp次数
    $pvpreset = $uinfo['pvpreset'];
    if($pvpreset == 0){
        $pvptimes = _getVipByPvpTimes($uid);
        $uinfo['pvp'] = $pvptimes;
        sql_update("update uinfo set pvp = $pvptimes, pvpreset = 1 where uid = $uid");
    }
    
    $logparams = array();
    $cuid=$uinfo['cuid'];//intval(sql_fetch_one_cell("select cuid from uinfo where uid=$uid"));
    $loginip = getClientIp();
    _getSystemData($serverid, $cuid,$logparams);
    $ulog = array($uid,$uinfo['uname'],$uinfo['ulv'],$loginip);
    $ulogparams = array_merge($logparams,$ulog);
    uidloginlog($ulogparams);
    $clog = array($cuid,$uinfo['uname'],$uinfo['ulv'],$loginip);
    $clogparams = array_merge($logparams,$clog);
    cuidloginlog($clogparams);
    $datestr = date('Y-m-d h:i:s',time());
    $suffix = rand(10, 99);
    $requestid = date('Ymdhis',time());
    $requestid = $uid.$requestid.$suffix;
    $endtime = microtime(true);
    $lasttime = (int) (($endtime - $starttime) * 1000);
    $content = 'PlayerLogin'.'|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'login'.'|'.$requestid.'|'."100000".'|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0';
    _createlogfile('hylr',$content);
    //========logend===================
    sql_update("update uinfo set logintime=unix_timestamp() where uid = $uid");
    $uguide = sql_fetch_one("select * from uguide where uid=$uid");
    if (!$uguide) {
        sql_insert("INSERT INTO uguide (`uid`,`guidestep`) VALUES ($uid,0)");
    }
    
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $pvemapid = intval(sql_fetch_one_cell("select mapid from upve where uid=$uid"));
    $emapid = intval(sql_fetch_one_cell("select emapid from upve where uid=$uid"));
    $ret= sql_fetch_one("select * from uguide where uid=$uid");
    $guidestep=intval($ret['guidestep']);
    $equipshop = intval(sql_fetch_one_cell("select reset from uequipshop where uid = $uid"));
    $partnershop = intval(sql_fetch_one_cell("select reset from upartnershop where uid = $uid"));
    $fragmentshop = intval(sql_fetch_one_cell("select reset from ufragmentshop where uid = $uid"));
    $uinfo['equipshop'] = $equipshop;
    $uinfo['partnershop'] = $partnershop;
    $uinfo['fragmentshop'] = $fragmentshop;
    $breadinfo = getBreadNum($uid,$params);
    _checkSpecialMapStagePartner($uid);
    //创建uidkey
    $signkey=_getUidKey($uid,$serverid);
    if(intval($uinfo['vip']) > 0 && intval($uinfo['vipreward']) == 0){
        _getVipBySweepticket($uid);
    }
    return array(
        1,
        $uid,
        $uinfo,
        0,
        $nowtime,
        $pvemapid,
        $emapid,
        $breadinfo,
        $guidestep,
    	$signkey,
    	$ret
    );
}


/**
 * 接口：获取随机名字
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getRandName($uid, $params)
{
    $name1 = sql_fetch_one("select * from cfg_autopre order by rand() limit 1");
    $name2 = sql_fetch_one("select * from cfg_autoname order by rand() limit 1");
    return array(
        1,
        $name1['name'] . $name2['name']
    );
}

/**
 * 接口：设置用户信息
 *
 * @param
 *            $uid
 * @param $params ['job','name','sex']            
 * @return int
 */
function setUinfo($uid, $params)
{
    $job = intval($params[0]);
    
    $uname = urldecode($params[1]);
    $uname = _filterstr($uname);
    $uname = ltrim(rtrim($uname));
    global $conn;
    $uname = $conn->escape_string($uname);
    if (mb_strlen($uname, 'UTF8') > 14) {
        return array(
            0,
            STR_NameTooLang
        );
    }
    if (mb_strlen($uname,'UTF8') < 1) {
        return array(
            0,
            STR_NameTooShort
        );
    }
    if (! _wordsFilterOK($uname)) {
        return array(
            0,
            STR_USER_NAMEERR
        );
    }
    // TODO:正式上线要删除
//     if (strstr($uname,'test_') === false) {
//         return array(
//             0,
//             STR_PlayerErr
//         );
//     }
    $ret = 0;
    if ($job >= 1 && $job <= 3) {
        $ret = sql_update("update uinfo set ujob='$job',uname='$uname' where uid=$uid");
    } else {
        return array(
            0,
            STR_DataErr
        );
    }
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uname;
    rolebuildlog($logparams);
    return array(
        1,
        $ret
    );
}

/**
 * 接口：获取用户信息
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getUinfo($uid, $params)
{
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts FROM uinfo WHERE uid=$uid");
    $uequip = sql_fetch_one("select * from uequip where uid=$uid");
    $my = new my(1, $uinfo, $uequip);
    return array(
        1,
        $uid,
        $uinfo,
        $my->format_to_array()
    );
}

function getUinfo2($uid,$params) {
    $theuid = $params[0];
    $info = sql_fetch_one("select * from uinfo where uid=$theuid");
    $usoulInfo = sql_fetch_one("select * from usoul where uid=$theuid");
    $uequip = sql_fetch_one("select * from uequip where uid=$theuid");
    $partnerid = $uequip['stagepartner'];
    if (!empty($partnerid)){
        $otherequips = sql_fetch_rows("select * from ubag where uid=$theuid and euser in ($partnerid)");
    }   
    $equips = sql_fetch_rows("select * from ubag where uid=$theuid and euser=1");
    $other = new my(1, $info, $uequip);
    return array(
        1,
        $info,
        $equips,
        $other->format_to_array(),
        $usoulInfo,
        $otherequips
    );
}


/**
 * 接口：获取道具配置
 *
 * @param $params []            
 * @return array
 */
function getCfgItem($params)
{
    $info = sql_fetch_rows("select * from cfg_item");
    return array(
        1,
        $info
    );
}

function _addCoin($uid, $c, $type = 0)
{
    $starttime = microtime(true);
    $res = 0;
    if ($c > 0) {
        $res = sql_update("update uinfo set ucoin=ucoin+$c where uid=$uid");
        if ($res) {
           $logparams = array();
           $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
           $cuid=$uinfo['cuid'];
           $serverid=$uinfo['serverid'];
           _getSystemData($serverid, $cuid, $logparams);
           $logparams[] = $uid;
           $logparams[] = $uinfo['ulv'];
           $logparams[] = $type;
           $logparams[] = $c;
           $logparams[] = $uinfo['ucoin'];
           $logparams[] = 1;
           acquirelog($logparams);
           $datestr = date('Y-m-d h:i:s',time());
           $suffix = rand(10, 99);
           $requestid = date('Ymdhis',time());
           $requestid = $uid.$requestid.$suffix;
           $endtime = microtime(true);
           $lasttime = (int) (($endtime - $starttime) * 1000);
           $ulv = $uinfo['ulv'];
           $coinnum = $uinfo['ucoin'];
           $platid = "100000";
           $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_addCoin'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$type.'|'.$platid.'|'.$ulv.'|'.$coinnum.'|'.$c.'|'.$type.'|1';
           _createlogfile('hylr',$content);
        }
    }
    return $res;
}

/**
 * 充值 0,开宝箱-宝箱 id
 */
function _addUg($uid, $c, $type = 0)
{
    $starttime = microtime(true);
    $res = 0;
    if ($c > 0) {
        $res = sql_update("update uinfo set ug=ug+$c where uid=$uid");
        if ($res) {
            $logparams = array();
            $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
            $cuid=$uinfo['cuid'];
            $serverid=$uinfo['serverid'];
            _getSystemData($serverid, $cuid, $logparams);
            $logparams[] = $uid;
            $logparams[] = $uinfo['ulv'];
            $logparams[] = $type;
            $logparams[] = $c;
            $logparams[] = $uinfo['ug'];
            $logparams[] = 2;
            acquirelog($logparams);
            $datestr = date('Y-m-d h:i:s',time());
            $suffix = rand(10, 99);
            $requestid = date('Ymdhis',time());
            $requestid = $uid.$requestid.$suffix;
            $endtime = microtime(true);
            $lasttime = (int) (($endtime - $starttime) * 1000);
            $ulv = $uinfo['ulv'];
            $ugnum = $uinfo['ug'];
            $platid = "100000";
            $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_addUg'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$type.'|'.$platid.'|'.$ulv.'|'.$ugnum.'|'.$c.'|'.$type.'|2';
            _createlogfile('hylr',$content);
        }
    }
    return $res;
}

function _addExp($uid, $e)
{
    $starttime = microtime(true);
    if ($e <= 0) {
        return array(
            0,
            0
        );
    }
    $db_uinfo = sql_fetch_one("select ulv,uexp,uname from uinfo where uid=$uid");
    $lv = intval($db_uinfo['ulv']);
    $myexp = floatval($db_uinfo['uexp']);
    $allexp = $myexp + $e;
    $newlv = $lv;
    if ($lv < MAXULV) {
        $lv_cfg = sql_fetch_one("select lv,maxexp from cfg_userlv where allexp<=$allexp and $allexp<maxexp");
        if ($lv_cfg) {
            $newlv = intval($lv_cfg['lv']);
        }
    }
    sql_update("update uinfo set ulv=$newlv,uexp=uexp+$e where uid=$uid");
    if ($newlv != $lv) {
        if ($newlv % 10 == 0) {
            _addSysMsg(sprintf(STR_USER_SysMsg1,$db_uinfo['uname'],$newlv));
        }
        resetBreadNum($uid);
        //============log==================
        $datestr = date('Y-m-d h:i:s',time());
        $suffix = rand(10, 99);
        $requestid = date('Ymdhis',time());
        $requestid = $uid.$requestid.$suffix;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $platid = "100000";
        $content = 'PlayerExpFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_addExp'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$platid.'|'.$e.'|'.$lv.'|'.$newlv.'|0|0';
        _createlogfile('hylr',$content);
    }
    return array(
        $lv,
        $newlv
    );
}

/**
 * 接口：获取背包信息
 * @param   $uid      
 * @param   $params
 * @return array
 */
function getBag($uid, $params)
{
    $res = sql_fetch_rows("select * from ubag where uid=$uid");
    $items = sql_fetch_rows("select * from uitem where uid=$uid and count > 0");
    return array(
        1,
        $res,
        $items
    );
}

/**
 * 花费钻石
 * @param   $uid     
 * @param   $g        
 * @param   $log       
 * @return number|unknown
 */
function _spendGbytype($uid, $g, $log)
{
    $starttime = microtime(true);
    if ($g <= 0) {
        $g = 0;
        return 1;
    }
    
    $ret = sql_update("UPDATE uinfo SET ug=ug-$g WHERE uid=$uid and ug>=$g");
    if ($ret) {
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $log;
        $logparams[] = $log;
        $logparams[] = $g;
        $logparams[] = $uinfo['ug'];
        $logparams[] = 2;
        moneycostlog($logparams);
        $datestr = date('Y-m-d h:i:s',time());
        $requestid = date('Ymdhis',time());
        $requestid = $uid.$requestid;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $ulv = $uinfo['ulv'];
        $ugnum = $uinfo['ug'];
        $platid = "100000";
        $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_spendGbytype'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$log.'|'.$platid.'|'.$ulv.'|'.$ugnum.'|'.$g.'|'.$log.'|2';
        _createlogfile('hylr',$content);
    }
    return $ret;
}

function _spendCoin($uid, $c, $log)
{
    $starttime = microtime(true);
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = sql_update("UPDATE uinfo SET ucoin=ucoin-$c WHERE uid=$uid and ucoin>=$c");
    if ($ret) {
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $log;
        $logparams[] = $log;
        $logparams[] = $c;
        $logparams[] = $uinfo['ucoin'];
        $logparams[] = 1;
        moneycostlog($logparams);
        $datestr = date('Y-m-d h:i:s',time());
        $suffix = rand(10, 99);
        $requestid = date('Ymdhis',time());
        $requestid = $uid.$requestid.$suffix;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $ulv = $uinfo['ulv'];
        $coinnum = $uinfo['ucoin'];
        $platid = "100000";
        $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_spendCoin'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$log.'|'.$platid.'|'.$ulv.'|'.$coinnum.'|'.$c.'|'.$log.'|1';
        _createlogfile('hylr',$content);
    }
    return $ret;
}

function _checkCoin($uid, $c)
{
    if ($c < 1) {
        return true;
    }
    $u_info = sql_fetch_one("select * from uinfo where uid='$uid'");
    $uc = intval($u_info['ucoin']);
    if ($c >= 0 && $uc >= $c) {
        return true;
    } else {
        return false;
    }
}


function _getVipByBuyCoin($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['buycoin']);
    return $count;
}

// 购买金币
function buyCoin($uid, $params)
{
    $ug = $params[0];
    $cfg = sql_fetch_one("select * from cfg_buycoin where buydiamond = $ug");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $coin = $cfg['coin'];
    $price = $cfg['buydiamond'];
    $uinfo = sql_fetch_one("select u.*,c.buycoin as cfgbuycoin from uinfo u inner join cfg_vip c on u.vip=c.vip where uid=$uid");
    if (! $uinfo) {
        return array(
            0,
            ""
        );
    }
    $buycoin = intval($uinfo['buycoin']);
    $count = _getVipByBuyCoin($uid);
    $ulv = intval($uinfo['ulv']);
    if ($buycoin >= $count) {
        return array(
            0,
            STR_cishu
        );
    }
    if(!_spendGbytype($uid, $price, '购买金币'))
    {
        return array(
            0,
            STR_UgOff
        );
    }
    $ret = sql_update("update uinfo set ucoin=ucoin+$coin,buycoin=buycoin+1 where uid=$uid");
    if ($ret != 1) {
        return array(
            0,
            STR_Buy
        );
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    _updateUTaskProcess($uid, 1018);
    return array(
        1,
        $coin,
        $uinfo
    );
}

// 购买金币
function newBuyCoin($uid, $params)
{
	$buytype=$params[0];
	$uinfo = sql_fetch_one("select u.*,c.buycoin as cfgbuycoin from uinfo u inner join cfg_vip c on u.vip=c.vip where uid=$uid");
	if (! $uinfo) {
		return array(
				0,
				""
		);
	}
	$buycoin = intval($uinfo['buycoin']);
	$count = _getVipByBuyCoin($uid);
	$ulv = intval($uinfo['ulv']);
	$buycoin_arr=sql_fetch_one("select * from cfg_buycoin where id=$buytype");
	
	$coin = intval($buycoin_arr['coin']);
	$price = intval($buycoin_arr['buydiamond']);
	if ($buycoin >= $count) {
		return array(
				0,
				STR_cishu
		);
	}

	_spendGbytype($uid, $price, '购买金币');
	$ret = sql_update("update uinfo set ucoin=ucoin+$coin,buycoin=buycoin+1 where uid=$uid");
	if ($ret != 1) {
		return array(
				0,
				STR_Buy
		);
	}
	$uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
	_updateUTaskProcess($uid, 1018);
	return array(
			1,
			$coin,
			$uinfo
	);
}

function buyUg($uid, $params)
{
//     $res = sql_update("update uinfo set ug=ug+100000 where uid=$uid");
    return array(
        1,
        $res
    );
}


/**
 * 接口：获取玩家新手引导
 *
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function getGuideInfo($uid, $params)
{
    $uinfo = sql_fetch_one("select * from uguide where uid=$uid");
    if (! $uinfo) {
        sql_update("insert into uguide (uid) values ($uid)");
        $uinfo = sql_fetch_one("select * from uguide where uid=$uid");
    }
    return array(
        1,
        $uinfo
    );
}

/**
 * 接口：显示新手引导
 *
 * @param
 *            $uid
 * @param $params ['type']            
 * @return array
 */
function viewGuide($uid, $params)
{
    $type = $params[0];
    $type = strtolower(strval($type));
    $type = trim($type);
    $types = array('battle','boss','map','skill','partner','delequipnew','skillzhuanjing','yuansu', 'guanghuan', 'wujiangzhaomu');
    if (in_array($type, $types)) {
        if ($type == "boss") {
            sql_update("update uguide set $type=LEAST(9,boss+1) where uid=$uid");
        } else {
            sql_update("update uguide set $type=1 where uid=$uid");
        }
        return array(
            1
        );
    }
    return array(
        0,
        STR_DataErr
    );
}

function setGuide($uid, $params)
{
    $value = intval($params[0]);
    if($value == 101){
        sql_update("update uguide set starstate = 1 where uid=$uid");
    }
    elseif($value == 102){
        sql_update("update uguide set qualitystate = 1 where uid=$uid");
    }
    elseif($value == 103){
        sql_update("update uguide set skillstate = 1 where uid=$uid");
    }
    elseif($value == 104){
        sql_update("update uguide set uplv = 1 where uid=$uid");
    }
    elseif($value == 105){
        sql_update("update uguide set girlskill = 1 where uid=$uid");
    }
    elseif($value == 106){
        sql_update("update uguide set ruins = 1 where uid=$uid");
    }
    elseif($value == 107){
        sql_update("update uguide set forge = 1 where uid=$uid");
    }
    elseif($value == 108){
        sql_update("update uguide set arena = 1 where uid=$uid");
    }
    elseif($value == 109){
        sql_update("update uguide set equip = 1 where uid=$uid");
    }
    elseif($value == 110){
        sql_update("update uguide set abattoir = 1 where uid=$uid");
    }
    elseif($value == 111){
        sql_update("update uguide set treasure = 1 where uid=$uid");
    }
    else{
        sql_update("update uguide set guidestep = $value where uid=$uid");
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $value;
        guidelog($logparams);
    }
    return array(
            1
    );
}

//获取剧情信息
function getSceneInfo($uid, $params)
{
    $uinfo = sql_fetch_one("select * from uscene where uid=$uid");
    if (! $uinfo) {
        sql_update("insert into uscene (uid) values ($uid)");
        $uinfo = sql_fetch_one("select * from uscene where uid=$uid");
    }
    return array(
        1,
        $uinfo
    );
}

//设置剧情
function setScene($uid, $params)
{
    $value = intval($params[0]);
    sql_update("update uscene set scenestep=$value where uid=$uid");
    return array(
        1
    );
}


function _filterstr($str)
{
    $str = str_replace("'", "’", $str);
    $str = str_replace("\'", "’", $str);
    $str = str_replace("\"", "”", $str);
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);
    return $str;
}

function _addSysMsg($msg,$cid = 0) {
    sql_insert("insert into log_sys_chat(cid,msg,ts) values($cid,'$msg',UNIX_TIMESTAMP())");
}


/**
 * 接口：获取其他用户信息
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function getOtherUinfo($uid, $params)
{
    $otheruid = $params[0];
    $uinfo = sql_fetch_one("SELECT *,UNIX_TIMESTAMP() as ts
        FROM uinfo
        WHERE uid=$otheruid");
    $uequip = sql_fetch_one("select * from uequip where uid=$otheruid");
    $reputation = intval(sql_fetch_one_cell("select count from uitem where uid=$otheruid and itemid = 8"));
    $my = new my(1, $uinfo, $uequip);
    return array(
        1,
        $otheruid,
        $uinfo,
        $my->format_to_array(),
        $reputation
    );
}

/**
 * 接口：获取面包数
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function getBreadNum($uid, $params)
{
    $res = sql_fetch_one("select * from uinfo where uid=$uid");
    $breadtime = intval($res['breadtime']);
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $time = $nowtime - $breadtime;
    $maxbread = 0;
    $lv = $res['ulv'];
    $viplv = $res['vip'];
    if($lv > 100){
        $lv = 100;
    }
    $cfg = sql_fetch_one("select * from cfg_userlv where lv = $lv");
    $vipbread = intval(sql_fetch_one_cell("select bread from `cfg_vip` where vip = $viplv"));
    if($cfg){
        $maxbread = $cfg['bread'] + $vipbread;
        if($time > 0){
            $addnum = intval($time / 300);
            $addtime = intval($time % 300);
            $breadnum = $res['bread'];
            if($addnum > 0 && $maxbread > $breadnum ){
                if($maxbread <= ($breadnum + $addnum)){
                    sql_update("update uinfo set bread=$maxbread,breadtime=UNIX_TIMESTAMP()-$addtime where uid=$uid");
                }
                else{
                    sql_update("update uinfo set bread=bread+$addnum,breadtime=UNIX_TIMESTAMP()-$addtime where uid=$uid");
                }
            }
        }
    }
    $bread = intval(sql_fetch_one_cell("select bread from uinfo where uid=$uid"));
    $newtime = 0;
    if($bread < $maxbread){
        $newtime = 300 - (intval(sql_fetch_one_cell("select UNIX_TIMESTAMP() - breadtime from uinfo where uid=$uid")) % 300);
    }
    else{
        sql_update("update uinfo set breadtime=UNIX_TIMESTAMP() where uid=$uid");
    }  
    return array(
        1,
        $bread,
        $maxbread,
        $newtime
    );
}

//恢复最大面包数
function resetBreadNum($uid)
{
    $res = sql_fetch_one("select * from uinfo where uid=$uid");
    $maxbread = 0;
    $lv = $res['ulv'];
    $viplv = $res['vip'];
    if($lv > 100){
        $lv = 100;
    }
    $cfg = sql_fetch_one("select * from cfg_userlv where lv = $lv");
    $vipbread = intval(sql_fetch_one_cell("select bread from `cfg_vip` where vip = $viplv"));
    if($cfg){
        $breadnum = $res['bread'];
        $maxbread = $cfg['bread'] + $vipbread;
        if($maxbread > $breadnum){
            sql_update("update uinfo set bread=$maxbread where uid=$uid");
        }
    }
}


//检测面包
function _checkBread($uid, $c)
{
	$params = array();
	getBreadNum($uid, $params);
    if ($c < 1) {
        return true;
    }
    $u_info = sql_fetch_one("select * from uinfo where uid='$uid'");
    $ub = intval($u_info['bread']);
    if ($c >= 0 && $ub >= $c) {
        return true;
    } else {
        return false;
    }
}

//花费面包
function _spendBread($uid, $c, $log)
{
    $starttime = microtime(true);
    $params = array();
    $binfo = getBreadNum($uid, $params);
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = 0;
    if(intval($binfo[1]) == intval($binfo[2])){
        $ret = sql_update("UPDATE uinfo SET bread=bread-$c, breadtime=UNIX_TIMESTAMP() WHERE uid=$uid and bread>=$c");
    }
    else{
        $ret = sql_update("UPDATE uinfo SET bread=bread-$c WHERE uid=$uid and bread>=$c");
    }
    if ($ret) {
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $log;
        $logparams[] = $log;
        $logparams[] = $c;
        $logparams[] = $uinfo['bread'];
        $logparams[] = 3;
        moneycostlog($logparams);
        $datestr = date('Y-m-d h:i:s',time());
        $suffix = rand(10, 99);
        $requestid = date('Ymdhis',time());
        $requestid = $uid.$requestid.$suffix;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $ulv = $uinfo['ulv'];
        $breadnum = $uinfo['bread'];
        $platid = "100000";
        $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_spendBread'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$log.'|'.$platid.'|'.$ulv.'|'.$breadnum.'|'.$c.'|'.$log.'|3';
        _createlogfile('hylr',$content);
    }
    
    return $ret;
}

function _getVipByBuyBread($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['buybread']);
    return $count;
}

//购买面包
function buyBread($uid, $params)
{
    $starttime = microtime(true);
    $uinfo = sql_fetch_one("select * from uinfo where uid='$uid'");
    $breadtime = intval($uinfo['breadtime']);
    $count = _getVipByBuyBread($uid);
    if(intval($uinfo['buybread']) >= $count){
        return array(
            0,
            STR_PVP_BuyOff
        );
    }
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $time = $nowtime - $breadtime;
    $maxbread = 0;
    $lv = $uinfo['ulv'];
    $viplv = $uinfo['vip'];
    if($lv > 100){
        $lv = 100;
    }
    $cfg = sql_fetch_one("select * from cfg_userlv where lv = $lv");
    $vipbread = intval(sql_fetch_one_cell("select bread from `cfg_vip` where vip = $viplv"));
    if($cfg && $time > 0){
        $addnum = intval($time / 300);
        $maxbread = $cfg['bread'] + $vipbread;
    }
    $costnum = 100;
    if (! _spendGbytype($uid, $costnum, '购买面包')) {
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("update uinfo set bread=bread+$maxbread, breadtime=UNIX_TIMESTAMP(), buybread = buybread+1 where uid=$uid");
    $breadnum = intval(sql_fetch_one_cell("select bread from uinfo where uid='$uid'"));
    _updateUTaskProcess($uid, 1006);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = 1;
    $logparams[] = $maxbread;
    $logparams[] = $uinfo['bread'];
    $logparams[] = 3;
    acquirelog($logparams);
    $datestr = date('Y-m-d h:i:s',time());
    $suffix = rand(10, 99);
    $requestid = date('Ymdhis',time());
    $requestid = $uid.$requestid.$suffix;
    $endtime = microtime(true);
    $lasttime = (int) (($endtime - $starttime) * 1000);
    $ulv = $uinfo['ulv'];
    $breadnum = $uinfo['bread'];
    $platid = "100000";
    $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'buyBread'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|buyBread|'.$platid.'|'.$ulv.'|'.$breadnum.'|'.$maxbread.'|buyBread|3';
    _createlogfile('hylr',$content);
    return array(
        1,
        $breadnum,
        $costnum
    );
}

//增加面包数
function _addBreadNum($uid, $num, $type = 0)
{
    $res = 0;
    if ($num > 0) {
        $res = sql_update("update uinfo set bread=bread+$num where uid=$uid");
        if ($res) {
        }
    }
    $breadnum = intval(sql_fetch_one_cell("select bread from uinfo where uid='$uid'"));
    return array(
        1,
        $breadnum
    );
}

//检测水晶值
function _checkCrystal($uid, $c)
{
    if ($c < 1) {
        return true;
    }
    $u_info = sql_fetch_one("select * from uinfo where uid='$uid'");
    $uf = intval($u_info['crystal']);
    if ($c >= 0 && $uf >= $c) {
        return true;
    } else {
        return false;
    }
}

//花费水晶值
function _spendCrystal($uid, $c, $log)
{
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = sql_update("UPDATE uinfo SET crystal=crystal-$c WHERE uid=$uid and crystal>=$c");
    if ($ret) {
    }
    return $ret;
}

function _addCrystal($uid, $num, $type = 0)
{
    $res = 0;
    if ($num > 0) {
        $res = sql_update("UPDATE uinfo SET crystal=crystal+$num WHERE uid=$uid");
        if ($res) {
        }
    }
    return $res;
}


function _addHonor($uid, $num, $type = 0)
{
    $res = 0;
    if ($num > 0) {
        $res = sql_update("UPDATE uinfo SET honor=honor+$num WHERE uid=$uid");
        if ($res) {
        }
    }
    return $res;
}

//检测荣誉值
function _checkHonor($uid, $c)
{
    if ($c < 1) {
        return true;
    }
    $u_info = sql_fetch_one("select * from uinfo where uid='$uid'");
    $uf = intval($u_info['honor']);
    if ($c >= 0 && $uf >= $c) {
        return true;
    } else {
        return false;
    }
}

//花费荣誉值
function _spendHonor($uid, $c, $log)
{
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = sql_update("UPDATE uinfo SET honor=honor-$c WHERE uid=$uid and honor>=$c");
    if ($ret) {
    }

    return $ret;
}

//花费公会贡献值
function _spendClubScore($uid, $c, $log)
{
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $cinfo = sql_fetch_one("select * from uclub where uid=$uid");
    if(!$cinfo){
        return false;
    }
    $ret = sql_update("update uclub set totalscore=totalscore-$c where uid=$uid and totalscore>=$c");
    if ($ret) {
    }

    return $ret;
}

//花费勇者币
function _spendBraveCoin($uid, $c, $log)
{
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = sql_update("UPDATE uinfo SET bravecoin=bravecoin-$c WHERE uid=$uid and bravecoin>=$c");
    return $ret;
}

//获取VIP钥匙数量加成
function _getVipByKeyNum($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['key']);
    return $count;
}

//获取钥匙数量
function getKeyNum($uid, $params)
{
    $res = sql_fetch_one("select * from uinfo where uid=$uid");
    $keytime = intval($res['keytime']);
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    $time = $nowtime - $keytime;
    $maxkey = 20;
    $maxkey += _getVipByKeyNum($uid);
    if($time > 0){
        $addnum = intval($time / 1080);
        $keynum = $res['keynum'];
        if($addnum > 0 && $maxkey > $keynum ){
            if($maxkey <= ($keynum + $addnum)){
                sql_update("update uinfo set keynum=$maxkey, keytime=UNIX_TIMESTAMP() where uid=$uid");
            }
            else{
                sql_update("update uinfo set keynum=keynum+$addnum, keytime=UNIX_TIMESTAMP() where uid=$uid");
            }
        }
    }
    $key = intval(sql_fetch_one_cell("select keynum from uinfo where uid=$uid"));
    $newtime = 0;
    if($key != $maxkey){
        $newtime = 1080 - (intval(sql_fetch_one_cell("select UNIX_TIMESTAMP() - keytime from uinfo where uid=$uid")) % 1080);
    }
    return array(
        1,
        $key,
        $maxkey,
        $newtime
    );
}

//检测钥匙数量
function _checkKey($uid, $c)
{
    if ($c < 1) {
        return true;
    }
    $u_info = sql_fetch_one("select * from uinfo where uid='$uid'");
    $ukey = intval($u_info['keynum']);
    if ($c >= 0 && $ukey >= $c) {
        return true;
    } else {
        return false;
    }
}

//花费钥匙
function _spendKey($uid, $c, $log)
{
    $starttime = microtime(true);
    $params = array();
    $keyinfo = getKeyNum($uid, $params);
    if ($c <= 0) {
        $c = 0;
        return true;
    }
    $ret = 0;
    if(intval($keyinfo[1]) == intval($keyinfo[2])){
        $ret = sql_update("update uinfo set keynum=keynum-$c, keytime=UNIX_TIMESTAMP() where uid=$uid and keynum>=$c");
    }
    else{
        $ret = sql_update("update uinfo set keynum=keynum-$c where uid=$uid and keynum>=$c");
    }
    if ($ret) {
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $log;
        $logparams[] = $log;
        $logparams[] = $c;
        $logparams[] = $uinfo['keynum'];
        $logparams[] = 4;
        moneycostlog($logparams);
        $datestr = date('Y-m-d h:i:s',time());
        $suffix = rand(10, 99);
        $requestid = date('Ymdhis',time());
        $requestid = $uid.$requestid.$suffix;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $ulv = $uinfo['ulv'];
        $keynum = $uinfo['keynum'];
        $platid = "100000";
        $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_spendKey'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$log.'|'.$platid.'|'.$ulv.'|'.$keynum.'|'.$c.'|'.$log.'|4';
        _createlogfile('hylr',$content);
    }
    return $ret;
}

function _getVipByBuyKey($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['buykey']);
    return $count;
}

//购买钥匙
function buyKey($uid, $params)
{
    $starttime = microtime(true);
    $num = 10;
    $uinfo = sql_fetch_one("select * from uinfo where uid='$uid'");
    $count = _getVipByBuyKey($uid);
    if(intval($uinfo['buykey']) >= $count){
        return array(
            0,
            STR_PVP_BuyOff
        );
    }
    $costnum = 10 * $num;
    if (! _spendGbytype($uid, $costnum, '购买钥匙')) {
        return array(
            0,
            STR_UgOff
        );
    }
    sql_update("update uinfo set keynum=keynum+$num, keytime=UNIX_TIMESTAMP(),buykey=buykey+1 where uid=$uid");
    $keynum = intval(sql_fetch_one_cell("select keynum from uinfo where uid='$uid'"));
    _updateUTaskProcess($uid, 1007);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = 2;
    $logparams[] = $num;
    $logparams[] = $uinfo['keynum'];
    $logparams[] = 4;
    acquirelog($logparams);
    $datestr = date('Y-m-d h:i:s',time());
    $suffix = rand(10, 99);
    $requestid = date('Ymdhis',time());
    $requestid = $uid.$requestid.$suffix;
    $endtime = microtime(true);
    $lasttime = (int) (($endtime - $starttime) * 1000);
    $ulv = $uinfo['ulv'];
    $platid = "100000";
    $content = 'MoneyFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'buyKey'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|buyKey|'.$platid.'|'.$ulv.'|'.$keynum.'|'.$num.'|buyKey|4';
    _createlogfile('hylr',$content);
    return array(
        1,
        $keynum,
        $costnum
    );
}

//增加钥匙数
function _addKeyNum($uid, $num, $type = 0)
{
    $res = 0;
    if ($num > 0) {
        $res = sql_update("update uinfo set keynum=keynum+$num, keytime=UNIX_TIMESTAMP() where uid=$uid");
        if ($res) {
        }
    }
    $keynum = intval(sql_fetch_one_cell("select keynum from uinfo where uid='$uid'"));
    return array(
        1,
        $keynum
    );
}

//获取礼包码礼物
function getGiftCode($uid, $params)
{
    $code = $params[0];
    $giftcode = sql_fetch_one("select * from server_giftcode where code = '$code'");
    if(!$giftcode){
        return array(
            0,
            STR_Gift_Code_Error
        );
    }
    $type = intval($giftcode['type']);
    $isexist = sql_fetch_one("select * from server_giftcode where type = $type and uid = $uid");
    if(intval($giftcode['isuse']) == 1){
        return array(
            0,
            STR_Gift_Code_Isuse
        );
    }
    if($isexist){
        return array(
            0,
            STR_Gift_Type_Code_Isuse
        );
    }
    $giftstr = $giftcode['gift'];
    $giftarr = explode(",", $giftstr);
    $items = array();
    foreach ($giftarr as $g){
        $items[] = explode("|", $g);
    }
    $itemrewards = array();
    foreach ($items as $v){
        $itemid = intval($v[0]);
        $count = intval($v[1]);
        $cfgitem = sql_fetch_one("select * from cfg_item where itemid = $itemid");
        if($cfgitem){
            $itemrewards[] = array($itemid, $count); 
            $itemtype = intval($cfgitem['itemType']);
            if($itemtype == 0){
                if($itemid == 1){
                    _addCoin($uid, $count, '获取礼包码奖励');
                }elseif ($itemid == 2){
                    _addUg($uid, $count, '获取礼包码奖励');
                }elseif ($itemid == 3){
                    _addBreadNum($uid, $count, '获取礼包码奖励');
                }
                elseif($itemid == 4){
                    _addKeyNum($uid, $count, '获取礼包码奖励');
                }
                elseif($itemid == 11){
                    _addCrystal($uid, $count, '获取礼包码奖励');
                }
            }
            else{
                _addItem($uid, $itemid, $count, '获取礼包码奖励');
            }
        }
    }
    sql_update("update server_giftcode set isuse = 1, uid = $uid where code = '$code'");
    return array(
        1,
        $itemrewards
    );
}

//获取购买次数
function getBuyNum($uid, $params)
{
    $uinfo = sql_fetch_one("select buybread,buycoin from uinfo where uid='$uid'");
    return array(
        1,
        $uinfo
    );
}

//VIP等级提升
function _upVIPLevel($uid, $viplevel)
{
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if($cfg){
        $pay = intval($cfg['pay']);
        sql_update("update uinfo set vip = $viplevel, vippay = $pay where uid = $uid");
        $partnerbag = intval($cfg['partnerbag']);
        if($partnerbag > 0){
            sql_update("update uinfo set partnerbag = partnerbag+$partnerbag where uid = $uid");
        }
        $equipbag = intval($cfg['equipbag']);
        if($equipbag > 0){
            sql_update("update uinfo set bag = bag+$equipbag where uid = $uid");
        }
    }
}

//发送世界聊天消息
function sendWorldMsg($uid, $params)
{
    $msg = $params[0];
    $msg = "{\"content\": \"".$msg."\",\"type\":\"say\",\"to_channel\":\"m\",\"to_uid\":\"0\"}";
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    mem_instance()->set('chat_sys_w:'.$serverid, $msg);
    mem_instance()->expire('chat_sys_w:'.$serverid, 86400);
}

//接收世界聊天消息
function revWorldMsg($uid, $params)
{
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $res = mem_instance()->get('chat_sys_w:'.$serverid);
    if ($res) {
        return $res;
    }
    return '';
}

//发送公会聊天消息
//键值:{
//    "cid": 1, 			// 公会ID
//    "msg": "公会战开始啦！” 	// 消息内容
//}
function sendClubMsg($uid, $params)
{
    $msg = $params[0];
    mem_instance()->set('chat_sys_c:'.SERVER_ID, $msg);
    mem_instance()->expire('chat_sys_c:'.SERVER_ID, 86400);
}

//接收公会聊天消息
function revClubMsg($uid, $params)
{
    $res = mem_instance()->get('chat_sys_c:'.SERVER_ID);
    if ($res) {
        return $res;
    }
    return '';
}

function _getVipBySweepticket($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return false;
    }
    $count = intval($cfg['sweepticket']);
    _addMail($uid, "vip每日奖励", "vip每日奖励获取扫荡券", 0, 0, 0,531,$count);
    sql_update("update uinfo set vipreward = 1 where uid=$uid");
}

//创建订单号
function createBillOrderNo($uid,$params)
{
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $datestr = date('Y-m-d H:i:s',time());
    $suffix = rand(1000000, 9999999);
    $requestid = date('Ymdhis',time());
    $outtradeno = $uid.$requestid.$suffix;
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    _sql_insert("insert into `bili_orderno`(outtradeno,uid,sid) values('$outtradeno','$uid',$serverid)");
    return array(
        1,
        $outtradeno
    );
}

//查询订单号
function selectBillOrderNo($uid,$params)
{
    $serverid=intval(sql_fetch_one_cell("select serverid from uinfo where uid=$uid"));
    $orderno = $params[0];
    require_once 'platform/bili_queryOrder.php';
    $result =  _biliOrderNo($orderno);
    if(isset($result)){
        $resdata = json_decode($result,true);
        $biliorderno = $resdata['order_no'];
        $outtradeno = $resdata['out_trade_no'];
        //   $money = $resdata['pay_money'];
        //   $moneytype = $resdata['product_name'];
        _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
        $ret = _sql_update("update `bili_orderno` set orderno='$biliorderno', ordercontent='$result' where outtradeno='$outtradeno' and uid=$uid and sid=$serverid");
        if(!$ret){
            return 0;
        }
        else{
            return 1;
        }
    }
    return 0;
}

//验证订单号
function verifyBillOrderNo($uid,$params)
{
    $uinfo=sql_fetch_one("select * from uinfo where uid=$uid");
    $serverid = $uinfo['serverid'];
    $cuid = $uinfo['cuid'];
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $orderdatas = _sql_fetch_rows("select * from `bili_orderno` where uid=$uid and sid=$serverid and state=0");
    if($orderdatas){
        $isOk = false;
        foreach ($orderdatas as $v){
            $orderno = $v['orderno'];
            $ordercontent = $v['ordercontent'];
            $moneytype = $v['moneytype'];
            if(strcmp($moneytype,'Diamonds') == 0){
                $money = $v['money'];
                if($money > 0){
                    $cfgdiamond = sql_fetch_one("select * from cfg_buydiamond where rmb = $money and type = 2");
                    if(!$cfgdiamond){
                        continue;
                    }
                    $ug = $cfgdiamond['diamond'];
                    $pay = intval(sql_fetch_one_cell("select vippay from uinfo where uid = $uid"));
                    $firstpay = 0;
                    if($pay == 0){
                        _addUg($uid, 2*$ug,"玩家充值获取");
                        $firstpay = 1;
                    }
                    else{
                        _addUg($uid, $ug,"玩家充值获取");
                    }
                    sql_update("update uinfo set vippay = vippay + $money where uid = $uid");
                    sql_update("update udrawforpay set paydraw = paydraw + $money where uid = $uid");
                    _sql_update("update `bili_orderno` set state=1 where orderno=$orderno and state=0");
                    $isOk = true;
                    $logparams = array($orderno,10000,10000,0,$cuid,uid,$money,1, $firstpay,$serverid,$ordercontent);
                    rechargelog($logparams);
                }
            }
            elseif(strcmp($moneytype,'Card') == 0){
                //加入月卡
                $money = $v['money'];
                _addMonthcardByOrder($uid);
                if($money > 0){
                    $cfgdiamond = sql_fetch_one("select * from cfg_buydiamond where rmb = $money and type = 1");
                    if(!$cfgdiamond){
                        continue;
                    }
                    $ug = $cfgdiamond['diamond'];
                    $pay = intval(sql_fetch_one_cell("select vippay from uinfo where uid = $uid"));
                    $firstpay = 0;
                    if($pay == 0){
                        _addUg($uid, 2*$ug,"玩家充值获取");
                        $firstpay = 1;
                    }
                    else{
                        _addUg($uid, $ug,"玩家充值获取");
                    }
                    sql_update("update uinfo set vippay = vippay + $money where uid = $uid");
                    sql_update("update udrawforpay set paydraw = paydraw + $money where uid = $uid");
                    _sql_update("update `bili_orderno` set state=1 where orderno=$orderno and state=0");
                    $isOk = true;
                    $logparams = array($orderno,10000,10000,0,$cuid,uid,$money,3, $firstpay,$serverid,$ordercontent);
                    rechargelog($logparams);
                }
            }
        }
        if($isOk){
            return array(
                1
            );
        }
    }
    return array(
        0
    );
}

//查询钻石
function getUg($uid,$params)
{
    $ug = intval(sql_fetch_one_cell("select ug from uinfo where uid = $uid"));
    return array(
        1,
        $ug
    );
}

//购买经验副本次数
function buyEmapCount($uid,$params)
{
    $emapid = $params[0];
    $viplevel = sql_fetch_one_cell("select vip from uinfo where uid = $uid");
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    $upve = sql_fetch_one("select * from upve where uid=$uid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $vipemapnum = intval($cfg['emapnum']);
    $emapnumstr = $upve['emapnum'];
    $buyemapstr = $upve['buyemapnum'];
    //购买次数
    $arrbuyemapnum = explode(',',$buyemapstr);
    $buyemapinfo = array();
    foreach ($arrbuyemapnum as $value){
        $arr = explode('|',$value);
        if(count($arr) == 2){
            $id = $arr[0];
            $num = $arr[1];
            $buyemapinfo[] = array($id,$num);
        }
    }
    $exsit = false;
    $buyemapnum = 0;
    foreach ($buyemapinfo as &$bm){
        if($bm[0] == $emapid){
            $buyemapnum = $bm[1];
            $bm[1] += 1;
            $exsit = true;
        }
    }
    if(!$exsit){
        $buyemapinfo[] = array($emapid,1);
    }
    if($buyemapnum + 1 > $vipemapnum){
        return array(
            0,
            STR_EMAPNUM_BUY_ERROR
        );
    }
    $buyemapnumarr = array();
    foreach ($buyemapinfo as $v){
        $buyemapnumarr[] = implode('|',$v);
    }
    $buyemapstr = implode(',',$buyemapnumarr);
    $cfgdata = sql_fetch_one("select * from cfg_reflash where type = 5 and times = $buyemapnum + 1");
    if(!$cfgdata){
        return array(
            0,
            STR_Param_Error
        );
    }
    $money = $cfgdata['money'];
    $cost = $cfgdata['amout'];
    if($money == 1){
        if (! _spendCoin($uid, $cost, "购买精英副本次数")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 2){
        if (! _spendGbytype($uid, $cost, "购买精英副本次数")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    //精英副本次数重置
    $arremapnum = explode(',',$emapnumstr);
    $emapinfo = array();
    foreach ($arremapnum as $e){
        $arr = explode('|',$e);
        if(count($arr) == 2){
            $eid = $arr[0];
            $enum = $arr[1];
            $emapinfo[] = array($eid,$enum);
        }
    }
    $exsit = false;
    foreach ($emapinfo as &$map){
        if($map[0] == $emapid){
            $map[1] = 0;
            $exsit = true;
        }
    }
    if(!$exsit){
        $emapinfo[] = array($emapid,0);
    }
    $emapnumarr = array();
    foreach ($emapinfo as $ei){
        $emapnumarr[] = implode('|',$ei);
    }
    $emapnumstr = implode(',',$emapnumarr);
    sql_update("update upve set emapnum='$emapnumstr',buyemapnum='$buyemapstr' where uid = $uid");
    return array(
        1,
        $cost
    );
}

function _addMonthcardByOrder($uid)
{
    $process=sql_fetch_one_cell("select process from udailytask where uid=$uid and tid=1019");
    if($process==1){
        $num=30;
    }
    else{
        $num=29;
        sql_update("update udailytask set process=1 where uid=$uid and tid=1019");
    }
    $nowtime = time();
    $nowdaya = strtotime(date("Y-m-d", $nowtime));
    $nowdayb=($num*86400);
    $nowdayc=$nowdayb+$nowdaya;
    sql_update("insert into umonthcard(uid,num,time) values($uid,0,$nowdayc) on duplicate key update num=0,time=time+$nowdayb");
}

function getClientIp(){
    $ip = $_SERVER["REMOTE_ADDR"];
    return $ip;
}

function sendHorseRaceLamp($url, $serverid, $content)
{
    $param = 'serverid='.$serverid.'&'.'content='.$content;
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
}

function getNowTime($uid,$params)
{
    $nowtime = intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP()"));
    return array(
        1,
        $nowtime
    );
}


?>