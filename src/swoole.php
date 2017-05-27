#!/bin/env php
<?php
/**
 * 默认时区定义
 */
date_default_timezone_set('Asia/Shanghai');

/**
 * 设置错误报告模式
 */
error_reporting(0);

/**
 * 设置默认区域
 */
setlocale(LC_ALL, "zh_CN.utf-8");

/**
 * 检测 PDO_MYSQL
 */
if (!extension_loaded('pdo_mysql')) {
    //exit('PDO_MYSQL extension is not installed' . PHP_EOL);
}
/**
 * 检查exec 函数是否启用
 */
if (!function_exists('exec')) {
    exit('exec function is disabled' . PHP_EOL);
}
/**
 * 检查命令 lsof 命令是否存在
 */
exec("whereis lsof", $out);
if ($out[0] == 'lsof:') {
    exit('lsof is not found' . PHP_EOL);
}
/*
 * 定义项相关常量
 */
define('DS', DIRECTORY_SEPARATOR);//反斜扛
define('SWOOLE_PATH', __DIR__);//swoole目录
define('SWOOLE_TASK_NAME_PRE', 'swooleServ');
define('DEBUG', true);//是否开启调试模式
define('LOG_PATH', SWOOLE_PATH . 'log' . DS);//日志目录
define('SWOOLE_START_TIME', microtime(true));//时间
define('SWOOLE_START_MEM', memory_get_usage());//内存

//可执行命令
$cmds = array('start','stop','restart','reload','close','status','list',);
$shortopts = "dDh:p:n:";
$longopts = array('help','daemon','nondaemon','host:','port:','name:',);
$opts = getopt($shortopts, $longopts);

if (isset($opts['help']) || $argc < 2) {
    echo <<<HELP
用法：php swoole.php 选项 ... 命令[start|stop|restart|reload|close|status|list]
管理swoole-task服务,确保系统 lsof 命令有效
如果不指定监听host或者port，使用配置参数

参数说明
    --help  显示本帮助说明
    -d, --daemon    指定此参数,以守护进程模式运行,不指定则读取配置文件值
    -D, --nondaemon 指定此参数，以非守护进程模式运行,不指定则读取配置文件值
    -h, --host  指定监听ip,例如 php swoole.php -h127.0.0.1
    -p, --port  指定监听端口port， 例如 php swoole.php -h127.0.0.1 -p9520
    -n, --name  指定服务进程名称，例如 php swoole.php -ntest start, 则进程名称为SWOOLE_TASK_NAME_PRE-name
启动swoole-task 如果不指定 host和port，读取默认配置
强制关闭swoole-task 必须指定port,没有指定host，关闭的监听端口是  *:port,指定了host，关闭 host:port端口
平滑关闭swoole-task 必须指定port,没有指定host，关闭的监听端口是  *:port,指定了host，关闭 host:port端口
强制重启swoole-task 必须指定端口
平滑重启swoole-task 必须指定端口
获取swoole-task 状态，必须指定port(不指定host默认127.0.0.1), tasking_num是正在处理的任务数量(0表示没有待处理任务)
HELP;
    exit;
}

//参数检查
foreach ($opts as $k => $v) {
    if (($k == 'h' || $k == 'host')) {
        if (empty($v)) {
            exit("参数 -h --host 必须指定值" . PHP_EOL);
        }
    }
    if (($k == 'p' || $k == 'port')) {
        if (empty($v)) {
            exit("参数 -p --port 必须指定值" . PHP_EOL);
        }
    }
    if (($k == 'n' || $k == 'name')) {
        if (empty($v)) {
            exit("参数 -n --name 必须指定值" . PHP_EOL);
        }
    }
}

//命令检查
$cmd = $argv[$argc - 1];
if (!in_array($cmd, $cmds)) {
    exit("输入命令有误 : {$cmd}, 请查看帮助文档".PHP_EOL);
}

$apps_name = $opts['n'];
if(empty($apps_name)){
    exit("参数 -n --name 必须".PHP_EOL);
}

