<?php

/**
 * W3 CDN Class
 */

if (! defined('W3_CDN_FTP')) {
    define('W3_CDN_FTP', 'ftp');
}

if (! defined('W3_CDN_CF')) {
    define('W3_CDN_CF', 'cf');
}

if (! defined('W3_CDN_S3')) {
    define('W3_CDN_S3', 's3');
}

/**
 * Class W3_Cdn
 */
class W3_Cdn
{
    /**
     * Returns W3_Cdn_Base instance
     *
     * @param string $engine
     * @param array $config
     * @return W3_Cdn_Base
     */
    function &instance($engine, $config = array())
    {
        static $instances = array();
        
        $instance_key = sprintf('%s_%s', $engine, md5(serialize($config)));
        
        if (! isset($instances[$instance_key])) {
            switch ($engine) {
                case W3_CDN_FTP:
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Ftp.php';
                    $instances[$instance_key] = & new W3_Cdn_Ftp($config);
                    break;
                
                case W3_CDN_CF:
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Cf.php';
                    $instances[$instance_key] = & new W3_Cdn_Cf($config);
                    break;
                
                case W3_CDN_S3:
                    require_once W3TC_LIB_W3_DIR . '/Cdn/S3.php';
                    $instances[$instance_key] = & new W3_Cdn_S3($config);
                    break;
                
                default:
                    trigger_error('Incorrect CDN engine', E_USER_WARNING);
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';
                    $instances[$instance_key] = & new W3_Cdn_Base();
                    break;
            }
        }
        
        return $instances[$instance_key];
    }
}
