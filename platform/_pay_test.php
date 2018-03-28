<?php
require_once 'db.php';
function _getArrayStringForGet($param)
{
    $preStr = null;
    foreach ($param as $key => $value) {
        if (!$preStr) {
            $preStr .= $key . "=" . $value;
        } else {
            $preStr .= "&" . $key . "=" . $value;
        }
    }
    return $preStr;
}

?>