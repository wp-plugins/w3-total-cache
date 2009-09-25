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

if (! defined('W3_CACHE_FILE')) {
    define('W3_CACHE_FILE', 'file');
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
        
        $instance_key = sprintf('%s_%s', $engine, md5(serialize($config)));
        
        if (! isset($instances[$instance_key])) {
            switch ($engine) {
                case W3_CACHE_MEMCACHED:
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
                    $instances[$instance_key] = W3_Cache_Memcached::instance($config['engine'], $config);
                    break;
                
                case W3_CACHE_APC:
                    require_once W3TC_LIB_W3_DIR . '/Cache/Apc.php';
                    $instances[$instance_key] = & new W3_Cache_Apc();
                    break;
                
                case W3_CACHE_FILE:
                    require_once W3TC_LIB_W3_DIR . '/Cache/File.php';
                    $instances[$instance_key] = & new W3_Cache_File($config);
                    break;
                
                default:
                    trigger_error('Incorrect cache engine', E_USER_WARNING);
                    require_once W3TC_LIB_W3_DIR . '/Cache/Base.php';
                    $instances[$instance_key] = & new W3_Cache_Base();
                    break;
            }
        }
        
        return $instances[$instance_key];
    }
}
