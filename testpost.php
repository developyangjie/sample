<?php
$loginCheckUrl = "http://data.hylr.igamesocial.cn/websocket_send.php";
$post_string ="serverid=102&content='ceshww'";
// $post_string ="cpoid=&ext=1&itemid=7&money=1.00&oid=10001160&sid=0&uid=da24d3b5aec06afca8f838c07af6a080&sign=ee5c9cfdec12277c7fed82b1959730df";
$ch = curl_init ();
curl_setopt ( $ch, CURLOPT_URL, $loginCheckUrl );
curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_string );
curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 0 );
curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
$result = curl_exec ( $ch );
$res=json_decode($result);
// var_dump($result);
// $param=array(
// 	'sid'=>1,
// 	'time'=>1460122479,
// 	'uid'=>da24d3b5aec06afca8f838c07af6a080
// );
// echo _createSign1($param,'IgNklMgsz4s1Utl2yYk');

// //内部方法
// function _createSign1($params , $appkey){
// 	if (isset($params["sign"])) {
// 		unset($params["sign"]);
// 	}
// 	if (isset($params["page"])) {
// 		unset($params["page"]);
// 	}
// 	if (isset($params["per"])) {
// 		unset($params["per"]);
// 	}
// 	ksort($params);
// 	$str  = "";
// 	foreach($params as $key=>$value){
// 		$str  .=  $key ."=". $value;
// 	}

// 	// 	return $str.$appkey;
// 	return md5($str.$appkey);
// }
?>