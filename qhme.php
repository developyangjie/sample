<?php
require_once 'db.php';
$qhid = intval($_GET['qhId']);
$accessToken = $_GET['accessToken'];
$loginInfo = sql_fetch_one_cell("select qhid from login_qh360 where qhid=$qhid and access_token='$accessToken'");
if (!$loginInfo) {
    echo "[0]";
} else {
    echo "[1]";
}
?>