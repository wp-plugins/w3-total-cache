<?php

/**
 * W3 Plugin base class
 */

/**
 * Class W3_Plugin
 */
class W3_Plugin
{
    /**
     * Config
     *
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * PHP5 Constructor
     */
    function __construct()
    {
        require_once W3TC_LIB_W3_DIR . '/Config.php';
        $this->_config = W3_Config::instance(false);
    }
    
    /**
     * PHP4 Constructor
     *
     * @return W3_Plugin
     */
    function W3_Plugin()
    {
        $this->__construct();
    }
    
    /**
     * Runs plugin
     */
    function run()
    {
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin
     */
    function &instance()
    {
        static $instance = null;
        
        if ($instance === null) {
            $class = __CLASS__;
            $instance = & new $class();
        }
        
        return $instance;
    }
    
    /**
     * Check if plugin is locked
     *
     * @return boolean
     */
    function locked()
    {
        static $locked = null;
        
        if ($locked === null) {
            $locked = false;
            $config = basename(W3TC_CONFIG_PATH);
            $config_dir = dirname(W3TC_CONFIG_PATH);
            
            $dir = @opendir($config_dir);
            
            if ($dir) {
                while (($entry = @readdir($dir))) {
                    if (strpos($entry, W3TC_CONFIG_NAME) === 0 && $entry !== $config) {
                        $locked = true;
                        break;
                    }
                }
                @closedir($dir);
            }
        }
        
        return $locked;
    }
}
