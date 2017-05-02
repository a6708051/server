<?php
/**
 * redis基础操作类；暂未处理主从及多台情况
 */
namespace server\db;
use server\lib;

class RedisServer
{
    private static $instance = NULL;
    protected $_REDIS;
    private $_TRANSACTION = NULL;

    private function __construct($host = NULL, $port = NULL)
    {
        $this->_REDIS = new \Redis();
        $this->_REDIS->pconnect($host, $port);
    }

    public static function getInstance($host = NULL, $port = NULL)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($host, $port);
        }
        return self::$instance;
    }

    /**
     * @param $key
     * @param $value
     * @param null $expire
     * @return bool
     * 设置缓存，有则更新
     */
    public function set($key, $value, $expire = NULL)
    {
        $data = $this->dataToJson($value);
        if (empty($expire)) {
            $res = $this->_REDIS->set($key, $data);
        } else {
            $res = $this->_REDIS->setex($key, $expire, $value);
        }
        return $res;
    }

    /**
     * @param null $key （数组 | 单个）
     * @return mixed
     * 获取缓存
     */
    public function get($key)
    {
        $func = is_array($key) ? 'mGet' : 'get';
        $res = $this->_REDIS->{$func}($key);
        return $this->jsonToData($res);
    }

    /**
     * @param $key （数组 | 单个）
     * @return int
     * 删除缓存
     */
    public function del($key)
    {
        $res = $this->_REDIS->del($key);
        return $res;
    }

    //---------------------------------- redis 有序集合 start ----------------------------------------------
    /**
     * @param $key
     * @param $score
     * @param $value
     * @return int
     * 给有序集合中添加数据
     */
    public function zAdd($key, $score, $value)
    {
        $res = $this->_REDIS->zAdd($key, $score, $value);
        return $res;
    }

    /**
     * @param $key
     * @param $value
     * @return int
     * 删除有序集合中的某值
     */
    public function zRem($key, $value)
    {
        $res = $this->_REDIS->zRem($key, $value);
        return $res;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return int
     * 删除有序集合中score在范围中的值
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        $res = $this->_REDIS->zRemRangeByScore($key, $start, $end);
        return $res;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param bool $with_score 为true返回结果带上score：array('value1'=>score1, 'value2'=>score2);
     *                          为false:array('value1', 'value2')
     * @return array
     * 获取有序集合中在范围内的数据
     */
    public function zRevRange($key, $start, $end, $with_score = NULL)
    {
        $res = $this->_REDIS->zRevRange($key, $start, $end, $with_score);
        return $res;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param array $options
     * @return array
     * 返回有序集合中指定分数区间的成员列表。有序集成员按分数值递增(从小到大)次序排列；具有相同分数值的成员按字典序来排列
     */
    public function zRangeByScore($key, $start, $end, array $options = array())
    {
        $res = $this->_REDIS->zRangeByScore($key, $start, $end, $options);
        return $res;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param array $options 两个有效参数：withscores=>true 和 limit=>array($offset, $count)
     * @return array
     * 返回有序集中指定分数区间内的所有的成员。有序集成员按分数值递减(从大到小)的次序排列；具有相同分数值的成员按字典序来排列
     */
    public function zRevRangeByScore($key, $start, $end, array $options = array())
    {
        $res = $this->_REDIS->zRevRangeByScore($key, $start, $end, $options);
        return $res;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return int
     * 返回有序集合中指定分数区间的成员数量
     */
    public function zCount($key, $start, $end)
    {
        $res = $this->_REDIS->zCount($key, $start, $end);
        return $res;
    }

    /**
     * @param $key
     * @return int
     * 返回集合中元素的数量
     */
    public function zCard($key)
    {
        $res = $this->_REDIS->zCard($key);
        return $res;
    }

    /**
     * @param $key
     * @param $value
     * @return int
     * 返回有序集中指定成员的排名
     */
    public function zRank($key, $value)
    {
        $res = $this->_REDIS->zRank($key, $value);
        return $res;
    }
    //---------------------------------- redis 有序集合 end ----------------------------------------------

    //---------------------------------- redis 队列 start ----------------------------------------------
    /**
     * @param $list
     * @param $value
     * @param int $repeat 判断value是否存在，存在则不入队列
     * @return array
     * 数据插入队列左(头)
     */
    public function listLPush($list, $value, $repeat = 0)
    {
        $func = $repeat ? 'lPush' : 'lPushx';
        $data = $this->_REDIS->{$func}($list, $value);
        return $this->jsonToData($data);
    }

    /**
     * @param $list
     * @return bool
     * 左弹出数据
     */
    public function listLPop($list)
    {
        $res = $this->_REDIS->lPop($list);
        return $res;
    }

    /**
     * @param $list
     * @param $value
     * @param int $repeat 判断value是否存在，存在则不入队列
     * @return array
     * 数据插入队列右(尾)
     */
    public function listRPush($list, $value, $repeat = 0)
    {
        $func = $repeat ? 'rPush' : 'rPushx';
        $data = $this->_REDIS->{$func}($list, $value);
        return $this->jsonToData($data);
    }

    /**
     * @param $list
     * @return bool
     * 右弹出数据
     */
    public function listRPop($list)
    {
        $res = $this->_REDIS->rPop($list);
        return $res;
    }

    /**
     * @param $channel
     * @param $msg
     * @return int
     * 发布消息
     */
    public function publish($channel, $msg)
    {
        $res = $this->_REDIS->publish($channel, $msg);
        return $res;
    }

    /**
     * @param $channels array
     * @param $callback
     * 订阅(监听)
     */
    public function subscribe($channels, $callback)
    {
        $this->_REDIS->subscribe($channels, $callback);
    }
    //---------------------------------- redis 队列 end ----------------------------------------------

    //---------------------------------- redis hash操作 start ----------------------------------------------
    /**
     * @param $hash
     * @param $key
     * @param $value
     * @param $update int 0:存在则设置失败；1:存在则更新
     * @return bool
     * 设置hash中的key->value
     */
    public function hashSet($hash, $key, $value, $update = 1)
    {
        $func = $update>0 ? 'hSet' : 'hSetNx';
        $res = $this->_REDIS->{$func}($hash, $key, $value);
        return $res;
    }

    /**
     * @param $hash
     * @param $data array('key'=>'value')
     * @return bool
     * 批量设置hash中的值
     */
    public function hashMSet($hash, $data)
    {
        $res = $this->_REDIS->hMset($hash, $data);
        return $res;
    }

    /**
     * @param $hash
     * @param array $key
     * @return null
     * 获取hash中的值
     */
    public function hashGet($hash, $key = array())
    {
        $func = is_array($key) && !empty($key) ? 'hMGet' : 'hGet';
        $res = $this->_REDIS->{$func}($hash, $key);
        return $res;
    }

    /**
     * @param $hash
     * @param $key
     * @return bool|null
     * 判断hash中key是否存在
     */
    public function hashExists($hash, $key)
    {
        $res = $this->_REDIS->hExists($hash, $key);
        return $res;
    }

    /**
     * @param $hash
     * @param $key
     * @return int
     * 删除hash中的某个key
     */
    public function hashDel($hash, $key)
    {
        $res = $this->_REDIS->hDel($hash, $key);
        return $res;
    }
    //---------------------------------- redis hash操作 end ----------------------------------------------

    //---------------------------------- redis 事务 start -------------------------------------------
    /**
     * 开始事务
     */
    public function tranStart()
    {
        $this->_TRANSACTION = $this->_REDIS->multi();
    }

    /**
     * @return mixed
     * 提交事务
     */
    public function tranCommit()
    {
        return $this->_TRANSACTION->exec();
    }

    /**
     * @return mixed
     * 回滚事务
     */
    public function tranRollback()
    {
        return $this->_TRANSACTION->discard();
    }
    //---------------------------------- redis 事务 end -------------------------------------------

    /**
     * @param $key
     * @param $time
     * @return bool
     * 设置过期时间
     */
    public function setExpire($key, $time)
    {
        $res = $this->_REDIS->expire($key, $time);
        return $res;
    }

    /**
     * 析构函数，释放连接
     */
    public function __destruct()
    {
        $this->_REDIS->close();
    }

    private function dataToJson($data)
    {
        return lib\Functions::dataToJson($data);
    }

    private function jsonToData($data)
    {
        return lib\Functions::jsonToData($data);
    }
}