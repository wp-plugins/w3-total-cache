<?php

/**
 * PECL Memcached class
 */
require_once W3TC_LIB_W3_DIR . '/Cache/Memcached/Base.php';

/**
 * Class W3_Cache_Memcached_Native
 */
class W3_Cache_Memcached_Native extends W3_Cache_Memcached_Base
{
    /**
     * Memcache object
     *
     * @var Memcache
     */
    var $_memcache = null;
    
    /**
     * PHP5 constructor
     *
     */
    function __construct($config)
    {
        parent::__construct($config);
        
        $this->_memcache = & new Memcache();
    }
    
    /**
     * PHP4 constructor
     *
     */
    function W3_Cache_Memcached_Native($config)
    {
        $this->__construct($config);
    }
    
    /**
     * Connects to the server
     *
     * @return boolean
     */
    function connect()
    {
        if (! empty($this->config['servers'])) {
            $persistant = isset($this->config['persistant']) ? (boolean) $this->config['persistant'] : false;
            
            foreach ((array) $this->config['servers'] as $server) {
                list ($ip, $port) = explode(':', $server);
                $this->_memcache->addServer(trim($ip), (integer) trim($port), $persistant);
            }
        } else {
            return false;
        }
        
        if (! empty($this->config['compress_threshold'])) {
            $this->_memcache->setCompressThreshold((integer) $this->config['compress_threshold']);
        }
        
        return true;
    }
    
    /**
     * Disconnects from the server
     *
     * @return boolean
     */
    function disconnect()
    {
        return @$this->_memcache->close();
    }
    
    /**
     * Adds data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function add($key, $var, $expire = 0)
    {
        return @$this->_memcache->add($key, $var, false, $expire);
    }
    
    /**
     * Sets data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function set($key, $var, $expire = 0)
    {
        return @$this->_memcache->set($key, $var, false, $expire);
    }
    
    /**
     * Returns data
     *
     * @param string $key
     * @return mixed
     */
    function get($key)
    {
        return @$this->_memcache->get($key);
    }
    
    /**
     * Replaces data
     *
     * @param string $key
     * @param mixed $var
     * @param integer $expire
     * @return boolean
     */
    function replace($key, $var, $expire = 0)
    {
        return @$this->_memcache->replace($key, $var, false, $expire);
    }
    
    /**
     * Deletes data
     *
     * @param string $key
     * @return boolean
     */
    function delete($key)
    {
        return @$this->_memcache->delete($key);
    }
    
    /**
     * Flushes all data
     *
     * @return boolean
     */
    function flush()
    {
        return @$this->_memcache->flush();
    }
}
