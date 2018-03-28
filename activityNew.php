<?php
//define('CDKEY_URL', "http://sggj.gz.1251009140.clb.myqcloud.com", true);
define('CDKEY_URL', "http://sggjgamebar.gz.1251009140.clb.myqcloud.com/", true);
//!活动类型定义
class activityTypeDef
{
    const christmasActivity = 1;//!对应unewAct数据库中arg1为剩蛋,arg2为开蛋次数,arg3为奖励领取次数
    const firstPayActivity = 2; //!首充活动
    const monthCardActivity = 3;//!月卡活动,对应unewAct数据库中arg1为是否领取过
    const winterRechargeActivity = 4;//!冬季充值活动,对应unewAct数据库中arg1当日时间戳,arg2-arg6为每种奖励领取情况
    const SERVEROPEN_GIFTPACKAGE_ACTIVITY = 5;  // 开服礼包
    const SOULOPEN_GIFTPACKAGE_ACTIVITY = 6;    // 神兽之魂礼包
    const NEW_USER_GIFT_ACTIVITY = 7;           // 新手活动
    const PAY_RETURN_ACTIVITY = 8;              // 充值返利活动
    const PAY_EVERY_DAY_ACTIVITY = 9;           // 连续充值活动
    const VIP_GIFT_ACTIVITY = 10;               // VIP福利
    const ROTLA_ACTIVITY = 11;                  // 夺宝奇兵
    const MAMMON_GIFT_ACTIVITY = 12;            // 财神献礼
    const CHANGE_NAME_ACTIVITY = 13;            // 更名奖励
    const DABAI_ACTIVITY = 14;                  // 大白
    const CDKEY_ACTIVITY = 15;                  // CDKEY
    const COIN_ACTIVITY = 16;                  // coin
    const EXP_ACTIVITY = 17;                   // exp
    const DROP_ACTIVITY = 18;                  // drop
    const OPEN_BOX_ACTIVITY = 24;                   //开箱寻宝活动，arg1-arg3为三种礼箱领取的时间戳，arg4-arg6为三种礼箱已经免费的次数
    const DAILY_RECHARGE_ACTIVITY = 25;         //天天充值!对应unewAct数据库中arg1为第一天,arg2为第二天,以此到第七天
    const JIANGDONG_RECHARGE_ACTIVITY = 26;     //江东绝色!对应unewAct数据库中arg1为充值数,arg2为领取情况
    const BUQUE_ZHANSHEN_ACTIVITY = 27;     //不驱战神活动
    const SIX_ONE_LINE = 28; //六一连线
}

/**
 * 接口：获取玩家今日充值数
 * @return inter
 */
function getTodayRecharge($uid)
{
    $rec = sql_fetch_one("SELECT sum(ug) as recharge FROM `pay_gamebar` WHERE `billts`>=UNIX_TIMESTAMP(CURDATE()) and uid=$uid");
    if (! $rec) {
        return 0;
    }
    return intval($rec["recharge"]);
}


/**
 * 接口：获取玩家一段时间内充值数
 * @return inter
 */
function getTimeRecharge($uid, $startts, $endts)
{
    $rec = array();
    if ($endts == 0){
        $rec = sql_fetch_one("SELECT sum(ug) as recharge FROM `pay_gamebar` WHERE `billts`>=$startts and uid=$uid");
    }
    else{
        $rec = sql_fetch_one("SELECT sum(ug) as recharge FROM `pay_gamebar` WHERE `billts`>=$startts and `billts`<$endts and uid=$uid");
    }
    
    if (! $rec) {
        return 0;
    }
    return intval($rec["recharge"]);
}

function _get_reg_days($uid) {
    return intval(sql_fetch_one_cell("select datediff(NOW(),FROM_UNIXTIME(`ts`)) as dayoffset from cuser where uid =$uid limit 1")) + 1;
}

/**
 * 接口：获取开放的活动
 * @return array
 */
function getOpenActivity($realopen = false)
{
	$sql = "";
	if($realopen) {
		$sql = "select *, UNIX_TIMESTAMP() as nts from server_act where (startts <= UNIX_TIMESTAMP() and (endts>=UNIX_TIMESTAMP() or endts = 0)) order by (startts - UNIX_TIMESTAMP()) asc, said desc";
	} else {
		$sql = "select *, UNIX_TIMESTAMP() as nts from server_act where (endts>=UNIX_TIMESTAMP() or endts = 0) order by (startts - UNIX_TIMESTAMP()) asc, said desc";
	}
    $sact = sql_fetch_rows($sql);
    if (! $sact) {
        return false;
    }
    return $sact;
}

/**
 * 接口：判断指定活动是否开启
 * @param $actType 活动id
 * @return array
 */
function checkActivityOpenById($actType)
{
    static $actList;
    if (is_null($actList)) {
        $actList = getOpenActivity(true);
        if (!$actList){
            $actList = array();
        }
    }
    foreach ($actList as $actInfo) {
        if ($actInfo['said'] == $actType) {
            return $actInfo;
        }
    }
    return false;
}

/**
 * 接口：增加剩蛋
 * @param $uid
 * @param $num 数量
 */
function addShengDanItem($uid, $num = 1)
{
    $actId = activityTypeDef::christmasActivity;
    sql_update("insert into unewAct (uid, actid, arg1) values ($uid, $actId, $num) on duplicate key update arg1=arg1+$num");
}

/**
 * 接口：获取活动数据
 * @param $uid
 * @param $params
 */
function getNewActivityInfo($uid, $params)
{
    $uactinfo = array();
    $actInfo = getOpenActivity();
    

    $otherInfo = array();
    //!当日充值
    $otherInfo['todayPay'] = getTodayRecharge($uid);
    
    setDailyRecharge($uid);
    setJiangDongRecharge($uid);
    setSixOneLine($uid,2);
    if ($actInfo){
        $actIds = "";
        foreach ($actInfo as $info){
            if (empty($actIds)){
                $actIds = $info["said"];
            }else{
                $actIds = $actIds . "," . $info["said"];
            }
        }
        $uactinfo = sql_fetch_rows("select * from unewAct where uid=$uid and actid in ($actIds)");
    }
    
   
    return array(
            1,
            $actInfo,
            $uactinfo,
            $otherInfo,           
            _get_reg_days($uid),    // 注册天数
    );
} 

/**
 * 接口：打开剩蛋
 * @param $uid
 * @param $params
 */
function openShengDan($uid, $params)
{
    $reward = array();
    $reward[] = array(
            //!奖励ID，数量, 概率
            array(3, 10, 25),    //!强化符
            array(12, 3, 20),    //!下级宝石袋
            array(1, 88888, 30), //!铜钱
            array(5, 10000, 25)  //!经验
     );
     $reward[] = array(
            //!奖励ID，数量, 概率
            array(11, 3, 35),    //!首领挑战令
            array(4, 2, 20),     //!神器精魄
            array(7, 300, 15),   //!荣誉
            array(41, 3, 30)     //!小铜锤
    );

    $openType = 1;
    $needShengDan = 15;
    if(intval($params[0]) == 2){
        $openType = 2;
        $needShengDan = 50;   
    }
    if (checkActivityOpenById(activityTypeDef::christmasActivity)){
        
        //!检查需求
        $actId = activityTypeDef::christmasActivity;
        $uactinfo = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
        if (!$uactinfo || intval($uactinfo['arg1']) < $needShengDan) {
            return array(
                    0,
                    STR_DataErr
            );
        }
        
        //!随机奖励
        $randNum = rand(1, 100);
        $num = 0;
        $addReward = array(); //!增加奖励，前端显示用
        for ($i = 0; $i < count($reward[$openType-1]); ++$i){
            $num += $reward[$openType-1][$i][2];
            if($randNum <= $num){
                if ($reward[$openType-1][$i][0] == 1){ //!铜钱
                    _addCoin($uid, $reward[$openType-1][$i][1],'activity'.$actId);
                }
                if ($reward[$openType-1][$i][0] == 5){ //!经验
                    _addExp($uid, $reward[$openType-1][$i][1]);
                }else{
                    _addItem($uid, $reward[$openType-1][$i][0], $reward[$openType-1][$i][1]);
                }
                $addReward[] = array($reward[$openType-1][$i][0], $reward[$openType-1][$i][1]);
                break;
            }
        }
        
        //!扣除
        sql_update("update unewAct set arg1=arg1-$needShengDan, arg2=arg2+1 where uid=$uid and actid=$actId");
        $uactinfo["arg1"] -= $needShengDan;
        $uactinfo["arg2"] += 1;
        
        //!返回结果
        $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
                1,
                $uactinfo,
                $uinfo,
                $uitem,
                $addReward
        );
    }
    
    return array(
           0,
            STR_ActivityOver
    );
}

/**
 * 接口：领取剩蛋奖励
 * @param $uid
 * @param $params
 */
function getShengDanReward($uid, $params)
{
    $reward = array(
            //!奖励ID，数量
            array(2, 88), //!元宝
            array(31, 6), //!铜钥匙
            array(13, 3), //!上级宝石袋
            array(8, 100) //!声望
    );
    if (checkActivityOpenById(activityTypeDef::christmasActivity)){
        $actId = activityTypeDef::christmasActivity;
        $uactinfo = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");

        if (!$uactinfo) {
            return array(
                    0,
                    STR_DataErr
            );
        }
        
        $times = intval($uactinfo['arg2'] / 10);
        if ($times <= intval($uactinfo['arg3'])){
            return array(
                    0,
                    STR_CanNot_Reward
            );
        }
        
        for ($i = 0; $i < count($reward); ++$i){
            if ($reward[$i][0] == 2){ //!元宝
                _addUg($uid, $reward[$i][1],'activity'.$actId);
            }else{
                _addItem($uid, $reward[$i][0], $reward[$i][1]);
            }
        }

        //!增加次数
        sql_update("update unewAct set arg3=arg3+1 where uid=$uid and actid=$actId");
        $uactinfo["arg3"] += 1;

        $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
                1,
                $uactinfo,
                $uinfo,
                $uitem,
                $reward
        );
    }

    return array(
            0,
            STR_ActivityOver
    );
}

