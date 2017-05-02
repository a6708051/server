<?php
namespace server\support;

use server\db;

class Task
{
    protected $swoole_server;
    private $server_config = array(
        'host' => '0.0.0.0',
        'port' => 9508,
        'worker_num' => 2,    //工作进程数量
        'daemonize' => false,  //是否作为守护进程
        'max_request' => 10000,
        'dispatch_mode' => 2,
        'task_worker_num' => 8,  //task进程的数量
        "task_ipc_mode " => 3 ,  //使用消息队列通信，并设置为争抢模式
    );
    
    public function __construct($config_params = array())
    {
        $this->server_config = array_merge($this->server_config, $config_params);
        $this->swoole_server = new \Swoole\Server($this->server_config['host'], $this->server_config['port']);
        unset($this->server_config['host']);
        unset($this->server_config['port']);
        $this->swoole_server->set($this->server_config);
        $this->swoole_server->on('Receive', [$this, 'onReceive']);
        $this->swoole_server->on('Task', [$this, 'onTask']);
        $this->swoole_server->on('Finish', [$this, 'onFinish']);
    }

    /**
     * 收到消息
     */
    public static function onReceive($swoole_server, $fd, $from_id, $data)
    {
        return new static($swoole_server, $fd, $from_id, $data);
    }

    /**
     * 非阻塞投递任务
     */
    public static function onTask($swoole_server, $task_id, $from_id, $data)
    {
        return new static($swoole_server, $task_id, $from_id, $data);
    }

    /**
     * 任务完成
     */
    public static function onFinish($swoole_server, $task_id, $data)
    {
        return new static($swoole_server, $task_id, $data);
    }

    public function start()
    {
        $this->swoole_server->start();
    }
}

