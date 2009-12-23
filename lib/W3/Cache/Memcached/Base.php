<?php

/**
 * Base memcached class
 */
require_once W3TC_LIB_W3_DIR . '/Cache/Base.php';

/**
 * Class W3_Cache_Memcached_Base
 */
class W3_Cache_Memcached_Base extends W3_Cache_Base
{
    /**
     * Server config
     *
     * @var array
     */
    var $config = array();
    
    /**
     * PHP5 Constructor
     *
     * @param array $config
     */
    function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * PHP4 Constructor
     *
     * @param array $config
     * @return W3_Cache_Memcached_Base
     */
    function W3_Cache_Memcached_Base($config)
    {
        $this->__construct($config);
    }
    
    /**
     * Inits the engine
     *
     * @abstract
     * @return boolean
     */
    function connect()
    {
        return false;
    }
    
    /**
     * Disconnects from the memcached server
     *
     * @abstract
     * @return bool
     */
    function disconnect()
    {
        return false;
    }
}
