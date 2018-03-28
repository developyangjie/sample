<?php

/**
 * 接口：获取道具列表
 * @param $uid
 * @param $params []
 * @return array
 */
function getItem($uid, $params)
{
    $res = sql_fetch_rows("select * from uitem where uid=$uid");
    return array(
        $res
    );
}

//检测道具数量
function _checkItem($uid, $itemid, $count)
{
    if ($count == 0) {
        return 1;
    }
    $ret = intval(sql_fetch_one_cell("select count(*) from uitem where uid = $uid and itemid=$itemid and count>=$count"));
    return $ret;
}

//扣除道具
function _subItem($uid, $itemid, $count,$act = '')
{
    $starttime = microtime(true);
    if ($count == 0) {
        return 1;
    }
    $ret = sql_update("update uitem set count=count-$count where uid=$uid and itemid=$itemid and count>=$count");
    if ($ret) {
        $logparams = array();
        $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
        $cuid=$uinfo['cuid'];
        $serverid=$uinfo['serverid'];
        _getSystemData($serverid, $cuid, $logparams);
        $logparams[] = $uid;
        $logparams[] = $uinfo['ulv'];
        $logparams[] = $itemid;
        $itemname = sql_fetch_one_cell("select name from cfg_item where itemid = $itemid");
        $logparams[] = $itemname;
        $logparams[] = $act;
        $logparams[] = $count;
        removeitemlog($logparams);
        $datestr = date('Y-m-d h:i:sa',time());
        $requestid = date('Ymdhisa',time());
        $requestid = $uid.$requestid;
        $endtime = microtime(true);
        $lasttime = (int) (($endtime - $starttime) * 1000);
        $itemnum = sql_fetch_one_cell("select count from uitem where uid = $uid");
        $platid = "100000";
        $content = 'ItemFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_subItem'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$platid.'|'.$itemid.'|'.$itemnum.'|'.$count.'|1'.$act;
        _createlogfile('hylr',$content);
    }
    return $ret;
}

//增加道具
function _addItem($uid, $itemid, $count,$act = '')
{
    $starttime = microtime(true);
    if (intval($itemid) <= 0 || intval($count) <= 0) {
        return 0;
    }
    $ret = sql_update("insert into uitem (uid,itemid,count) values ($uid,$itemid,$count) on DUPLICATE KEY update count=count+$count");
    if ($ret) {
       $logparams = array();
       $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
       $cuid=$uinfo['cuid'];
       $serverid=$uinfo['serverid'];
       _getSystemData($serverid, $cuid, $logparams);
       $logparams[] = $uid;
       $logparams[] = $uinfo['ulv'];
       $logparams[] = $itemid;
       $itemname = sql_fetch_one_cell("select name from cfg_item where itemid = $itemid");
       $logparams[] = $itemname;
       $logparams[] = $act;
       $logparams[] = $count;
       getitemlog($logparams);
       $datestr = date('Y-m-d h:i:sa',time());
       $requestid = date('Ymdhisa',time());
       $requestid = $uid.$requestid;
       $endtime = microtime(true);
       $lasttime = (int) (($endtime - $starttime) * 1000);
       $itemnum = sql_fetch_one_cell("select count from uitem where uid = $uid");
       $platid = "100000";
       $content = 'ItemFlow|'.$datestr.'|'.$uid.'|'.$lasttime.'|'.'_addItem'.'|'.$requestid.'|'.'d757c31738e747f189eaf3132d63c71e'.'|'.$platid.'|'.$itemid.'|'.$itemnum.'|'.$count.'|0'.$act;
       _createlogfile('hylr',$content);
    }
    return $ret;
}


/**
 * 接口：使用道具
 *
 * @param
 *            $uid
 * @param $params ['itemid']            
 * @return array
 */
