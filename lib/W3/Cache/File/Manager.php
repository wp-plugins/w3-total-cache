<?php

require_once W3TC_LIB_W3_DIR . '/Cache/File.php';

class W3_Cache_File_Manager
{
    var $_cache_dir = '';
    
    function __construct($config = array())
    {
        $this->_cache_dir = (isset($config['cache_dir']) ? trim($config['cache_dir']) : 'cache');
    }
    
    function W3_Cache_File_Manager($config = array())
    {
        $this->__construct($config);
    }
    
    function clean()
    {
        return $this->_clean($this->_cache_dir, true);
    }
    
    function _clean($path, $empty = false)
    {
        $dir = @opendir($path);
        if ($dir) {
            while (($entry = @readdir($dir))) {
                if ($entry != '.' && $entry != '..') {
                    $full_path = $path . '/' . $entry;
                    if (is_dir($full_path)) {
                        $result = $this->_clean($full_path);
                    } elseif (! $this->is_valid($full_path)) {
                        $result = @unlink($full_path);
                    }
                    if (! $result) {
                        @closedir($dir);
                        return false;
                    }
                }
            }
            @closedir($dir);
            if (! $empty) {
                @rmdir($path);
            }
            return true;
        }
        return false;
    }
    
    function is_valid($file)
    {
        $valid = false;
        if (is_readable($file)) {
            $ftime = @filemtime($file);
            if ($ftime) {
                $fp = @fopen($file, 'rb');
                if ($fp) {
                    $expires = @fread($fp, 4);
                    if ($expires !== false) {
                        list (, $expire) = @unpack('L', $expires);
                        $expire = ($expire && $expire <= W3_CACHE_FILE_EXPIRE_MAX ? $expire : W3_CACHE_FILE_EXPIRE_MAX);
                        if ($ftime > (time() - $expire)) {
                            $valid = true;
                        }
                    }
                    @fclose($fp);
                }
            }
        }
        return $valid;
    }
}