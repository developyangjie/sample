<?php
include_once 'math.php';

//获取商店
function getShop($uid, $params)
{
    $shopcfg = sql_fetch_rows("select * from cfg_shop where shoptype = 1");
    $items = array();
    $prob = 0;
    foreach ($shopcfg as $value){
        $items[] = $value;
    }
    $ushop = sql_fetch_one("select * from ushop where uid = $uid");
    if(!$ushop){
        sql_update("insert into ushop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP())");
        $ushop = sql_fetch_one("select * from ushop where uid = $uid");
    }
    return array(
        1,
        $items,
        $ushop
    );
}

//购买商店
function buyShop($uid, $params)
{
    $id = $params[0];
    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 1 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $ushop = sql_fetch_one("select * from ushop where uid = $uid");
    if(!$ushop){
        sql_update("insert into ushop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP())");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $bid = $arr[0];
                $num = $arr[1];
                $buysinfo[] = array($bid,$num);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if(!_spendCrystal($uid, $cost,"buyShop")){
            return array(
                0,
                STR_Equip_RongLErr
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'buyShop')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"buyShop")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'buyShop');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] =  array($id,1);
        }
    }
    else{
        $buysinfo[] =  array($id,1);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update ushop set buys = '$buys',ts = UNIX_TIMESTAMP() where uid = $uid");
    $res = sql_fetch_one("select buys from ushop where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 1;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}

function refreshEquipShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1){
        return array(
            0,
            STR_LvOff
        );
    }
    $reset = intval(sql_fetch_one_cell("select reset from uequipshop where uid = $uid"));
    $refreshnum = 0;
    if($reset < 10){
        $refreshnum = $reset + 1;
    }
    elseif($reset >= 10){
        $refreshnum = 10;
    }
    $cfg = sql_fetch_one("select * from cfg_reflash where type = 2 and times = $refreshnum");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $money = intval($cfg['money']);
    $cost = intval($cfg['amout']);
    if($money == 2){
        if (! _spendCoin($uid, $cost, "刷新装备商店")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 1){
        if (! _spendGbytype($uid, $cost, "刷新装备商店")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    /*********wxl******************/
    $randt=array();
    $shopcfg=array();
    $randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=2");
    $probabilitys=$randt['probability'];
    $probabilitys_arr=explode(",", $probabilitys);
    $num=0;
    for($i=0;$i<1000;$i++){
        $min=0;
        $randnum=rand(1, 10000);
        foreach ($probabilitys_arr as $probabilitys_one){
            $probabilitys_one_arr=explode("|", $probabilitys_one);
            $max=intval($probabilitys_one_arr[1]);
            $randtypeid=intval($probabilitys_one_arr[0]);

            if($randnum>$min&&$randnum<=$max){
                $shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 2 and probability=$randtypeid order by rand() limit 1");
                if(intval($shopdate['needlv'])>$ulv){
                    break;
                }
                array_push($shopcfg, $shopdate);
                $num++;
                break;
            }
            $min=$probabilitys_one_arr[1];
        }
        if($num==6){
            break;
        }  
    }
    /*************************wxl*************/
    $goods = "";
    $ids = array();
    $prob = 0;
    $i=1;
    if(isset($shopcfg)){
        foreach ($shopcfg as $value){
            $ids[] =((string)$i."|".$value['id']);
            $i++;
        }
        if(count($ids) > 0){
            $goods = implode(",", $ids);
        }
    }
    $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
    sql_update("INSERT IGNORE INTO uequipshop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
    sql_update("update uequipshop set reset=reset+1 where uid = $uid");
    $ushop = sql_fetch_one("select * from uequipshop where uid = $uid");
    $items_arr = explode(",",$ushop['goods']);
    $goods_arr=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($goods_arr, $value_arr[1]);
    }
    $goodst=implode(",", $goods_arr);

    if($goodst){
        $items = sql_fetch_rows("select * from cfg_shop where shoptype = 2 and id in ($goodst)");
    }
    return array(
        1,
        $items,
        $time,
        $ushop
    );
}

function _refreshEquipShop($uid)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    /*********wxl******************/
    $randt=array();
    $shopcfg=array();
    $randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=2");
    $probabilitys=$randt['probability'];
    $probabilitys_arr=explode(",", $probabilitys);

    $num=0;
    for($i=0;$i<1000;$i++){
        $min=0;
        $randnum=rand(1, 10000);
        foreach ($probabilitys_arr as $probabilitys_one){

            $probabilitys_one_arr=explode("|", $probabilitys_one);
            $max=intval($probabilitys_one_arr[1]);
            $randtypeid=intval($probabilitys_one_arr[0]);

            if($randnum>$min&&$randnum<=$max){
                $shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 2 and probability=$randtypeid order by rand() limit 1");
                if(intval($shopdate['needlv'])>$ulv){
                    break;
                }
                array_push($shopcfg, $shopdate);
                $num++;
                break;
            }
            $min=$probabilitys_one_arr[1];
        }
        if($num==6){
            break;
        }
    }
    /*************************wxl******************************/

    $goods = "";
    $ids = array();
    $prob = 0;
    $i=1;
    foreach ($shopcfg as $value){
        $ids[] =((string)$i."|".$value['id']);
        $i++;
    }
    if(count($ids) > 0){
        $goods = implode(",", $ids);
    }
    $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
    sql_update("INSERT IGNORE INTO uequipshop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
}

//获取装备商店
function getEquipShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1){
        return array(
            0,
            STR_LvOff
        );
    }
    $res = sql_fetch_one("select *, ts-UNIX_TIMESTAMP() as tsleft,UNIX_TIMESTAMP() as nowtime from uequipshop where uid=$uid");
    if (!$res || intval($res['tsleft']) < 0) {
        _refreshEquipShop($uid);
    }
    $items = array();
    $goods = sql_fetch_one_cell("select goods from uequipshop where uid=$uid");
    $items_arr = explode(",",$goods);
    $goods_arr=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($goods_arr, $value_arr[1]);
    }
    $goodst=implode(",", $goods_arr);
    if($goodst){
        $items = sql_fetch_rows("select * from cfg_shop where shoptype = 2 and id in ($goodst)");
    }

    $time = intval(sql_fetch_one_cell("select ts-UNIX_TIMESTAMP() as tsleft from uequipshop where uid=$uid"));
    $ushop = sql_fetch_one("select * from uequipshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into uequipshop(uid,buys) values ($uid, '')");
        $ushop = sql_fetch_one("select * from uequipshop where uid = $uid");
    }
    return array(
        1,
        $items,
        $time,
        $ushop
    );
}

