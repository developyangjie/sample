<?php
/**
 * Memcached封装
 * User: viki
 * Date: 14/11/24
 * Time: 12:37
 */
$redis;

function redis_connect($host = MEM_HOST, $port = MEM_PORT, $weight = 100)
{
    global $redis;
    $redis = new Redis();
    $redis->connect($host,$port);
}

function mem_instance()
{
    global $redis;
    if (! isset($redis)) {
        redis_connect();
    }
    return $redis;
}