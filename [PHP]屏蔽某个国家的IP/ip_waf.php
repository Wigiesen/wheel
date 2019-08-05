<?php
session_start();
define('AUTHKEY', 'XXXXXXXXXX');
//用户IP [上线后这里变为真实用户IP]
$ipAddress = getClientIp();
if (isset($_GET['key']) && !empty($_GET['key'])) {
    $key = $_GET['key'];
    if ($key != AUTHKEY) {
        echo "Your authKey is error!";
        return;
    }else{
        $_SESSION['auth'] = true;
    }
}else{
    if ($_SESSION['auth'] != true) {
        //获取IP数据
        $ipInfo = file_get_contents("http://ip-api.com/php/{$ipAddress}");
        //IP数据格式化
        $ipInfo = unserialize($ipInfo);
        //判断用户IP是否是来自日本
        if ($ipInfo['countryCode'] == 'JP' || $ipInfo['country'] == 'Japan') {
            echo "Your ip address is {$ipAddress} , The ip address is block";
            return;
        }
    }
}

//获取用户真实IP函数
function getClientIp()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    if (getenv('HTTP_X_REAL_IP')) {
        $ip = getenv('HTTP_X_REAL_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        $ips = explode(',', $ip);
        $ip = $ips[0];
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '0.0.0.0';
    }
    return $ip;
}