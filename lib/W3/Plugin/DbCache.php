<?php

/**
 * W3 DbCache plugin
 */
require_once dirname(__FILE__) . '/../Plugin.php';

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
        register_activation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        if ($this->_config->get_boolean('dbcache.debug')) {
            add_action('wp_footer', array(
                &$this, 
                'footer'
            ));
        }
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_DbCache
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
    
    /**
     * Activate plugin action
     */
    function activate()
    {
        @copy(W3_PLUGIN_CONTENT_DIR . '/db.php', WP_CONTENT_DIR . '/db.php');
        @chmod(WP_CONTENT_DIR . '/db.php', 0666);
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        @unlink(WP_CONTENT_DIR . '/db.php');
    }
    
    /**
     * Footer action - show stats
     */
    function footer()
    {
        global $wpdb;
        
        $hostname = gethostbyaddr($_SERVER['SERVER_ADDR']);
        
        if (is_a($wpdb, 'W3_Db')) {
            echo '<div style="text-align: left;font-size: 10px;">';
            echo sprintf("Served from: %s<br />\n", $hostname);
            echo sprintf("Total queries: %d<br />\n", $wpdb->query_total);
            echo sprintf("Cached queries: %d<br />\n", $wpdb->query_hits);
            echo sprintf("Total query time: %.4f<br />\n", $wpdb->time_total);
            echo sprintf("Storage: %s<br />\n", $this->_config->get_string('dbcache.engine', 'N/A'));
            echo "<br />\n";
            foreach ((array) $wpdb->query_stats as $index => $query) {
                echo sprintf("<div style=\"color: %s\">%d) Query: %s, Caching/Cached: %d/%d, Time: %.4f</div>\n", ($query['caching'] && $query['cached'] ? '#0c0' : ($query['caching'] ? '#f90' : '#f00')), $index + 1, $query['query'], $query['caching'], $query['cached'], $query['time_total']);
            }
            echo '</div>';
        }
    }
}
