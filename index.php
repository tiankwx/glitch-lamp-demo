<?php
date_default_timezone_set("Asia/Shanghai");
ini_set('display_errors',1);            //错误信息
ini_set('display_startup_errors',1);    //php启动错误信息
error_reporting(-1);                    //打印出所有的 错误信息
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); //将出错信息输出到一个文本文件

define("APP_ROOT", dirname(__file__) . DIRECTORY_SEPARATOR);
include_once(APP_ROOT . "tools" . DIRECTORY_SEPARATOR . "view.php");
echo $ip = getIP();

$class = new view();
$s = $class->insert($ip, $_SERVER);


function getIP()
{
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    elseif (isset($_SERVER["HTTP_CLIENT_IP"])) $ip = $_SERVER["HTTP_CLIENT_IP"];
    elseif (isset($_SERVER["REMOTE_ADDR"])) $ip = $_SERVER["REMOTE_ADDR"];
    elseif (getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
    elseif (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
    elseif (getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
    else $ip = "127.0.0.1";

    $ips = explode(",", $ip);
    if (isset($ips['0'])) {
        return $ips['0'];
    }
    return $ip;
}