/**
 * 获取服务器IP、端口信息
 */
if (is_file(SWOOLE_PATH. DS  . 'config.json')) {
    if ($cfg = json_decode(file_get_contents(SWOOLE_PATH. DS  . 'config.json'), true)) {
        if (isset($cfg['apps'][$apps_name])) {
            $host = $cfg['apps'][$apps_name]['host'];
            $port = $cfg['apps'][$apps_name]['port'];
            //获取类名
            $arr = explode('.', $cfg['apps'][$apps_name]['directory']);
            $url_arr = explode('/', $arr[0]);
            $class_name = end($url_arr);
            include $cfg['apps'][$apps_name]['directory'];// 加载 swoole server
        }else{
            $host = '120.24.52.9';
            $port = 9508;
            $class_name = 'TestServer';
        }
    }
}
define('SWOOLE_TASK_PID_PATH', SWOOLE_PATH . DS . 'tmp' . DS . 'swoole-task-'.$class_name.'.pid');
//允许执行的应用
$apps_arr = array_keys($cfg['apps']);
if(!in_array($apps_name,$apps_arr)){
    exit("参数 -n --name 指定值不在配置里，请查看是否已配置该应用".PHP_EOL);
}

function portBind($port) {
    $ret = array();
    $cmd = "lsof -i :{$port}|awk '$1 != \"COMMAND\"  {print $1, $2, $9}'";
    exec($cmd, $out);
    if ($out) {
        foreach ($out as $v) {
            $a = explode(' ', $v);
            list($ip, $p) = explode(':', $a[2]);
            $ret[$a[1]] = array(
                'cmd' => $a[0],
                'ip' => $ip,
                'port' => $p,
            );
        }
    }
    return $ret;
}

function servStart($host, $port, $daemon, $name,$class_name) {
    echo "正在启动应用".$name." swoole-task 服务" . PHP_EOL;
    if (!is_writable(dirname(SWOOLE_TASK_PID_PATH))) {
        exit("swoole-task-pid文件需要目录的写入权限:" . dirname(SWOOLE_TASK_PID_PATH) . PHP_EOL);
    }
    if (file_exists(SWOOLE_TASK_PID_PATH)) {
        $pid = explode("\n", file_get_contents(SWOOLE_TASK_PID_PATH));
        $cmd = "ps ax | awk '{ print $1 }' | grep -e \"^{$pid[0]}$\"";
        exec($cmd, $out);
        if (!empty($out)) {
            exit("swoole-task pid文件 " . SWOOLE_TASK_PID_PATH . " 存在，swoole-task 服务器已经启动，进程pid为:{$pid[0]}" . PHP_EOL);
        } else {
            echo "警告:swoole-task pid文件 " . SWOOLE_TASK_PID_PATH . " 存在，可能swoole-task服务上次异常退出(非守护模式ctrl+c终止造成是最大可能)" . PHP_EOL;
            unlink(SWOOLE_TASK_PID_PATH);
        }
    }

    $bind = portBind($port);
    if ($bind) {
        foreach ($bind as $k => $v) {
            if ($v['ip'] == '*' || $v['ip'] == $host) {
                exit("端口已经被占用 {$host}:$port, 占用端口进程ID {$k}" . PHP_EOL);
            }
        }
    }
    unset($_SERVER['argv']);
    $_SERVER['argc'] = 0;

    //确保服务器启动后swoole-task-pid文件必须生成
    if (!empty(portBind($port)) && !file_exists(SWOOLE_TASK_PID_PATH)) {
        exit("swoole-task pid文件生成失败( " . SWOOLE_TASK_PID_PATH . ") ,请手动关闭当前启动的swoole-task服务检查原因" . PHP_EOL);
    }
    echo "启动应用".$name." swoole-task 服务成功" . PHP_EOL;

    $namespace =  "server\\app\\".$name."\\".$class_name;
    $server = new $namespace(array('host'=>$host,'port'=>$port,'daemonize'=>$daemon));
    $server->start();

}

