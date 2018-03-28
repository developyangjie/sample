<?php 
function getSoulCfg($params) {
    $res = sql_fetch_rows("select * from cfg_soul");
    return array(
        1,
        $res
    );
}

function getUSoul($uid,$params) {
    $usoulInfo = sql_fetch_one("select * from usoul where uid=$uid");
    if (!$usoulInfo) {
        return array(
            0,
            STR_DataErr2."-20"
        );
    }
    return array(
        1,
        $usoulInfo
    );
}

function upSoul($uid,$params) {
    $type = $params[0];
    if ($type < 0 || $type > 4) {
        return array(
            0,
            STR_DataErr
        );
    }
    $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
    if (intval($uInfo['ulv']) < 10) {
        return array(
            0,
            STR_Soul_Lv_Low
        );
    }
    $uSoul = sql_fetch_one("select * from usoul where uid=$uid");
    $soulKey = 'soul'.$type.'lv';
    $soulCntKey = 'soul'.$type.'pcnt';
    $slv = $uSoul[$soulKey] + 1;
    $soulCfg = sql_fetch_one("select * from cfg_soul where type=$type and lv=$slv");
    if ($soulCfg) {
        if ($soulCfg['needlv'] > $uInfo['ulv']) {
            return array(
                0,
                sprintf(STR_Lv_Low,$soulCfg['needlv'])
            );
        }
        $coin = $soulCfg['coin'];
        if ($coin > $uInfo['ucoin']) {
            return array(
                0,
                STR_CoinOff.$coin
            );
        }
        $soulCnt = $soulCfg['soulcnt'];
        $name = _getSoulNameByType($type);
        if ($soulCnt > $uSoul['soul'.$type.'pcnt']) {
            return array(
                0,
                $name.STR_Soul_Cnt_NotEnough
            );
        }
        $uSoul[$soulKey] += 1;
        $soul1Lv = $uSoul['soul1lv'];
        $soul2Lv = $uSoul['soul2lv'];
        $soul3Lv = $uSoul['soul3lv'];
        $soul4Lv = $uSoul['soul4lv'];
        $soulEps = sql_fetch_rows("select ep from cfg_soul where (type=1 and lv=$soul1Lv) or (type=2 and lv=$soul2Lv) or (type=3 and lv=$soul3Lv) or (type=4 and lv=$soul4Lv)");
        $epAlls = array_fill(0, 51, 0);
        foreach ($soulEps as $epinfo) {
            $eparr = explode(',', $epinfo['ep']);
            foreach ($eparr as $epstr) {
                $epstrarr = explode('|', $epstr);
                if (count($epstrarr) == 2 && $epstrarr[0] != 0 && $epstrarr[1] != 0) {
                    $pindex = $epstrarr[0];
                    $pvalue = $epstrarr[1];
                    $epAlls[$pindex] += $pvalue;
                }
            }
        }
        $realep = "0|0";
        foreach ($epAlls as $k => $v) {
            if ($v != 0) {
                $realep .= ','.$k.'|'.$v;
            }
        }
        // 减去原有的EP，因为equip的ep之前已经添加过
        $eparr = explode(',', $uSoul['ep']);
        foreach ($eparr as $epstr) {
            $epstrarr = explode('|', $epstr);
            if (count($epstrarr) == 2 && $epstrarr[0] != 0 && $epstrarr[1] != 0) {
                $pindex = $epstrarr[0];
                $pvalue = $epstrarr[1];
                $epAlls[$pindex] -= $pvalue;
            }
        }
        
        _spendCoin($uid, $coin, 'upsoul');
        sql_update("update usoul set `$soulKey`=`$soulKey`+1,`$soulCntKey`=`$soulCntKey`-$soulCnt,ep='$realep' where uid=$uid");
        
        // 装备本身属性
        $uequip = sql_fetch_one("select ep from uequip where uid=$uid");
        $equipep = $uequip['ep'];
        $eparr = explode(',', $equipep);
        foreach ($eparr as $epstr) {
            $epstrarr = explode('|', $epstr);
            if (count($epstrarr) == 2 && $epstrarr[0] != 0 && $epstrarr[1] != 0) {
                $pindex = $epstrarr[0];
                $pvalue = $epstrarr[1];
                $epAlls[$pindex] += $pvalue;
            }
        }
        // 
        $realep = "0|0";
        foreach ($epAlls as $k => $v) {
            if ($v != 0) {
                $realep .= ','.$k.'|'.$v;
            }
        }
        sql_update("insert into uequip (uid,es,ep) values ('$uid','','$realep') ON DUPLICATE KEY UPDATE es='',ep='$realep'");
        
        $uSoul = sql_fetch_one("select * from usoul where uid=$uid");
        $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
        
        $myequip = array();
        $myequip['ep'] = $realep;
        $myequip['skill'] = '';
        $oldzhanli = $uInfo['zhanli'];
        
        $my = new my(1, $uInfo, $myequip);
        $zhanli = $my->zhanli;
        $uInfo['zhanli'] = $zhanli;
        
        sql_update("update uinfo set zhanli=$zhanli where uid=$uid");
        sql_update("update upvp set zhanli=$zhanli where uid=$uid");
        
        if ($oldzhanli < $zhanli) {
            log_newzhanli($zhanli);
        }
        if ($slv % 5 == 0) {
            $lvStr = $slv.STR_Soul_LvStr;
            if ($slv == 20) {
                $lvStr = $slv.STR_Soul_LvFull;
            }
            _addSysMsg(sprintf(STR_SOUL_SysMsg1,$uInfo['uname'],$name.STR_Soul_NameSuffix,$lvStr));
        }
        return array(
            1,
            $realep,
            $uInfo,
            $my->format_to_array(),
            $uSoul
        );
    } else {
        return array(
            0,
            STR_Soul_SoulLv_Top
        );
    }
}

