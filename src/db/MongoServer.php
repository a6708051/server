<?php
/**
 * mongo基础操作类；暂未处理主从及多台情况
 */
namespace server\db;

class MongoServer
{
    private static $instance = NULL;
    public $mongo;
    private $host = '120.76.22.59';
    private $port = '27017';
 
    private $db;
    public $dbname;
    private $table = NULL;
 
    /**
     * 初始化类，得到mongo的实例对象
     */
    private function __construct($host = NULL, $port = NULL, $dbname = NULL, $table = NULL)
    {
 
        if ($dbname === NULL) {
            $this->throwError('集合不能为空！');
        }
 
        //判断是否传递了host和port
        if (!empty($host)) {
            $this->host = $host;
        }
        if (!empty($port)) {
            $this->port = $port;
        }
 
        $this->table = $table;

        $this->mongo = new \MongoClient($this->host . ':' . $this->port);
        $this->dbname = $this->mongo->selectDB($dbname);
        $this->db = $this->dbname->selectCollection($table);

    }
 
    /**
     * 单例模式
     * @return object|null
     */
    public static function getInstance($host = NULL, $port = NULL, $dbname = NULL, $table = NULL)
    {

        if (!(self::$instance instanceof self)) {
            self::$instance = new self($host, $port, $dbname, $table);
        }

        return self::$instance;
    }
 
    /**
     * 插入一条数据
     * @param array $doc
     * @return array
     */
    public function insert($doc = array())
    {
        if (empty($doc)) {
            $this->throwError('插入的数据不能为空！');
            return false;
        }

        //保存数据信息
        $rs = false;
        try {
            if (!$rs = $this->db->insert($doc)) {
                throw new \MongoException('插入数据失败');
            }
        } catch (\MongoException $e) {
            $this->throwError($e->getMessage());
        }
        return $rs;
    }
 
    /**
     * 插入多条数据信息
     * @param array $doc
     */
    public function insertMulti($doc = array())
    {
        if (empty($doc)) {
            $this->throwError('插入的数据不能为空！');
            return;
        }
        //插入数据信息
        foreach ($doc as $key => $val) {
            //判断$val是不是数组
            if (is_array($val)) {
                $this->insert($val);
            }
        }
    }
 
    /**
     * 查找一条记录
     * @return array|null
     */
    public function findOne($query = array())
    {
        $result = array();
        try {
            if ($result = $this->db->findOne($query)) {
                return $result;
            } else {
                throw new \MongoException('查找数据失败');
            }
        } catch (\MongoException $e) {
            $this->throwError($e->getMessage());
        }

        return $result;
    }
 
    /**
     * 查找所有的文档
     * @return array|null
     */
    public function find($query = array())
    {
        $result = array();
        try {
            if ($result = $this->db->find($query)) {

            } else {
                throw new \MongoException('查找数据失败');
            }
        } catch (\MongoException $e) {
            $this->throwError($e->getMessage());
        }
 
        $arr = array();
        foreach ($result as $id => $val) {
            $arr[] = $val;
        }
        return $arr;
    }
 
    /**
     * 获取记录条数
     * @return int
     */
    public function getCount()
    {
        try {
            if ($count = $this->db->count()) {
                return $count;
            } else {
                throw new \MongoException('查找总数失败');
            }
        } catch (\MongoException $e) {
            $this->throwError($e->getMessage());
        }
    }
 
    /**
     * 获取所有的数据库
     * @return array
     */
    public function getDbs()
    {
        return $this->mongo->listDBs();
    }
 
    /**
     * 关闭数据库的链接
     */
    public function closeDb()
    {
        $this->mongo->close(TRUE);
    }
 
    /**
     * 输出错误信息
     * @params $errorInfo 错误内容
     */
    public function throwError($errorInfo='')
    {
        echo "error info：".print_r($errorInfo, true);
    }
 
}