//购买装备商店
function buyEquipShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1)
    {
        return array(
            0,
            STR_LvOff
        );
    }
    $id = $params[0];
    $buytype=$params[1];
    $goods = sql_fetch_one_cell("select goods from uequipshop where uid=$uid");
    $items_arr = explode(",",$goods);
    $items=array();
    $types=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($items, $value_arr[1]);
        array_push($types, $value_arr[0]);
        if($value_arr[0]==$buytype){
            if($value_arr[1]!=$id){
                return array(
                    0,
                    STR_GoodsNotExist
                );
            }
        }
    }
     
    if(!in_array($id, $items)){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    if(!in_array($buytype, $types)){
        return array(
            0,
            STR_GoodsNotExist
        );
    }

    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 2 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $ushop = sql_fetch_one("select * from uequipshop where uid = $uid");
    if(!$ushop){
        $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
        sql_update("insert into uequipshop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP()+$time)");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr)>2){
                $bid = $arr[0];
                $num = $arr[1];
                $type=$arr[2];
                $buysinfo[] = array($bid,$num,$type);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id&&$buysinfo[$i][2]==$buytype){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买装备商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买装备商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if (! _spendCrystal ($uid, $cost,'购买装备商店')) {
            return array(
                0,
                STR_Crystal_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买装备商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买装备商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'buyEquipShop');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id && $buysinfo[$i][2]==$buytype){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] =  array($id,1,$buytype);
        }
    }
    else{
        $buysinfo[] =  array($id,1,$buytype);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update uequipshop set buys = '$buys' where uid = $uid");
    $res = sql_fetch_one("select buys from uequipshop where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 2;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}

//刷新勇者商店
function _refreshPartnerShop($uid)
{
	/*********wxl******************/
	$randt=array();
	$shopcfg=array();
	$randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=3");
	$probabilitys=$randt['probability'];
	$probabilitys_arr=explode(",", $probabilitys);
	
	$num=0;
	for($i=0;$i<1000;$i++){
		$min=0;
		$randnum=rand(1, 10000);
		foreach ($probabilitys_arr as $probabilitys_one){
	
			$probabilitys_one_arr=explode("|", $probabilitys_one);
			$max=intval($probabilitys_one_arr[1]);
			$randtypeid=intval($probabilitys_one_arr[0]);
	
			if($randnum>$min&&$randnum<=$max){
				$shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 3 and probability=$randtypeid order by rand() limit 1");
				array_push($shopcfg, $shopdate);
				$num++;
				break;
			}
			$min=$probabilitys_one_arr[1];
		}
		if($num==6){
			break;
		}
	}
	/*************************wxl******************************/
	
	$goods = "";
	$ids = array();
	$prob = 0;
	$i=1;
	foreach ($shopcfg as $value){
		$ids[] =((string)$i."|".$value['id']);
		$i++;
	}
	if(count($ids) > 0){
		$goods = implode(",", $ids);
	}
	$time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
	sql_update("INSERT IGNORE INTO upartnershop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
	
	
	
//     $shopcfg=sql_fetch_rows("select * from cfg_shop where shoptype = 3");
//     $goods = "";
//     $ids = array();
//     $i=1;
//     foreach ($shopcfg as $value){
//         $ids[] = $i."|".$value['id'];
//         $i++;
//     }
//     if(count($ids) > 0){
//         $goods = implode(",", $ids);
//     }
//     sql_update("INSERT IGNORE INTO upartnershop (uid,goods,ts) values ($uid,'$goods',UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE goods='$goods',ts=UNIX_TIMESTAMP()");
}

function refreshPartnerShop($uid, $params)
{
	$ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
	if($ulv<1){
		return array(
				0,
				STR_LvOff
		);
	}
	$reset = intval(sql_fetch_one_cell("select reset from upartnershop where uid = $uid"));
	$refreshnum = 0;
	if($reset < 10){
		$refreshnum = $reset + 1;
	}
	elseif($reset >= 10){
		$refreshnum = 10;
	}
	$cfg = sql_fetch_one("select * from cfg_reflash where type = 2 and times = $refreshnum");
	if(!$cfg){
		return array(
				0,
				STR_Param_Error
		);
	}
	$money = intval($cfg['money']);
	$cost = intval($cfg['amout']);
	if($money == 2){
		if (! _spendCoin($uid, $cost, "刷新勇者商店")) {
			return array(
					0,
					STR_CoinOff . $cost
			);
		}
	}
	elseif($money == 1){
		if (! _spendGbytype($uid, $cost, "刷新勇者商店")) {
			return array(
					0,
					STR_UgOff . $cost
			);
		}
	}
	/*********wxl******************/
	$randt=array();
	$shopcfg=array();
	$randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=3");
	$probabilitys=$randt['probability'];
	$probabilitys_arr=explode(",", $probabilitys);
	$num=0;
	for($i=0;$i<1000;$i++){
		$min=0;
		$randnum=rand(1, 10000);
		foreach ($probabilitys_arr as $probabilitys_one){
			$probabilitys_one_arr=explode("|", $probabilitys_one);
			$max=intval($probabilitys_one_arr[1]);
			$randtypeid=intval($probabilitys_one_arr[0]);

			if($randnum>$min&&$randnum<=$max){
				$shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 3 and probability=$randtypeid order by rand() limit 1");
				array_push($shopcfg, $shopdate);
				$num++;
				break;
			}
			$min=$probabilitys_one_arr[1];
		}
		if($num==6){
			break;
		}
	}
	/*************************wxl*************/
	$goods = "";
	$ids = array();
	$prob = 0;
	$i=1;
	if(isset($shopcfg)){
		foreach ($shopcfg as $value){
			$ids[] =((string)$i."|".$value['id']);
			$i++;
		}
		if(count($ids) > 0){
			$goods = implode(",", $ids);
		}
	}
	$time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
	sql_update("INSERT IGNORE INTO upartnershop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
	sql_update("update upartnershop set reset=reset+1 where uid = $uid");
	$ushop = sql_fetch_one("select * from upartnershop where uid = $uid");
	$items_arr = explode(",",$ushop['goods']);
	$goods_arr=array();
	foreach ($items_arr as $value){
		$value_arr=explode("|", $value);
		array_push($goods_arr, $value_arr[1]);
	}
	$goodst=implode(",", $goods_arr);

	if($goodst){
		$items = sql_fetch_rows("select * from cfg_shop where shoptype = 3 and id in ($goodst)");
	}
	return array(
			1,
			$items,
			$time,
			$ushop
	);
}



//获取勇者商店
function getPartnerShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1){
        return array(
            0,
            STR_LvOff
        );
    }
    $res = sql_fetch_one("select *, ts-UNIX_TIMESTAMP() as tsleft,UNIX_TIMESTAMP() as nowtime from upartnershop where uid=$uid");
    if (!$res || intval($res['tsleft']) < 0) {
    	    _refreshPartnerShop($uid);
    }
    $items = array();
    $goods = sql_fetch_one_cell("select goods from upartnershop where uid=$uid");
    $items_arr = explode(",",$goods);
    $goods_arr=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($goods_arr, $value_arr[1]);
    }
    $goodst=implode(",", $goods_arr);
    if($goodst){
        $items = sql_fetch_rows("select * from cfg_shop where shoptype = 3 and id in ($goodst)");
    }
    $time = intval(sql_fetch_one_cell("select ts-UNIX_TIMESTAMP() as tsleft from upartnershop where uid=$uid"));
    $ushop = sql_fetch_one("select * from upartnershop where uid = $uid");
    if(!$ushop){
        sql_update("insert into upartnershop(uid) values ($uid)");
        $ushop = sql_fetch_one("select * from upartnershop where uid = $uid");
    }
    return array(
        1,
        $items,
        $time,
        $ushop
    );
}

//购买勇者商店
function buyPartnerShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1)
    {
        return array(
            0,
            STR_LvOff
        );
    }
    $id = $params[0];
    $buytype=$params[1];
    $goods = sql_fetch_one_cell("select goods from upartnershop where uid=$uid");
    $items_arr = explode(",",$goods);
    $items=array();
    $types=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        
        array_push($types, $value_arr[0]);
        array_push($items, $value_arr[1]);
        if($value_arr[0]==$buytype){
            if($value_arr[1]!=$id){
                return array(
                    0,
                    STR_GoodsNotExist.'1'
                );
            }
        }  
    }
     
    if(!in_array($id, $items)){
        return array(
            0,
            STR_GoodsNotExist.'2'
        );
    }
    if(!in_array($buytype, $types)){
        return array(
            0,
            STR_GoodsNotExist.'3'
        );
    }
    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 3 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist.'4'
        );
    }
    $ushop = sql_fetch_one("select * from upartnershop where uid = $uid");
    if(!$ushop){
        $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
        sql_update("insert into upartnershop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP()+$time)");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr)>2){
                $bid = $arr[0];
                $num = $arr[1];
                $type=$arr[2];
                $buysinfo[] = array($bid,$num,$type);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id && $buysinfo[$i][2]==$buytype){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买勇者商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买勇者商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if(!_spendCrystal($uid, $cost,"购买勇者商店")){
            return array(
                0,
                STR_Equip_RongLErr
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买勇者商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买勇者商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    elseif(intval($shopcfg['money']) == 6){
        if (! _spendBraveCoin($uid, $cost,"购买勇者商店")) {
            return array(
                0,
                STR_BraveCoin_off
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'勇者商店购买');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id && $buysinfo[$i][2]==$buytype){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] =  array($id,1,$buytype);
        }
    }
    else{
        $buysinfo[] =  array($id,1,$buytype);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update upartnershop set buys = '$buys' where uid = $uid");
    $res = sql_fetch_one("select * from upartnershop where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 3;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}

function _getVipByVIPShop($uid)
{
    $count = 0;
    $viplevel = intval(sql_fetch_one_cell("select vip from uinfo where uid='$uid'"));
    $cfg = sql_fetch_one("select * from cfg_vip where vip = $viplevel");
    if(!$cfg){
        return $count;
    }
    $count = intval($cfg['vipshop']);
    return $count;
}

//获取VIP商店
function getVIPShop($uid, $params)
{
    $shopcfg = sql_fetch_rows("select * from cfg_shop where shoptype = 4");
    $items = array();
    $count = _getVipByVIPShop($uid);
    //     for ($i = 0; $i < $count; $i++){
    //             $items[] = $shopcfg[$i];
    //     }

    $ushop = sql_fetch_one("select * from uvipshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into uvipshop(uid,buys) values ($uid, '')");
        $ushop = sql_fetch_one("select * from uvipshop where uid = $uid");
    }
    return array(
        1,
        $shopcfg,
        $ushop
    );
}

//购买VIP商店
function buyVIPShop($uid, $params)
{
    $id = $params[0];
    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 4 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $number=$id-8400000;
    $count = _getVipByVIPShop($uid);
    if($count<$number)
    {
        return array(
            0,
            STR_Club_VIP_Not_Enough
        );
    }

    $ushop = sql_fetch_one("select * from uvipshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into uvipshop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP())");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $bid = $arr[0];
                $num = $arr[1];
                $buysinfo[] = array($bid,$num);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买vip商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买vip商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if(!_spendCrystal($uid, $cost,"购买vip商店")){
            return array(
                0,
                STR_Equip_RongLErr
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买vip商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买vip商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'vip商店购买');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] = array($id,1);
        }
    }
    else{
        $buysinfo[] =  array($id,1);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update uvipshop set buys = '$buys',ts = UNIX_TIMESTAMP() where uid = $uid");
    $res = sql_fetch_one("select * from uvipshop where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 4;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}


/**
 * 接口：获取荣誉商店
 *
 * @param
 *            $uid
 * @param $params []
 * @return array
 */
function getHonorShop($uid, $params)
{
    $shopcfg = sql_fetch_rows("select * from cfg_shop where shoptype = 5");
    $items = array();
    foreach ($shopcfg as $value){
        $items[] = $value;
    }
    $ushop = sql_fetch_one("select * from uhonorshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into uhonorshop(uid,buys) values ($uid, '')");
        $ushop = sql_fetch_one("select * from uhonorshop where uid = $uid");
    }
    return array(
        1,
        $items,
        $ushop
    );
}

/**
 * 接口：荣誉商店购买
 *
 * @param
 *            $uid
 * @param $params ['eid']
 * @return array
 */
function buyHonorShop($uid, $params)
{
    $ulv = intval(sql_fetch_one_cell("select ulv from uinfo where uid=$uid"));
    if ($ulv < 20) {
        return array(
            0,
            STR_Lv_Low2
        );
    }
    $id = $params[0];
    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 5 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $ushop = sql_fetch_one("select * from uhonorshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into uhonorshop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP())");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $bid = $arr[0];
                $num = $arr[1];
                $buysinfo[] = array($bid,$num);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买荣誉商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买荣誉商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if(!_spendCrystal($uid, $cost,"购买荣誉商店")){
            return array(
                0,
                STR_Equip_RongLErr
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买荣誉商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买荣誉商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'vip商店购买');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] = array($id,1);
        }
    }
    else{
        $buysinfo[] =  array($id,1);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update uhonorshop set buys = '$buys',ts = UNIX_TIMESTAMP() where uid = $uid");
    $res = sql_fetch_one("select * from uhonorshop where uid = $uid");
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 5;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    $costitem = array(intval($shopcfg['money']), $cost);

    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}


/**
 * 接口：获取公会商店
 *
 * @param
 *            $uid
 * @param $params ['itemid','cid']
 * @return array
 */
function getClubShop($uid, $params)
{
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $shopcfg = sql_fetch_rows("select * from cfg_shop where shoptype = 6");
    $items = array();
    foreach ($shopcfg as $value){
        $items[] = $value;
    }
    $ushop = sql_fetch_one("select buys from uclub where uid = $uid");
    return array(
        1,
        $items,
        $ushop
    );
}

/**
 * 接口：公会商店购买
 *
 * @param
 *            $uid
 * @param $params ['itemid','cid']
 * @return array
 */
function buyClubShop($uid, $params)
{
    $id = $params[0];
    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 6 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $uclub = sql_fetch_one("select * from uclub where uid=$uid and state > 0");
    if (!$uclub) {
        return array(
            0,
            STR_Club_JoinIn
        );
    }
    $buys = $uclub['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr) == 2){
                $bid = $arr[0];
                $num = $arr[1];
                $buysinfo[] = array($bid,$num);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买公会商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买公会商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if(!_spendCrystal($uid, $cost,"购买公会商店")){
            return array(
                0,
                STR_Equip_RongLErr
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买公会商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买公会商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    $retp = array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'公会商店购买');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] =  array($id,1);
        }
    }
    else{
        $buysinfo[] =  array($id,1);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buygoods = implode(",", $data);
    sql_update("update uclub set buys = '$buygoods' where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 6;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $buygoods,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}


//刷新碎片商店
function refreshFragmentShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1){
        return array(
            0,
            STR_LvOff
        );
    }
    $reset = intval(sql_fetch_one_cell("select reset from ufragmentshop where uid = $uid"));
    $refreshnum = 0;
    if($reset < 10){
        $refreshnum = $reset + 1;
    }
    elseif($reset >= 10){
        $refreshnum = 10;
    }
    $cfg = sql_fetch_one("select * from cfg_reflash where type = 2 and times = $refreshnum");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $money = intval($cfg['money']);
    $cost = intval($cfg['amout']);
    if($money == 2){
        if (! _spendCoin($uid, $cost, "刷新碎片商店")) {
            return array(
                0,
                STR_CoinOff . $cost
            );
        }
    }
    elseif($money == 1){
        if (! _spendGbytype($uid, $cost, "刷新碎片商店")) {
            return array(
                0,
                STR_UgOff . $cost
            );
        }
    }
    /*********wxl******************/
    $randt=array();
    $shopcfg=array();
    $randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=7");
    $probabilitys=$randt['probability'];
    $probabilitys_arr=explode(",", $probabilitys);
    $num=0;
    for($i=0;$i<1000;$i++){
        $min=0;
        $randnum=rand(1, 10000);
        foreach ($probabilitys_arr as $probabilitys_one){
            $probabilitys_one_arr=explode("|", $probabilitys_one);
            $max=intval($probabilitys_one_arr[1]);
            $randtypeid=intval($probabilitys_one_arr[0]);

            if($randnum>$min&&$randnum<=$max){
                $shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 7 and probability=$randtypeid order by rand() limit 1");
                if(intval($shopdate['needlv'])>$ulv){
                    break;
                }
                array_push($shopcfg, $shopdate);
                $num++;
                break;
            }
            $min=$probabilitys_one_arr[1];
        }
        if($num==6){
            break;
        }
    }
    /*************************wxl*************/
    $goods = "";
    $ids = array();
    $prob = 0;
    $i=1;
    if(isset($shopcfg)){
        foreach ($shopcfg as $value){
            $ids[] =((string)$i."|".$value['id']);
            $i++;
        }
        if(count($ids) > 0){
            $goods = implode(",", $ids);
        }
    }
    $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
    sql_update("INSERT IGNORE INTO ufragmentshop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
    sql_update("update ufragmentshop set reset=reset+1 where uid = $uid");
    $ushop = sql_fetch_one("select * from ufragmentshop where uid = $uid");
    $items_arr = explode(",",$ushop['goods']);
    $goods_arr=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($goods_arr, $value_arr[1]);
    }
    $goodst=implode(",", $goods_arr);

    if($goodst){
        $items = sql_fetch_rows("select * from cfg_shop where shoptype = 7 and id in ($goodst)");
    }
    return array(
        1,
        $items,
        $time,
        $ushop
    );
}

function _refreshFragmentShop($uid)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    /*********wxl******************/
    $randt=array();
    $shopcfg=array();
    $randt=sql_fetch_one("select probability from cfg_randomtype where type=1 and subtype=7");
    $probabilitys=$randt['probability'];
    $probabilitys_arr=explode(",", $probabilitys);

    $num=0;
    for($i=0;$i<1000;$i++){
        $min=0;
        $randnum=rand(1, 10000);
        foreach ($probabilitys_arr as $probabilitys_one){

            $probabilitys_one_arr=explode("|", $probabilitys_one);
            $max=intval($probabilitys_one_arr[1]);
            $randtypeid=intval($probabilitys_one_arr[0]);

            if($randnum>$min&&$randnum<=$max){
                $shopdate=sql_fetch_one("select * from cfg_shop where shoptype = 7 and probability=$randtypeid order by rand() limit 1");
                if(intval($shopdate['needlv'])>$ulv){
                    break;
                }
                array_push($shopcfg, $shopdate);
                $num++;
                break;
            }
            $min=$probabilitys_one_arr[1];
        }
        if($num==6){
            break;
        }
    }
    /*************************wxl******************************/

    $goods = "";
    $ids = array();
    $prob = 0;
    $i=1;
    foreach ($shopcfg as $value){
        $ids[] =((string)$i."|".$value['id']);
        $i++;
    }
    if(count($ids) > 0){
        $goods = implode(",", $ids);
    }
    $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
    sql_update("INSERT IGNORE INTO ufragmentshop (uid,goods,buys,ts) values ($uid,'$goods','',UNIX_TIMESTAMP()+$time) ON DUPLICATE KEY UPDATE goods='$goods',buys='',ts=UNIX_TIMESTAMP()+$time");
}

//获取碎片商店
function getFragmentShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1){
        return array(
            0,
            STR_LvOff
        );
    }
    $res = sql_fetch_one("select *, ts-UNIX_TIMESTAMP() as tsleft,UNIX_TIMESTAMP() as nowtime from ufragmentshop where uid=$uid");
    if (!$res || intval($res['tsleft']) < 0) {
        _refreshFragmentShop($uid);
    }
    $items = array();
    $goods = sql_fetch_one_cell("select goods from ufragmentshop where uid=$uid");
    $items_arr = explode(",",$goods);
    $goods_arr=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($goods_arr, $value_arr[1]);
    }
    $goodst=implode(",", $goods_arr);
    if($goodst){
        $items = sql_fetch_rows("select * from cfg_shop where shoptype = 7 and id in ($goodst)");
    }
    $time = intval(sql_fetch_one_cell("select ts-UNIX_TIMESTAMP() as tsleft from ufragmentshop where uid=$uid"));
    $ushop = sql_fetch_one("select * from ufragmentshop where uid = $uid");
    if(!$ushop){
        sql_update("insert into ufragmentshop(uid,buys) values ($uid, '')");
        $ushop = sql_fetch_one("select * from ufragmentshop where uid = $uid");
    }
    return array(
        1,
        $items,
        $time,
        $ushop
    );
}