function servStop($host, $port, $isRestart = false) {
    echo "正在停止 swoole-task 服务" . PHP_EOL;
    if (!file_exists(SWOOLE_TASK_PID_PATH)) {
        exit('swoole-task-pid文件:' . SWOOLE_TASK_PID_PATH . '不存在' . PHP_EOL);
    }
    $pid = explode("\n", file_get_contents(SWOOLE_TASK_PID_PATH));
    $bind = portBind($port);
    if (empty($bind) || !isset($bind[$pid[0]])) {
        exit("指定端口占用进程不存在 port:{$port}, pid:{$pid[0]}" . PHP_EOL);
    }
    $cmd = "kill {$pid[0]}";
    exec($cmd);
    do {
        $out = array();
        $c = "ps ax | awk '{ print $1 }' | grep -e \"^{$pid[0]}$\"";
        exec($c, $out);
        if (empty($out)) {
            break;
        }
    } while (true);
    //确保停止服务后swoole-task-pid文件被删除
    if (file_exists(SWOOLE_TASK_PID_PATH)) {
        unlink(SWOOLE_TASK_PID_PATH);
    }
    $msg = "执行命令 {$cmd} 成功，端口 {$host}:{$port} 进程结束" . PHP_EOL;
    if ($isRestart) {
        echo $msg;
    } else {
        exit($msg);
    }
}

function servReload($host, $port, $isRestart = false) {
    echo "正在平滑重启 swoole-task 服务" . PHP_EOL;
    try {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $ret = $client->connect($host, $port);
        if (empty($ret)) {
            exit("{$host}:{$port} swoole-task服务不存在或者已经关闭" . PHP_EOL);
        } else {
            $client->send(json_encode(array('action' => 'reload')));
        }
        $msg = "执行命令reload成功，端口 {$host}:{$port} 进程重启" . PHP_EOL;
        if ($isRestart) {
            echo $msg;
        } else {
            exit($msg);
        }
    } catch (Exception $e) {
        exit($e->getMessage() . PHP_EOL . $e->getTraceAsString());
    }
}

function servClose($host, $port, $isRestart = false) {
    echo "正在关闭 swoole-task 服务" . PHP_EOL;
    try {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $ret = $client->connect($host, $port);
        if (empty($ret)) {
            exit("{$host}:{$port} swoole-task服务不存在或者已经关闭" . PHP_EOL);
        } else {
            $client->send(json_encode(array('action' => 'close')));
        }
        //确保停止服务后swoole-task-pid文件被删除
        if (file_exists(SWOOLE_TASK_PID_PATH)) {
            unlink(SWOOLE_TASK_PID_PATH);
        }
        $msg = "执行命令close成功，端口 {$host}:{$port} 进程结束" . PHP_EOL;
        if ($isRestart) {
            echo $msg;
        } else {
            exit($msg);
        }
    } catch (\Exception $e) {
        exit($e->getMessage() . PHP_EOL . $e->getTraceAsString());
    }
}

function servStatus($host, $port) {
    echo "swoole-task {$host}:{$port} 运行状态" . PHP_EOL;
    $pid = explode("\n", file_get_contents(SWOOLE_TASK_PID_PATH));
    $bind = portBind($port);
    if (empty($bind) || !isset($bind[$pid[0]])) {
        exit("指定端口占用进程不存在 port:{$port}, pid:{$pid[0]}" . PHP_EOL);
    }
    $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
    $ret = $client->connect($host, $port);
    if (empty($ret)) {
        exit("{$host}:{$port} swoole-task服务不存在或者已经停止" . PHP_EOL);
    } else {
        $client->send(json_encode(array('action' => 'status')));
        $out = $client->recv();
        $a = json_decode($out);
        $b = array(
            'start_time' => '服务器启动的时间',
            'connection_num' => '当前连接的数量',
            'accept_count' => '接受的连接数量',
            'close_count' => '关闭的连接数量',
            'tasking_num' => '当前正在排队的任务数',
            'request_count' => '请求的连接数量',
            'worker_request_count' => 'worker连接数量',
            'task_process_num' => '任务进程数量'
        );
        foreach ($a as $k1 => $v1) {
            if ($k1 == 'start_time') {
                $v1 = date("Y-m-d H:i:s", $v1);
            }
            echo $b[$k1] . ":\t$v1" . PHP_EOL;
        }
    }
    exit();
}

