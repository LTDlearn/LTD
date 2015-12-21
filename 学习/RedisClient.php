<?php

/**
 * Redis 操作类
 * 集成了redisSentinel
 * @author 雷子
 * @package My_Utils
 */

namespace Library\Utils;

use Library\Utils\RedisSentinelClient;
use Library\Utils\Ini;

class RedisClient
{

    private $_redis;
    private $_host;
    private $_port;
    private $_authName;
    private $_auth;
    private static $_client;
    private static $_prefix;

    public function __construct()
    {
        
    }

    public static function getInstance($prefix = 'GUANYE:HONGBAO:')
    {
        if (empty(self::$_client)) {
            self::$_client = new self();
        }

        self::$_prefix = $prefix;
        self::$_client->getRedis();
        return self::$_client;
    }

    /**
     * 获取Redis连接
     */
    public function getRedis()
    {
        //建立redis链接
        $this->_redis = new \Redis();

        //加载Sentinel配置文件
        $redisConfig = Ini::getDefaultConfigInfo('redis.ini', APPLICATION_ENV);
        $this->_host = $redisConfig['master']['host'];
        $this->_port = $redisConfig['master']['port'];
        $this->_authName = $redisConfig['master']['name'];
        $this->_auth = $redisConfig['master']['auth'];

        //实例化Sentinel
        $redisSentinelClient = new RedisSentinelClient($this->_host, $this->_port);
        $redisSentinelClientResult = $redisSentinelClient->get_master_addr_by_name($this->_authName);

        //Sentinel建立连接
        $sentinelConfig = array();
        $sentinelConfig['host'] = array_pop(array_keys($redisSentinelClientResult[0]));
        $sentinelConfig['port'] = $redisSentinelClientResult[0][$sentinelConfig['host']];

        $this->_redis->pconnect($sentinelConfig['host'], $sentinelConfig['port']);
        $this->_redis->auth($this->_auth);
    }

    /**
     * 构建一个字符串
     * @param $key KEY名称
     * @param $value 设置值
     * @param int $timeOut 时间  0表示无过期时间
     * @return bool
     */
    public function set($key, $value, $timeOut = 0)
    {
        $keys = strtoupper(self::$_prefix . $key);
        $retRes = $this->_redis->set($keys, $value);
        if ($timeOut > 0) {
            $retRes = $this->_redis->setex($keys, $timeOut, $value);
        }
        return $retRes;
    }

    /**
     * 搜索Keys
     * @param $keys
     * @return mixed
     */
    public function keys($key)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->keys($keys);
    }

    /**
     * 构建一个列表(先进后去，类似栈)
     * @param $key
     * @param $value
     * @return int
     */
    public function lpush($key, $value)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->lpush($keys, $value);
    }

    public function lpop($key)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->lpop($keys);
    }

    /**
     * 构建一个列表(先进先去，类似队列)
     * @param $key $key KEY名称
     * @param $value $value 值
     * @return int
     */
    public function rpush($key, $value)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->rpush($keys, $value);
    }

    /**
     * 获取所有列表数据（从头到尾取）
     * @param $key KEY名称
     * @param $head 开始
     * @param $tail 结束
     * @return array
     */
    public function lranges($key, $head, $tail)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->lrange($keys, $head, $tail);
    }

    /**
     * 通过key获取数据
     * @param $key
     * @return bool|string
     */
    public function get($key)
    {
        $keys = strtoupper(self::$_prefix . $key);
        $result = $this->_redis->get($keys);
        return $result;
    }

    /**
     *    set hash opeation
     */
    public function hset($name, $key, $value)
    {
        $names = strtoupper(self::$_prefix . $name);
        if (is_array($value)) {
            $value = serialize($value);
        }
        return $this->_redis->hset($names, $key, $value);
    }

    /**
     *    get hash opeation
     */
    public function hget($name, $key, $serialize = false)
    {
        $names = strtoupper(self::$_prefix . $name);
        $row = $this->_redis->hget($names, $key);
        if ($serialize === true && $row != false) {
            $row = unserialize($row);
        }
        return $row;
    }

    /**
     *    get hash opeation
     */
    public function hgetAll($name)
    {
        $names = strtoupper(self::$_prefix . $name);
        return $this->_redis->hgetAll($names);
    }

    /**
     *    set hash opeation
     */
    public function hmset($key, $field, $timeOut = 0)
    {
        $names = strtoupper(self::$_prefix . $key);
        if ($timeOut > 0) {
            $retRes = $this->_redis->expire($names, $timeOut);
        }
        return $this->_redis->hmset($names, $field);
    }

    /**
     *    get hash len
     */
    public function hlen($key)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->hlen($names);
    }

    /**
     *    add zset opeation
     */
    public function zadd($key, $score, $member)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zadd($names, $score, $member);
    }

    /**
     *    add zset opeation
     */
    public function zcard($key)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zcard($names);
    }

    /**
     *    add zset opeation
     */
    public function zcount($key, $min, $max)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zcount($names, $min, $max);
    }

    /**
     *    add zset opeation
     */
    public function zscore($key, $member)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zscore($names, $member);
    }

    /**
     *    add zset opeation
     */
    public function zincrby($key, $increment, $member)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zincrby($names, $increment, $member);
    }

    /**
     *    add zset opeation
     */
    public function zrange($key, $start, $stop, $withscores = false)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zrange($names, $start, $stop, $withscores);
    }

    /**
     *    add zset opeation
     */
    public function zrevrange($key, $start, $stop, $withscores = false)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zrevrange($names, $start, $stop, $withscores);
    }

    /**
     *    add zset opeation
     */
    public function zrangebyscore($key, $min, $max)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zrangebyscore($names, $min, $max);
    }

    /**
     * Parameters
      key
      start: string
      end: string
      options: array
      Two options are available: withscores => TRUE, and limit => array($offset, $count)
      Return value
      Array containing the values in specified range.
     */
    public function zrevrangebyscore($key, $start, $end)
    {
        $names = strtoupper(self::$_prefix . $key);
        return $this->_redis->zrevrangebyscore($names, $start, $end);
    }

    /**
     * 判断指定的key是否存在
     * @param $key
     * @return bool|string
     */
    public function exists($key)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->exists($keys);
    }

    /**
     * 删除一条数据key
     * @param $key
     */
    public function remove($key)
    {
        $keys = strtoupper(self::$_prefix . $key);
        return $this->_redis->delete($keys);
    }

    /**
     * 清洗（删除）已经存储的所有的元素
     * @access private
     * @return array
     */
    public function flush()
    {
        return $this->_redis->flushall();
    }

}
