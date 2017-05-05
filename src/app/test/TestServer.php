<?php
namespace server\app\test;
use server;

require __DIR__.DIRECTORY_SEPARATOR.'../../Autoload.php';
server\Autoload::register();

class TestServer extends server\support\Socket
{
    private $server_config = array(
        'host'=>'0.0.0.0',
        'port'=>9999,
        'worker_num'=>4,    //工作进程数量
//        'daemonize'=>true,  //是否作为守护进程
    );
    public function __construct($config_params = array())
    {
        $this->server_config = array_merge($this->server_config, $config_params);
        parent::__construct($this->server_config);
    }

    public static function onConnect($socket_server, $fd, $from_id)
    {
        echo "Client:Connect.\n";
        $socket_server->send($fd, 'welcome to here');
    }

    public static function onReceive($socket_server, $fd, $from_id, $data)
    {
        $socket_server->send($fd, 'Server: '.$data);
    }

    public static function onClose($socket_server, $fd)
    {
        echo "Client: Close.\n";
    }

    public function start()
    {
        parent::start();
    }
}

$obj = new TestServer();
$obj->start();
