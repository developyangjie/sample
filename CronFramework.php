<?php
require_once 'db.php';
require_once 'platformdb.php';
define('CRON_MODE', 1);

function doRunScheduleTable($scheduleTable, $count)
{
    foreach ($scheduleTable as $key => $value) {
        if ($count % $key != 0)
            continue;

        foreach ($value as $k => $v) {
            try {
                $startTime = microtime(true);
                
                _sql_connect(LOG_DB_HOST, LOG_DB_USERNAME, LOG_DB_PASSWORD, LOG_DB_DATABASE);
                $serlist = _sql_fetch_rows("select id,name,ip,spareip,chatip,chatport,type,state from server_list order by id asc");
                $array=array();
                foreach ($serlist as $value)
                {
                	$serverid=intval($value['id']);
                	if($serverid==1||$serverid==102){
                		sql_connect_by_sid($serverid);
                		
                		if($v=="doResetPvpCount"||$v=="DoDAY")
                		{
                			$v($serverid);
                		}
                		else 
                		{
                			$v();
                		}
                	}
                }
                
                $now = microtime(true);
                $timecost = $now - $startTime;
                echo "Call Time: " . $v . " -- " . $timecost . "\n";
            } catch (Exception $e) {
                cronLogException($e);
            }
        }
    }
}

function doRunWeekScheduleTable($scheduleTable, $count)
{
    foreach ($scheduleTable as $key => $value) {
        if ($count != $key)
            continue;

        foreach ($value as $k => $v) {
            try {
                $startTime = microtime(true);
                $v();
                $now = microtime(true);
                $timecost = $now - $startTime;
                echo "Call Time: " . $v . " -- " . $timecost . "\n";
            } catch (Exception $e) {
                cronLogException($e);
            }
        }
    }
}

/**
 * 定时运行的脚本
 *
 * @param unknown_type $param
 */
function runClockCron($scheduleTable)
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    $i = intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
    while (true) {
        if (($i % 3600) == 1831) {
            $startTime = time();
            $localTime = localtime($startTime, true);
            $i = intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
        }
        $startTime = microtime(true);
        try {
            foreach ($scheduleTable as $key => $value) {
                if ($i != $key)
                    continue;
                foreach ($value as $k => $v) {
                    try {
                        $startTime = microtime(true);
                        $v();
                        $now = microtime(true);
                        $timecost = $now - $startTime;
                        echo "Call Time: " . $v . " -- " . $timecost . "\n";
                    } catch (Exception $e) {
                        cronLogException($e);
                    }
                }
            }
        } catch (Exception $e) {
            cronLogException($e);
        }
        $now = microtime(true);
        $timecost = $now - $startTime;
        if ($timecost >= 0 && $timecost < 1) {
            usleep((1 - $timecost) * 1000000);
        }
        $startTime = $now;
        $i++;
    }
}

function runCron($scheduleTable)
{
    $startTime = time();
    $localTime = localtime($startTime, true);
    // 一天经过的秒数
    $i = intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
    while (true) {
        if (($i % 3600) == 1831) {
            $startTime = time();
            $localTime = localtime($startTime, true);
            $i = intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
        }
        $startTime = microtime(true);
        try {
        	doRunScheduleTable($scheduleTable, $i);

            
        } catch (Exception $e) {
            cronLogException($e);
        }

        $now = microtime(true);
        $timecost = $now - $startTime;
        if ($timecost >= 0 && $timecost < 1) {
            usleep((1 - $timecost) * 1000000);
        }

        $startTime = $now;
        $i++;
    }
}

