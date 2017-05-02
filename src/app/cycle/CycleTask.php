<?php
/**
 * Created by PhpStorm.
 * User: abc
 * Date: 2017/4/13
 * Time: 15:34
 */
namespace server\test\cycle;
use server;

require '../../Autoload.php';
server\Autoload::register();

class CycleTask extends server\socket\Task
{
    private $_CURRENT_INDEX = 0;
    private $_CURRENT_TIME;
    private $_CYCLE_SOLTS = array();
    private $_SOLT_CYCLE;
    private $_SOLT_INDEX;
    private $_SOLT_DATA = array('cycle_num'=>0, 'data'=>array(), 'task_func'=>array(), 'callback_func'=>array());
    public function __construct(array $config_params = array())
    {
        parent::__construct($config_params);
    }

    public static function onReceive($socket_server, $fd, $from_id, $data)
    {

    }

    public static function onTask($socket_server, $task_id, $from_id, $data)
    {

    }

    public static function onFinish($socket_server, $task_id, $data)
    {

    }

    public function start()
    {
        parent::start();
    }

    /**
     * 将数据插入队列
     */
    private function insert()
    {
        $this->_CYCLE_SOLTS[$this->_SOLT_INDEX][] = $this->_SOLT_DATA;
    }

    /**
     * 计算该数据执行的时间
     * @param $time string 执行时间
     */
    private function calcTime($time)
    {
        $gap_time = strtotime($time) - time();
        if ($gap_time < 0) {
            return;
        }
        $cur_cycle_last_time = 3600 -$this->_CURRENT_INDEX;
        if ($gap_time >= $cur_cycle_last_time) {
            $this->_SOLT_CYCLE = floor(($gap_time - $cur_cycle_last_time)/3600) + 1;
            $this->_SOLT_INDEX = ($gap_time - $cur_cycle_last_time)%3600;
        } else {
            $this->_SOLT_CYCLE = 0;
            $this->_SOLT_INDEX = $this->_CURRENT_INDEX + $gap_time;
        }
    }

    public function run()
    {
        $this->_CURRENT_TIME = microtime(true);
        while (true) {
            if (isset($this->_CYCLE_SOLTS[$this->_CURRENT_INDEX])) {
                $data = $this->_CYCLE_SOLTS[$this->_CURRENT_INDEX];

                //@todo 调用异步方法分发

            }

            $this->_CURRENT_INDEX++;
            $this->_CURRENT_TIME++;

            //时间校准
            $sleep_time = $this->_CURRENT_TIME - microtime(true);
            if ($sleep_time > 1) {
                //@todo 超时的处理

                continue;
            }
            usleep($sleep_time * 1000 * 1000);
        }
    }

    public function test()
    {
        swoole_timer_after(3000, function() {
            echo 'test swoole_timer_after!'.PHP_EOL;
        });
    }
}

$obj = new CycleTask();
$obj->test();
echo 'end?'.PHP_EOL;