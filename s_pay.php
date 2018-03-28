<?php

/**
 * 获取平台交易号
 * @param unknown_type $uid
 * @param unknown_type $param
 */
function getTradeCode($uid, $param)
{
    $platform = $param[0];
    $type = intval($param[1]);    // type = 1 购买钻石 type = 2 月卡 （type = 0 是1.5.0版本的）
    $money = intval($param[2]);
    $ug = intval($param[3]);
    $orderCode = 0;

    if ($type == 2) {
        //return array(2,"月卡功能暫未開放");
    }
    switch ($platform) {
        case "maopao"://冒泡平台
            $orderCode = sql_insert("insert into pay_maopao(uid)values($uid)");
            break;
        case "xiaomi":
            $payFee = $money * 100;
            $orderCode = sql_insert("insert into pay_xiaomi(uid,payFee,payStart)values($uid, $payFee, NOW())");
            break;
        case "qh360":
            $payFee = $money * 100;
            $orderCode = sql_insert("insert into pay_qh360(uid,payFee)values($uid, $payFee)");
            break;
        default:
            $payFee = $money * 100;
            $orderCode = sql_insert("insert into pay_xiaomi(uid,payFee,payStart)values($uid, $payFee, NOW())");
            break;
    }
    return array(1, $orderCode);
}

function getTradeCodeNew($uid, $param)
{
    $platform = $param[0];
    $type = intval($param[1]);    // type = 1 购买钻石 type = 2 月卡 （type = 0 是1.5.0版本的）
    $money = intval($param[2]);
    $ug = intval($param[3]);
    $orderCode = 0;
    if ($type == 2) {
        //return array(2,"月卡功能暫未開放");
    }
    $payFee = $money * 100;
    $orderCode = sql_insert("insert into pay_xiaomi(uid,payFee,payStart,platform)values($uid, $payFee, NOW(),'$platform')");
    return array(1, $orderCode);
}

?>