/**
 * 接口：领取月卡十倍奖励
 * @param $uid
 * @param $params
 */
function getMonthCardReward($uid, $params)
{
    $reward = array(
            //!奖励ID，数量
            array(2, 128),    //!元宝
            array(1, 100000), //!铜钱
            array(12, 20),     //!下级宝石袋
            array(605, 1),     //!美酒
    );
    $actId = activityTypeDef::monthCardActivity;
    $activityInfo = checkActivityOpenById($actId);
    if ($activityInfo){ 
        $uactinfo = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
        if ($uactinfo && intval($uactinfo['arg1']) == 1) {
            return array(
                    0,
                    STR_Have_Reward
            );
        }

        //!购买了月卡，而且月卡没过期
        $ugiftinfo = sql_fetch_one("select *, UNIX_TIMESTAMP() as ts from ugift where uid=$uid");
        if (!$ugiftinfo){
            return array(
                    0,
                    STR_CanNot_Reward
            );
        }
        //!在活动时间内充月卡才有效而且月卡没过期
        if ((intval($activityInfo['startts']) > (intval($ugiftinfo['mgift']) - 86400*30)) || (intval($ugiftinfo['mgift']) < intval($ugiftinfo['ts']))){
            return array(
                    0,
                    STR_CanNot_Reward
            );
        }

    
        for ($i = 0; $i < count($reward); ++$i){
            if ($reward[$i][0] == 2){ //!元宝
                _addUg($uid, $reward[$i][1],'activity'.$actId);
            }else if ($reward[$i][0] == 1){
                _addCoin($uid, $reward[$i][1],'activity'.$actId);
            }else{
                _addItem($uid, $reward[$i][0], $reward[$i][1]);
            }
        }

        //!增加已经领取状态
        sql_update("insert into unewAct (uid, actid, arg1) values ($uid, $actId, 1) on duplicate key update arg1=1");

        $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
                1,
                $uinfo,
                $uitem,
                $reward
        );
    }

    return array(
            0,
            STR_ActivityOver
    );
}

/**
 * 接口：冬季充值奖励
 * @param $uid
 * @param $params
 */
function getWinterRechargeActivityReward($uid, $params)
{
    $rewards = array(
        100 => array(
                //!奖励ID，数量
                array(11, 3),    //!首领挑战令
                array(2, 100),    //!元宝
        ),
        500 => array(
                //!奖励ID，数量
                array(11, 5),    //!首领挑战令
                array(2, 200),    //!元宝
        ),
        1000 => array(
                //!奖励ID，数量
                array(4, 5),    //!神器精魄
                array(2, 300),    //!元宝
        ),
        3000 => array(
                //!奖励ID，数量
                array(4, 15),    //!神器精魄
                array(2, 600),    //!元宝
        ),
        5000 => array(
                //!奖励ID，数量
                array(13, 5),    //!上级宝石袋
                array(2, 1000),    //!元宝
        )
    );
     
    $actId = activityTypeDef::winterRechargeActivity;
    $activityInfo = checkActivityOpenById($actId);
    if ($activityInfo){
        //!当时充值数,充值条件判断
        $todayPay = getTodayRecharge($uid);
        if ($todayPay <= 0){
            return array(
                    0,
                    STR_CanNot_Reward
            );
        }
        
        //!检查及合并奖励, 找到数据库存贮key
        $reward = array();
        $keys = array();
        $index = 2;
        foreach ($rewards as $key => $value){
            if (in_array($key, $params)){
                //!领取条件不足
                if ($todayPay < $key){
                    return array(
                            0,
                            STR_CanNot_Reward
                    );
                }
                for ($i = 0; $i < count($value); ++$i){
                    $reward[$value[$i][0]] += $value[$i][1]; 
                }
                $keys[] = 'arg'.$index;
            }
            ++$index;
        }
        if (count($keys) <= 0){
            return array(
                    0,
                    STR_DataErr
            );
        }
        
        
        //!领取情况
        $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");
        $ts = intval($uactinfo['ts']);
        //!数据库中的有记录
        if ($uactinfo) {
            //!是本日而且已经领取了
            if ($ts == intval($uactinfo['arg1'])){
                for ($i = 0; $i < count($keys); ++$i){
                    if (intval($uactinfo[$keys[$i]]) == 1){
                        return array(
                                0,
                                STR_Have_Reward
                        );
                    }
                }
            }else{ //!不是本日的记录更新奖励领取记录
                sql_update("delete from unewAct where uid=$uid and actid=$actId");
            }
            
        }
        //!增加奖励
        foreach ($reward as $key => $value){
            if ($key == 2){ //!元宝
                _addUg($uid, $value,'activity'.$actId);
            }else{
                _addItem($uid, $key, $value);
            }
        }
        
        //!增加已经领取状态
        $keystr1 = implode(", ", $keys);
        $keyvalue1 = implode(", ", array_fill(0, count($keys), 1));
        $keystr2 = implode("= 1, ", $keys) . "= 1";
        sql_update("insert into unewAct (uid, actid, arg1, $keystr1) values ($uid, $actId, UNIX_TIMESTAMP(CURDATE()), $keyvalue1) on duplicate key update arg1=UNIX_TIMESTAMP(CURDATE()), $keystr2");
        $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        $uactinfo = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
        return array(
                1,
                $uinfo,
                $uitem,
                $reward,
                $uactinfo
        );
    }

    return array(
            0,
            STR_ActivityOver
    );
}

function getServerOpenGiftReward($uid,$params) {
    $isOver = checkActivityOpenById(activityTypeDef::SERVEROPEN_GIFTPACKAGE_ACTIVITY);
    if (!$isOver) {
        return array(
            0,
            STR_ActivityOver
        );
    }
    $res = array();
    $giftid = intval($params[0]);
    switch ($giftid) {
        case 1:
            return _buyLuckyGiftPackage($uid);
            break;
        case 2:
            return _buySupremeGiftPackage($uid);
            break;
        case 3:
            return _buyGemGiftPackage($uid);
            break;
        case 4:
            return _buyGoldOrSilverGiftPackage($uid,1);
            break;
        case 5:
            return _buyGoldOrSilverGiftPackage($uid,2);
            break;
        default:
            ;
        break;
    }
    return array(
        0,
        STR_DataErr
    );
}

// 福袋大礼包
function _buyLuckyGiftPackage($uid) {
    $actId = activityTypeDef::SERVEROPEN_GIFTPACKAGE_ACTIVITY;
    // 元宝不足
    $uinfo = sql_fetch_one("select ug,uname from uinfo where uid=$uid");
    $ug = intval($uinfo['ug']);
    if ($ug < 40) {
        return array(
            0,
            STR_UgOff
        );
    }
    // 限量礼包被抢空：actvalue1表示福袋
    $boughtnum = sql_fetch_one_cell("select actvalue1 from server_act where said=$actId");
    if ($boughtnum <= 0) {
        return array(
            0,
            STR_GiftPackage_Not_Enough
        );
    }
    $rewards = array(
        array('id' => 1,'name' => '铜钱','count' => 50000,'p' => 15),
        array('id' => 3,'name' => '强化符','count' => 5,'p' => 8),
        array('id' => 4,'name' => '神器精魂','count' => 1,'p' => 4),
        array('id' => 11,'name' => '首领挑战令','count' => 1,'p' => 8),
        array('id' => 12,'name' => '下级宝石袋','count' => 2,'p' => 8),
        array('id' => 13,'name' => '上级宝石袋','count' => 1,'p' => 4),
        array('id' => 21,'name' => '铜钥匙','count' => 3,'p' => 15),
        array('id' => 22,'name' => '银钥匙','count' => 2,'p' => 8),
        array('id' => 23,'name' => '金钥匙','count' => 1,'p' => 4),
        array('id' => 41,'name' => '小铜锤','count' => 3,'p' => 14),
        array('id' => 42,'name' => '小银锤','count' => 2,'p' => 8),
        array('id' => 43,'name' => '小金锤','count' => 1,'p' => 4)
    );
    $randRes =  _get_rand($rewards);
    $itemid = $randRes['id'];
    $count = $randRes['count'];
    $reward = array($itemid,$count);
    sql_query("BEGIN");
    $res1 = 0;
    $res2 = 0;
    $res3 = 0;
    $res4 = 0;
    // 礼包库存-1
    $res1 = sql_update("update server_act set actvalue1=actvalue1-1 where said=$actId and actvalue1 > 0");
    // 玩家领奖+1,arg1表示福袋
    $res2 = sql_update("insert into unewAct (uid, actid, arg1) values ($uid, $actId, 1) on duplicate key update arg1=arg1+1");
    if ($itemid == 1) {
        // 扣除元宝，加铜钱奖励
        $res3 = _spendGbytype($uid, 40, '_buyLuckyGiftPackage');
        sql_update("update uinfo set ucoin=ucoin+$count where uid=$uid");
        $res4 = 1;
    } else {
        // 扣除元宝
        $res3 = _spendGbytype($uid, 40, '_buyLuckyGiftPackage');
        // 加道具奖励
        $res4 = _addItem($uid, $itemid, $count,'activity'.$actId);
    }
    if ($res1 && $res2 && $res3 && $res4) {
        sql_query("COMMIT");
        
        _addActLog($actId, $uid,$uinfo['uname'] ,array('item' => array($reward)),1);
        _addSysMsg(sprintf(STR_ACT_SysMsg1,$uinfo['uname'],STR_ACT_5_LuckyGiftPackage,$randRes['name'].' * '.$count));
        _addUserIncomeConsumeActLog($uid,'activity'.$actId,2,40,0,1);
        if ($itemid == 1) {
            _addUserIncomeConsumeActLog($uid,'activity'.$actId,1,$count,1);
        } else {
//             _addUserIncomeConsumeActLog($uid, 'activity'.$actId, $itemid, $count,1);
        }
        
        $actInfo = sql_fetch_one("select * from server_act where said=$actId");
        $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
        $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
            1,
            $actInfo,
            $uactInfo,
            $uInfo,
            $reward
        );
    } else {
        sql_query("ROLLBACK");
        $err = '';
        if (!$res1) {
            $err = STR_GiftPackage_Not_Enough;
        }
        if (!$res2) {
            $err = STR_DataErr2."-1";
        }
        if (!$res3) {
            $err = STR_UgOff;
        }
        if (!$res4) {
            $err = STR_DataErr2."-2";
        }
        return array(
            0,
            $err
        );
    }
}

