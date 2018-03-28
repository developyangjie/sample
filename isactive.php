<?php
require_once 'db.php';
function is_active($account) {
    $user = sql_fetch_one("select uid from cuser where loginname='$account'");
    if ($user) {
        return 1;
    }
    return 0;
}
$res = 0;
$param = $_GET + $_POST;
if (isset($param['account'])) {
    $account = $param['account'];
    $res = is_active($account);
}
echo $res;
?>