function servList() {
    echo "本机运行的swoole-task服务进程" . PHP_EOL;
    $cmd = "ps aux|grep " . SWOOLE_TASK_NAME_PRE . "|grep -v grep|awk '{print $1, $2, $6, $8, $9, $11}'";
    exec($cmd, $out);
    if (empty($out)) {
        exit("没有发现正在运行的swoole-task服务" . PHP_EOL);
    }
    echo "USER PID RSS(kb) STAT START COMMAND" . PHP_EOL;
    foreach ($out as $v) {
        echo $v . PHP_EOL;
    }
    exit();
}

//监听ip 127.0.0.1，空读取配置文件
if (!empty($opts['h'])) {
    $host = $opts['h'];
    if (!filter_var($host, FILTER_VALIDATE_IP)) {
        exit("输入host有误:{$host}");
    }
}
if (!empty($opts['host'])) {
    $host = $opts['host'];
    if (!filter_var($host, FILTER_VALIDATE_IP)) {
        exit("输入host有误:{$host}");
    }
}
//监听端口，9501 读取配置文件
if (!empty($opts['p'])) {
    $port = (int)$opts['p'];
    if ($port <= 0) {
        exit("输入port有误:{$port}");
    }
}
if (!empty($opts['port'])) {
    $port = (int)$opts['port'];
    if ($port <= 0) {
        exit("输入port有误:{$port}");
    }
}
//进程名称 没有默认为 SWOOLE_TASK_NAME_PRE;
$name = SWOOLE_TASK_NAME_PRE;
if (!empty($opts['n'])) {
    $name = $opts['n'];
}
if (!empty($opts['name'])) {
    $name = $opts['n'];
}
//是否守护进程 -1 读取配置文件
$isdaemon = -1;
if (isset($opts['D']) || isset($opts['nondaemon'])) {
    $isdaemon = 0;
}
if (isset($opts['d']) || isset($opts['daemon'])) {
    $isdaemon = 1;
}
//启动swoole-task服务
if ($cmd == 'start') {
    servStart($host, $port, $isdaemon, $name,$class_name);
}
//强制停止swoole-task服务
if ($cmd == 'stop') {
    if (empty($port)) {
        exit("停止swoole-task服务必须指定port" . PHP_EOL);
    }
    servStop($host, $port);
}
//关闭swoole-task服务
if ($cmd == 'close') {
    if (empty($port)) {
        exit("停止swoole-task服务必须指定port" . PHP_EOL);
    }
    servClose($host, $port);
}
//强制重启swoole-task服务
if ($cmd == 'restart') {
    if (empty($port)) {
        exit("重启swoole-task服务必须指定port" . PHP_EOL);
    }
    echo "重启swoole-task服务" . PHP_EOL;
    servStop($host, $port, true);
    servStart($host, $port, $isdaemon, $name,$class_name);
}
//平滑重启swoole-task服务
if ($cmd == 'reload') {
    if (empty($port)) {
        exit("平滑重启swoole-task服务必须指定port" . PHP_EOL);
    }
    echo "平滑重启swoole-task服务" . PHP_EOL;
    servReload($host, $port, true);
}
//查看swoole-task服务状态
if ($cmd == 'status') {
    if (empty($host)) {
        $host = '127.0.0.1';
    }
    if (empty($port)) {
        exit("查看swoole-task服务必须指定port(host不指定默认使用127.0.0.1)" . PHP_EOL);
    }
    servStatus($host, $port);
}
//查看swoole-task服务进程列表
if ($cmd == 'list') {
    servList();
}


