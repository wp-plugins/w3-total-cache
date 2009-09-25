<?php

/**
 * W3 Total Cache plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_TotalCache
 */
class W3_Plugin_TotalCache extends W3_Plugin
{
    /**
     * Plugin action
     *
     * @var string
     */
    var $_action = '';
    
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
        
        add_action('admin_menu', array(
            &$this, 
            'admin_menu'
        ));
        
        add_filter('plugin_action_links_' . W3TC_FILE, array(
            &$this, 
            'plugin_action_links'
        ));
        
        add_filter('favorite_actions', array(
            &$this, 
            'favorite_actions'
        ));
        
        add_action('admin_notices', array(
            &$this, 
            'admin_notices'
        ));
        
        if ($this->_config->get_boolean('pgcache.enabled', true) && $this->_config->get_string('pgcache.engine', 'memcached') == 'file') {
            add_action('w3_cache_file_cleanup', array(
                &$this, 
                'cache_file_cleanup'
            ));
        }
        
        if ($this->_config->get_boolean('common.support.enabled', true) && $this->_config->get_string('common.support.type', 'footer') == 'footer') {
            add_action('wp_footer', array(
                &$this, 
                'footer'
            ));
        }
        
        if (is_admin()) {
            add_action('init', array(
                &$this, 
                'init'
            ));
        }
        
        if ($this->can_modify_contents()) {
            ob_start(array(
                &$this, 
                'ob_callback'
            ));
        }
        
        /**
         * Run DbCache plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/DbCache.php';
        $w3_plugin_dbcache = W3_Plugin_DbCache::instance();
        $w3_plugin_dbcache->run();
        
        /**
         * Run PgCache plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/PgCache.php';
        $w3_plugin_pgcache = W3_Plugin_PgCache::instance();
        $w3_plugin_pgcache->run();
        
        /**
         * Run CDN plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/Cdn.php';
        $w3_plugin_cdn = W3_Plugin_Cdn::instance();
        $w3_plugin_cdn->run();
        
        /**
         * Run Minify plugin
         */
        require_once W3TC_DIR . '/lib/W3/Plugin/Minify.php';
        $w3_plugin_minify = W3_Plugin_Minify::instance();
        $w3_plugin_minify->run();
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_TotalCache
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
            if (! is_dir(W3TC_CONTENT_DIR)) {
                if (@mkdir(W3TC_CONTENT_DIR, 0755)) {
                    @chmod(W3TC_CONTENT_DIR, 0755);
                } else {
                    w3_writable_error(W3TC_CONTENT_DIR);
                }
            }
            
            if (! is_dir(W3TC_CACHE_FILE_DIR)) {
                if (@mkdir(W3TC_CACHE_FILE_DIR, 0755)) {
                    @chmod(W3TC_CACHE_FILE_DIR, 0755);
                } else {
                    w3_writable_error(W3TC_CACHE_FILE_DIR);
                }
            }
            
            if (! is_dir(W3TC_CACHE_MINIFY_DIR)) {
                if (@mkdir(W3TC_CACHE_MINIFY_DIR, 0755)) {
                    @chmod(W3TC_CACHE_MINIFY_DIR, 0755);
                } else {
                    w3_writable_error(W3TC_CACHE_MINIFY_DIR);
                }
            }
            
