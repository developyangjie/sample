<?php

require_once 'bili_api.php';

/**
 * 用户会话验证
 */
function _biliAccVerify($accessKey)
{
    $biliApiHttpClient = new BiliApiHttpClient();
    if (isset($accessKey)) {
        $params['access_key'] = $accessKey;
        require_once 'bili_config.php';
        $result = $biliApiHttpClient->post($biliConfig['server_base_url'] . 'session.verify', $params, $biliConfig);
        return json_decode($result, true);
    }
}

?>