function runWeekCron($scheduleTable)
{
    sql_connect();

    $startTime = time();
    $localTime = localtime($startTime, true);
    $i = intval($localTime['tm_wday']) * 86400 + intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
    while (true) {
        if (($i % 3600) == 1831) {
            $startTime = time();
            $localTime = localtime($startTime, true);
            $i = intval($localTime['tm_wday']) * 86400 + intval($localTime['tm_hour']) * 3600 + intval($localTime["tm_min"]) * 60 + intval($localTime["tm_sec"]);
        }
        $startTime = microtime(true);
        try {
            doRunWeekScheduleTable($scheduleTable, $i);
        } catch (Exception $e) {
            cronLogException($e);
        }

        $now = microtime(true);
        $timecost = $now - $startTime;
        if ($timecost >= 0 && $timecost < 1) {
            usleep((1 - $timecost) * 1000000);
        }

        $startTime = $now;
        $i++;
    }
}

// 准时运行的脚本, 整点数能被整除时运行
function runPunctualCon($scheduleTable)
{
    sql_connect();

    while (true) {
        $startTime = time();
        $localTime = localtime($startTime, true);
        if ($localTime["tm_sec"] == 0 && $localTime["tm_min"] == 0)         // 是否是整点
        {
            $clockOfTheDay = intval($localTime['tm_hour']);
            if ($clockOfTheDay == 0) {
                $clockOfTheDay = 24;
            }
            foreach ($scheduleTable as $hours => $value) {
                if ($clockOfTheDay % $hours == 0) {
                    foreach ($value as $key => $function) {
                        try {
                            $function();
                        } catch (Exception $e) {
                            cronLogException($e);
                        }
                    }
                }
            }
            $now = time();
            $timeCost = $now - $startTime;
            cronLog("over" . ($timeCost));
            if ($timeCost < 3600 - 2) {
                cronLog("big" . (3600 - 2 - $timeCost));
                sleep(3600 - 2 - $timeCost);
            }
        } else {
            $secToHour = 3600 - ($localTime["tm_sec"] + 60 * $localTime["tm_min"]);
            if ($secToHour < 3) {
                cronLog("lit");
                usleep(100000);
            } else {
                cronLog("mid" . ($secToHour - 2));
                sleep($secToHour - 2);
            }
        }
    }
}

function runDayCon($scheduleTable)
{
    sql_connect();

    while (true) {
        $startTime = time();
        $localTime = localtime($startTime, true);
        if ($localTime["tm_sec"] == 0 && $localTime["tm_min"] == 0)         // 是否是整点
        {
            $clockOfTheDay = $localTime['tm_hour'];
            foreach ($scheduleTable as $hours => $value) {
                if ($clockOfTheDay % $hours == 0) {
                    foreach ($value as $key => $function) {
                        try {
                            $function();
                        } catch (Exception $e) {
                            cronLogException($e);
                        }
                    }
                }
            }
            $now = time();
            $timeCost = $now - $startTime;
            cronLog("over" . ($timeCost));
            if ($timeCost < 3600 - 2) {
                cronLog("big" . (3600 - 2 - $timeCost));
                sleep(3600 - 2 - $timeCost);
            }
        } else {
            $secToHour = 3600 - ($localTime["tm_sec"] + 60 * $localTime["tm_min"]);
            if ($secToHour < 3) {
                cronLog("lit");
                usleep(100000);
            } else {
                cronLog("mid" . ($secToHour - 2));
                sleep($secToHour - 2);
            }
        }
    }
}

function cronLog($msg)
{
    $time = date("Ymd H:i:s");
    echo "[$time]" . $msg . "\n";
}

function cronLogException(Exception $e)
{
    try {
        cronLog("=========================Start======================================\n");
        cronLog("getMessage()--" . $e->getMessage());
        foreach ($e->getTrace() as $t) {
            $args = "";
            $coma = "";
            foreach ($t["args"] as $a) {
                $args .= ($coma . var_export($a, true));
                $coma = ",";
            }
            cronLog("getTrace() -- " . $t["file"] . ":" . $t["line"] . " " . $t["function"] . "(" . $args . ")");
        }
        cronLog("============================end===================================\n");
    } catch (Exception $ee) {
        cronLog("cronLogException error!!!!!!!!!!!!!!!!!!!!!!\n");
    }
}

?>