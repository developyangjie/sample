<?php

//获取地图信息
function getTMapInfo($uid, $params)
{
    $cfg = sql_fetch_one("select * from cfg_funcunlock where id = 8");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if ($ulv < intval($cfg['lv'])) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $utmap = sql_fetch_rows("select * from utmap where uid = $uid");
    if (!$utmap){
       return  _refreashTMap($uid);        
    }
    foreach ($utmap as $key=>$value)
    {
		$mapids=$value['mapid'];
		$maptype=intval(sql_fetch_one_cell("select type from cfg_treasure where id=$mapids"));
		$utmap[$key]['mapid']=$maptype;
    	
    }
    
    $utreasure = sql_fetch_one("select * from utreasure where uid = $uid and statue = 0");
    if($utreasure){
        $mapid = $utreasure['mapid'];
        $starttime = $utreasure['sts'];
        $time = intval(sql_fetch_one_cell("select time from utmap where uid = $uid and mapid = $mapid"))* 60*60;
        $difftime = time() - $starttime;
        $counttime = 0;
        if($difftime < $time){
            $counttime = $time - $difftime;
        }
    }
    return array(
        1,
        $utmap,
        $utreasure,
        $counttime
    );
}

function refreashTMap($uid, $params)
{
    $treasure = intval(sql_fetch_one_cell("select treasure from uinfo where uid = $uid"));
    $refreshnum = 0;
    if($treasure < 10){
        $refreshnum = $treasure + 1;
    }
    elseif($treasure >= 10){
        $refreshnum = 10;
    }
    $cfg = sql_fetch_one("select * from cfg_reflash where type = 4 and times = $refreshnum");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $money = intval($cfg['money']);
    $cost = intval($cfg['amout']);
    if($money == 1){
        if (! _spendCoin($uid, $cost, "神魔刷新地图")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 2){
        if (! _spendGbytype($uid, $cost, "神魔刷新地图")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    sql_update("update uinfo set treasure = treasure + 1 where uid = $uid");
    return _refreashTMap($uid);
}

// 刷新寻宝地图
function _refreashTMap($uid)
{
    sql_update("delete from utmap where uid = $uid");
    $mapcfg = sql_fetch_rows("select * from cfg_treasure order by id desc");
    $rewards = array();
    $addpro = 0;
    foreach($mapcfg as $value){
        $r = rand(1,10000);
        $addpro += intval($value['probability']);
        if($r <= $addpro){
            $rewardid = intval($value['reward']);
            $cfg = sql_fetch_one("select * from cfg_reward where id = $rewardid");
            if(!$cfg){
                continue;
            }
            $items = array();
            if(intval($cfg['item1']) > 0){
                $items[] = array(id => $cfg['item1'], count => $cfg['count1'], prob => $cfg['prob1']);
            }
            if(intval($cfg['item2']) > 0){
                $items[] = array(id => $cfg['item2'], count => $cfg['count2'], prob => $cfg['prob2']);
            }
            if(intval($cfg['item3']) > 0){
                $items[] = array(id => $cfg['item3'], count => $cfg['count3'], prob => $cfg['prob3']);
            }
      /*      if(intval($cfg['item4']) > 0){
                $items[] = array(id => $cfg['item4'], count => $cfg['count4'], prob => $cfg['prob4']);
            }
            if(intval($cfg['item5']) > 0){
                $items[] = array(id => $cfg['item5'], count => $cfg['count5'], prob => $cfg['prob5']);
            }
            if(intval($cfg['item6']) > 0){
                $items[] = array(id => $cfg['item6'], count => $cfg['count6'], prob => $cfg['prob6']);
            }*/  //app只要三个奖励
            $totalprob = 0;
            foreach ($items as $item){
                $totalprob += intval($item['prob']);
            }
            $rewarditem = array();
            if($totalprob > 0){
                $rand = rand(1,$totalprob);
                $addprob = 0;
                for ($j = 0; $j < count($items); $j ++){
                    $addprob += intval($items[$j]['prob']);
                    if($rand <= $addprob){
                        $rewarditem[] = array(id => $items[$j]['id'], count => $items[$j]['count']);
                    }
                }   
            }
            $rewards[] = array($value['id'],$value['time'],$rewarditem);
            if(count($rewards) >= 3){
                break 1;
            } 
        }
    }
    foreach ($rewards as $v){
        $mapType = $v[0];
        $time = $v[1];
        $gifts = $v[2];
        $gArr = array();
        foreach ($gifts as $gift){
            $gArr[] = implode("|", $gift);
        }
        $gStr = implode(",", $gArr);
        sql_update("insert into utmap(uid,mapid,time,giftids) values($uid,$mapType,$time,'$gStr') on duplicate key update mapid = $mapType,time = $time,giftids = '$gStr'");
    }
    $utmap = sql_fetch_rows("select * from utmap where uid = $uid");
    //根据mapid 找出maptype并且替换
    foreach ($utmap as $key=>$value)
    {
    	$mapids=$value['mapid'];
    	$maptype=intval(sql_fetch_one_cell("select type from cfg_treasure where id=$mapids"));
    	$utmap[$key]['mapid']=$maptype;
    	 
    }
    return array(
        1,
        $utmap
    );
}

//寻宝
function treasure($uid, $params)
{
    $id = intval($params[0]);
    $utmap = sql_fetch_one("select * from utmap where uid = $uid and id = $id");
    if(!$utmap){
        return array(
            0,
            STR_NO_Map.$id
        );
    }
    $mapid = $utmap['mapid'];
    $giftidstr = $utmap['giftids'];
    $ut = sql_fetch_one("select *,curdate() as dt from utreasure where uid = $uid");
    if(!$ut){
        sql_update("insert into utreasure(uid,mapid,sts,statue,giftids,times,date) values($uid,$mapid,unix_timestamp(),0,'$giftidstr',0,curdate())");
        $ut = sql_fetch_one("select * from utreasure where uid = $uid");
        $counttime = intval(sql_fetch_one_cell("select time from utmap where uid = $uid and mapid = $mapid"))* 60*60;
        return array(
            1,
            $ut,
            $counttime
        );
    }
    $vip = sql_fetch_one_cell("select vip from uinfo where uid = $uid");
    $times = 1;
    if($vip >= 1 && $vip <= 6){
        $times = 2;
    } 
    if($vip >=7){
        $times = 3;
    }
    
  /*  if ($ut['dt'] == $ut[date] && intval($ut['times']) >=$times){
        return array(
            0,
            STR_Lack_Times
        );
    }*/
    if(intval($ut['statue']) == 1){
        if($ut['dt'] > $ut['date']){
            sql_update("update utreasure set sts=unix_timestamp(),statue=0,giftids='$giftidstr',mapid=$mapid,times=0,date=curdate() where uid = $uid");
        }
        else{
            sql_update("update utreasure set sts=unix_timestamp(),statue=0,giftids='$giftidstr',mapid=$mapid,date = curdate() where uid = $uid");
        }
    }
    $ut = sql_fetch_one("select * from utreasure where uid = $uid");
    $utreasure = sql_fetch_one("select * from utreasure where uid = $uid and statue = 0");
    $counttime = 0;
    if($utreasure){
        $mapid = $utreasure['mapid'];
        $starttime = $utreasure['sts'];
        $time = intval(sql_fetch_one_cell("select time from utmap where uid = $uid and mapid = $mapid"))* 60*60;
        $difftime = time() - $starttime;
        $counttime = 0;
        if($difftime < $time){
            $counttime = $time - $difftime;
        }
    }
    _updateUTaskProcess($uid, 1010);
    _startclubtask($uid, 11);
    return array(
        1,
        $ut,
        $counttime
    );   
}


// 获取地图寻宝礼包
function getTreasureGift($uid, $params)
{
    $ut = sql_fetch_one("select *,curdate() as dt,unix_timestamp() as nts from utreasure where uid = $uid");
    if (!$ut){
        return array(
            0,
            STR_No_GiftInfo
        );
    }
    
    if (intval($ut['statue']) == 1){
        return array(
            0,
            STR_Gift_HaveReward
        );
    }
    $t = 2;
    if (intval($ut['nts']) - intval($ut['sts']) < $t){
        return array(
            0,
            STR_Time_NO_Reach
        );
    }
    $giftidstr = $ut['giftids'];
    $giftarr = explode(",", $giftidstr);
    $gifts = array();
    foreach ($giftarr as $gift){
        $gifts[] = explode("|", $gift);
    }
    for ($i = 0; $i < count($gifts); $i++) {
        $itemid = intval($gifts[$i][0]);
        $count = intval($gifts[$i][1]);
        if($itemid == 1){
            _addCoin($uid, $count, '神魔封印奖励');
        }else if ($itemid == 2){
            _addUg($uid, $count, '神魔封印奖励');
        }else if ($itemid == 6){
            return array(
                0,
                STR_No_Equip
            );
        }else if($itemid > 100000){
            _createEquipByceid($uid, $itemid, $count, 0);
        }else{
            _addItem($uid, $itemid, $count, '神魔封印奖励');
        }       
    }
  
    sql_update("update utreasure set statue = 1, times = times + 1 where uid = $uid");
    sql_update("insert into utreasure_log(uid,mapid,sts,giftids,statue,times,date) select uid,mapid,sts,giftids,statue,times,date from utreasure where uid = $uid");
    $clubtask=_endclubtask($uid,11);
    return array(
        1,
        $gifts,
    	$clubtask
    );  
}
/**
 * 放弃寻宝
 */
function quitTreasure($uid,$params)
{
    if (!sql_fetch_one("select * from utreasure where uid = $uid")){
        return array(
            0,
           STR_No_Utinfo
        );
    }
    sql_update("update utreasure set statue = 1 , times = times + 1 where uid = $uid");
    return array(
        1
    );
}

/*
 * 获取两个其它玩家过去的当天奖励信息
 */
function getOtherUserTinfo($uid, $params)
{
    $infos = sql_fetch_rows("select t.*,u.uname from utreasure_log t left join uinfo u on u.uid = t.uid where statue = 1 order by t.sts desc limit 2");
    return array(
        1,
        $infos
    );
}