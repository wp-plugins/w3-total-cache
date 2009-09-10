<?php

if (! defined('W3_CACHE_FILE_EXPIRE_MAX')) {
    define('W3_CACHE_FILE_EXPIRE_MAX', 864000);
}

/**
 * APC class
 */
require_once W3TC_LIB_W3_DIR . '/Cache/Base.php';

/**
 * Class W3_Cache_File
 */
class W3_Cache_File extends W3_Cache_Base
{
    /**
     * Path to cache dir
     *
     * @var string
     */
    var $_cache_dir = '';
    
    /**
     * Current time
     *
     * @var integer
     */
    var $_time = 0;
    
    /**
     * PHP5 constructor
     *
     * @param array $config
     */
    function __construct($config)
    {
        $this->_cache_dir = isset($config['cache_dir']) ? trim($config['cache_dir']) : 'cache';
        $this->_time = time();
    }
    
    /**
     * PHP4 constructor
     *
     * @return W3_Cache_File
     */
    function W3_Cache_File()
    {
        $this->__construct($config);
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
        if ($this->get($key) === false) {
            return $this->set($key, $var, $expire);
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
        $path = $this->_get_path($key);
        if (($fp = @fopen($path, 'w'))) {
            @fputs($fp, $expire);
            @fputs($fp, @serialize($var));
            @fclose($fp);
            return true;
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
        $path = $this->_get_path($key);
        if (is_readable($path) && ($ftime = @filectime($path)) && ($fp = @fopen($path, 'r')) && ($expirec = @fgetc($fp)) !== false) {
            $expire = (integer) $expirec;
            $expire = ($expire && $expire <= W3_CACHE_FILE_EXPIRE_MAX ? $expire : W3_CACHE_FILE_EXPIRE_MAX);
            if (($ftime + $expire) <= $this->_time) {
                $data = '';
                while (! @feof($fp)) {
                    $data .= @fgets($fp, 4096);
                }
                @fclose($fp);
                return @unserialize($data);
            }
            @fclose($fp);
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
        if ($this->get($key) !== false) {
            return $this->set($key, $var, $expire);
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
        $path = $this->_get_path($key);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return false;
    }
    
    /**
     * Flushes all data
     *
     * @return boolean
     */
    function flush()
    {
    }
    
    /**
     * Returns file path for key
     *
     * @param string $key
     * @return string
     */
    function _get_path($key)
    {
        $hash = md5($key);
        $path = sprintf('%s/%s/%s/%s', $this->_cache_dir, substr($hash, 0, 2), substr($hash, 2, 2), substr($hash, 4, 28));
        $dir = dirname($path);
        if (! is_dir($dir)) {
            w3_mkdir($dir);
        }
        
        return $path;
    }
}