function useItem($uid, $params)
{
    //是否有此道具
    $itemid = intval($params[0]);
    $itemnum = intval($params[1]);
    if(!isset($itemnum))
    {
    	$itemnum=1;
    }
    $itemcfg = sql_fetch_one("select * from cfg_item where itemid=$itemid");
    if(!$itemcfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    //道具类型
    $itemtype = intval($itemcfg['itemType']);
    $subtype = intval($itemcfg['subType']);
    $sell = intval($itemcfg['sell']);
    $item = array();
    $ret = array();
    //面包
    if($itemid == 101){
        if (!_subItem($uid, $itemid, $itemnum,'使用道具101')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $num = 10 * $itemnum;
        _addBreadNum($uid, $num);
        return array(
            1,
            $num
        );
    }
    elseif($itemid == 102){
        if (!_subItem($uid, $itemid, $itemnum,'使用道具102')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $num = 30 * $itemnum;
        _addBreadNum($uid, $num);
        return array(
            1,
            $num
        );
    }
    elseif($itemid == 103){
        if (!_subItem($uid, $itemid, $itemnum,'使用道具103')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $num = 60 * $itemnum;
        _addBreadNum($uid, $num);
        return array(
            1,
            $num
        );
    }
    elseif($itemid == 104){
        if (!_subItem($uid, $itemid, $itemnum,'使用道具104')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $num = 5 * $itemnum;
        _addKeyNum($uid, $num);
        return array(
            1,
            $num
        );
    }
    elseif($itemid == 105){
        if (!_subItem($uid, $itemid, $itemnum,'使用道具105')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $num = 15 * $itemnum;
        _addKeyNum($uid, $num);
        return array(
            1,
            $num
        );
    }
    elseif($itemid >= 501 && $itemid <= 515){
        $viplevel = $itemid % 500;
        $curvip = intval(sql_fetch_one_cell("select vip from uinfo where uid = $uid"));
        if($curvip != $viplevel){
            if (!_subItem($uid, $itemid, 1,'使用vip道具')){
                return array(
                    0,
                    STR_ResourceOff
                );
            }
            _upVIPLevel($uid, $viplevel);
        }
        $pay = intval(sql_fetch_one_cell("select vippay from uinfo where uid = $uid"));
        return array(
            1,
            $viplevel,
            $pay
        );
    }
    elseif($itemtype == 2){
        $mapdata = getSpecialMap($uid, $params);
        $mapinfo = $mapdata[1];
        $maptype = intval($itemcfg['jump']);
        foreach ($mapinfo as $map){
            if($maptype == intval($map['maptype'])){
                return array(
                    0,
                    STR_Item_Error
                );
            }
        }
        sql_update("insert into usmapticket (uid, maptype, time) values ($uid, $maptype, UNIX_TIMESTAMP() + 3600) on DUPLICATE KEY update time = UNIX_TIMESTAMP() + 3600");
        $item = array($itemid, intval($itemcfg['amount']));
        return array(
            1,
            $item
        );
    }
    elseif($itemtype == 4){
        if (!_subItem($uid, $itemid, $itemnum,'随机宝箱')) {
            return array(
                0,
                STR_ResourceOff
            );
        }
        for($num = 1; $num <= $itemnum; $num ++){
            if($subtype == 0){
                $cfgboxs=sql_fetch_rows("select * from cfg_fixedbox where boxid = $itemid");
                foreach ($cfgboxs as $c){
                    if(intval($c['type']) == 1){
                        if(intval($c['itemid']) == 2){
                            _addUg($uid, intval($c['amount']), '随机宝箱');
                            $item[] = array(intval($c['itemid']), intval($c['amount']));
                        }
                        else{
                            _addItem($uid, intval($c['itemid']), intval($c['amount']), '随机宝箱');
                            $item[] = array(intval($c['itemid']), intval($c['amount']));
                        }
                    }
                    elseif(intval($c['type']) == 2){
                        $ret[] = _createEquipByceid($uid, intval($c['itemid']), intval($c['amount']), 0);
                    }
                    elseif(intval($c['type']) == 3){
                        $addid = _createPartner($uid, intval($c['itemid']), intval($c['amount']));
                        $ret[] = getPartnerbyPids($uid, array($addid));
                    }
                }
            }
            else{
            	
                $randt=array();
                $ids=array();
                $subtype=intval(sql_fetch_one_cell("select subtype from cfg_randombox where boxid = $itemid  limit 1"));
                $randt=sql_fetch_one("select probability from cfg_randomtype where type=3 and subtype=$subtype");
                $probabilitys=$randt['probability'];
                $probabilitys_arr=explode(",", $probabilitys);
       
                for($i=0;$i<$sell;$i++){
                    $min=0;
                    $randnum=rand(1, 10000);
                    foreach ($probabilitys_arr as $probabilitys_one){
                        $probabilitys_one_arr=explode("|", $probabilitys_one);
                        $max=intval($probabilitys_one_arr[1]);
                        $randtypeid=intval($probabilitys_one_arr[0]);
                        if($randnum>$min&&$randnum<=$max){
                            $boxdata=sql_fetch_one("select * from cfg_randombox where boxid = $itemid and prob=$randtypeid and subtype=$subtype order by rand() limit 1");
                            array_push($ids, $boxdata);
                            break;
                        }
                        $min=$probabilitys_one_arr[1];
                    }
                }
                if ($ids){
                    foreach ($ids as $v){
                        if(intval($v['type']) == 1){
                            if(intval($v['itemid']) == 2){
                                _addUg($uid, intval($v['amount']), '随机宝箱');
                                $item[] = array(intval($v['itemid']), intval($v['amount']));
                            }
                            else{
                                _addItem($uid, intval($v['itemid']), intval($v['amount']), '随机宝箱');
                                $item[] = array(intval($v['itemid']), intval($v['amount']));
                            }
                        }
                        elseif(intval($v['type']) == 2){
                            $ret[] = _createEquipByceid($uid, intval($v['itemid']), intval($v['amount']), 0);
                        }
                        elseif(intval($v['type']) == 3){
                            $addid = _createPartner($uid, intval($v['itemid']), intval($v['amount']));
                            $ret[] = getPartnerbyPids($uid, array($addid));
                        }
                    }
                }
            }
        }
        return array(
            1,
            $ret,
            $item
        );
    }
    elseif($itemtype == 10){
        if (!_subItem($uid, $itemid, $itemnum * intval($itemcfg['amount']),'碎片合成')){
            return array(
                0,
                STR_ResourceOff
            );
        }
        $item = array($itemid, $itemnum * intval($itemcfg['amount']));
        for($num = 1; $num <= $itemnum; $num++){
            _addItem($uid, intval($itemcfg['synid']), 1,'碎片合成');
        }
        $ret = array(intval($itemcfg['synid']), $itemnum);
        return array(
            1,
            $ret,
            $item
        );
    }
    elseif($itemtype == 12){ //!全成佣 兵
        $item = array($itemid, intval($itemcfg['amount']));
        if (_subItem($uid, $itemid, intval($itemcfg['amount']),'合成佣兵')) {
            $addid = _createPartner($uid, intval($itemcfg['synid']), intval($itemcfg['quality']));
            $ret = getPartnerbyPids($uid, array($addid));
            $ret[] = $addid;
            return array(
                1,
                $ret,
                $item
            );
        }
        return array(
            0,
            STR_ResourceOff
        );
    }
    elseif($itemtype == 14){ //!全成装备
        if (!_subItem($uid, $itemid, $itemnum * intval($itemcfg['amount']),'合成装备')) {
            return array(
                0,
                STR_ResourceOff
            );
        }
        $item = array($itemid, $itemnum * intval($itemcfg['amount']));
        for($num = 1; $num <= $itemnum; $num++){
            $ret[] = _createEquipByceid($uid, intval($itemcfg['synid']), 1, 0);
        }
        return array(
            1,
            $ret,
            $item
        );
    }
}


?>