function _getSoulNameByType($type) {
    if ($type == 1) {
        return STR_Soul_Type_1;
    } elseif ($type == 2) {
        return STR_Soul_Type_2;
    } elseif ($type == 3) {
        return STR_Soul_Type_3;
    } elseif ($type == 4) {
        return STR_Soul_Type_4;
    }
    return false;
}

function ptyjSoul($uid,$params) {//普通祈福
    $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
    if (intval($uInfo['ulv']) < 10) {
        return array(
            0,
            STR_Soul_Lv_Low
        );
    }
    $usoulInfo = sql_fetch_one("select * from usoul where uid=$uid");
    if (!$usoulInfo) {
        return array(
            0,
            STR_DataErr2."-21"
        );
    }
    $ptyj = $usoulInfo['ptyj'];
    if($ptyj <= 0) {
        return array(
            0,
            STR_Soul_TimesOver
        );
    }
    $type = mt_rand(1, 4);
    $cnt = mt_rand(90, 110);
    $soulName = 'soul'.$type.'pcnt';
    $res = sql_update("update usoul set ptyj=ptyj-1,ptyjts=UNIX_TIMESTAMP(),$soulName=$soulName+$cnt where uid=$uid");
    if ($res) {
        $usoulInfo = sql_fetch_one("select * from usoul where uid=$uid");
        setSixOneLine($uid,8);
        return array(
            1,
            array($soulName => $cnt),
            $usoulInfo
        );
    }
    return array(
        0,
        STR_DataErr
    );
}

function _addSoul($uid,$type,$count) {
    $soulType = 'soul'.$type.'pcnt';
    $res = sql_update("update usoul set $soulType=$soulType+$count where uid=$uid");
    return $res;
}

function gjyjSoul($uid,$params) {//高级祈福
    $uInfo = sql_fetch_one("select * from uinfo where uid=$uid");
    if (intval($uInfo['ulv']) < 10) {
        return array(
            0,
            STR_Soul_Lv_Low
        );
    }
    $usoulInfo = sql_fetch_one("select * from usoul where uid=$uid");
    if (!$usoulInfo) {
        return array(
            0,
            STR_DataErr2."-22"
        );
    }
    $gjyj = $usoulInfo['gjyj'] + 1;
    $cost = array(
        1 => 100,
        2 => 100,
        3 => 200,
        4 => 200,
        5 => 400,
        6 => 400,
        7 => 600,
        8 => 600,
        9 => 800,
        10 => 800
    );
    $costG = 0;
    if ($gjyj > 10) {
        $costG = 800;
    } else {
        $costG = $cost[$gjyj];
    }
    $isAdd = 0;
    $addRand = mt_rand(1, 10000);
    if ($addRand < $gjyj * 100) {
        $isAdd = 1;
    }
    $award = array();
    for($i = 1; $i <= 4; $i ++) {
        $cnt = mt_rand(90, 110);
        if ($isAdd) {
            $cnt *= 1.5;
        }
        $award['soul'.$i.'pcnt'] = intval($cnt);
    }
    if(_spendGbytype($uid, $costG, 'gjyjsoul')) {
        sql_update("update usoul set gjyj=gjyj+1,gjyjts=UNIX_TIMESTAMP(),soul1pcnt=soul1pcnt+".$award['soul1pcnt'].",soul2pcnt=soul2pcnt+".$award['soul2pcnt'].",soul3pcnt=soul3pcnt+".$award['soul3pcnt'].",soul4pcnt=soul4pcnt+".$award['soul4pcnt']." where uid=$uid");
        $usoulInfo = sql_fetch_one("select * from usoul where uid=$uid");
        setSixOneLine($uid,8);
        return array(
            1,
            $usoulInfo,
            $award,
            $isAdd
        );
    } else {
        return array(
            0,
            STR_UgOff
        );
    }
}
?>