function _get_rand($proArr) {
    $result = 0;
    $proSum = 0;
    $proVal = array();
    $proArr2 = array();
    for ($i = 0; $i < count($proArr); $i ++) {
        $arr = $proArr[$i];
        if ($i == 0) {
            $p = $arr['p'];
        }
        $id = $arr['id'];
        $proArr2[$id] = $arr;
        if ($i > 0) {
            $p += $proArr[$i - 1]['p']; 
        }
        $proSum += $arr['p'];
        $proVal[$id] = $p;
    }
    $randNum = mt_rand(1, $proSum);
    foreach ($proVal as $key => $proCur) {
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        }
    }
    return $proArr2[$result];
}

// 至尊大礼包
function _buySupremeGiftPackage($uid) {
    $actId = activityTypeDef::SERVEROPEN_GIFTPACKAGE_ACTIVITY;
    // 礼包不足
    $uinfo = sql_fetch_one("select ug,uname from uinfo where uid=$uid");
    $ug = intval($uinfo['ug']);
    if ($ug < 5000) {
        return array(
            0,
            STR_UgOff
        );
    }
    // 礼包库存不足 actvalue1表示至尊礼包
    $boughtnum = sql_fetch_one_cell("select actvalue2 from server_act where said=$actId");
    if ($boughtnum <= 0) {
        return array(
            0,
            STR_GiftPackage_Not_Enough
        );
    }
    // 已购买过
    $uBought = sql_fetch_one_cell("select arg2 from unewAct where uid=$uid and actid=$actId");
    if ($uBought != 0) {
        return array(
            0,
            STR_GiftPackage_Have_Bought
        );
    }
    $equipCfg = sql_fetch_rows("select * from cfg_equip where eid in (8005,12005,14005)");
    $equipCfg8005 = null;
    $equipCfg12005 = null;
    $equipCfg14005 = null;
    foreach ($equipCfg as $ecfg) {
        if ($ecfg['eid'] == 8005) {
            $equipCfg8005 = $ecfg;
        } elseif ($ecfg['eid'] == 12005) {
            $equipCfg12005 = $ecfg;
        } elseif ($ecfg['eid'] == 14005) {
            $equipCfg14005 = $ecfg;
        }
    }
    sql_query("BEGIN");
    $res1 = 0;
    $res2 = 0;
    $res3 = 0;
    $res4 = 0;
    $res5 = 0;
    $res6 = 0;
    $res7 = 0;
    $res8 = 0;
    $res9 = 0;
    // 礼包数量-1
    $res1 = sql_update("update server_act set actvalue2=actvalue2-1 where said=$actId and actvalue2 > 0");
    // 玩家领奖记录+1，arg2表示至尊礼包
    $res2 = sql_update("insert into unewAct (uid, actid, arg2) values ($uid, $actId, 1) on duplicate key update arg2=arg2+1");
    // 扣除元宝
    $res3 = _spendGbytype($uid, 5000, '_buyLuckyGiftPackage');
    // 添加声望
    $res4 = _addItem($uid, 8, 2000,'activity'.$actId);
    // 添加首领挑战卷
    $res5 = _addItem($uid, 11, 10,'activity'.$actId);
    $res6 = _doCreateEquip($uid, $equipCfg8005, 4, 36);
    $res7 = _doCreateEquip($uid, $equipCfg12005, 4, 34);
    $res8 = _doCreateEquip($uid, $equipCfg14005, 4, 47);
    if ($res1 && $res2 && $res3 && $res4 && $res5 && $res6 && $res7 && $res8) {
        sql_query("COMMIT");
        
        _addActLog($actId, $uid, $uinfo['uname'],array('item' => array(array(8,2000),array(11,10)),'equip' => array(array($res6,8005,4),array($res7,12005,4),array($res8,14005,4))),2);
        _addSysMsg(sprintf(STR_ACT_SysMsg2,$uinfo['uname'],STR_ACT_5_SupremeGiftPackage));
        _addUserIncomeConsumeActLog($uid,'activity'.$actId,2,5000,0,2);
        
        $actInfo = sql_fetch_one("select * from server_act where said=$actId");
        $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
        $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
            1,
            $actInfo,
            $uactInfo,
            $uInfo,
        );
    } else {
        sql_query("ROLLBACK");
        $err = '';
        if (!$res1) {
            $err = STR_GiftPackage_Not_Enough;
        }
        if (!$res2) {
            $err = STR_DataErr2."-3";
        }
        if (!$res3 || !$res4 || !$res5 || !$res6 || !$res7 || !$res8) {
            $err = STR_UgOff;
        }
        return array(
            0,
            $err
        );
    }
}

// 宝石大礼包
function _buyGemGiftPackage($uid) {
    $actId = activityTypeDef::SERVEROPEN_GIFTPACKAGE_ACTIVITY;
    // 礼包不足
    $uinfo = sql_fetch_one("select ug,uname from uinfo where uid=$uid");
    $ug = intval($uinfo['ug']);
    if ($ug < 2000) {
        return array(
            0,
            STR_UgOff
        );
    }
    // 礼包库存不足 actvalue2表示宝石礼包
    $boughtnum = sql_fetch_one_cell("select actvalue3 from server_act where said=$actId");
    if ($boughtnum <= 0) {
        return array(
            0,
            STR_GiftPackage_Not_Enough
        );
    }
    // 已购买过
    $uBought = sql_fetch_one_cell("select arg3 from unewAct where uid=$uid and actid=$actId");
    if ($uBought != 0) {
        return array(
            0,
            STR_GiftPackage_Have_Bought
        );
    }
    sql_query("BEGIN");
    // 礼包数量-1
    $res1 = sql_update("update server_act set actvalue3=actvalue3-1 where said=$actId and actvalue3 > 0");
    // 玩家领奖记录+1，arg2表示至尊礼包
    $res2 = sql_update("insert into unewAct (uid, actid, arg3) values ($uid, $actId, 1) on duplicate key update arg3=arg3+1");
    // 扣除元宝
    $res3 = _spendGbytype($uid, 2000, 'activity'.$actId);
    // 添加道具
    $res4 = _addItem($uid, 406, 1,'activity'.$actId);
    $res5 = _addItem($uid, 13, 4,'activity'.$actId);
    $res6 = _addItem($uid, 12, 20,'activity'.$actId);
    if ($res1 && $res2 && $res3 && $res4 && $res5 && $res6) {
        sql_query("COMMIT");
        
        _addActLog($actId, $uid,$uinfo['uname'], array('item' => array(array(406,1),array(13,4),array(12,20))),3);
        _addSysMsg(sprintf(STR_ACT_SysMsg2,$uinfo['uname'],STR_ACT_5_GemGiftPackage));
        
        $actInfo = sql_fetch_one("select * from server_act where said=$actId");
        $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
        $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
            1,
            $actInfo,
            $uactInfo,
            $uInfo,
        );
    } else {
        sql_query("ROLLBACK");
        $err = '';
        if (!$res1) {
            $err = STR_GiftPackage_Not_Enough;
        }
        if (!$res2) {
            $err = STR_DataErr2."-4";
        }
        if (!$res3) {
            $err = STR_UgOff;
        }
        if (!$res4 || !$res5 || !$res6) {
            $err = STR_DataErr2."-5";
        }
        return array(
            0,
            $err
        );
    }
}

// 黄金大礼包
function _buyGoldOrSilverGiftPackage($uid,$type = 1) {
    $itemCnt = $type == 1 ? 150 : 500;
    $costUg = $type == 1 ? 600 : 200;
    $rewardCoin = $type == 1 ? 800000 : 200000;
    $arg0 = $type == 1 ? 4 : 5;
    $item = $type == 1 ? array(array(43,2),array(23,1)) : array(array(42,1),array(22,2));
    $actvalue = $type == 1 ? 'actvalue4' : 'actvalue5';
    $argvalue = $type == 1 ? 'arg4' : 'arg5';
    $giftName = $type == 1 ? STR_ACT_5_GoldGiftPackage : STR_ACT_5_SilverGiftPackage;
    $giftId = $type == 1 ? 4 : 5;
    $actId = activityTypeDef::SERVEROPEN_GIFTPACKAGE_ACTIVITY;
    // 礼包不足
    $uinfo = sql_fetch_one("select ug,uname from uinfo where uid=$uid");
    $ug = intval($uinfo['ug']);
    if ($ug < $costUg) {
        return array(
            0,
            STR_UgOff
        );
    }
    // 礼包库存不足 actvalue3表示黄金礼包
    $boughtnum = sql_fetch_one_cell("select `$actvalue` from server_act where said=$actId");
    if ($boughtnum <= 0) {
        return array(
            0,
            STR_GiftPackage_Not_Enough
        );
    }
    
    sql_query("BEGIN");
    // 礼包数量-1
    $res1 = sql_update("update server_act set `$actvalue`=`$actvalue`-1 where said=$actId and `$actvalue` > 0");
    // 玩家领奖记录+1，arg4表示黄金礼包
    $res2 = sql_update("insert into unewAct (uid, actid, $argvalue) values ($uid, $actId, 1) on duplicate key update `$argvalue`=`$argvalue`+1");
    // 扣除元宝
    $res3 = _spendGbytype($uid, $costUg, '_buyGoldOrSilverGiftPackage');
    sql_update("update uinfo set ucoin=ucoin+$rewardCoin where uid=$uid");
    // 添加道具
    list($itemId1,$itemCnt1) = $item[0];
    list($itemId2,$itemCnt2) = $item[1];
    $res4 = _addItem($uid, $itemId1, $itemCnt1,'activity'.$actId);
    $res5 = _addItem($uid, $itemId2, $itemCnt2,'activity'.$actId);
    if ($res1 && $res2 && $res3 && $res4 && $res5) {
        sql_query("COMMIT");
        
        _addActLog($actId, $uid,$uinfo['uname'], array('item' => array(array($itemId1,$itemCnt1),array($itemId2,$itemCnt2),array(1,$rewardCoin))),$arg0);
        _addSysMsg(sprintf(STR_ACT_SysMsg2,$uinfo['uname'],$giftName));
        _addUserIncomeConsumeActLog($uid,'activity'.$actId,2,$costUg,0,$giftId);
        _addUserIncomeConsumeActLog($uid,'activity'.$actId,1,$rewardCoin,1,$giftId);
        
        $actInfo = sql_fetch_one("select * from server_act where said=$actId");
        $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
        $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
            1,
            $actInfo,
            $uactInfo,
            $uInfo,
        );
    } else {
        sql_query("ROLLBACK");
        $err = '';
        if (!$res1) {
            $err = STR_GiftPackage_Not_Enough;
        }
        if (!$res2) {
            $err = STR_DataErr2."-6";
        }
        if (!$res3) {
            $err = STR_UgOff;
        }
        if (!$res4 || !$res5) {
            $err = STR_DataErr2."-7";
        }
        return array(
            0,
            $err
        );
    }
}

