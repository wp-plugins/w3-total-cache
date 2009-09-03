<?php

/**
 * W3 Config object
 */

/**
 * Class W3_Config
 */
class W3_Config
{
    /**
     * Tabs count
     *
     * @var integer
     */
    var $_tabs = 0;
    
    /**
     * Array of config values
     *
     * @var array
     */
    var $_config = array();
    
    /**
     * Config keys
     */
    var $_keys = array(
        'dbcache.enabled' => 'boolean', 
        'dbcache.debug' => 'boolean', 
        'dbcache.engine' => 'string', 
        'dbcache.memcached.engine' => 'string', 
        'dbcache.memcached.servers' => 'array', 
        'dbcache.reject.admin' => 'boolean', 
        'dbcache.reject.uri' => 'array', 
        'dbcache.reject.cookie' => 'array', 
        'dbcache.reject.sql' => 'array', 
        'dbcache.lifetime' => 'integer', 
        
        'pgcache.enabled' => 'boolean', 
        'pgcache.debug' => 'boolean', 
        'pgcache.engine' => 'string', 
        'pgcache.memcached.engine' => 'string', 
        'pgcache.memcached.servers' => 'array', 
        'pgcache.lifetime' => 'integer', 
        'pgcache.compress' => 'boolean', 
        'pgcache.cache.logged' => 'boolean', 
        'pgcache.cache.query' => 'boolean', 
        'pgcache.cache.home' => 'boolean', 
        'pgcache.cache.feed' => 'boolean', 
        'pgcache.cache.404' => 'boolean', 
        'pgcache.cache.flush' => 'boolean', 
        'pgcache.cache.headers' => 'array', 
        'pgcache.accept.files' => 'array', 
        'pgcache.reject.uri' => 'array', 
        'pgcache.reject.ua' => 'array', 
        'pgcache.reject.cookie' => 'array', 
        'pgcache.mobile.check' => 'boolean', 
        'pgcache.mobile.whitelist' => 'array', 
        'pgcache.mobile.browsers' => 'array', 
        
        'minify.enabled' => 'boolean', 
        'minify.debug' => 'boolean', 
        'minify.engine' => 'string', 
        'minify.memcached.engine' => 'string', 
        'minify.memcached.servers' => 'array', 
        'minify.rewrite' => 'boolean', 
        'minify.logger' => 'boolean', 
        'minify.cache.path' => 'string', 
        'minify.cache.locking' => 'string', 
        'minify.docroot' => 'string', 
        'minify.fixtime' => 'integer', 
        'minify.compress' => 'boolean', 
        'minify.compress.ie6' => 'boolean', 
        'minify.options' => 'array', 
        'minify.symlinks' => 'array', 
        'minify.lifetime' => 'integer', 
        'minify.html.enable' => 'boolean', 
        'minify.html.strip.crlf' => 'boolean', 
        'minify.html.reject.admin' => 'boolean', 
        'minify.css.enable' => 'boolean', 
        'minify.css.strip.comments' => 'boolean', 
        'minify.css.strip.crlf' => 'boolean', 
        'minify.css.groups' => 'array', 
        'minify.js.enable' => 'boolean', 
        'minify.js.combine.header' => 'boolean', 
        'minify.js.combine.footer' => 'boolean', 
        'minify.js.strip.comments' => 'boolean', 
        'minify.js.strip.crlf' => 'boolean', 
        'minify.js.groups' => 'array', 
        
        'cdn.enabled' => 'boolean', 
        'cdn.debug' => 'boolean', 
        'cdn.engine' => 'string', 
        'cdn.domain' => 'string', 
        'cdn.includes.enable' => 'boolean', 
        'cdn.includes.files' => 'string', 
        'cdn.theme.enable' => 'boolean', 
        'cdn.theme.files' => 'string', 
        'cdn.minify.enable' => 'boolean', 
        'cdn.custom.enable' => 'boolean', 
        'cdn.custom.files' => 'array', 
        'cdn.import.external' => 'boolean', 
        'cdn.import.files' => 'string', 
        'cdn.limit.queue' => 'integer', 
        'cdn.ftp.host' => 'string', 
        'cdn.ftp.user' => 'string', 
        'cdn.ftp.pass' => 'string', 
        'cdn.ftp.path' => 'string', 
        'cdn.ftp.pasv' => 'boolean', 
        
        'common.support.enabled' => 'boolean', 
        'common.support.type' => 'string', 
        
        'notes.defaults' => 'boolean', 
    	'notes.wp_content_perms' => 'boolean',
    	'notes.cdn_first_time' => 'boolean', 
        'notes.no_memcached_nor_apc' => 'boolean', 
    );
    
    /**
     * Returns config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get($key, $default = null)
    {
        if (isset($this->_keys[$key]) && array_key_exists($key, $this->_config)) {
            return $this->_config[$key];
        }
        
        return $default;
    }
    
    /**
     * Returns string value
     *
     * @param string $key
     * @param string $default
     * @param boolean $trim
     * @return string
     */
    function get_string($key, $default = '', $trim = true)
    {
        $value = (string) $this->get($key, $default);
        
        return ($trim ? trim($value) : $value);
    }
    
