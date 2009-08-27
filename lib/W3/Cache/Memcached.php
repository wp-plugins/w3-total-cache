<?php

/**
 * Memcached engine API
 */

/**
 * W3 Cache memcached types
 */
if (! defined('W3_CACHE_MEMCACHED_AUTO')) {
    define('W3_CACHE_MEMCACHED_AUTO', 'auto');
}

if (! defined('W3_CACHE_MEMCACHED_NATIVE')) {
    define('W3_CACHE_MEMCACHED_NATIVE', 'native');
}

if (! defined('W3_CACHE_MEMCACHED_CLIENT')) {
    define('W3_CACHE_MEMCACHED_CLIENT', 'client');
}

/**
 * Class W3_Cache_Memcached
 */
class W3_Cache_Memcached
{
    /**
     * Returns memcached engine instance
     *
     * @return W3_Cache_Memcached_Base
     */
    function &instance($engine = W3_CACHE_MEMCACHED_AUTO, $config = array())
    {
        static $instance = null;
        
        if ($instance === null) {
            if ($engine == W3_CACHE_MEMCACHED_AUTO) {
                $engine = (class_exists('Memcache') ? W3_CACHE_MEMCACHED_NATIVE : W3_CACHE_MEMCACHED_CLIENT);
            }
            
            switch ($engine) {
                case W3_CACHE_MEMCACHED_NATIVE:
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached/Native.php';
                    $instance = & new W3_Cache_Memcached_Native($config);
                    break;
                
                case W3_CACHE_MEMCACHED_CLIENT:
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached/Client.php';
                    $instance = & new W3_Cache_Memcached_Client($config);
                    break;
                
                default:
                    trigger_error('Incorrect memcached engine', E_USER_WARNING);
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached/Base.php';
                    $instance = & new W3_Cache_Memcached_Base();
                    break;
            }
            
            $instance->connect();
        }
        
        return $instance;
    }
}
