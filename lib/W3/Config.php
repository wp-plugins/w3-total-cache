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
     * Returns config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_config)) {
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
        $this->_config[$key] = $value;
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
        if (file_exists($file)) {
            $config = include $file;
            
            foreach ($config as $key => $value) {
                $this->set($key, $value);
            }
        }
        
        return false;
    }
    
    /**
     * Reads config from request
     * @param array $keys
     */
    function read_request($keys)
    {
        require_once dirname(__FILE__) . '/Request.php';
        
        foreach ($keys as $key => $type) {
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
        return $this->read(W3_CONFIG_PATH);
    }
    
    /**
     * Loads default config
     * 
     * @return boolean
     */
    function load_default()
    {
        return $this->read(W3_CONFIG_DEFAULT_PATH);
    }
    
    /**
     * Saves config
     * 
     * @return boolean
     */
    function save()
    {
        return $this->write(W3_CONFIG_PATH);
    }
    
    /**
     * Returns config instance
     *
     * @return W3_Config
     */
    function &instance()
    {
        static $instance;
        
        if (! $instance) {
            $class = __CLASS__;
            $instance = & new $class();
            $instance->load_default();
            $instance->load();
        }
        
        return $instance;
    }
}
