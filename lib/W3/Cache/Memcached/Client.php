<?php

/**
 * PHP memcached client
 */
require_once W3TC_LIB_W3_DIR . '/Cache/Memcached/Base.php';
require_once W3TC_LIB_DIR . '/memcached-client.php';

/**
 * Class W3_Cache_Memcached_Client
 */
class W3_Cache_Memcached_Client extends W3_Cache_Memcached_Base
{
    /**
     * Memcached object
     *
     * @var memcached
     */
    var $_memcached = null;
    
    /**
     * Conects to the server
     *
     * @return boolean
     */
    function connect()
    {
        if (empty($this->config['servers'])) {
            return false;
        }
        
        $servers = array();
        
        foreach ($this->config['servers'] as $server) {
            list ($host, $port) = explode(':', $server);
            $servers[] = sprintf('%s:%d', trim($host), trim($port));
        }
        
        $this->_memcached = & new memcached_client(array(
            'servers' => $servers, 
            'persistant' => false, 
            'debug' => false, 
            'compress_threshold' => (! empty($this->config['compress_threshold']) ? (integer) $this->config['compress_threshold'] : 0)
        ));
        
        return true;
    }
    
    /**
     * Disconnects from servers
     *
     * @return boolean
     */
    function disconnect()
    {
        if (is_object($this->_memcached)) {
            $this->_memcached->disconnect_all();
            return true;
        }
        
        return false;
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
        if (is_object($this->_memcached)) {
            return $this->_memcached->add($key, $var, $expire);
        }
        
        return false;
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
        if (is_object($this->_memcached)) {
            return $this->_memcached->set($key, $var, $expire);
        }
        
        return false;
    }
    
    /**
     * Returns data
     *
     * @param string $key
     * @return mixed
     */
    function get($key)
    {
        if (is_object($this->_memcached)) {
            return $this->_memcached->get($key);
        }
        
        return false;
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
        if (is_object($this->_memcached)) {
            return $this->_memcached->replace($key, $var, $expire);
        }
        
        return false;
    }
    
    /**
     * Deletes data
     *
     * @param string $key
     * @return boolean
     */
    function delete($key)
    {
        if (is_object($this->_memcached)) {
            return $this->_memcached->delete($key);
        }
        
        return false;
    }
    
    /**
     * Fluhes all data
     *
     * @todo
     * @return boolean
     */
    function flush()
    {
        if (is_object($this->_memcached)) {
            return $this->_memcached->flush_all();
        }
        
        return false;
    }
}
