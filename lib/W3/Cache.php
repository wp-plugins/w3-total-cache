<?php

/**
 * W3 Cache class
 */

/**
 * W3 Cache engine types
 */
if (! defined('W3_CACHE_MEMCACHED')) {
    define('W3_CACHE_MEMCACHED', 'memcached');
}

if (! defined('W3_CACHE_APC')) {
    define('W3_CACHE_APC', 'apc');
}

/**
 * Class W3_Cache
 */
class W3_Cache
{
    /**
     * Returns cache engine instance
     *
     * @param string $engine
     * @param array $config
     * @return W3_Cache_Base
     */
    function &instance($engine, $config = array())
    {
        static $instances = array();
        
        if (! isset($instances[$engine])) {
            switch ($engine) {
                case W3_CACHE_MEMCACHED:
                    require_once dirname(__FILE__) . '/Cache/Memcached.php';
                    $instances[$engine] = W3_Cache_Memcached::instance($config['engine'], $config);
                    break;
                
                case W3_CACHE_APC:
                    require_once dirname(__FILE__) . '/Cache/Apc.php';
                    $instances[$engine] = & new W3_Cache_Apc();
                    break;
                
                default:
                    trigger_error('Incorrect cache engine', E_USER_WARNING);
                    require_once dirname(__FILE__) . '/Cache/Base.php';
                    $instances[$engine] = & new W3_Cache_Base();
                    break;
            }
        }
        
        return $instances[$engine];
    }
}
