Swoole应用框架

src  项目部署目录
├─app                   应用目录
│  ├─test               test应用目录
│  │  ├─TestServer.php  应用业务文件
│  │  └─ ...            更多业务文件
│  ├─watch              watch应用目录
│  │  ├─WatchServer.php 应用业务文件
│  │  └─ ...            更多业务文件
│  └─ ...
│
├─lib                   框架类库
│  ├─Functions.php      常用操作函数
│  └─ ...               更多
│
├─db                    数据库目录
│  ├─MongoServer.php    Mongo数据库操作类
│  ├─RedisServer.php    Redis数据库操作类
│  └─MysqlServer.php    Mysql数据库操作类
│  └─ ...               可扩展更多操作类
│
├─log                   系统日志目录
│  ├─File.php           文件日志操作类
│  ├─Db.php             数据库日志操作类
│  └─ ...
│
├─support               框架系统目录
│  ├─Socket.php         Socket基类
│  ├─Task.php           任务基类
│  └─ ...
├─tmp                   临时文件目录
├─Autoload.php          自动加载类
├─config.json           应用config配置文件
├─README.md             README 文件
├─swoole.php            命令管理文件
└─...

## 使用说明 ##

1.环境要求
需安装swoole扩展

2.应用配置
打开config.json文件，添加相应的应用配置即可

3.管理命令
php swoole.php -n 应用名称[test|watch] -d start|restart|stop|status|list