//购买碎片商店
function buyFragmentShop($uid, $params)
{
    $ulv=sql_fetch_one_cell("select ulv from uinfo where uid=$uid");
    if($ulv<1)
    {
        return array(
            0,
            STR_LvOff
        );
    }
    $id = $params[0];
    $buytype=$params[1];
    $goods = sql_fetch_one_cell("select goods from ufragmentshop where uid=$uid");
    $items_arr = explode(",",$goods);
    $items=array();
    $types=array();
    foreach ($items_arr as $value){
        $value_arr=explode("|", $value);
        array_push($items, $value_arr[1]);
        array_push($types, $value_arr[0]);
        if($value_arr[0]==$buytype){
            if($value_arr[1]!=$id){
                return array(
                    0,
                    STR_GoodsNotExist
                );
            }
        }
    }
    if(!in_array($id, $items)){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    if(!in_array($buytype, $types)){
        return array(
            0,
            STR_GoodsNotExist
        );
    }

    $shopcfg = sql_fetch_one("select * from cfg_shop where shoptype = 7 and id = $id");
    if(!$shopcfg){
        return array(
            0,
            STR_GoodsNotExist
        );
    }
    $ushop = sql_fetch_one("select * from ufragmentshop where uid = $uid");
    if(!$ushop){
        $time = 10800 - ((intval(sql_fetch_one_cell("SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CURDATE())"))) % 10800);
        sql_update("insert into ufragmentshop(uid,buys,ts) values ($uid, '', UNIX_TIMESTAMP()+$time)");
    }
    $buys = $ushop['buys'];
    $buysarr = array();
    $buysinfo = array();
    if($buys){
        $buysarr = explode(',',$buys);
        foreach ($buysarr as $value){
            $arr = explode('|',$value);
            if(count($arr)>2){
                $bid = $arr[0];
                $num = $arr[1];
                $type=$arr[2];
                $buysinfo[] = array($bid,$num,$type);
            }
        }
    }
    $buysnum = 0;
    if(count($buysinfo) >0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id&&$buysinfo[$i][2]==$buytype){
                $buysnum = $buysinfo[$i][1];
            }
        }
    }
    if(intval($shopcfg['buycount']) > 0 && $buysnum >= intval($shopcfg['buycount'])){
        return array(
            0,
            STR_GoodsBuyNotCount
        );
    }
    $cost = intval($shopcfg['price']);
    // 1代表钻石  2代表金币  3代表水晶  4代表荣誉  5代表贡献
    if(intval($shopcfg['money']) == 1){
        if (!_spendGbytype($uid, $cost,"购买碎片商店")){
            return array(
                0,
                STR_UgOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 2){
        if(!_spendCoin($uid, $cost,"购买碎片商店")){
            return array(
                0,
                STR_CoinOff
            );
        }
    }
    elseif(intval($shopcfg['money']) == 3){
        if (! _spendCrystal ($uid, $cost,'购买碎片商店')) {
            return array(
                0,
                STR_Crystal_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 4){
        if (! _spendHonor($uid, $cost,'购买碎片商店')) {
            return array(
                0,
                STR_Honor_off
            );
        }
    }
    elseif(intval($shopcfg['money']) == 5){
        if (! _spendClubScore($uid, $cost,"购买碎片商店")) {
            return array(
                0,
                STR_GongXian
            );
        }
    }
    $itemtype = intval($shopcfg['itemtype']);
    //1。item 2.装备 3.勇者
    $addp = array();
    $adde = array();
    $rete=array();
    $getitem=array();
    if($itemtype == 1){
        _addItem($uid, intval($shopcfg['itemid']), intval($shopcfg['count']),'buyEquipShop');
        $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    elseif($itemtype == 2){
        $rete[] = _createEquipByceid($uid,intval($shopcfg['itemid']),intval($shopcfg['count']),0);
    }
    elseif($itemtype == 3){
        $addp[] = _createPartner($uid, intval($shopcfg['itemid']), intval($shopcfg['count']));
    }
    if(count($addp)>0){
        $retp[] = getPartnerbyPids($uid, $addp);
    }
    $isexist = false;
    if(count($buysinfo) > 0){
        for($i = 0; $i < count($buysinfo); $i ++){
            if($buysinfo[$i][0] == $id && $buysinfo[$i][2]==$buytype){
                $buysinfo[$i][1] = $buysinfo[$i][1] + 1;
                $isexist = true;
            }
        }
        if(!$isexist){
            $buysinfo[] =  array($id,1,$buytype);
        }
    }
    else{
        $buysinfo[] =  array($id,1,$buytype);
    }
    $data = array();
    foreach($buysinfo as $v){
        $data[] = implode("|", $v);
    }
    $buys = implode(",", $data);
    sql_update("update ufragmentshop set buys = '$buys' where uid = $uid");
    $res = sql_fetch_one("select buys from ufragmentshop where uid = $uid");
    $costitem = array(intval($shopcfg['money']), $cost);
    $getitem = array(intval($shopcfg['itemid']), intval($shopcfg['count']));
    $logparams = array();
    $uinfo = sql_fetch_one("select * from uinfo where uid = $uid");
    $cuid=$uinfo['cuid'];
    $serverid=$uinfo['serverid'];
    _getSystemData($serverid, $cuid, $logparams);
    $logparams[] = $uid;
    $logparams[] = $uinfo['ulv'];
    $logparams[] = $uinfo['vip'];
    $logparams[] = 2;
    $logparams[] = intval($shopcfg['itemid']);
    $logparams[] = intval($shopcfg['count']);
    $logparams[] = intval($shopcfg['money']);
    $logparams[] = $cost;
    shoplog($logparams);
    return array(
        1,
        $res,
        $costitem,
        $getitem,
        $rete,
        $retp
    );
}


?>