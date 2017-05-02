<?php
namespace server\support;

class Client
{
    protected $client_server;
    private $client_config = [
        'host'=>'0.0.0.0',
        'port'=>9508,
        'timeout'=>1,
    ];
    
    public function __construct($config_params = array())
    {
        $this->client_config = array_merge($this->client_config, $config_params);
        $this->client_server = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client_server->on('connect', [$this, 'onConnect']);
        $this->client_server->on('receive', [$this, 'onReceive']);
        $this->client_server->on('error', [$this, 'onError']);
        $this->client_server->on('close', [$this, 'onClose']);
    }

    /**
     * 建立连接
     */
    public static function onConnect($client_server)
    {
        return new static($client_server);
    }

    /**
     * 收到消息
     */
    public static function onReceive($client_server, $data)
    {
        return new static($client_server, $data);
    }

    /**
     * 连接错误的回调
     */
    public static function onError($client_server)
    {
        return new static($client_server);
    }

    /**
     * 关闭连接
     */
    public static function onClose($client_server)
    {
        return new static($client_server);
    }

    public function start()
    {
        $this->client_server->connect($this->client_config['host'], $this->client_config['port'] , $this->client_config['timeout']);
    }
}

