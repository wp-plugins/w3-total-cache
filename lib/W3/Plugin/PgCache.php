<?php

/**
 * W3 PgCache plugin
 */
require_once dirname(__FILE__) . '/../Plugin.php';

/**
 * Class W3_Plugin_PgCache
 */
class W3_Plugin_PgCache extends W3_Plugin
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
        
        if ($this->_config->get_boolean('pgcache.enabled')) {
            add_action('publish_phone', array(
                &$this, 
                'on_post_change'
            ));
            
            add_action('publish_post', array(
                &$this, 
                'on_post_change'
            ));
            
            add_action('edit_post', array(
                &$this, 
                'on_post_change'
            ));
            
            add_action('delete_post', array(
                &$this, 
                'on_post_change'
            ));
            
            add_action('comment_post', array(
                &$this, 
                'on_comment_change'
            ));
            
            add_action('edit_comment', array(
                &$this, 
                'on_comment_change'
            ));
            
            add_action('delete_comment', array(
                &$this, 
                'on_comment_change'
            ));
            
            add_action('wp_set_comment_status', array(
                &$this, 
                'on_comment_status'
            ), 1, 2);
            
            add_action('trackback_post', array(
                &$this, 
                'on_comment_change'
            ));
            
            add_action('pingback_post', array(
                &$this, 
                'on_comment_change'
            ));
            
            add_action('switch_theme', array(
                &$this, 
                'on_change'
            ));
            
            add_action('edit_user_profile_update', array(
                &$this, 
                'on_change'
            ));
        }
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_PgCache
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
        if (! $this->update_wp_config()) {
            die('<strong>' . ABSPATH . 'wp-config.php</strong> could not be written, please edit config and add:<br /><strong style="color:#f00;">define(\'WP_CACHE\', true);</strong> before <strong style="color:#f00;">require_once(ABSPATH . \'wp-settings.php\');</strong><br />then re-activate plugin.');
        }
        
        if (! $this->locked()) {
            if (@copy(W3_PLUGIN_CONTENT_DIR . '/advanced-cache.php', WP_CONTENT_DIR . '/advanced-cache.php')) {
                @chmod(WP_CONTENT_DIR . '/advanced-cache.php', 0666);
            } else {
                w3_writable_error(WP_CONTENT_DIR . '/advanced-cache.php');
            }
        }
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        if (! $this->locked()) {
            @unlink(WP_CONTENT_DIR . '/advanced-cache.php');
        }
    }
    
    /**
     * Updates WP config
     *
     * @return boolean
     */
    function update_wp_config()
    {
        static $updated = false;
        
        if (defined('WP_CACHE') || $updated) {
            return true;
        }
        
        if (! ($config_data = @file_get_contents(ABSPATH . 'wp-config.php'))) {
            return false;
        }
        
        if (! ($fp = @fopen(ABSPATH . 'wp-config.php', 'w'))) {
            return false;
        }
        
        $config_data = preg_replace('~<\?(php)?~', "\\0\r\n/** Enable W3 Total Cache **/\r\ndefine('WP_CACHE', true); // Added by W3 Total Cache\r\n", $config_data, 1);
        
        @fputs($fp, $config_data);
        @fclose($fp);
        
        $updated = true;
        
        return true;
    }
    
    /**
     * Post edit action
     *
     * @param integer $post_id
     */
    function on_post_edit($post_id)
    {
        if ($this->_config->get_boolean('pgcache.cache.flush', false)) {
            $this->on_change();
        } else {
            $this->on_post_change($post_id);
        }
    }
    
    /**
     * Post change action
     *
     * @param integer $post_id
     */
    function on_post_change($post_id)
    {
        static $flushed_posts = array();
        
        if (! in_array($post_id, $flushed_posts)) {
            $w3_pgcache = W3_PgCache::instance();
            $w3_pgcache->flush_post($post_id);
            
            $flushed_posts[] = $post_id;
        }
    }
    
    /**
     * Comment change action
     *
     * @param integer $comment_id
     */
    function on_comment_change($comment_id)
    {
        $post_id = 0;
        
        if ($comment_id) {
            $comment = get_comment($comment_id, ARRAY_A);
            $post_id = ! empty($comment['comment_post_ID']) ? (int) $comment['comment_post_ID'] : 0;
        }
        
        $this->on_post_change($post_id);
    }
    
    /**
     * Comment status action
     *
     * @param integer $comment_id
     * @param string $status
     */
    function on_comment_status($comment_id, $status)
    {
        if (preg_match('@wp-admin@', $_SERVER['REQUEST_URI']) && ($status === 'approve' || $status === '1')) {
            $this->on_comment_change($comment_id);
        }
    }
    
    /**
     * Cange action 
     */
    function on_change()
    {
        static $flushed = false;
        
        if (! $flushed) {
            $w3_pgcache = W3_PgCache::instance();
            $w3_pgcache->flush();
        }
    }
}