function buySoulGift($uid,$params) {
    $actId = activityTypeDef::SOULOPEN_GIFTPACKAGE_ACTIVITY;
    $isOver = checkActivityOpenById($actId);
    if (!$isOver) {
        return array(
            0,
            STR_ActivityOver
        );
    }
    $gift = intval($params[0]);
    if ($gift < 0 || $gift > 4) {
        return array(
            0,
            STR_DataErr
        );
    }
    $costUg = 588;
    if ($gift == 0) {
        $costUg = 1888;
    }
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $ug = intval($uinfo['ug']);
    if ($ug < $costUg) {
        return array(
            0,
            STR_UgOff
        );
    }
    $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
    if ($gift == 0) {
        if ($uactInfo['arg5'] > 0) {
            return array(
                0,
                STR_ACT_6_Rune_Soldout
            );
        }
        $ujob = $uinfo['ujob'];
        $runeInfo = sql_fetch_one("SELECT * FROM `cfg_skill` WHERE `sjob`=$ujob and `lv`=4 order by rand() limit 1");
        $runeId = $runeInfo['sid'];
        sql_query("BEGIN");
        $res1 = sql_update("insert into unewAct (uid, actid, arg5) values ($uid, $actId, 1) on duplicate key update `arg5`=`arg5`+1");
        $res2 = _spendGbytype($uid, $costUg, 'activity'.$actId);
        $res3 = _addItem($uid, $runeId, 1,'activity'.$actId);
        $res4 = sql_update("update server_act set actvalue5=actvalue5+1 where said=$actId");
        if ($res1 && $res2 && $res3) {
            sql_query("COMMIT");
            _addActLog($actId, $uid, $uinfo['uname'], array('item' => array(array($runeId,1))));
            _addSysMsg(sprintf(STR_ACT_SysMsg3,$uinfo['uname'],$runeInfo['sname']));
            
            $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
            $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
            return array(
                1,
                $uactInfo,
                $uinfo,
                $runeId
            );
        } else {
            sql_query("ROLLBACK");
            $err = '';
            if (!$res1 || !$res3) {
                $err = STR_DataErr2."-8";
            }
            if (!$res2) {
                $err = STR_UgOff;
            }
            return array(
                0,
                $err
            );
        }
    } else {
        if (intval($uinfo['vip']) < 1) {
            return array(
                0,
                STR_Club_VIP_Not_Enough
            );
        }
        $actvalue = 'actvalue'.$gift;
        $boughtnum = sql_fetch_one_cell("select `$actvalue` from server_act where said=$actId");
        if ($boughtnum <= 0) {
            return array(
                0,
                _getSoulNameByType($gift).STR_GiftPackage_Not_Enough
            );
        }
        $argvalue = 'arg'.$gift;
        $soulkey = 'soul'.$gift.'pcnt';
        sql_query("BEGIN");
        $res1 = sql_update("update server_act set `$actvalue`=`$actvalue`-1 where said=$actId and `$actvalue` > 0");
        $res2 = sql_update("insert into unewAct (uid, actid, $argvalue) values ($uid, $actId, 1) on duplicate key update `$argvalue`=`$argvalue`+1");
        $res3 = _spendGbytype($uid, $costUg, 'buySoulGift');
        $res4 = sql_update("update usoul set `$soulkey`=`$soulkey`+1500 where uid=$uid");
        if ($res1 && $res2 && $res3 && $res4) {
            sql_query("COMMIT");
            _addActLog($actId, $uid, $uinfo['uname'], array('soul' => array(array($soulkey,1500))));
            _addSysMsg(sprintf(STR_ACT_SysMsg2,$uinfo['uname'],_getSoulNameByType($gift).STR_Soul_Peace.' * 1500'));
            _addUserIncomeConsumeActLog($uid,'activity'.$actId,2,40,0,1);
            
            $actInfo = sql_fetch_one("select * from server_act where said=$actId");
            $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
            $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
            $uSoul = sql_fetch_one("select * from usoul where uid=$uid");
            return array(
                1,
                $actInfo,
                $uactInfo,
                $uInfo,
                $uSoul
            );
        } else {
            sql_query("ROLLBACK");
            $err = '';
            if (!$res1) {
                $err = _getSoulNameByType($gift).STR_GiftPackage_Not_Enough;
            }
            if (!$res3) {
                $err = STR_UgOff;
            }
            if (!$res2 || !$res4) {
                $err = STR_DataErr2."-9";
            }
            return array(
                0,
                $err
            );
        }
    }
}

function getAct10Vip($uid,$params) {
    $actId = activityTypeDef::VIP_GIFT_ACTIVITY;
    $isOver = checkActivityOpenById($actId);
    if (!$isOver) {
        return array(
            0,
            STR_ActivityOver
        );
    }
    $vip = sql_fetch_one_cell("select vip from uinfo where uid=$uid");
    if ($vip < 1) {
        return array(
            0,
            STR_ACT_10_VIP_Low
        );
    }
    $uactInfo = sql_fetch_one("select *,datediff(FROM_UNIXTIME(`arg2`,'%Y-%m-%d'),CURDATE()) as dayoffset from unewAct where actid=$actId and uid=$uid limit 1");
    if ($uactInfo && intval($uactInfo['dayoffset']) == 0) {
        return array(
            0,
            STR_ACT_10_Have_Reward
        );
    }
    $vipReward = array(
        1 => array(1 => 30000),
        2 => array(1 => 50000),
        3 => array(1 => 80000,21 => 1),
        4 => array(1 => 100000,22 => 1),
        5 => array(1 => 150000,3 => 10),
        6 => array(1 => 200000,3 => 15,10 => 50),
        7 => array(1 => 250000,3 => 20,10 => 80),
        8 => array(1 => 300000,3 => 25,10 => 110),
        9 => array(1 => 400000,3 => 30,10 => 140,8 => 80),
        10 => array(1 => 500000,3 => 35,10 => 170,8 => 100),
        11 => array(1 => 600000,3 => 40,10 => 200,8 => 120),
        12 => array(1 => 700000,3 => 45,10 => 230,8 => 140),
        13 => array(1 => 800000,3 => 50,10 => 260,8 => 160),
        14 => array(1 => 900000,3 => 55,10 => 290,8 => 180),
        15 => array(1 => 1000000,3 => 60,10 => 320,8 => 200)
    );
    $theReward = $vipReward[$vip];
    $res = array();
    $soulName = '';
    foreach ($theReward as $k => $v) {
        if ($k == 1) {
            _addCoin($uid, $v,'activity'.$actId);
        } elseif ($k == 10) {
            $type = mt_rand(1, 4);
            $soulName = 'soul'.$type.'pcnt';
            sql_update("update usoul set $soulName=$soulName+$v where uid=$uid");
        } else {
            _addItem($uid, $k, $v,'activity'.$actId);
        }
    }
    sql_update("insert into unewAct (uid, actid, arg1,arg2) values ($uid, $actId, 1,UNIX_TIMESTAMP()) on duplicate key update arg1=arg1+1,arg2=UNIX_TIMESTAMP()");
    $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
    $uactInfo = sql_fetch_one("select * from unewAct where actid=$actId and uid=$uid");
    return array(
        1,
        $uactInfo,
        $uInfo,
        $soulName
    );
}

function _setAct8Info($uid,$payTotal) {
    $actId = activityTypeDef::PAY_RETURN_ACTIVITY;
    $actInfo = checkActivityOpenById($actId);
    // arg1:总数；
    if ($actInfo) {
        sql_update("insert into unewAct (uid, actid, arg1) values ($uid, $actId,$payTotal) on duplicate key update `arg1`=`arg1`+$payTotal");
    }
}