    /**
     * Returns integer value
     *
     * @param string $key
     * @param integer $default
     * @return integer
     */
    function get_integer($key, $default = 0)
    {
        return (integer) $this->get($key, $default);
    }
    
    /**
     * Returns boolean value
     *
     * @param string $key
     * @param boolean $default
     * @return boolean
     */
    function get_boolean($key, $default = false)
    {
        return (boolean) $this->get($key, $default);
    }
    
    /**
     * Returns array value
     *
     * @param string $key
     * @param array $default
     * @return array
     */
    function get_array($key, $default = array())
    {
        return (array) $this->get($key, $default);
    }
    
    /**
     * Sets config value
     *
     * @param string $key
     * @param string $value
     */
    function set($key, $value)
    {
        if (isset($this->_keys[$key])) {
            $type = $this->_keys[$key];
            settype($value, $type);
            $this->_config[$key] = $value;
        }
        
        return false;
    }
    
    /**
     * Flush config
     */
    function flush()
    {
        $this->_config = array();
    }
    
    /**
     * Reads config from file
     *
     * @param string $file
     * @return array
     */
    function read($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $config = include $file;
            
            if (! is_array($config)) {
                return false;
            }
            
            foreach ($config as $key => $value) {
                $this->set($key, $value);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Reads config from request
     */
    function read_request()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        foreach ($this->_keys as $key => $type) {
            $request_key = str_replace('.', '_', $key);
            
            if (! isset($_REQUEST[$request_key])) {
                continue;
            }
            
            switch ($type) {
                case 'string':
                    $this->set($key, W3_Request::get_string($request_key));
                    break;
                
                case 'int':
                case 'integer':
                    $this->set($key, W3_Request::get_integer($request_key));
                    break;
                
                case 'float':
                case 'double':
                    $this->set($key, W3_Request::get_double($request_key));
                    break;
                
                case 'bool':
                case 'boolean':
                    $this->set($key, W3_Request::get_boolean($request_key));
                    break;
                
                case 'array':
                    $this->set($key, W3_Request::get_array($request_key));
                    break;
            }
        }
    }
    
    /**
     * Writes config
     *
     * @param string $file
     * @return boolean
     */
    function write($file)
    {
        @$fp = fopen($file, 'w');
        if (! $fp) {
            return false;
        }
        
        @fputs($fp, "<?php\r\n\r\nreturn array(\r\n");
        
        $this->_tabs = 1;
        foreach ($this->_config as $key => $value) {
            $this->_write($fp, $key, $value);
        }
        
        @fputs($fp, ");");
        @fclose($fp);
        
        return true;
    }
    
    /**
     * Writes config pair
     *
     * @param resource $fp
     * @param string $key
     * @param mixed $value
     */
    function _write($fp, $key, $value)
    {
        @fputs($fp, str_repeat("\t", $this->_tabs));
        
        if (is_string($key)) {
            @fputs($fp, sprintf("'%s' => ", addslashes($key)));
        }
        
        switch (gettype($value)) {
            case 'object':
            case 'array':
                @fputs($fp, "array(\r\n");
                ++ $this->_tabs;
                foreach ((array) $value as $k => $v) {
                    $this->_write($fp, $k, $v);
                }
                -- $this->_tabs;
                @fputs($fp, sprintf("%s),\r\n", str_repeat("\t", $this->_tabs)));
                return;
            
            case 'integer':
                $data = (string) $value;
                break;
            
            case 'double':
                $data = (string) $value;
                break;
            
            case 'boolean':
                $data = ($value ? 'true' : 'false');
                break;
            
            case 'NULL':
                $data = 'null';
                break;
            
            default:
            case 'string':
                $data = "'" . addslashes((string) $value) . "'";
                break;
        }
        
        @fputs($fp, $data . ",\r\n");
    }
    
    /**
     * Loads config
     * 
     * @return boolean
     */
    function load()
    {
        return $this->read(W3TC_CONFIG_PATH);
    }
    
    /**
     * Loads default config
     * 
     * @return boolean
     */
    function load_default()
    {
        return $this->read(W3TC_CONFIG_DEFAULT_PATH);
    }
    
    /**
     * Saves config
     * 
     * @return boolean
     */
    function save()
    {
        return $this->write(W3TC_CONFIG_PATH);
    }
    
    /**
     * Returns config instance
     *
     * @param boolean $check_config
     * @return W3_Config
     */
    function &instance($check_config = true)
    {
        static $instance = null;
        
        if ($instance === null) {
            $class = __CLASS__;
            $instance = & new $class();
            
            if (! $instance->load_default()) {
                die(sprintf('<strong>W3 Total Cache Error:</strong> Unable to read default config file <strong>%s</strong> or it is broken. Please re-install plugin.', W3TC_CONFIG_DEFAULT_PATH));
            }
            
            if (! $instance->load() && $check_config) {
                die(sprintf('<strong>W3 Total Cache Error:</strong> Unable to read config file or it is broken. Please create <strong>%s</strong> from <strong>%s</strong>.', W3TC_CONFIG_PATH, W3TC_CONFIG_DEFAULT_PATH));
            }
        }
        
        return $instance;
    }
}
