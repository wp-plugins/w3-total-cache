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
        require_once dirname(__FILE__) . '/Config.php';
        $this->_config = W3_Config::instance();
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
        
        if (! $instance) {
            $class = __CLASS__;
            $instance = & new $class();
        }
        
        return $instance;
    }
}
