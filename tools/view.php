<?php

// use Medoo\Medoo;

include_once("orm.php");

class view
{
    private $model;
    public function __construct()
    {
        // 初始化配置
        $this->model = new \Medoo\Medoo([
            'database_type' => 'mysql',
            'database_name' => 'app',
            'server' => '127.0.0.1',
            'username' => 'app',
            'password' => 'gomix-App',
            'charset' => 'utf8'
        ]);
    }

    public function insert($ip = "127.0.0.1", $server)
    {
        // 插入数据
        $this->model->insert('view', [
            'ip' => $ip,
            'mip' => $this->mip(),
            'server_name' => isset($server['SERVER_NAME']) ? $server['SERVER_NAME'] : null,
            'host' => isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : null,
            'agent' => isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : null
        ]);
    }

    public function mip()
    {
        $data = file_get_contents("https://ipv4.teams.cloudflare.com/");
        if (isset($data) and strlen($data) > 6) {
            $data2 = json_decode($data, true);
            if (isset($data2['ip'])) {
                return $data2['ip'];
            }
        }
        return null;
    }

    ///////////end class////////////////////////////////////////////////
}
