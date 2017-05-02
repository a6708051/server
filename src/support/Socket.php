<?php
namespace server\support;

use server\db;

class Socket
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
        $this->socket_server->on('connect', [$this, 'onConnect']);
        $this->socket_server->on('receive', [$this, 'onReceive']);
        $this->socket_server->on('close', [$this, 'onClose']);
    }

    /**
     * Server启动在主进程的主线程回调此函数
     * @param $serv
     */
    public static function onStart($serv) {
        //记录进程id,脚本实现自动重启
        $pid = "{$serv->master_pid}\n{$serv->manager_pid}";
        file_put_contents(SWOOLE_TASK_PID_PATH, $pid);
    }

    /**
     * 建立连接
     */
    public static function onConnect($socket_server, $fd, $from_id)
    {
        return new static($socket_server, $fd, $from_id);
    }

    /**
     * 收到消息
     */
    public static function onReceive($socket_server, $fd, $from_id, $data)
    {
        return new static($socket_server, $fd, $from_id, $data);
    }

    /**
     * 关闭连接
     */
    public static function onClose($socket_server, $fd)
    {
        return new static($socket_server, $fd);
    }

    public function start()
    {
        $this->socket_server->start();
    }
}

