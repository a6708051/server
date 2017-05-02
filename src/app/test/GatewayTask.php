<?php
namespace server\app\test;
use server;

require '../../Autoload.php';
server\Autoload::register();

class GatewayTask extends server\socket\Task
{
    private $config = ['port'=>9508];
    private static $redisInstance;
    public function __construct()
    {
        $this->newRedisInstance();
        parent::__construct($this->config);
    }

    private function newRedisInstance()
    {
        self::$redisInstance = server\support\RedisServer::getInstance('120.76.22.59', 6379);
    }

    public static function onReceive($task_server, $fd, $from_id, $data)
    {
        $hash = $data['dis'];
        $key = $data['ip'];
        $value = $data['time'];

        //判断是否有数据仍然存在
        $last_value = self::$redisInstance->hashGet($hash, $key);
        if (!empty($last_value)) {
            if ($value - $last_value < 2) {
                //@todo 调用频繁
                return;
            }
        }

        $task_server->task(array('hash'=>$hash, 'key'=>$key, 'value'=>$value));
        echo "start task!".PHP_EOL;
        echo $data.PHP_EOL;
    }

    public static function onTask($task_server, $task_id, $from_id, $data)
    {
        //@todo 请求分发


        //将数据更新到redis中
        self::$redisInstance->hashSet($data['hash'], $data['key'], $data['value']);

        echo "dispatch task!".PHP_EOL;
        $task_server->finish($data);
    }

    public static function onFinish($task_server, $task_id, $data)
    {
        sleep(1);

        $new_value = self::$redisInstance->hashGet($data['hash'], $data['key']);
        if ($new_value == $data['value']) {
            self::$redisInstance->hashDel($data['hash'], $data['key']);
        }
        echo "task finish!".PHP_EOL;
    }

    public function start()
    {
        parent::start();
    }

}

$obj = new GatewayTask();
$obj->start();

