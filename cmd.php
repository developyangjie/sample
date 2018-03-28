<?php
require_once("db.php");
require_once 'config.php';
require_once 's_user_new.php';
require_once 'partner.php';
require_once 'mail.php';
// require_once 's_pay.php';
// require_once 's_cdkey.php';
require_once 's_item.php';
require_once 'log.php';
require_once 'club.php';
require_once 'boss.php';
require_once 'treasure.php';

/**
 * 远程调用server端总入口
 */
class cmd
{
    /**
     * 用来接收带有UID的远程调用：
     * $param格式：uid+function+
     */
    static function c($param)
    {
        try {
            $uid = intval(array_shift($param));
            $commandFunc = array_shift($param);
            $sid = intval(array_shift($param));
            $ret = array(1);
            if (stripos($commandFunc, "_") !== false) {
                return;
            }
//             $xhprof_on = false;
//             if (function_exists("xhprof_enable") && mt_rand(1, 10) == 1) {
//                 xhprof_enable();
//                 $xhprof_on = true;
//             }

            $starttime = microtime(true);
            sql_connect_by_sid($sid);
       
            if (function_exists($commandFunc)) {
                $ret[] = $commandFunc($uid, $param);
            } else {
                throw new Exception("command $commandFunc not found");
            }

            $endtime = microtime(true);
            $lasttime = (int)(($endtime - $starttime) * 1000);
            if ($lasttime >= 300) {
                //				if($xhprof_on){
                //					$xhprof_data = xhprof_disable();
                //					$xhprof_root = '/www/xhprof/';
                //					include_once $xhprof_root."xhprof_lib/utils/xhprof_lib.php";
                //					include_once $xhprof_root."xhprof_lib/utils/xhprof_runs.php";
                //					$xhprof_runs = new XHProfRuns_Default();
                //					$run_id = $xhprof_runs->save_run($xhprof_data, "cmd.$lasttime");
                //				}
                //
                $executeTimeInfo = $commandFunc . "," . $lasttime . "," . time() . "," . $uid;
                @file_put_contents('../log/'.date('Ymd').'.'.LOG_DB_DATABASE.'.executetime.log', $executeTimeInfo.PHP_EOL,FILE_APPEND);
            }

            return $ret;
        } catch (Exception $e) {
            $ret = array(0);
            $ret[] = $e->getMessage();
            if (DEVELOP_MODE == 1)
                $ret[] = $e->getTrace();
            error_log($e->getMessage());
            return $ret;
        }
    }

    /**
     * 用来接收不带有UID的远程调用，在用户登录之前使用：
     * $param格式：function + args；
     */
    static function g($param)
    {
        try {
            $s = microtime();
            $commandFunc = array_shift($param);
            $ret = array(1);
            if (stripos($commandFunc, "_") !== false) {
                return array(0, 1);
            }
            _sql_connect();
            if (function_exists($commandFunc)) {
                $ret[] = $commandFunc($param);
            } else {
                throw new Exception("Global command $commandFunc not found");
            }
            $e = microtime();
            $l = $e - $s;
            return $ret;
        } catch (Exception $e) {
            $ret = array(0);
            $ret[] = $e->getMessage();
            if (DEVELOP_MODE == 1) {
                $ret[] = $e->getTrace();
            }
            error_log($e->getMessage());
            return $ret;
        }
    }
}

?>