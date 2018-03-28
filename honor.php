<?php


function _getHonorPrice($itemid, $count, $rate)
{
    // rate 是折扣,9是1折,8是2折 以此类推;
    $itemid = intval($itemid);
    $price = 400;
    switch ($itemid) {
        case 4: // 神器碎片
            $price = 200;
            break;
        case 11: // BOSS挑战券
            $price = 300;
            break;
        case 12: // 宝石袋
            $price = 30;
            break;
        case 13: // 高级宝石袋
            $price = 300;
            break;
        case 41: // 小铜锤
            $price = 50;
            break;
        case 42: // 小银锤
            $price = 150;
            break;
        case 43: // 小金锤
            $price = 400;
            break;
        default: // 错误
            break;
    }
    return $price * $count * ((10 - $rate) / 10);
}

function _refreshHonorShop($uid, $tsleft)
{
    // 现在物品颜色不区分
    $goods = "0";
    $buys = "0";
    $count = 0;
    $rate = 0;
    $range = array(
        3000,
        4000,
        4500,
        5500,
        6500,
        8000,
        10000
    );
    for ($i = 0; $i < 6; $i ++) {
        $rand = rand(0, 10000);
        $goods = $goods . ",";
        if ($rand < $range[0]) { // 宝石袋
            $eid = 121 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } elseif ($rand < $range[1]) { // 高级宝石袋
            $eid = 131 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } elseif ($rand < $range[2]) { // boss挑战券
            $eid = 111 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } elseif ($rand < $range[3]) { // 神器碎片
            $eid = 41 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } elseif ($rand < $range[4]) { // 小金锤
            $eid = 431 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } elseif ($rand < $range[5]) { // 小银锤
            $eid = 421 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        } else { // 小铜锤
            $eid = 411 * 10000 + $i * 1000 + $rate * 100 + 3 * 10 + 1;
            $goods = $goods . $eid;
            $count ++;
        }
    }
    if ($tsleft < 0) {
        // 到时间需要刷了;
        sql_update("INSERT IGNORE INTO uhonorshop (uid,goods,buys,ts,reset,count) values ($uid,'$goods','$buys',UNIX_TIMESTAMP(curdate())+86400,0,$count) ON DUPLICATE KEY UPDATE goods='$goods',buys='$buys',count=$count,ts=UNIX_TIMESTAMP(CURDATE())+86400,reset=0");
    } else {
        sql_update("INSERT IGNORE INTO uhonorshop (uid,goods,buys,ts,reset,count) values ($uid,'$goods','$buys',UNIX_TIMESTAMP(curdate())+86400,0,$count) ON DUPLICATE KEY UPDATE goods='$goods',buys='$buys',count=$count");
    }
}

function _refreshHonorShopAndTs($uid)
{}



/**
 * 刷新荣誉商店
 * 
 * @param
 *            $uid
 * @param $params []            
 * @return array
 */
function resetHonorShop($uid, $params)
{
    $res = sql_fetch_one("select *, ts-UNIX_TIMESTAMP() as tsleft from uhonorshop where uid=$uid");
    if (! $res) {
        return array(
            0,
            STR_Honor_Err1
        );
    }
    $times = intval($res['reset']);
    $cost = 150;
    if ($times < 1) {
        $cost = 10;
    } elseif ($times < 3) {
        $cost = 30;
    } elseif ($times < 6) {
        $cost = 80;
    } else {
        $cost = 150;
    }
    if (! _spendHonor($uid, $cost,'resetHonorShop')) {
        return array(
            0,
            STR_Honor_off
        );
    }
    $tsleft = intval($res['tsleft']);
    $ret = sql_update("update uhonorshop set `count`=0,reset=reset+1 where uid=$uid and reset=$times");
    _refreshHonorShop($uid, $tsleft);
    $res = sql_fetch_one("select * from uhonorshop where uid=$uid");
    return array(
        1,
        $res
    );
}

?>
