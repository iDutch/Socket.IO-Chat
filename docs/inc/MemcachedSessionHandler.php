<?php
/**
 * memcacheSessionHandler class
 * @class           memcacheSessionHandler
 * @file            memcacheSessionHandler.class.php
 * @brief           This class is used to store session data with memcache, it store in json the session to be used more easily in Node.JS
 * @version         0.1
 * @date            2012-04-11
 * @author          Deisss
 * @licence         LGPLv3
 * This class is used to store session data with memcache, it store in json the session to be used more easily in Node.JS
 */
class MemcachedSessionHandler implements \SessionHandlerInterface
{

    const DEFAULT_TTL = 1800;
    const DEFAULT_PREFIX = 'memcached-'; 

    private $memCached;
    private $ttl;
    private $prefix;

    /**
     * Constructor
     */
    public function __construct(Memcached $memcached, array $options = array()){
        $this->memCached = $memcached;
        if ($diff = array_diff(array_keys($options), array('prefix', 'expiretime'))) {
            throw new \InvalidArgumentException(sprintf('The following options are not supported "%s"', implode(', ', $diff)));
        }
        $this->ttl = isset($options['expiretime']) ? (int)$options['expiretime'] : self::DEFAULT_TTL;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : self::DEFAULT_PREFIX;
    }

    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime
     * @return boolean True if everything succeed
     */
    public function open($savePath, $sessionName){
        return true;
    }

    /**
     * Read the id
     * @param string $id The SESSID to search for
     * @return string The session saved previously
     */
    public function read($sessionId){
        return $this->memCached->get($this->prefix . $sessionId) ? : '';
    }

    /**
     * Write the session data, convert to json before storing
     * @param string $id The SESSID to save
     * @param string $data The data to store, already serialized by PHP
     * @return boolean True if memcached was able to write the session data
     */
    public function write($sessionId, $data){
        return $this->memCached->set($this->prefix . $sessionId, $data, time() + $this->ttl);
    }

    /**
     * Delete object in session
     * @param string $id The SESSID to delete
     * @return boolean True if memcached was able delete session data
     */
    public function destroy($sessionId){
        return $this->memCached->delete($this->prefix . $sessionId);
    }

    /**
     * Close gc
     * @return boolean Always true
     */
    public function gc($lifetime){
        return true;
    }

    /**
     * Close session
     * @return boolean Always true
     */
    public function close(){
        return true;
    }
}
?>