            if (! is_dir(W3TC_LOG_DIR)) {
                if (@mkdir(W3TC_LOG_DIR, 0755)) {
                    @chmod(W3TC_LOG_DIR, 0755);
                } else {
                    w3_writable_error(W3TC_LOG_DIR);
                }
            }
        }
        
        if (! file_exists(W3TC_CONFIG_PATH)) {
            if (@copy(W3TC_CONFIG_DEFAULT_PATH, W3TC_CONFIG_PATH)) {
                @chmod(W3TC_CONFIG_PATH, 0644);
            } else {
                w3_writable_error(W3TC_CONFIG_PATH);
            }
        }
        
        wp_schedule_event(time(), 'twicedaily', 'w3_cache_file_cleanup');
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        wp_clear_scheduled_hook('w3_cache_file_cleanup');
        
        @unlink(W3TC_CONFIG_PATH);
        
        if (! $this->locked()) {
            w3_rmdir(W3TC_LOG_DIR);
            w3_rmdir(W3TC_CACHE_MINIFY_DIR);
            w3_rmdir(W3TC_CACHE_FILE_DIR);
            w3_rmdir(W3TC_CONTENT_DIR);
        }
    }
    
    /**
     * Init Action
     */
    function init()
    {
        wp_enqueue_style('w3tc-options', WP_PLUGIN_URL . '/w3-total-cache/inc/css/options.css');
        wp_enqueue_script('w3tc-options', WP_PLUGIN_URL . '/w3-total-cache/inc/js/options.js');
        
        /**
         * Run plugin action
         */
        if (isset($_REQUEST['w3tc_action']) && current_user_can('manage_options')) {
            $action = trim($_REQUEST['w3tc_action']);
            
            if (method_exists($this, $action)) {
                call_user_func(array(
                    &$this, 
                    $action
                ));
                die();
            }
        }
    }
    
    /**
     * Admin menu
     */
    function admin_menu()
    {
        add_options_page('W3 Total Cache', 'W3 Total Cache', 'manage_options', W3TC_FILE, array(
            &$this, 
            'options'
        ));
    }
    
    /**
     * Plugin action links filter
     *
     * @return array
     */
    function plugin_action_links($links)
    {
        array_unshift($links, '<a class="edit" href="options-general.php?page=' . W3TC_FILE . '">Settings</a>');
        
        return $links;
    }
    
    /**
     * favorite_actions filter
     */
    function favorite_actions($actions)
    {
        $actions['options-general.php?page=' . W3TC_FILE . '&amp;flush_all'] = array(
            'Empty Caches', 
            'manage_options'
        );
        
        return $actions;
    }
    
    /**
     * Admin notices action
     */
    function admin_notices()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        $notice = W3_Request::get_string('w3tc_notice');
        $error = W3_Request::get_string('w3tc_error');
        
        if (! empty($notice)) {
            echo sprintf('<div id="message" class="updated fade"><p>%s</p></div>', $notice);
        }
        
        if (! empty($error)) {
            echo sprintf('<div id="message" class="error"><p>%s</p></div>', $error);
        }
    }
    
    /**
     * Does disk cache cleanup
     * @return void
     */
    function cache_file_cleanup()
    {
        require_once W3TC_LIB_W3_DIR . '/Cache/File/Manager.php';
        
        $w3_cache_file_manager = & new W3_Cache_File_Manager(array(
            'cache_dir' => W3TC_CACHE_FILE_DIR
        ));
        
        $w3_cache_file_manager->clean();
    }
    
    /**
     * Footer plugin action
     */
    function footer()
    {
        echo '<div style="text-align: center;">Performance Optimization <a href="http://www.w3-edge.com/wordpress-plugins/" rel="external">WordPress Plugins</a> by W3 EDGE</div>';
    }
    
    /**
     * Options page
     */
    function options()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $config = & $this->_config;
        $errors = array();
        $notes = array();
        
        $tab = W3_Request::get_string('tab');
        
        switch ($tab) {
            case 'general':
            case 'pgcache':
            case 'dbcache':
            case 'minify':
            case 'cdn':
            case 'install':
            case 'faq':
            case 'about':
                break;
            
            default:
                $tab = 'general';
        }
        
        /**
         * Save config
         */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config->read_request();
            
            /**
             * General tab
             */
            if ($tab == 'general') {
                $debug = W3_Request::get_boolean('debug');
                
                $config->set('dbcache.debug', $debug);
                $config->set('pgcache.debug', $debug);
                $config->set('minify.debug', $debug);
                $config->set('cdn.debug', $debug);
            }
            
            /**
             * Minify tab
             */
            if ($tab == 'minify') {
                $js_files = W3_Request::get_array('js_files');
                $css_files = W3_Request::get_array('css_files');
                
                $js_groups = array();
                $css_groups = array();
                
                foreach ($js_files as $group => $locations) {
                    foreach ((array) $locations as $location => $files) {
                        switch ($location) {
                            case 'include':
                                $js_groups[$group][$location]['blocking'] = true;
                                break;
                            case 'include-nb':
                                $js_groups[$group][$location]['blocking'] = false;
                                break;
                            case 'include-footer':
                                $js_groups[$group][$location]['blocking'] = true;
                                break;
                            case 'include-footer-nb':
                                $js_groups[$group][$location]['blocking'] = false;
                                break;
                        }
                        foreach ((array) $files as $file) {
                            if (! empty($file)) {
                                $js_groups[$group][$location]['files'][] = $file;
                            }
                        }
                    }
                }
                
                foreach ($css_files as $group => $locations) {
                    foreach ((array) $locations as $location => $files) {
                        foreach ((array) $files as $file) {
                            if (! empty($file)) {
                                $css_groups[$group][$location]['files'][] = $file;
                            }
                        }
                    }
                }
                
                $config->set('minify.js.groups', $js_groups);
                $config->set('minify.css.groups', $css_groups);
            }
            
            /**
             * Save config
             */
            if ($config->save()) {
                $this->link_delete();
                
                if ($config->get_boolean('common.support.enabled', true) && ($link_category_id = $this->link_get_category_id($config->get_string('common.support.type', 'footer')))) {
                    $this->link_insert($link_category_id);
                }
                
                if ($tab == 'minify' && $config->get_boolean('minify.upload') && $config->get_boolean('cdn.enabled') && $config->get_string('cdn.engine') != 'mirror') {
                    $this->cdn_upload_minify();
                }
                
                if (headers_sent()) {
                    $notes[] = 'Plugin configuration updated successfully.';
                } else {
                    w3_redirect('', 'w3tc_notice=Plugin configuration updated successfully.');
                }
            } else {
                if (headers_sent()) {
                    $errors[] = 'Unable to save plugin configuration: config file is not writeable.';
                } else {
                    w3_redirect('', 'w3tc_error=Unable to save plugin configuration: config file is not writeable.');
                }
            }
        }
        
        $pgcache_enabled = $config->get_boolean('pgcache.enabled');
        $dbcache_enabled = $config->get_boolean('dbcache.enabled');
        $minify_enabled = $config->get_boolean('minify.enabled');
        $cdn_enabled = $config->get_boolean('cdn.enabled');
        
        $enabled = ($pgcache_enabled || $dbcache_enabled || $minify_enabled || $cdn_enabled);
        $debug = ($config->get_boolean('dbcache.debug') || $config->get_boolean('pgcache.debug') || $config->get_boolean('minify.debug') || $config->get_boolean('cdn.debug'));
        
        $check_memcache = $this->check_memcache();
        $check_apc = $this->check_apc();
        
        $pgcache_memcached = ($config->get_string('pgcache.engine') == 'memcached');
        $dbcache_memcached = ($config->get_string('dbcache.engine') == 'memcached');
        $minify_memcached = ($config->get_string('minify.engine') == 'memcached');
        
        $can_empty_memcache = ($pgcache_memcached || $dbcache_memcached || $minify_memcached);
        $can_empty_apc = ($config->get_string('dbcache.engine') == 'apc' || $config->get_string('pgcache.engine') == 'apc' || $config->get_string('minify.engine') == 'apc');
        $can_empty_file = ($config->get_string('pgcache.engine') == 'file' || $config->get_string('minify.engine') == 'file');
        
        /**
         * Flush all caches
         */
        if (isset($_REQUEST['flush_all'])) {
            if ($can_empty_memcache) {
                $this->flush_memcached();
            }
            if ($can_empty_apc) {
                $this->flush_apc();
            }
            if ($can_empty_file) {
                $this->flush_file();
            }
            
            if (headers_sent()) {
                $notes[] = 'All caches emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=All caches emptied successfully.');
            }
        }
        
        /**
         * Flush memcached cache
         */
        if (isset($_REQUEST['flush_memcached'])) {
            if ($can_empty_memcache) {
                $this->flush_memcached();
            }
            
            if (headers_sent()) {
                $notes[] = 'Memcached cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=Memcached cache emptied successfully.');
            }
        }
        
        /**
         * Flush APC cache
         */
        if (isset($_REQUEST['flush_apc'])) {
            if ($can_empty_apc) {
                $this->flush_apc();
            }
            
            if (headers_sent()) {
                $notes[] = 'APC cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=APC cache emptied successfully.');
            }
        }
        
        /**
         * Flish disk cache
         */
        if (isset($_REQUEST['flush_file'])) {
            if ($can_empty_file) {
                $this->flush_file();
            }
            
            if (headers_sent()) {
                $notes[] = 'Disk cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=Disk cache emptied successfully.');
            }
        }
        
        /**
         * Flush page cache
         */
        if (isset($_REQUEST['flush_pgcache'])) {
            $this->flush_pgcache();
            
            if (headers_sent()) {
                $notes[] = 'Page cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=Page cache emptied successfully.');
            }
        }
        
        /**
         * Flush db cache
         */
        if (isset($_REQUEST['flush_dbcache'])) {
            $this->flush_dbcache();
            
            if (headers_sent()) {
                $notes[] = 'Database cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=Database cache emptied successfully.');
            }
        }
        
        /**
         * Flush minify cache
         */
        if (isset($_REQUEST['flush_minify'])) {
            $this->flush_minify();
            
            if (headers_sent()) {
                $notes[] = 'Minify cache emptied successfully.';
            } else {
                w3_redirect('', 'w3tc_notice=Minify cache emptied successfully.');
            }
        }
        
        /**
         * Hide notes
         */
        if (isset($_REQUEST['hide_note'])) {
            $setting = sprintf('notes.%s', W3_Request::get_string('hide_note'));
            $config->set($setting, false);
            $config->save();
            
            if (! headers_sent()) {
                w3_redirect();
            }
        }
        
        /**
         * Do some checks
         */
        if ($config->get_boolean('notes.defaults')) {
            $notes[] = 'The plugin is in quick setup mode, our recommended defaults are set. Simply satisfy all warnings and enable the plugin to get started or customize all of the settings you wish. <a href="options-general.php?page=' . W3TC_FILE . '&hide_note=defaults">Hide this message</a>';
        }
        
        /**
         * Check wp-content permissions
         */
        if ($config->get_boolean('notes.wp_content_perms')) {
            $wp_content_stat = stat(WP_CONTENT_DIR);
            $wp_content_mode = ($wp_content_stat['mode'] & 0777);
            if ($wp_content_mode != 0755) {
                $notes[] = '<strong>' . WP_CONTENT_DIR . '</strong> is <strong>writeable</strong>! You should change the permissions and make it more restrictive. Use your ftp client, or the following command to fix things: <strong>chmod 755 ' . WP_CONTENT_DIR . '</strong>. <a href="options-general.php?page=' . W3TC_FILE . '&hide_note=wp_content_perms">Hide this message</a>';
            }
        }
        
        /**
         * CDN checks
         */
        if ($tab == 'cdn') {
            if ($config->get('notes.cdn_first_time')) {
                $notes[] = 'It appears this is the first time you are using CDN feature. Unless you wish to first import attachments in your posts that are not already in the media library, please start a <strong>"manual export to <acronym title="Content Delivery Network">CDN</acronym>"</strong> and only enable this module after pending attachments have been successfully uploaded. <a href="options-general.php?page=' . W3TC_FILE . '&hide_note=cdn_first_time">Hide this message</a>';
            }
            
            if ($cdn_enabled && $config->get('cdn.domain') == '') {
                $errors[] = 'The <strong>"Replace domain in URL with"</strong> field must be populated. Enter the hostname of your <acronym title="Content Delivery Network">CDN</acronym> provider. <em>This is the hostname you would enter into your address bar in order to view objects in your browser.</em>';
            }
        }
        
        /**
         * Check for memcached & APC
         */
        if (! $check_memcache && ! $check_apc && $config->get_boolean('notes.no_memcached_nor_apc')) {
            $notes[] = '<strong>Memcached</strong> nor <strong>APC</strong> appear to be installed correctly. <a href="options-general.php?page=' . W3TC_FILE . '&hide_note=no_memcached_nor_apc">Hide this message</a>';
        }
        
        /**
         * Check for PgCache availability
         */
        if ($pgcache_enabled) {
            if (! $this->check_advanced_cache()) {
                $errors[] = '<strong>Page caching</strong> is not available. <strong>advanced-cache.php</strong> is not installed. Either the <strong>' . WP_CONTENT_DIR . '</strong> directory is not write-able or you have another caching plugin installed.';
            } elseif (! defined('WP_CACHE')) {
                $errors[] = '<strong>Page caching</strong> is not available. <strong>WP_CACHE</strong> constant is not defined in wp-config.php.';
            } elseif ($pgcache_memcached && ! $this->is_memcache_available($config->get_array('pgcache.memcached.servers'))) {
                $errors[] = sprintf('<strong>Page caching</strong> is not available.  Memcached server <strong>%s</strong> is not running or is non-responsive.', implode(', ', $config->get_array('pgcache.memcached.servers')));
            }
        }
        
        /**
         * Check for DbCache availability
         */
        if ($dbcache_enabled) {
            if (! $this->check_db()) {
                $errors[] = '<strong>Database caching</strong> is not available. <strong>db.php</strong> is not installed. Either the <strong>' . WP_CONTENT_DIR . '</strong> directory is not write-able or you have another caching plugin installed.';
            } elseif ($dbcache_memcached && ! $this->is_memcache_available($config->get_array('dbcache.memcached.servers'))) {
                $errors[] = sprintf('<strong>Database caching</strong> is not available.  Memcached server <strong>%s</strong> is not running or is non-responsive.', implode(', ', $config->get_array('dbcache.memcached.servers')));
            }
        }
        
        /**
         * Check for minify availability
         */
        if ($minify_enabled && $minify_memcached && ! $this->is_memcache_available($config->get_array('minify.memcached.servers'))) {
            $errors[] = sprintf('<strong>Minify</strong> is not available. Memcached server <strong>%s</strong> is not running or is non-responsive.', implode(', ', $config->get_array('minify.memcached.servers')));
        }
        
        /**
         * Show page
         */
        include W3TC_DIR . '/inc/options/common/header.phtml';
        include W3TC_DIR . '/inc/options/' . $tab . '.phtml';
        include W3TC_DIR . '/inc/options/common/footer.phtml';
    }
    
    /**
     * CDN queue action
     */
    function cdn_queue()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $cdn_queue_action = W3_Request::get_string('cdn_queue_action');
        $cdn_queue_tab = W3_Request::get_string('cdn_queue_tab');
        
        $notes = array();
        
        switch ($cdn_queue_tab) {
            case 'upload':
            case 'delete':
                break;
            
            default:
                $cdn_queue_tab = 'upload';
        }
        
        switch ($cdn_queue_action) {
            case 'delete':
                $cdn_queue_id = W3_Request::get_integer('cdn_queue_id');
                if (! empty($cdn_queue_id)) {
                    $w3_plugin_cdn->queue_delete($cdn_queue_id);
                    $notes[] = 'File successfully deleted from the queue';
                }
                break;
            
            case 'empty':
                $cdn_queue_type = W3_Request::get_integer('cdn_queue_type');
                if (! empty($cdn_queue_type)) {
                    $w3_plugin_cdn->queue_empty($cdn_queue_type);
                    $notes[] = 'Queue successfully emptied';
                }
                break;
        }
        
        $queue = $w3_plugin_cdn->queue_get();
        $title = 'Unsuccessfull transfers queue';
        
        include W3TC_DIR . '/inc/popup/common/header.phtml';
        include W3TC_DIR . '/inc/popup/cdn_queue.phtml';
        include W3TC_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN export library action
     */
    function cdn_export_library()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_attachments_count();
        $title = 'Media library export';
        
        include W3TC_DIR . '/inc/popup/common/header.phtml';
        include W3TC_DIR . '/inc/popup/cdn_export_library.phtml';
        include W3TC_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN export library process
     */
    function cdn_export_library_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->export_library($limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN import library action
     */
    function cdn_import_library()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_import_posts_count();
        $cdn_host = $this->_config->get_string('cdn.domain');
        
        $title = 'Media library import';
        
        include W3TC_DIR . '/inc/popup/common/header.phtml';
        include W3TC_DIR . '/inc/popup/cdn_import_library.phtml';
        include W3TC_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN import library process
     */
    function cdn_import_library_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->import_library($limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{src: '%s', dst: '%s', result: %d, error: '%s'}", addslashes($result['src']), addslashes($result['dst']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN rename domain action
     */
    function cdn_rename_domain()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $total = $w3_plugin_cdn->get_rename_posts_count();
        
        $title = 'Domain rename tool';
        
        include W3TC_DIR . '/inc/popup/common/header.phtml';
        include W3TC_DIR . '/inc/popup/cdn_rename_domain.phtml';
        include W3TC_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN rename domain process
     */
    function cdn_rename_domain_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $limit = W3_Request::get_integer('limit');
        $offset = W3_Request::get_integer('offset');
        $names = W3_Request::get_array('names');
        
        $count = null;
        $total = null;
        $results = array();
        
        @$w3_plugin_cdn->rename_domain($names, $limit, $offset, $count, $total, $results);
        
        echo sprintf("{limit: %d, offset: %d, count: %d, total: %s, results: [\r\n", $limit, $offset, $count, $total);
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{old: '%s', new: '%s', result: %d, error: '%s'}", addslashes($result['old']), addslashes($result['new']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * CDN export action
     */
    function cdn_export()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $cdn_export_type = W3_Request::get_string('cdn_export_type', 'custom');
        
        switch ($cdn_export_type) {
            case 'includes':
                $title = 'Includes export';
                $files = $w3_plugin_cdn->get_files_includes();
                break;
            
            case 'theme':
                $title = 'Theme export';
                $files = $w3_plugin_cdn->get_files_theme();
                break;
            
            case 'minify':
                $title = 'Minify export';
                $files = $w3_plugin_cdn->get_files_minify();
                break;
            
            default:
            case 'custom':
                $title = 'Custom files export';
                $files = $w3_plugin_cdn->get_files_custom();
                break;
        }
        
        include W3TC_DIR . '/inc/popup/common/header.phtml';
        include W3TC_DIR . '/inc/popup/cdn_export_file.phtml';
        include W3TC_DIR . '/inc/popup/common/footer.phtml';
    }
    
    /**
     * CDN export process
     */
    function cdn_export_process()
    {
        set_time_limit(1000);
        
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        
        $files = W3_Request::get_array('files');
        $upload = array();
        $results = array();
        
        foreach ($files as $file) {
            $upload[$file] = $file;
        }
        
        @$w3_plugin_cdn->upload($upload, false, $results);
        
        echo "{results: [\r\n";
        
        $results_count = count($results);
        foreach ($results as $index => $result) {
            echo sprintf("\t{local_path: '%s', remote_path: '%s', result: %d, error: '%s'}", addslashes($result['local_path']), addslashes($result['remote_path']), addslashes($result['result']), addslashes($result['error']));
            if ($index < $results_count - 1) {
                echo ',';
            }
            echo "\r\n";
        }
        
        echo ']}';
    }
    
    /**
     * Uploads minify files to CDN
     */
    function cdn_upload_minify()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        
        $w3_plugin_cdn = W3_Plugin_Cdn::instance();
        $files = $w3_plugin_cdn->get_files_minify();
        $upload = array();
        
        foreach ($files as $file) {
            $upload[$file] = $file;
        }
        
        return @$w3_plugin_cdn->upload($upload);
    }
    
    /**
     * CDN Test FTP
     */
    function cdn_test_ftp()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        require_once W3TC_LIB_W3_DIR . '/Cdn.php';
        
        $host = W3_Request::get_string('host');
        $user = W3_Request::get_string('user');
        $pass = W3_Request::get_string('pass');
        $path = W3_Request::get_string('path');
        $pasv = W3_Request::get_string('pasv');
        
        $w3_cdn_ftp = & W3_Cdn::instance('ftp', array(
            'host' => $host, 
            'user' => $user, 
            'pass' => $pass, 
            'path' => $path, 
            'pasv' => $pasv
        ));
        
        $error = null;
        
        if ($w3_cdn_ftp->test($error)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = sprintf('Test failed. Error: %s', $error);
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Check if memcache is available
     *
     * @param array $servers
     * @return boolean
     */
    function is_memcache_available($servers)
    {
        static $results = array();
        
        $key = md5(serialize($servers));
        
        if (! isset($results[$key])) {
            require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
            
            $memcached = W3_Cache_Memcached::instance(W3_CACHE_MEMCACHED_AUTO, array(
                'servers' => $servers, 
                'persistant' => false
            ));
            
            $test_string = sprintf('test_' . md5(time()));
            $memcached->set($test_string, $test_string, 60);
            
            $results[$key] = ($memcached->get($test_string) == $test_string);
        }
        
        return $results[$key];
    }
    
    /**
     * Test memcached
     */
    function test_memcached()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        $servers = W3_Request::get_string('servers');
        
        if ($this->is_memcache_available($servers)) {
            $result = true;
            $error = 'Test passed';
        } else {
            $result = false;
            $error = 'Test failed';
        }
        
        echo sprintf('{result: %d, error: "%s"}', $result, addslashes($error));
    }
    
    /**
     * Returns link category ID
     *
     * @param string $support_type
     * @return integer
     */
    function link_get_category_id($support_type)
    {
        $matches = null;
        
        if (preg_match('~^link_category_(\d+)$~', $support_type, $matches)) {
            return $matches[1];
        }
        
        return false;
    }
    
    /**
     * Insert plugin link into Blogroll
     * 
     * @param integer $link_category_id
     */
    function link_insert($link_category_id)
    {
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';
        wp_insert_link(array(
            'link_url' => W3TC_LINK_URL, 
            'link_name' => W3TC_LINK_NAME, 
            'link_category' => array(
                $link_category_id
            )
        ));
    }
    
    /**
     * Deletes plugin link from Blogroll
     */
    function link_delete()
    {
        $bookmarks = get_bookmarks();
        $link_id = 0;
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->link_url == W3TC_LINK_URL) {
                $link_id = $bookmark->link_id;
                break;
            }
        }
        
        if ($link_id) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
            wp_delete_link($link_id);
        }
    }
    
    /**
     * Flush specified cache
     *
     * @param string $type
     */
    function flush($type)
    {
        if ($this->_config->get_string('pgcache.engine') == $type) {
            $this->flush_pgcache();
        }
        
        if ($this->_config->get_string('dbcache.engine') == $type) {
            $this->flush_dbcache();
        }
        
        if ($this->_config->get_string('minify.engine') == $type) {
            $this->flush_minify();
        }
    }
    
    /**
     * Flush memcached cache
     * @return void
     */
    function flush_memcached()
    {
        $this->flush('memcached');
    }
    
    /**
     * Flush APC cache
     * @return void
     */
    function flush_apc()
    {
        $this->flush('apc');
    }
    
    /**
     * Flush file cache
     * @return void
     */
    function flush_file()
    {
        $this->flush('file');
    }
    
    /**
     * Flush page cache
     */
    function flush_pgcache()
    {
        require_once W3TC_DIR . '/lib/W3/PgCache.php';
        $w3_pgcache = W3_PgCache::instance();
        $w3_pgcache->flush();
    }
    
    /**
     * Flush page cache
     */
    function flush_dbcache()
    {
        require_once W3TC_DIR . '/lib/W3/Db.php';
        $w3_db = W3_Db::instance();
        $w3_db->flush_cache();
    }
    
    /**
     * Flush minify cache
     */
    function flush_minify()
    {
        require_once W3TC_DIR . '/lib/W3/Minify.php';
        $w3_minify = W3_Minify::instance();
        $w3_minify->flush();
    }
    
    /**
     * Checks if advanced-cache.php exists
     * 
     * @return boolean
     */
    function check_advanced_cache()
    {
        return (file_exists(WP_CONTENT_DIR . '/advanced-cache.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/advanced-cache.php')) && strstr($script_data, 'W3_PgCache') !== false);
    }
    
    /**
     * Checks if db.php exists
     *
     * @return boolean
     */
    function check_db()
    {
        return (file_exists(WP_CONTENT_DIR . '/db.php') && ($script_data = @file_get_contents(WP_CONTENT_DIR . '/db.php')) && strstr($script_data, 'W3_Db') !== false);
    }
    
    /**
     * Checks Memcache availability
     *
     * @return boolean
     */
    function check_memcache()
    {
        return class_exists('Memcache');
    }
    
    /**
     * Checks APC availability
     *
     * @return boolean
     */
    function check_apc()
    {
        return function_exists('apc_store');
    }
    
    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        global $wpdb;
        
        if (! w3_is_xml($buffer)) {
            return $buffer;
        }
        
        $host = gethostbyaddr($_SERVER['SERVER_ADDR']);
        $date = date('Y-m-d H:i:s');
        
        if ($this->_config->get_boolean('common.support.enabled')) {
            $buffer .= sprintf("\r\n<!-- Served from: %s @ %s by W3 Total Cache -->", $host, $date);
        } else {
            $buffer .= <<<DATA
<!--
This site's performance optimized by W3 Total Cache:

W3 Total Cache improves the user experience of your blog by caching
frequent operations, reducing the weight of various files and providing
transparent content delivery network integration.

Learn more about our WordPress Plugins: http://www.w3-edge.com/wordpress-plugins/
DATA;
            
            $buffer .= "\r\n\r\n";
            
            if ($this->_config->get_boolean('minify.enabled', true)) {
                $buffer .= sprintf("Minified using %s\r\n", w3_get_engine_name($this->_config->get_string('minify.engine')));
            }
            
            if ($this->_config->get_boolean('pgcache.enabled', true)) {
                $buffer .= sprintf("Page Caching using %s\r\n", w3_get_engine_name($this->_config->get_string('pgcache.engine')));
            }
            
            if ($this->_config->get_boolean('dbcache.enabled', true) && is_a($wpdb, 'W3_Db')) {
                $buffer .= sprintf("Database Caching %d/%d queries in %.3f seconds using %s\r\n", $wpdb->query_hits, $wpdb->query_total, $wpdb->time_total, w3_get_engine_name($this->_config->get_string('dbcache.engine')));
            }
            
            if ($this->_config->get_boolean('cdn.enabled', true)) {
                $buffer .= sprintf("Content Delivery Network via %s\r\n", $this->_config->get_string('cdn.domain', 'N/A'));
            }
            
            $buffer .= sprintf("\r\nServed from: %s @ %s -->", $host, $date);
        }
        
        if ($this->_config->get_boolean('dbcache.enabled', true) && $this->_config->get_boolean('dbcache.debug') && is_a($wpdb, 'W3_Db')) {
            $buffer .= "\r\n\r\n" . $wpdb->get_debug_info();
        }
        
        return $buffer;
    }
    
    /**
     * Check if we can do modify contents
     * @return boolean
     */
    function can_modify_contents()
    {
        /**
         * Skip if admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }
        
        /**
         * Skip if doint AJAX
         */
        if (defined('DOING_AJAX')) {
            return false;
        }
        
        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            return false;
        }
        
        /**
         * Skip if APP request
         */
        if (defined('APP_REQUEST')) {
            return false;
        }
        
        /**
         * Skip if XMLRPC request
         */
        if (defined('XMLRPC_REQUEST')) {
            return false;
        }
        
        /**
         * Check request URI
         */
        if (! $this->check_request_uri()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function check_request_uri()
    {
        $reject_uri = array(
            'wp-login', 
            'wp-register'
        );
        
        foreach ($reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        return true;
    }
}
