<?php
define('LOG_PATH', '../log/');

function _locallog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "locallog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
    chown($filename, "nobody");
    chgrp($filename, "nobody");
}

function _errorlog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "errorlog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _cmdlog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "cmdlog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _coinlog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "coinlog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _glog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "glog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _itemlog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "itemlog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _equiplog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "equiplog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _honorlog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "honorlog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _forgelog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "forgelog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _jinghualog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "jinghualog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _advequiplog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "advequiplog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}

function _maillog($msg)
{
    $filename = LOG_PATH.date('Ymd') . "." . DB_DATABASE .".". "maillog.log";
    @file_put_contents($filename, date("Y-m-d H:i:s") . "," . $msg . PHP_EOL, FILE_APPEND);
}
?>