function getAct11Reward($uid, $params) {
    $actId = activityTypeDef::ROTLA_ACTIVITY;
    $actInfo = checkActivityOpenById($actId);
    $type = intval($params[0]);
    if ($type != 1 && $type != 10) {
        return array(
            0,
            STR_DataErr
        );
    }
    if ($actInfo) {
        // arg1:祈福1次次数；arg2:祈福10次次数;arg3:祈福时间
        $userAct = sql_fetch_one("select *,datediff(FROM_UNIXTIME(`arg3`,'%Y-%m-%d'),CURDATE()) as rday from unewAct where uid=$uid and actid=$actId limit 1");
        $needInit = 0;
        $arg1 = 0;
        $arg2 = 0;
        $arg3 = 0;
        $rday = intval($userAct['rday']);
        if ($userAct) {
            if ($rday == 0) {
                $arg1 = intval($userAct['arg1']);
                $arg2 = intval($userAct['arg2']);
            }
        }
        if ($type == 10 && $arg2 >= 200) {
            return array(
                0,
                STR_ACT_11_Got_All10
            );
        }
        $costUg = 0;
        if ($type == 1 && $arg1 >= 3) {
            $costUg = 50;
        }elseif ($type == 10) {
            $costUg = 480;
        }
        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        if ($uinfo['ug'] < $costUg) {
            return array(
                0,
                STR_UgOff
            );
        }
        _spendGbytype($uid, $costUg, 'activity'.$actId);
        $bingo = true;
        if ($type == 1 && mt_rand(1, 10000) > 1000) {
            $bingo = false;
        }
        $rewards = array(
            array('id' => 3,'name' => '强化符','count' => 15,'p' => 20),
            array('id' => 4,'name' => '神器精魂','count' => 1,'p' => 6),
            array('id' => 11,'name' => '首领挑战令','count' => 3,'p' => 20),
            array('id' => 12,'name' => '下级宝石袋','count' => 3,'p' => 14),
            array('id' => 21,'name' => '铜钥匙','count' => 3,'p' => 20),
            array('id' => 41,'name' => '小铜锤','count' => 3,'p' => 20),
        );
        $goRewards = array();
        for ($i = 0; $i < $type; $i ++) {
            $randRes =  _get_rand($rewards);
            if (!isset($goRewards[$randRes['id']])) {
                $goRewards[$randRes['id']] = 0;
            }
            $goRewards[$randRes['id']] += $randRes['count'];
        }
        foreach ($goRewards as $k => $v) {
            _addItem($uid, $k, $v, 'activity'.$actId);
        }
        
        $goRewards2 = array();
        $soulRewards = array();
        if ($bingo) {
            $rewards = array(
                array('id' => 4,'name' => '神器精魂','count' => 3,'p' => 9),
                array('id' => 13,'name' => '上级宝石袋','count' => 2,'p' => 18),
                array('id' => 23,'name' => '金钥匙','count' => 5,'p' => 27),
                array('id' => 43,'name' => '小金锤','count' => 5,'p' => 27),
                array('id' => 44,'name' => '神兽碎片','count' => 100,'p' => 10),
                array('id' => 45,'name' => '3级符文','count' => 1,'p' => 9)
            );
            $rt = mt_rand(1, 10000) <= 6000 ? 2 : 3;
            for ($i = 0; $i < $rt; $i ++) {
                $randRes =  _get_rand($rewards);
                $itemId = intval($randRes['id']);
                $count = $randRes['count'];
                if ($itemId != 44) {
                    if ($itemId == 45) {
                        $ujob = $uinfo['ujob'];
                        $runeInfo = sql_fetch_one("SELECT * FROM `cfg_skill` WHERE `sjob`=$ujob and `lv`=3 order by rand() limit 1");
                        $itemId = $runeInfo['sid'];
                    }
                    if (!isset($goRewards2[$itemId])) {
                        $goRewards2[$itemId] = 0;
                    }
                    $goRewards2[$itemId] += $count;
                    _addItem($uid, $itemId, $count,'activity'.$actId);
                } else {
                    $soulType = mt_rand(1, 4);
                    if (!isset($soulRewards[$itemId])) {
                        $soulRewards[$soulType] = 0;
                    }
                    $soulRewards[$soulType] += $count;
                    _addSoul($uid, $soulType, $count);
                }
            }
        }
        $argName = '';
        $argName2 = '';
        if ($type == 1) {
            $argName = 'arg1';
            $argName2 = 'arg2';
        } else {
            $argName = 'arg2';
            $argName2 = 'arg1';
        }
        if ($rday == 0) {
            sql_update("insert into unewAct (uid, actid, $argName,arg3) values ($uid, $actId, 1,UNIX_TIMESTAMP()) on duplicate key update $argName=$argName+1,arg3=UNIX_TIMESTAMP()");
        } else {
            sql_update("insert into unewAct (uid, actid, $argName,$argName2,arg3) values ($uid, $actId, 1,0,UNIX_TIMESTAMP()) on duplicate key update $argName=1,$argName2=0,arg3=UNIX_TIMESTAMP()");
        }
        
        $uActInfo = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
        return array(
            1,
            $uActInfo,
            $goRewards,
            $goRewards2,
            $soulRewards
        );
    } else {
        return array(
            0,
            STR_ActivityOver
        );
    }
}

function getAct12Reward($uid,$params) {
    $actId = activityTypeDef::MAMMON_GIFT_ACTIVITY;
    $actInfo = checkActivityOpenById($actId);
    if ($actInfo) {
        $userAct = sql_fetch_one("select *,datediff(FROM_UNIXTIME(`arg2`,'%Y-%m-%d'),CURDATE()) as rday from unewAct where uid=$uid and actid=$actId limit 1");
        // arg1:拆红包次数；arg2:拆红包时间
        $arg1 = 0;
        $arg2 = 0;
        $rday = 0;
        if ($userAct) {
            $rday = $userAct['rday'];
            if ($rday == 0) {
                $arg1 = $userAct['arg1'];
                $arg2 = $userAct['arg2'];
            }
        }
        if ($arg1 >= 4) {
            return array(
                0,
                STR_ACT_12_Got_All
            );
        }
        $payTotal = getTodayRecharge($uid);
        $chaiNum = 1;
        $remainPay = 0;
        $addUg = 66;
        $addCoin = mt_rand(50000, 100000);
        $addItem3 = mt_rand(0, 50);
        $addItem11 = mt_rand(1, 5);
        if ($payTotal >= 100) {
            $chaiNum ++;
            $addUg = mt_rand(50, 100);
            $addCoin = mt_rand(200000, 300000);
            $addItem3 = mt_rand(51, 100);
            $addItem11 = mt_rand(6, 10);
        } else {
            $remainPay = 100 - $payTotal;
        }
        if ($payTotal >= 600) {
            $chaiNum ++;
            $addUg = mt_rand(300, 600);
            $addCoin = mt_rand(400000, 500000);
            $addItem3 = mt_rand(101, 150);
            $addItem11 = mt_rand(11, 15);
        } else {
            $remainPay = 600 - $payTotal;
        }
        if ($payTotal >= 1600) {
            $chaiNum ++;
            $addUg = mt_rand(800, 1600);
            $addCoin = mt_rand(600000, 700000);
            $addItem3 = mt_rand(151, 200);
            $addItem11 = mt_rand(16, 20);
        } else {
            $remainPay = 1600 - $payTotal;
        }
        if ($arg1 >= $chaiNum) {
            return array(
                0,
                sprintf(STR_ACT_12_Got_All2,$remainPay)
            );
        }
        if ($rday == 0) {
            sql_update("insert into unewAct (uid, actid, arg1, arg2) values ($uid, $actId,$arg1 + 1, UNIX_TIMESTAMP()) on duplicate key update `arg1`=`arg1`+1,`arg2`=UNIX_TIMESTAMP()");
        } else {
            sql_update("insert into unewAct (uid, actid, arg1, arg2) values ($uid, $actId,1, UNIX_TIMESTAMP()) on duplicate key update `arg1`=1,`arg2`=UNIX_TIMESTAMP()");
        }
        _addUg($uid, $addUg,'activity'.$actId);
        _addCoin($uid, $addCoin,'activity'.$actId);
        _addItem($uid, 3, $addItem3,'activity'.$actId);
        _addItem($uid, 11, $addItem11,'activity'.$actId);
        $userAct = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
        return array(
            $userAct,
            $addUg,
            $addCoin,
            array(array(3,$addItem3),array(11,$addItem11))
        );
    } else {
        return array(
            0,
            STR_ActivityOver
        );
    }
}

function _addActLog($actid,$uid,$uname,$items,$arg0 = 0) {
//     $itemsArr = array();
//     foreach ($items as $key => $value) {
//         $itemStr = $key.':';
//         $tmp = array();
//         foreach ($value as $info) {
//             $tmp[] = implode('|', $value);
//         }
//         $itemStr .= implode(',', $tmp);
//         $itemsArr[] = $itemStr;
//     }
//     $tmpStr = implode(';', $itemsArr);
    $tmpStr = json_encode($items);
    sql_insert("insert into uact_log (actid,arg0,uid,uname,items,ts) values ($actid,$arg0,$uid,'$uname','$tmpStr',UNIX_TIMESTAMP())");
}

function getActLog($uid,$params) {
    $actid = intval($params[0]);
    $isRand = intval($params[1]);
    $sql = '';
    if ($isRand == 0) {
        $sql = "select * from uact_log where actid=$actid and ts < UNIX_TIMESTAMP() order by ts desc limit 10";
    } else {
        $sql = "select * from uact_log where actid=$actid and ts < UNIX_TIMESTAMP() order by rand() desc limit 10";
    }
    $rows = sql_fetch_rows($sql);
    for ($i = 0; $i < count($rows); ++$i){
        $rows[$i]['items'] = json_decode($rows[$i]['items'], true);
    }
    return array(1,$rows);
}

function _check_equip_double($uid) {
    if (checkdouble($uid, 3) || checkActivityOpenById(activityTypeDef::DROP_ACTIVITY)){
        return true;
    }
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 1 || $regDay == 2;
    }
    
    return false;
}

function _check_coin_double($uid) {
    if (checkdouble($uid, 1) || checkActivityOpenById(activityTypeDef::COIN_ACTIVITY)){
        return true;
    }
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 3;
    }
    
    return false;
}

function _check_exp_double($uid) {
    if (checkdouble($uid, 2) || checkActivityOpenById(activityTypeDef::EXP_ACTIVITY)){
        return true;
    }
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 4;
    }
    
    return false;
}

function _check_quick_run_double($uid) {
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 5;
    }
    return false;
}

function _check_forge_point_double($uid) {
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 6;
    }
    return false;
}

