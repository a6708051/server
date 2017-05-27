<?php
namespace server\support;

use server\db;

class Http
{
    protected $socket_server;
    private $server_config = array(
        'host'=>'0.0.0.0',
        'port'=>9508,
        'worker_num'=>2,    //工作进程数量
        'daemonize'=>false,  //是否作为守护进程
    );

    public function __construct($config_params = array())
    {
        $this->server_config = array_merge($this->server_config, $config_params);
        $this->socket_server = new \Swoole\Server($this->server_config['host'], $this->server_config['port']);
        unset($this->server_config['host']);
        unset($this->server_config['port']);
        $this->socket_server->set($this->server_config);
        $this->socket_server->on('Start', array($this, 'onStart'));
        $this->socket_server->on('request', [$this, 'onRequest']);
    }

    /**
     * 收到消息&返回消息
     * @param $request
     * @param $response
     * @return static
     */
    public static function onRequest($request, $response)
    {
        return new static($request, $response);
    }

    public function start()
    {
        $this->socket_server->start();
    }
}

