<?php
/*!
 * @class RedisSentinelClient
 * Redis Sentinel 客户端
 * $redis = new RedisSentinelClient('127.0.0.1', 29000);
 * $obj = $redis->get_master_addr_by_name('redis-master');
 */

class My_Server_RedisSentinelClient
{
    protected $_socket;
    protected $_host;
    protected $_port;

    public function __construct($h, $p = 29000) {
        $this->_host = $h;
        $this->_port = $p;
    }
    public function __destruct() {
        if ($this->_socket) {
            $this->_close();
        }
    }

    /*
     * @retval boolean true 连接成功
     * @retval boolean false 连接失敗
     */
    public function ping() {
        if ($this->_connect()) {
            $this->_write('PING');
            $this->_write('QUIT');
            $data = $this->_get();
            $this->_close();
            return ($data === '+PONG');
        } else {
            return false;
        }
    }

    /*!
     * SENTINEL masters 指令
     *
     * @retval array 从服务器返回参数
     * @code
     * array (
     *   [0]  => // master の index
     *     array(
     *       'name' => 'mymaster',
     *       'host' => 'localhost',
     *       'port' => 6379,
     *       ...
     *     ),
     *   ...
     * )
     * @endcode
     */
    public function masters() {
        if($this->_connect()) {
            $this->_write('SENTINEL masters');
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new Exception();
        }
    }

    /*!
     * SENTINEL slaves 发出指令
     *
     * @param [in] $master string  主服务器名称
     * @retval array 从服务器返回参数
     * @code
     * array (
     *   [0]  =>
     *     array(
     *       'name' => 'mymaster',
     *       'host' => 'localhost',
     *       'port' => 6379,
     *       ...
     *     ),
     *   ...
     * )
     * @endcode
     */
    public function slaves($master) {
        if ($this->_connect()) {
            $this->_write('SENTINEL slaves ' . $master);
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new Exception();
        }
    }

    /*!
     * SENTINEL is-master-down-by-addr 发出指令
     *
     * @param [in] $ip   string  对象服务器IP地址
     * @param [in] $port integer 端口号
     * @retval array 从服务器返回参数
     * @code
     * array (
     *   [0]  => 1
     *   [1]  => leader
     * )
     * @endcode
     */
    public function is_master_down_by_addr($ip, $port) {
        if ($this->_connect()) {
            $this->_write('SENTINEL is-master-down-by-addr ' . $ip . ' ' . $port);
            $this->_write('QUIT');
            $data = $this->_get();
            $lines = explode("\r\n", $data, 4);
            list (/* elem num*/, $state, /* length */, $leader) = $lines;
            $this->_close();
            return array(ltrim($state, ':'), $leader);
        } else {
            throw new Exception();
        }
    }

    /*!
     * SENTINEL get-master-addr-by-name 发出指令
     *
     * @param [in] $master string 名称
     * @retval array 从服务器返回参数
     * @code
     * array (
     *   [0]  =>
     *     array(
     *       '<IP ADDR>' => '<PORT>',
     *     )
     * )
     * @endcode
     */
    public function get_master_addr_by_name($master) {
        if ($this->_connect()) {
            $this->_write('SENTINEL get-master-addr-by-name ' . $master);
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new Exception();
        }
    }

    /*!
     * SENTINEL reset 发出指令
     *
     * @param [in] $pattern string 学会名称模式（风格）glob
     * @retval integer pattern 调和了主人的数
     */
    public function reset($pattern) {
        if ($this->_connect()) {
            $this->_write('SENTINEL reset ' . $pattern);
            $this->_write('QUIT');
            $data = $this->_get();
            $this->_close();
            return ltrim($data, ':');
        } else {
            throw new Exception();
        }
    }

    /*!
     * Sentinel 服务器进行连接
     *
     * @retval boolean true  接続成功
     * @retval boolean false 接続失敗
     */
    protected function _connect() {
        $this->_socket = @fsockopen($this->_host, $this->_port, $en, $es);

        return !!($this->_socket);
    }

    /*!
     * Sentinel 服务器连接切断
     *
     * @retval boolean true  切断成功
     * @retval boolean false 切断失敗
     */
    protected function _close() {
        $ret = @fclose($this->_socket);
        $this->_socket = null;
        return $ret;
    }

    /*!
     * Sentinel 从服务器的镰刀。归还。
     *
     * @retval boolean true  有残数据
     * @retval boolean false 残无数据
     */
    protected function _receiving() {
        return !feof($this->_socket);
    }

    /*!
     * Sentinel 对服务器指令发出
     *
     * @param [in] $c string 指令字符串
     * @retval mixed integer 的字节数
     * @retval mixed boolean false 发生错误
     */
    protected function _write($c) {
        return fwrite($this->_socket, $c . "\r\n");
    }

    /*!
     * Sentinel 从服务器的返回值
     *
     * @retval string 返却値
     */
    protected function _get() {
        $buf = '';
        while($this->_receiving()) {
            $buf .= fgets($this->_socket);
        }
        return rtrim($buf, "\r\n+OK\n");
    }

    /*!
     * 多次元階層を表す Redis 响应字符串转换序列
     *
     * @param [in] $data string 从服务器的返回值字符串
     * @retval array 配列1
     */
    protected function _extract($data) {
        if (!$data) return array();
        $lines = explode("\r\n", $data);
        $is_root = $is_child = false;
        $c = count($lines);
        $results = $current = array();
        for ($i = 0; $i < $c; $i++) {
            $str = $lines[$i];
            $prefix = substr($str, 0, 1);
            if ($prefix === '*') {
                if (!$is_root) {
                    $is_root = true;
                    $current = array();
                    continue;
                } else if (!$is_child) {
                    $is_child = true;
                    continue;
                } else {
                    $is_root = $is_child = false;
                    $results[] = $current;
                    continue;
                }
            }
            $keylen = $lines[$i++];
            $key    = $lines[$i++];
            $vallen = $lines[$i++];
            $val    = $lines[$i++];
            $current[$key] = $val;

            --$i;
        }
        $results[] = $current;
        return $results;
    }
}
