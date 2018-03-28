<?php
// ��ȡ����boss��Ϣ
function getWorldBossDate($uid, $params)
{
    $cfgs = sql_fetch_rows("select * from cfg_worldboss");
    return array(
        1,
        $cfgs
    );
}

// ��ʼ����boss����ս��
function startWorldBossBattle($uid, $params)
{
	$mapid = intval($params[0]);
    $muid = intval($params[1]);
    $mapcfg = sql_fetch_one("select * from cfg_worldboss where id=$mapid");
    
    $bexp = $mapcfg['exp'];
    $bcoin = $mapcfg['coin'];
    $consume = $mapcfg['consume'];
    // //����Ƿ��㹻
    // if(!_checkBread($uid,$consume)){
    // return array(
    // 0,
    // STR_BreadOff
    // );
    // }
    // //�жϺ�������
    // $ftime = sql_fetch_one("select UNIX_TIMESTAMP()-f.time as time from ufriend f where fuid=$muid and uid = $uid");
    // if($ftime['time']>0){
    // return array(
    // 0,
    // FRIEND_STAGEPARTNER_TIME
    // );
    // }
    
    $myequip = sql_fetch_one("select * from uequip where uid=$uid");
    $partnerid = $myequip['pvpstagepartner'] ? $myequip['pvpstagepartner'] : '';
    if (! $partnerid) {
        return array(
            0,
            STR_Partner_Load_Error
        );
    }
    $minfo = sql_fetch_one("select * from uinfo where uid=$muid");
    $mequip = sql_fetch_one("select * from uequip where uid=$muid");
    if (! $minfo || ! $mequip) {
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $mname = $minfo['uname'];
    $randp = sql_fetch_rows("select * from `upartner` where uid=$muid order by rare desc limit 5");
    if (count($randp) == 0) {
        return array(
            0,
            STR_Match_Data_Error
        );
    }
    $gid = intval(sql_fetch_one_cell("SELECT pvegirl FROM `uequip` WHERE `uid`=$uid"));
    if (! $gid) {
        return array(
            0,
            STR_Not_Girl
        );
    }
    // �޸ĺ���cd
    sql_update("update ufriend set worldtime=UNIX_TIMESTAMP() where uid=$uid and fuid=$muid");
    
    //ȡ�����䲢�����������
    $rewardid=$mapcfg['rewardid'];
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if(!$cfg){
        return array(
            0,
            STR_Param_Error
        );
    }
    $num = rand(intval($cfg['minget']),intval($cfg['maxget']));
	sql_update("insert into uspve (uid,time,rewardnum) values ($uid,UNIX_TIMESTAMP(),$num) on DUPLICATE KEY update uid=$uid,time=UNIX_TIMESTAMP(),rewardnum=$num");
    // ========================��ҵ�Ӷ����===========================
    $my = randomMySkill($uid);
    // ========================���ֵ�Ӷ����===========================
    $match = randomMatchPvpSkill($muid);
    _updateUTaskProcess($uid, 1009);
    return array(
        1,
        $bcoin,
        $my,
        $match
    );
}

// ��������boss����ս��
function endWorldBossBattle($uid, $params)
{
    $mapid = $params[0]; // ��ͼID
    $win = $params[1]; // �Ƿ�ʤ��
    
    if ($win == 2) {
        return array(
            2,
            $win
        );
    }
    
    $mapcfg = sql_fetch_one("select * from cfg_worldboss where id=$mapid");
    $uspve = sql_fetch_one("select * from uspve where uid=$uid");
    if(!$uspve){
        return array(
            0,
            STR_DataErr2
        );
    }
    $umapids = $uspve['mapid'];
    $bexp = $mapcfg['exp'];
    $bcoin = $mapcfg['coin'];
    $award = $mapcfg['award'];
    $consume = $mapcfg['consume'];
    // ����
    $rewardid = intval($mapcfg['rewardid']);
    $cfg = sql_fetch_one("select * from cfg_reward where id=$rewardid");
    if (! $cfg) {
        return array(
            0,
            STR_Param_Error
        );
    }
    $items = array();
    if (intval($cfg['item1']) > 0) {
        $items[] = array(
            id => $cfg['item1'],
            count => $cfg['count1'],
            prob => $cfg['prob1']
        );
    }
    if (intval($cfg['item2']) > 0) {
        $items[] = array(
            id => $cfg['item2'],
            count => $cfg['count2'],
            prob => $cfg['prob2']
        );
    }
    if (intval($cfg['item3']) > 0) {
        $items[] = array(
            id => $cfg['item3'],
            count => $cfg['count3'],
            prob => $cfg['prob3']
        );
    }
    if (intval($cfg['item4']) > 0) {
        $items[] = array(
            id => $cfg['item4'],
            count => $cfg['count4'],
            prob => $cfg['prob4']
        );
    }
    if (intval($cfg['item5']) > 0) {
        $items[] = array(
            id => $cfg['item5'],
            count => $cfg['count5'],
            prob => $cfg['prob5']
        );
    }
    if (intval($cfg['item6']) > 0) {
        $items[] = array(
            id => $cfg['item6'],
            count => $cfg['count6'],
            prob => $cfg['prob6']
        );
    }
    $totalprob = 0;
    foreach ($items as $item) {
        $totalprob += intval($item['prob']);
    }
    $num = $uspve['rewardnum'];
    if ($totalprob > 0) {
        $rewarditem = array();
        for ($i = 1; $i <= $num; $i ++) {
            $rand = rand(1, $totalprob);
            $addprob = 0;
            for ($j = 0; $j < count($items); $j ++) {
                $addprob += intval($items[$j]['prob']);
                if ($rand <= $addprob) {
                    $rewarditem[] = $items[$j];
                    break 1;
                }
            }
        }
    }
    foreach ($rewarditem as $value) {
        _addItem($uid, $value['id'], $value['count'], 'pve');
    }
    // �۳����
    _spendBread($uid, $consume, 'worldBossBattle');
    // ����Ӳ��
    _addCoin($uid, $bcoin, 'worldBoss');
    // ���뾭��
    _addExp($uid, $bexp);
    
    $star=3;
    return array(
        1,
        $consume, // �۳��������
        $bexp, // ��ȡ��������
        $bcoin, // ��ȡ�������
        $rewarditem,
    	$win,
    	$star
    ) // �������
;
}

?>