<?php
namespace util;

class SessionPredis extends \SessionHandler {

    protected $handler = null;
    protected $options = [
        'scheme'     => 'tcp',
        'host'       => '127.0.0.7',
        'port'       => 6379,
        'pass'   => '',
        'select'     => 0,
        'expire'     => 3600,
        // 'timeout'      => 0, // 超时时间(秒)
        'persistent' => false,
        'prefix'     => '',
        'session_name' => '', // sessionkey前缀
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = []) {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (!$this->options['password']) {
            unset($this->options['password']);
        }
        if (!$this->handler) {
            $this->handler = new \Predis\Client($this->options);
        }
    }

    /**
     * 打开Session
     * @access public
     * @param string $savePath
     * @param mixed  $sessName
     * @return bool
     * @throws Exception
     */
    public function open($savePath, $sessName)
    {
        // 建立连接
        // $func = $this->options['persistent'] ? 'pconnect' : 'connect';

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        // $this->handler->quit();
        $this->handler = null;
        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param string $sessID
     * @return string
     */
    public function read($sessID)
    {
        $session = $this->handler->get($this->options['prefix'] . $this->options['session_name'] . $sessID);
        if ($session) {
            return $session;
        }
        return '';
    }

    /**
     * 写入Session
     * @access public
     * @param string $sessID
     * @param String $sessData
     * @return bool
     */
    public function write($sessID, $sessData)
    {
        if ($this->options['expire'] > 0) {
            return ($this->handler->setex($this->options['prefix'] . $this->options['session_name'] . $sessID, $this->options['expire'], $sessData)->getPayload() == 'OK');

        } else {
            return ($this->handler->set($this->options['prefix'] . $this->options['session_name'] . $sessID, $sessData)->getPayload() == 'OK');
        }
    }

    /**
     * 删除Session
     * @access public
     * @param string $sessID
     * @return bool
     */
    public function destroy($sessID)
    {
        $this->handler->del($this->options['prefix'] . $this->options['session_name'] . $sessID);
        return true;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     * @return bool
     */
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