function _check_equip_sell_double($uid) {
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 7;
    }
    return false;
}

function _check_quick_run_half_price($uid) {
    if (checkActivityOpenById(activityTypeDef::NEW_USER_GIFT_ACTIVITY)) {
        $regDay = _get_reg_days($uid);
        return $regDay == 8;
    }
    return false;
}

function _check_quick_run_coin_exp_addition($vip) {
    if (checkActivityOpenById(activityTypeDef::VIP_GIFT_ACTIVITY)) {
        if ($vip < 5) {
            return 0;
        } elseif ($vip <= 6) {
            return 500;
        } elseif ($vip <= 8) {
            return 1000;
        } elseif ($vip <= 10) {
            return 1500;
        } elseif ($vip <= 12) {
            return 2000;
        } elseif ($vip <= 13) {
            return 2500;
        } elseif ($vip <= 14) {
            return 3000;
        } elseif ($vip <= 15) {
            return 4000;
        }
    }
    return 0;
}


/**
 * 接口：领取更名奖励
 * @param $uid
 * @param $params
 */
function getChangeNameReward($uid, $params)
{
    $reward = array(
            //!奖励ID，数量
            array(2, 100), //!元宝
    );
    if (checkActivityOpenById(activityTypeDef::CHANGE_NAME_ACTIVITY)){
        $actId = activityTypeDef::CHANGE_NAME_ACTIVITY;
        $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");

        if ($uactinfo && intval($uactinfo['arg1'] >= intval($uactinfo['ts']))) {
            return array(
                    0,
                    STR_Have_Reward
                );
        }

        for ($i = 0; $i < count($reward); ++$i){
            if ($reward[$i][0] == 2){ //!元宝
                _addUg($uid, $reward[$i][1],'activity'.$actId);
            }else{
                _addItem($uid, $reward[$i][0], $reward[$i][1]);
            }
        }

        //!增加次数
        sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg1) values ($uid, $actId, UNIX_TIMESTAMP(CURDATE())) ON DUPLICATE KEY UPDATE arg1 = UNIX_TIMESTAMP(CURDATE())");

        $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
        return array(
                1,
                $uinfo
        );
    }

    return array(
            0,
            STR_ActivityOver
    );
}


/**
 * 接口：获取CDKey兑换奖励
 * @param $uid
 * @param $param ['cdkey']
 * @return array
 */
function getCdkeyReward($uid, $params)
{
    $actId = activityTypeDef::CDKEY_ACTIVITY;
    if (!checkActivityOpenById($actId)){
        return array(
                0,
                STR_ActivityOver
        );
    }
    
    $cdkey = strtolower(strval($params[0]));
    $cdkey = trim($cdkey);
    if (!ctype_alnum($cdkey)) {
        return array(
                0,
                STR_Cdkey_Error
        );
    }
      
    $db_uinfos = sql_fetch_one("SELECT * FROM cuser WHERE uid=$uid");
    if (!$db_uinfos){
        return array(
                0,
                STR_DataErr
        );
    }
    $loginname = $db_uinfos['loginname'];

    $url = CDKEY_URL . "/cdkeycreater.php?";
    $url .= "uid=" . $uid;
    $url .= "&loginname=" . $loginname;
    $url .= "&cdkey=" . $cdkey;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($result, true);
    if (count($result) < 2 || $result[0] == 0){
        return $result;
    }
    $addReward = array();
    for ($i = 0; $i < count($result[1]); ++$i){
        if ($result[1][$i][0] == 1){ //!铜钱
            _addCoin($uid, $result[1][$i][1],'activity'.$actId);
        }elseif ($result[1][$i][0] == 2){ //!元宝
            _addUg($uid, $result[1][$i][1],'activity'.$actId);
        }elseif ($result[1][$i][0] == 5){ //!经验
            _addExp($uid, $result[1][$i][1]);
        }else{
            _addItem($uid, $result[1][$i][0], $result[1][$i][1]);
        }
        $addReward[] = array($result[1][$i][0], $result[1][$i][1]);
    }
   
    //!返回结果
    $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
    $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
    return array(
            1,
            $uinfo,
            $uitem,
            $addReward
    ); 
}


function setTodayCost($uid, $ug)
{
    $oldug = $ug;
    $actId = activityTypeDef::DABAI_ACTIVITY;
    if (checkActivityOpenById($actId)){
        $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");
        
        //!每日抽取次数
        $times = 0;
        $shiwuTimes = 0;
        if ($uactinfo && intval($uactinfo['arg1']) == intval($uactinfo['ts'])) {
            $ug += intval($uactinfo['arg2']);
            $times = intval($uactinfo['arg4']);
            $shiwuTimes = intval($uactinfo['arg5']);
        }
        
        
        //!增加数据
        sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg1, arg2, arg3, arg4, arg5) values ($uid, $actId, UNIX_TIMESTAMP(CURDATE()), $ug, 0, $times, $shiwuTimes) ON DUPLICATE KEY UPDATE arg1 = UNIX_TIMESTAMP(CURDATE()), arg2=$ug, arg4=$times, arg5=$shiwuTimes");
        
    }
    
    
    $ug = $oldug;
    //!不驱战神活动
    $actId = activityTypeDef::BUQUE_ZHANSHEN_ACTIVITY;
    if (checkActivityOpenById($actId)){
        $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");
    
        $arg3 = $arg4 = $arg5 = $arg6 = 0;
        //!每日抽取次数
        if ($uactinfo && intval($uactinfo['arg1']) == intval($uactinfo['ts'])) {
            $ug += intval($uactinfo['arg2']);
            $arg3 = intval($uactinfo['arg3']);
            $arg4 = intval($uactinfo['arg4']);
            $arg5 = intval($uactinfo['arg5']);
            $arg6 = intval($uactinfo['arg6']);
        }
        if ($arg3 == 0 && $ug >= 588){
            $arg3 = 1;
            _addMail($uid,STR_BUQUE_ZHANSHENG1,STR_BUQUE_ZHANSHENG1, 0, 0, 0, 606,10);
        }
        if ($arg4 == 0 && $ug >= 2888){
            $arg4 = 1;
            _addMail($uid,STR_BUQUE_ZHANSHENG2,STR_BUQUE_ZHANSHENG2, 0, 0, 0, 18003,2);
        }
        if ($arg5 == 0 && $ug >= 5888){
            $arg5 = 1;
            _addMail($uid,STR_BUQUE_ZHANSHENG3,STR_BUQUE_ZHANSHENG3, 0, 0, 0, 18004,1);
        }
        if ($arg6 == 0 && $ug >= 9888){
            $arg6 = 1;
            _addMail($uid,STR_BUQUE_ZHANSHENG4,STR_BUQUE_ZHANSHENG4, 0, 0, 0, 91014,5);
        }
        //!增加数据
        sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg1, arg2, arg3, arg4, arg5,arg6) values ($uid, $actId, UNIX_TIMESTAMP(CURDATE()), $ug, $arg3, $arg4, $arg5, $arg6) ON DUPLICATE KEY UPDATE arg1 = UNIX_TIMESTAMP(CURDATE()), arg2 = $ug, arg3 = $arg3, arg4=$arg4, arg5=$arg5, arg6=$arg6");
    
    }
}

function getDabaiKey($uid){
    $db_uinfos = sql_fetch_one("SELECT * FROM cuser WHERE uid=$uid");
    if (!$db_uinfos){
        return array(
                0,
                STR_DataErr
        );
    }
    $loginname = $db_uinfos['loginname'];
    
    $url = CDKEY_URL . "/dabaikey.php?";
    $url .= "cmd=dabaiKey";
    $url .= "&uid=" . $uid;
    $url .= "&loginname=" . $loginname;
    $url .= "&sid=" . SERVER_ID;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($result, true);
    
    return $result;
}

/**
 * 接口：获取大白抽奖
 * @param $uid
 * @param $param []
 * @return array
 * arg1:时间戳
 * arg2:消费数
 * arg3:是否第一次抽
 * arg4:每日抽取次数
 * arg5:今日是否实物抽奖
 * arg10: xxx,xxx,xx 兑换码逗号分隔
 */
