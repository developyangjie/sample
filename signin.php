<?php 
$secret_key = 's2Li9sr0G3xH';
$client_path = "http://cdn.sggj.txwy.com/index.html";
$param = $_GET + $_POST;
if (isset($param['account']) && isset($param['time']) && isset($param['sign'])) {
    $account = $param['account'];
    $time = $param['time'];
    $sign = $param['sign'];
    $right_sign = md5($account.'_'.$time.'_'.$secret_key);
    $platform = isset($param['platform']) ? $param['platform'] : 'twxy';
    if ($right_sign == $sign) {
        header("Location:".$client_path."?platform=$platform&openid=".$account."&openkey=".$sign."&time=".$time);
    } else {
        header("Location: http://sggj.txwy.com");
    }
} else {
    header("Location: http://sggj.txwy.com");
}
exit();
?>