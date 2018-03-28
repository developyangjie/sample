<?php
require_once 'platformdb.php';
require_once 'config.php';
_sql_connect(LOG_DB_HOST,LOG_DB_USERNAME,LOG_DB_PASSWORD,LOG_DB_DATABASE);
$server_list=_sql_fetch_rows("select * from server_list order by id asc");
// var_dump($server_list);
$str_tmp="<?php\r\n";
$str_end="?>\r\n";
$str_tmp.="//服务器列表\r\n\n";
$str_tmp.="$"."server_list=array(\r\n\n";
foreach ($server_list as $value)
{

		$name=$value['name'];
		$type=$value['type'];
		$state=$value['state'];
		$opentime=$value['opentime'];
		$dbhost=$value['dbhost'];
		$dbname=$value['dbname'];
		$dbuser=$value['dbuser'];
		$dbpass=$value['dbpass'];
		$version=$value['version'];
		$str_tmp.="'".$value['id']."'"."=>array(name=>'".$name."',type=>'".$type."',state=>'".$state."',opentime=>'".$opentime."',dbhost=>'".$dbhost."',dbname=>'".$dbname."',dbuser=>'".$dbuser."',dbpass=>'".$dbpass."',version=>'".$version."'),\r\n\n";

}
$str_tmp.=");\r\n";
$str_tmp.=$str_end;

$sf="server_list.php"; //鏂囦欢鍚�
$fp=fopen($sf,"w"); //鍐欐柟寮忔墦寮�枃浠�
fwrite($fp,$str_tmp); //瀛樺叆鍐呭
fclose($fp);
?>