function getdabaiReward($uid, $params)
{
    $reward = array();
    $reward[] = array(2, 50, 5331);//!奖励ID，数量, 概率
    $reward[] = array(2, 100, 2854);//!奖励ID，数量, 概率
    $reward[] = array(2, 150, 678);//!奖励ID，数量, 概率
    $reward[] = array(2, 200, 432);//!奖励ID，数量, 概率
    $reward[] = array(2, 300, 388);//!奖励ID，数量, 概率
    $reward[] = array(2, 500, 213);//!奖励ID，数量, 概率
    $reward[] = array(2, 700, 104);//!奖励ID，数量, 概率
    $condition = array(288, 588, 888);
    $actId = activityTypeDef::DABAI_ACTIVITY;
    if (/*checkActivityOpenById($actId)*/true){
        $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");
        if (!$uactinfo) {
            sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg1, arg2) values ($uid, $actId, UNIX_TIMESTAMP(CURDATE()), 0)");
            $uactinfo = sql_fetch_one("select *, UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid=$uid and actid=$actId");
        }
        //!一次实物抽奖
        if (intval($params[0] == 1)){
            $result = array(0, STR_CanNot_Reward);
            if (intval($uactinfo['arg1']) == intval($uactinfo['ts']) && 0 == intval($uactinfo['arg5']) && intval($uactinfo['arg2']) >= $condition[count($condition) - 1]){
                $result = getDabaiKey($uid);
                
                if ($result[0] == 1){
                    $keys = array();
                    if (!empty($uactinfo['arg10'])){
                        $keys = explode(",", $uactinfo['arg10']);
                    }
                    $keys[] = $result[1];
                    $keystr = implode(",", $keys);
                    //!增加次数
                    sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg5, arg10) values ($uid, $actId, 1, '$keystr') ON DUPLICATE KEY UPDATE arg5=arg5+1, arg10='$keystr'");
                    $result[] = sql_fetch_one("select * from unewAct where uid=$uid and actid=$actId");
                }
            }
            
            return $result;
        }else{
            $addReward = array(); //!增加奖励，前端显示用
            if (count($condition) <= intval($uactinfo['arg4'])){
                return array(
                        0,
                        STR_CanNot_Reward
                );
            }elseif (intval($uactinfo['arg3']) == 0){ //!第一次抽
                _addUg($uid, 50,'activity'.$actId);
                $addReward[] = array(2, 50);
                //!增加次数
                sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg3) values ($uid, $actId, 1) ON DUPLICATE KEY UPDATE arg3 = 1");
                
            }elseif (intval($uactinfo['arg1']) == intval($uactinfo['ts']) && intval($uactinfo['arg2']) >= $condition[intval($uactinfo['arg4'])]){//!当天消费条件满足
                //!增加次数
                sql_update("INSERT IGNORE INTO unewAct (uid, actid, arg4) values ($uid, $actId, 1) ON DUPLICATE KEY UPDATE arg4 = arg4 + 1");
                //!随机奖励
                $randNum = rand(1, 10000);
                $num = 0;
                for ($i = 0; $i < count($reward); ++$i){
                    $num += $reward[$i][2];
                    if($randNum <= $num){
                        if ($reward[$i][0] == 1){ //!铜钱
                            _addCoin($uid, $reward[$i][1],'activity'.$actId);
                        }
                        if ($reward[$i][0] == 2){ //!金币
                            _addUg($uid, $reward[$i][1],'activity'.$actId);
                        }else{
                            _addItem($uid, $reward[$i][0], $reward[$i][1]);
                        }
                        $addReward[] = array($reward[$i][0], $reward[$i][1]);
                        break;
                    }
                }
                
            }else{
                return array(
                        0,
                        STR_CanNot_Reward
                );
            }
            
            $uinfo = sql_fetch_one("select * from uinfo where uid=$uid");
            return array(
                    1,
                    $uinfo,
                    $addReward
            );
        }
    }

    return array(
            0,
            STR_ActivityOver
    );
}


function setdabaiKeyAddr($uid, $params)
{
    $actId = activityTypeDef::DABAI_ACTIVITY;
    if (checkActivityOpenById($actId)){
        $db_uinfos = sql_fetch_one("SELECT * FROM cuser WHERE uid=$uid");
        if (!$db_uinfos){
            return array(
                    0,
                    STR_DataErr
            );
        }
        $loginname = $db_uinfos['loginname'];
         
        $uname = urldecode($params[1]);
        $uname = _filterstr($uname);
        $uname = ltrim(rtrim($uname));
        global $conn;
        $uname = $conn->escape_string($uname);
        
        if (!is_numeric($params[2])) {
            return array(
                    0,
                    STR_Addr_Error
            );
        }
        $url = CDKEY_URL . "/dabaikey.php?";
        $url .= "cmd=dabaiKeyAddr";
        $url .= "&uid=" . $uid;
        $url .= "&loginname=" . $loginname;
        $url .= "&sid=" . SERVER_ID;
        $url .= "&key=" . $params[0];
        $url .= "&name=" . $uname;
        $url .= "&tel=" . $params[2];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($result, true);
        
        return $result;
    }
    
    return array(
            0,
            STR_ActivityOver
    );
}


/**
 * 获得宝箱礼物
 * @param unknown $uid
 * @param unknown $params
 */
function getOpenBoxReward($uid,$params){
    
   $isFree = intval($params[0]);//是否为免费打开宝箱
   $actid = activityTypeDef::OPEN_BOX_ACTIVITY;
   $activityInfo = checkActivityOpenById($actid);
   $ugArr = array(50,100,200);
   $cdArr = array(600,1800,3600);
   if ($activityInfo) {
      
       
       //参数验证
       if(($isFree != 1) && ($isFree != 0)){
           return array(
               0,
               STR_DataErr
           );
       }
       $boxType = intval($params[1]);
       if($boxType < 1 || $boxType > 3){
           return array(
               0,
               STR_DataErr
           );
       }
       
       $tt = 0;
       $arg = 'arg'.$boxType;//记录上次领取的时间戳的字段'arg1/2/3'
       $arg2 = 'arg'.($boxType+3);//记录领取过的次数的字段
       $costUg = $ugArr[$boxType-1];//需要花费的元宝数
       $cdSec  = $cdArr[$boxType-1]; //冷却时间      
       $uactinfo = sql_fetch_one("select *,UNIX_TIMESTAMP() as ts, CURDATE() as today, from_unixtime($arg,'%Y-%m-%d')as td from unewAct where uid=$uid and actid=$actid");
       $opentimes = intval(sql_fetch_one_cell("select opentimes from cfg_vip v inner join uinfo u on u.vip = v.vip where u.uid = $uid"));//免费开宝箱的次数
           
       if($isFree == 0){   
           if (!_spendGbytype($uid, $costUg, '_openBoxgift')) {
               return array(
                   0,
                   STR_UgOff
               );         
            }
             //扣元宝
       }else{             
            if($uactinfo){        
                if(($uactinfo['td'] < $uactinfo['today']) && intval($uactinfo[$arg])!=0) {//时间戳不为今天，可以免费打开宝箱        
                    $tt = 1;       
                }else{
                    
                    if(intval($uactinfo['ts'])-intval($uactinfo[$arg]) < $cdSec){//cd时间没完， 不能打开宝箱
                        return array(
                            0,
                            STR_CD_NoReach
                        );
                    }                
                    if($uactinfo[$arg2]>=$opentimes){//免费次数用完，不能打开宝箱
                        return array(
                            0,
                            STR_Reach_Limit
                        );
                    }             
                    $tt = $uactinfo[$arg2] +1;
                    
                }
               
            }
       }
               
       //领奖   
       $reward = sql_fetch_rows("select * from cfg_vipchest where boxtype = $boxType");
       $num = 0;
       for($j = 0; $j < 3; $j++){
           $randNum = rand(1,10000);
            for ($i = 0; $i < count($reward); ++$i){  
                $num += intval($reward[$i]['prob']);    
                if($randNum <= $num){    
                    if ($reward[$i]['itemid'] == 1){ //!铜钱
                        _addCoin($uid, $reward[$i]['count'],activityTypeDef::OPEN_BOX_ACTIVITY);
                    }
                    if ($reward[$i]['itemid'] == 2){ //!金币
                        _addUg($uid, $reward[$i]['count'],activityTypeDef::OPEN_BOX_ACTIVITY);
                    }else{
                        _addItem($uid, $reward[$i]['itemid'], $reward[$i]['count']);
                    }
                    $addReward[] = array($reward[$i]['itemid'], $reward[$i]['count']);
                    break;
                }
            }
        }
                
        if($isFree == 1){//更新记录
          sql_update("insert into unewAct(uid,actid,$arg,$arg2) values($uid,$actid,unix_timestamp(),1) ON DUPLICATE KEY UPDATE  $arg = unix_timestamp(), $arg2 = $tt");
        }
        return array(
           1,
           $addReward
        );
   }
   return array(
           0,
           STR_ActivityOver
   );
}



/**
 * 领取天天充值奖励
 * @param $uid
 * @param $params['day']
 * @return array
 */
function getdailyRechargeReward($uid,$params){
    $rewards = array(
        //!奖励ID，数量
        array(3, 2), //!强化符
        array(601, 1), //!附魔之尘
        array(605, 1), //!美酒
    );
    $actId = activityTypeDef::DAILY_RECHARGE_ACTIVITY;
    $activityInfo = checkActivityOpenById($actId);
    if ($activityInfo) {
        $day = intval($params[0]);
        $udailyInfo = sql_fetch_one("select * from unewAct where uid = $uid and actid = $actId");
        if (!$udailyInfo) {
            return array(
                0,
                STR_CanNot_Reward
            );
        }
        $daystatus = intval($udailyInfo['arg'.$day]);
        if($day < 1 || $day > 7 || $daystatus == 0){
            return array(
                0,
                STR_CanNot_Reward
            );
        }elseif($daystatus == 2){
            return array(
                0,
                STR_Have_Reward
            );
        }else{
            //领取奖励
            for($i = 0;$i <count($rewards);$i++){
                _addItem($uid, $rewards[$i][0], $rewards[$i][1],'activity'.$actId);
            }
            //修改领取状态
            sql_update("update unewAct set arg$day = 2 where uid = $uid and actid = $actId");
            $uitem = sql_fetch_rows("select * from uitem where uid=$uid");
            return array(
                1,
                $uitem,
                $rewards
            );
        }
    }
    return array(
        0,
        STR_ActivityOver
    );

}

function setDailyRecharge($uid)
{
    $actId = activityTypeDef::DAILY_RECHARGE_ACTIVITY;
    if (checkActivityOpenById($actId)){
        $actInfo = sql_fetch_one("select UNIX_TIMESTAMP(from_unixtime(startts,'%Y-%m-%d')) as sts, UNIX_TIMESTAMP(from_unixtime(endts,'%Y-%m-%d')) as ets from server_act where said=$actId");
        //获取第*天
        $startts = intval($actInfo['sts']);
        $endts = intval($actInfo['ets']);
        
        //!初始化状态
        $arg = array_fill(0, 7, 0);
        $uactinfo = sql_fetch_one("select * from unewAct where uid = $uid and actid = $actId");
        if(!$uactinfo){
            sql_update("insert into unewAct (uid,actid) values ($uid,$actId)");
        }else{
            if(intval($uactinfo['arg8']) == $startts){ //!说明是同期活动
                $arg[0] = intval($uactinfo['arg1']);
                $arg[1] = intval($uactinfo['arg2']);
                $arg[2] = intval($uactinfo['arg3']);
                $arg[3] = intval($uactinfo['arg4']);
                $arg[4] = intval($uactinfo['arg5']);
                $arg[5] = intval($uactinfo['arg6']);
                $arg[6] = intval($uactinfo['arg7']);
            }
        }
        
        
        //!检查充值情况
        $recharges = sql_fetch_rows("select UNIX_TIMESTAMP(from_unixtime(billts,'%Y-%m-%d')) as bts,sum(cost) as pcnt from pay_gamebar where uid=$uid and billts >= $startts and billts <= $endts group by bts order by bts;");
        $ischange = false;
        for($i = 0; $i < count($recharges); ++$i){
            $day = floor((intval($recharges[$i]['bts'])-$startts)/86400);
            if($day <= 7 && $arg[$day] == 0){
                $arg[$day] = 1;
                $ischange = true;
            }
        }
        
        if($ischange){
            //!更新数据
            $str = "";
            for($i = 1; $i <= 7; ++$i){
                $stat = $arg[$i - 1];
                $str .= "arg" . $i . "=$stat,";
            }
            $str .= "arg8 = $startts";
            sql_update("update unewAct set $str where uid = $uid and actid = $actId");
        }
    }
}

