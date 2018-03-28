<?php
require_once 'db.php';
require_once 'config.php';

$skills = sql_fetch_rows("select  sid,mp,sjob,starget,stype,satk,buffid,buffstep,buffrate from cfg_skill");
$dic = array();
foreach ($skills as $sk) {
    $key = strval($sk['sid']);
    $dic[$key] = $sk;
}
var_export($dic);