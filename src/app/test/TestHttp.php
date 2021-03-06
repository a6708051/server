<?php
namespace server\app\test;
use server;

require __DIR__.DIRECTORY_SEPARATOR.'../../Autoload.php';
server\Autoload::register();

class TestHttp extends server\support\Http
{
    private $server_config = array(
        'host'=>'0.0.0.0',
        'port'=>9999,
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

        $data = server\lib\Functions::doCurlPostRequest('www.lieshow.com');
        $socket_server->send($fd, $data);
        $socket_server->close($fd);
//        $socket_server->send($fd, 'welcome to here');
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

$obj = new TestHttp();
$obj->start();
