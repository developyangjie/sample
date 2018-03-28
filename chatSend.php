<?php
require_once(dirname(__FILE__) . '/phpbuffer/BigEndianBytesBuffer.php');

define('CHAT_HOST', "db.ttgj:3306", true);
define('CHAT_DATABASE', "p11", true);
define('CHAT_USERNAME', "p11", true);
define('CHAT_PASSWORD', "yeswecan2014", true);

function __chatSendMsg($serverip, $serverport, $msg)
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
    $result = socket_connect($socket, $serverip, $serverport);
    if ($result == FALSE) {
        $result = socket_last_error($socket);
        throw new Exception("socket_connect() failed.\nReason: $result " . socket_strerror($result));
    }

    $buffer = new BigEndianBytesBuffer();
    $buffer->writeInt(18); // SYSTEM BROADCAST
    $buffer->writeUShortString("broadCastSinCode");
    $buffer->writeUShortString($msg);

    // 再加个包长度
    $buffer2 = new BigEndianBytesBuffer();
    $buffer2->writeInt($buffer->getWriteLen());
    $buffer2->writeBytes($buffer->readAllBytes());

    socket_write($socket, $buffer2->readAllBytes());
    /*$result = socket_read($socket,1024);
    if($result == FALSE)
    {
        $result = socket_last_error($socket);
        error_log("socket_read() failed.\nReason: ($result) " . socket_strerror($result) );
        return $result;
    }

    $buffer = new BigEndianBytesBuffer($result);
    if($buffer->readInt() != 4)
        $chat='<td><font color="#FF0000">protocol error</font></td>';
    if($buffer->readInt() != 1003)
        $chat='<td><font color="#FF0000">response error</font></td>';*/

    socket_close($socket);
}

function chatSendMsg($serverip, $serverport, $msg)
{
    //这里要加try catch 防止挂掉
    try {
        __chatSendMsg($serverip, $serverport, $msg);
    } catch (Exception $e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}

function _chatSendMsgOnly($msg)
{
    try {
// 		if(defined('CHAT_SERVER_IP')&&defined('CHAT_SERVER_PORT')){
// 			chatSendMsg(CHAT_SERVER_IP, CHAT_SERVER_PORT, $msg);
// 		}
        /*		sql_connect(CHAT_HOST,CHAT_USERNAME,CHAT_PASSWORD,CHAT_DATABASE);
                $db=DB_DATABASE;
                $chat=sql_fetch_one("select * from s_list_nei where sdb='$db' limit 1");
                if($chat){
                    chatSendMsg($chat["chatadd"], $chat["chatport"], $msg);
                }
                sql_connect();*/
    } catch (Exception $e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}

// chatSendMsg("192.168.0.221", "8001", ' { RTE("系統：", 25,cc.c3b(255,0,0)), RTE("恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！恭喜XXX獲得一等獎！", 25,cc.c3b(0,255,0)) } ');
?>