function setJiangDongRecharge($uid)
{
    $actId = activityTypeDef::JIANGDONG_RECHARGE_ACTIVITY;
    if (checkActivityOpenById($actId)){
        $actInfo = sql_fetch_one("select * from server_act where said=$actId");
        //获取第*天
        $startts = intval($actInfo['startts']);
        $endts = intval($actInfo['endts']);
  
        $totalRe = getTimeRecharge($uid, $startts, $endts);
        
        $oldRe = 0;
        $xiaoqiao = 0;
        $daqiao1 = $daqiao2 = $daqiao3 = $daqiao4 = 0;
        //!初始化状态
        $uactinfo = sql_fetch_one("select * from unewAct where uid = $uid and actid = $actId");

        if($uactinfo){
            $oldRe = intval($uactinfo['arg1']);
            $xiaoqiao = intval($uactinfo['arg2']);
            $daqiao1 = intval($uactinfo['arg3']);
            $daqiao2 = intval($uactinfo['arg4']);
            $daqiao3 = intval($uactinfo['arg5']);
            $daqiao4 = intval($uactinfo['arg6']);
        }
        
        if ($totalRe != $oldRe){ //!说明充值有变化
            if ($xiaoqiao == 0 && $totalRe >= 500){//!小乔可以领取
                _addMail($uid,STR_JIANGDONG_XIAOQIAO,STR_JIANGDONG_XIAOQIAO, 0, 0, 0, 90763,30);
                $xiaoqiao = 1;
            }
            
            if ($daqiao1 == 0 && $totalRe >= 1000){ //!大乔可以领取
                _addMail($uid,STR_JIANGDONG_DAQIAO1,STR_JIANGDONG_DAQIAO1, 0, 0, 0, 90304,5,18003,5);
                $daqiao1 = 1;
            }
            
            if ($daqiao2 == 0 && $totalRe >= 2888){ //!大乔可以领取
                _addMail($uid,STR_JIANGDONG_DAQIAO2,STR_JIANGDONG_DAQIAO2, 0, 0, 0, 90304,5,18003,5);
                $daqiao2 = 1;
            }
            
            if ($daqiao3 == 0 && $totalRe >= 3888){ //!大乔可以领取
                _addMail($uid,STR_JIANGDONG_DAQIAO3,STR_JIANGDONG_DAQIAO3, 0, 0, 0, 90304,5,18003,5);
                $daqiao3 = 1;
            }
            
            if ($daqiao4 == 0 && $totalRe >= 5888){ //!大乔可以领取
                _addMail($uid,STR_JIANGDONG_DAQIAO4,STR_JIANGDONG_DAQIAO4, 0, 0, 0, 90304,15,18003,5);
                $daqiao4 = 1;
            }
            
            
            sql_update("insert into unewAct (uid,actid,arg1,arg2,arg3,arg4,arg5,arg6) values ($uid,$actId,$totalRe,$xiaoqiao,$daqiao1,$daqiao2,$daqiao3,$daqiao4) on duplicate key update arg1=$totalRe,arg2=$xiaoqiao,arg3=$daqiao1,arg4=$daqiao2,arg5=$daqiao3,arg6=$daqiao4");
        }
    }
}
/***
 * 获取“六一连线”的奖励
 * @param unknown $uid
 */

function getSixOneLineReward($uid,$params){
    $reward = array(
        array(3, 20),
        array(18003, 3),
        array(4, 2),
        array(504, 3),
        array(601, 5),
        array(13, 5),
        array(18004, 1),
        array(18004, 2)   
    ); 
    $tastRefer = array(0, 5, 1, 10, 8, 1, 10, 1, 3, 20);
    $tast = array_fill(0, 10, 0);
    $rewardInfo = array();
    
    $actId = activityTypeDef::SIX_ONE_LINE;
    if(!checkActivityOpenById($actId)){
        return array(
            0,
            STR_ActivityOver
        );
    }
    $uactinfo = sql_fetch_one("select * ,CURDATE() as today, from_unixtime(arg9,'%Y-%m-%d')as td from unewAct where uid = $uid and actid = $actId");
    $index = -1;
    $rewardID = intval($params[0]);
    if($rewardID > 8 || $rewardID < 1){
        return array(
            0,
            STR_Param_Error
        );
    }
    
    if($uactinfo && $uactinfo['today'] == $uactinfo['td']){
       $tastArr = explode(",", $uactinfo['arg10']);
       foreach ($tastArr as $tastStr){         
           $tastArrStr = explode("|", $tastStr);
           if(count($tastArrStr) == 2){
               if($tastRefer[$tastArrStr[0]] == $tastArrStr[1]){
                   $tast[$tastArrStr[0]] = 1;
               }
           }
       }
       
       if(intval($uactinfo['arg1']) == 0 && $tast[1] == 1 && $tast[2] == 1 && $tast[3] == 1 && $rewardID == 1){                     
           $index = 0;         
       }
       if(intval($uactinfo['arg2']) == 0 && $tast[4] == 1 && $tast[5] == 1 && $tast[6] == 1 && $rewardID == 2){
           $index = 1;
       }
       if(intval($uactinfo['arg3']) == 0 && $tast[7] == 1 && $tast[8] == 1 && $tast[9] == 1 && $rewardID == 3){
           $index = 2;
       }
       if(intval($uactinfo['arg4']) == 0 && $tast[1] == 1 && $tast[4] == 1 && $tast[7] == 1 && $rewardID == 4){
           $index = 3;
       }
       if(intval($uactinfo['arg5']) == 0 && $tast[2] == 1 && $tast[5] == 1 && $tast[8] == 1 && $rewardID == 5){
           $index = 4;
       }
       if(intval($uactinfo['arg6']) == 0 && $tast[3] == 1 && $tast[6] == 1 && $tast[9] == 1 && $rewardID == 6){
           $index = 5;
       }
       if(intval($uactinfo['arg7']) == 0 && $tast[1] == 1 && $tast[5] == 1 && $tast[9] == 1 && $rewardID == 7){
           $index = 6;
       }
       if(intval($uactinfo['arg8']) == 0 && $tast[1] == 1 && $tast[2] == 1 && $tast[3] == 1 && $tast[4] == 1 && $tast[5] == 1 && $tast[6] == 1 && $tast[7] == 1 && $tast[8] == 1 && $tast[9] == 1 && $rewardID == 8){
           $index = 7;
       }
       if($index > -1){ 
           _addItem($uid, $reward[$index][0], $reward[$index][1]);
           $rewardInfo['itemid'] = $reward[0][0];
           $rewardInfo['count'] = $reward[0][1];
           $arg = 'arg'.($index + 1);
           sql_update("update unewAct set $arg = 1 where uid = $uid and actid = $actId");     
           return array(
               1,
               $rewardInfo
           );
       }else{
           return array(
               0,
               STR_NoReward
           );
       }
      
    }else{
        return array(
            0,
            STR_NoReward
        );
    }
                    
}

/**
 * 六一连线
 */
function setSixOneLine($uid,$typeId){
    $actId = activityTypeDef::SIX_ONE_LINE;
    if(checkActivityOpenById($actId)){
        $uinfo = sql_fetch_one("select * ,UNIX_TIMESTAMP(CURDATE()) as ts from unewAct where uid = $uid and actid = $actId");
        $arg10 = "1|0,2|0,3|0,4|0,5|0,6|0,7|0,8|0,9|0";
        if(!$uinfo){
            sql_update("insert into unewAct (uid,actid,arg9,arg10) values ($uid,$actId,UNIX_TIMESTAMP(CURDATE()),'$arg10')");
        }else{
            if($uinfo['ts'] != $uinfo['arg9']){
                $arg9 = $uinfo['ts'];
                $str = "";
                for($i = 1; $i <= 8; ++$i){
                    $str .= "arg" . $i . "=0,";
                }
                $str .= "arg9 = $arg9,arg10 = '$arg10'";
                sql_update("update unewAct set $str where uid = $uid and actid = $actId");
            }
        }
        $real = sql_fetch_one_cell("select arg10 from unewAct where uid = $uid and actid = $actId");
        $real = explode(',',$real);
        $taskArr = array(
        //活动id，达成次数
            1=>5, //武将进阶
            2=>1, //元宝充值
            3=>10, //挑战首领
            4=>8, //快速战斗
            5=>1,  //获得史诗级武将
            6=>10,  //挑战竞技场
            7=>1,   //购买铜钱
            8=>3,  //神兽祈福
            9=>20   //强化装备
        );
        if($typeId == 2){   //充值元宝
            $task = explode('|',$real[1]);
            $todaypay = getTodayRecharge($uid);
            if($todaypay > 0 && $task[1] != 1){
                $real[1] = "2|1";
            }
        }else{
            $task = explode('|',$real[$typeId-1]);
            if($task[1] < $taskArr[$typeId]){
                $num = $task[1] + 1;
                $real[$typeId-1] = "$typeId|$num";
            }
        }
        $real = implode(',',$real);
        sql_update("update unewAct set arg10 = '$real' where uid = $uid and actid = $actId");

   }
}
?>