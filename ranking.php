<?php
/**
 * 接口：战力排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopZhanLi($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank where type='zhanli' ORDER BY zhanli desc LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：战士战力排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopZhanShi($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='zhanshi' ORDER BY zhanli desc LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：猎人战力排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopLieRen($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='lieren' ORDER BY zhanli desc LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：法师战力排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopFaShi($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='fashi' ORDER BY zhanli desc LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：角斗士排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopJueDouShi($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,`index`,uname,ujob,sex,ulv,zhanli,sig FROM urank where type='juedoushi' ORDER BY `index` LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：公会排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopClub($uid, $params)
{
    $res = sql_fetch_rows("SELECT uid,cid,cname,uname,ujob,sex,clv FROM urank where type='club' ORDER BY exp desc LIMIT 20");
    return array(1, $res);
}

/**
 * 接口：所有排行榜
 * @param $uid
 * @param $params []
 * @return array
 */
function TopTop($uid, $params)
{
    $res[0] = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank where type='zhanli' ORDER BY zhanli desc LIMIT 1");
    $res[1] = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='zhanshi' ORDER BY zhanli desc LIMIT 1");
    $res[2] = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='lieren' ORDER BY zhanli desc LIMIT 1");
    $res[3] = sql_fetch_rows("SELECT uid,cname,uname,ujob,sex,ulv,zhanli,sig FROM urank  where type='fashi' ORDER BY zhanli desc LIMIT 1");
    $res[4] = sql_fetch_rows("SELECT uid,`index`,uname,ujob,sex,ulv,zhanli,sig FROM urank where type='juedoushi' ORDER BY `index` LIMIT 1");
    $res[5] = sql_fetch_rows("SELECT uid,cid,cname,uname,ujob,sex,clv FROM urank where type='club' ORDER BY exp desc LIMIT 1");
    return array(1, $res);
}


?>