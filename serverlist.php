<?php
$ioscheckv = 100;
$openid = 0;
function getip(){
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
        $ip = getenv("REMOTE_ADDR");
    else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
        $ip = $_SERVER['REMOTE_ADDR'];
    else
        $ip = "unknown";
    return $ip;
}

$testopenids = array(
    '766D86E2B90DCD8ECB5EB5C5B55DD58D', // 新羽
    'A9AD1E6470E9AE8D62A0F31D405170E2', // viki
    '10BFA73C1FF876EAADB467AD4B6ECB5C', // 晟游网络官方QQ
    '4256577D8445381B1263F06FE72E7F65', // 吴珂
    'F70A3AA4D9E49E26CA33834822DBB41E', // 索多
    '2E5D0AAFE8CB9F4B7C11EB7358F099FA', // 乔飞
    '001A0BDD9811028B0D79F8957001BA9F', // TT
    '1919649', // TT
    '16176785',	
    '21455132',
'22679991',
);
$testips = array(
'60.199.178.97',
'180.168.111.42',
'58.247.9.122',
'222.44.96.38',
'180.175.187.135'
);

$toppayres = array();
$request = array_merge($_GET, $_POST);
if (!isset($request['ver']) || !isset($request['platform'])) {
    $toppayres = array(0,'参数错误');
} else {
    $debuguser = false;
    if (isset($request['openid'])) {
        $openid = $request['openid'];
        if (in_array($openid, $testopenids)) {
            $debuguser = true;
        }
    }
    $ip = getip();
    if(in_array($ip,$testips)) {
    	$debuguser = true;
    }

    require_once 'db.php'; 
    sql_connect("192.168.1.188:3306","root","6emYvgIYkt1VS8bjJA","sggj_gamebar");
    $ver = $request['ver'];
    $platform = $request['platform'];
    $vers = explode(".", $ver);
    $baseVer = intval($vers[0]) * 10000 + intval($vers[1]) * 100 + intval($vers[2]);
    if ($baseVer < 10000) {
        $toppayres = array(0,'版本过低');
    } else {
        $sql = "";
        if ($debuguser) {
            $sql = "select id,name,url,state,chatserver, h5chatserver, platform from tb_servers where state != 0 order by opentime desc";
        } else {
            $sql = "select id,name,url,state,chatserver, h5chatserver, platform from tb_servers where state != 0 and UNIX_TIMESTAMP(opentime)<=UNIX_TIMESTAMP() order by opentime desc";
        }
        
        if (intval($request['checkios']) == $ioscheckv){
            $sql = "select id,name,url from tb_servers where id = $openid";
        }
        $serverlist = sql_fetch_rows($sql);
        $toppayres = array(1,$serverlist);
    }
}

function url_encode($str) {
    if(is_array($str)) {
        foreach($str as $key=>$value) {
            $str[urlencode($key)] = url_encode($value);
        }
    }else if (!is_object($str)){
        $str = urlencode($str);
    }
    return $str;
}

if (isset($request['callback'])) {
    echo $request['callback'] . '(' . json_encode(array(1, $toppayres)) . ')';
} else if (isset($request['msgid'])) {
    $toppayres['msgid'] = $request['msgid'];
    echo urldecode(json_encode(url_encode($toppayres)));
} else {
    //var_dump($toppayres);
    //!防止json_encode对中文进行\u转码
    //echo json_encode($toppayres);
    echo urldecode(json_encode(url_encode($toppayres)));
}
exit();
?>
