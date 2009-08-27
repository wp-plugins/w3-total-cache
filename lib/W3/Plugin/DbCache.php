<?php

/**
 * W3 DbCache plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_DbCache
 */
class W3_Plugin_DbCache extends W3_Plugin
{
    /**
     * Runs plugin
     */
    function run()
    {
        register_activation_hook(W3TC_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3TC_FILE, array(
            &$this, 
            'deactivate'
        ));
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_DbCache
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
     * Activate plugin action
     */
    function activate()
    {
        if (! $this->locked()) {
            $file_db = WP_CONTENT_DIR . '/db.php';
            
            if (@copy(W3TC_CONTENT_DIR . '/db.php', $file_db)) {
                @chmod($file_db, 0666);
            } else {
                w3_writable_error($file_db);
            }
        }
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        if (! $this->locked()) {
            @unlink(WP_CONTENT_DIR . '/db.php');
        }
    }
}
