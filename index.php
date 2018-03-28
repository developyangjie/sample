<?php
require_once("platformdb.php");
require_once 'config.php';
header("Access-Control-Allow-Origin: *");

function getServerList(){
    _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
    $serlist = _sql_fetch_rows("select id,name,ip,type,state from server_list");
    if($serlist){
        return array(
            1,
            $serlist
        );
    }
    return array(
        0,
        $serlist
    );
}
$result = getServerList();
$res = json_encode($result, true);
echo $res;
?>
