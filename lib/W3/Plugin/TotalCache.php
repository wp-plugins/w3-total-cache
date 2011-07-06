<?php

/**
 * W3 Total Cache plugin
 */
if (!defined('W3TC')) {
    die();
}

require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_TotalCache
 */
class W3_Plugin_TotalCache extends W3_Plugin {
    /**
     * Runs plugin
     *
     * @return void
     */
    function run() {
        register_activation_hook(W3TC_FILE, array(
            &$this,
            'activate'
        ));

        register_deactivation_hook(W3TC_FILE, array(
            &$this,
            'deactivate'
        ));

        add_action('init', array(
            &$this,
            'init'
        ));

        add_action('admin_bar_menu', array(
            &$this,
            'admin_bar_menu'
        ), 150);

        if (isset($_REQUEST['w3tc_theme']) && isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == W3TC_POWERED_BY) {
            add_filter('template', array(
                &$this,
                'template_preview'
            ));

            add_filter('stylesheet', array(
                &$this,
                'stylesheet_preview'
            ));
        } elseif ($this->_config->get_boolean('mobile.enabled') || $this->_config->get_boolean('referrer.enabled')) {
            add_filter('template', array(
                &$this,
                'template'
            ));

            add_filter('stylesheet', array(
                &$this,
                'stylesheet'
            ));
        }

        /**
         * CloudFlare support
         */
        if ($this->_config->get_boolean('cloudflare.enabled')) {
            add_action('wp_set_comment_status', array(
                &$this,
                'cloudflare_set_comment_status'
            ), 1, 2);

            require_once W3TC_LIB_W3_DIR . '/CloudFlare.php';
            $w3_cloudflare =& new W3_CloudFlare();

            $w3_cloudflare->fix_remote_addr();

        }

        if ($this->_config->get_string('common.support') == 'footer') {
            add_action('wp_footer', array(
                &$this,
                'footer'
            ));
        }

        if ($this->can_ob()) {
            ob_start(array(
                &$this,
                'ob_callback'
            ));
        }

        /**
         * Run DbCache plugin
         */
        require_once W3TC_LIB_W3_DIR . '/Plugin/DbCache.php';
        $w3_plugin_dbcache = & W3_Plugin_DbCache::instance();
        $w3_plugin_dbcache->run();

        /**
         * Run ObjectCache plugin
         */
        require_once W3TC_LIB_W3_DIR . '/Plugin/ObjectCache.php';
        $w3_plugin_objectcache = & W3_Plugin_ObjectCache::instance();
        $w3_plugin_objectcache->run();

        /**
         * Run PgCache plugin
         */
        require_once W3TC_LIB_W3_DIR . '/Plugin/PgCache.php';
        $w3_plugin_pgcache = & W3_Plugin_PgCache::instance();
        $w3_plugin_pgcache->run();

        /**
         * Run CDN plugin
         */
        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';
        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
        $w3_plugin_cdn->run();

        /**
         * Run BrowserCache plugin
         */
        require_once W3TC_LIB_W3_DIR . '/Plugin/BrowserCache.php';
        $w3_plugin_browsercache = & W3_Plugin_BrowserCache::instance();
        $w3_plugin_browsercache->run();

        /**
         * Run Minify plugin
         */
        if (W3TC_PHP5) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
            $w3_plugin_minify = & W3_Plugin_Minify::instance();
            $w3_plugin_minify->run();
        }

        /**
         * Run admin plugin
         */
        if (is_admin()) {
            require_once W3TC_LIB_W3_DIR . '/Plugin/TotalCacheAdmin.php';
            $w3_plugin_totalcacheadmin = & W3_Plugin_TotalCacheAdmin::instance();
            $w3_plugin_totalcacheadmin->run();
        }
    }

    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_TotalCache
     */
    function &instance() {
        static $instances = array();

        if (!isset($instances[0])) {
            $class = __CLASS__;
            $instances[0] = & new $class();
        }

        return $instances[0];
    }

    /**
     * Activate plugin action
     *
     * @return void
     */
    function activate() {
        /**
         * Disable buggy sitewide activation in WPMU and WP 3.0
         */
        if ((w3_is_wpmu() && isset($_GET['sitewide'])) || (w3_is_multisite() && isset($_GET['networkwide']))) {
            w3_network_activate_error();
        }

        /**
         * Check installation files
         */
        $files = array(
            W3TC_INSTALL_FILE_ADVANCED_CACHE,
            W3TC_INSTALL_FILE_DB,
            W3TC_INSTALL_FILE_OBJECT_CACHE
        );

        $nonexistent_files = array();

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $nonexistent_files[] = $file;
            }
        }

        if (count($nonexistent_files)) {
            $error = sprintf('Unfortunately core file(s): (<strong>%s</strong>) are missing, so activation will fail. Please re-start the installation process from the beginning.', implode(', ', $nonexistent_files));

            w3_activate_error($error);
        }

        if (!@is_dir(W3TC_CONTENT_DIR) && !@mkdir(W3TC_CONTENT_DIR)) {
            w3_writable_error(W3TC_CONTENT_DIR);
        }

        if (!@is_dir(W3TC_CACHE_FILE_DBCACHE_DIR) && !@mkdir(W3TC_CACHE_FILE_DBCACHE_DIR)) {
            w3_writable_error(W3TC_CACHE_FILE_DBCACHE_DIR);
        }

        if (!@is_dir(W3TC_CACHE_FILE_OBJECTCACHE_DIR) && !@mkdir(W3TC_CACHE_FILE_OBJECTCACHE_DIR)) {
            w3_writable_error(W3TC_CACHE_FILE_OBJECTCACHE_DIR);
        }

        if (!@is_dir(W3TC_CACHE_FILE_PGCACHE_DIR) && !@mkdir(W3TC_CACHE_FILE_PGCACHE_DIR)) {
            w3_writable_error(W3TC_CACHE_FILE_PGCACHE_DIR);
        }

        if (!@is_dir(W3TC_CACHE_FILE_MINIFY_DIR) && !@mkdir(W3TC_CACHE_FILE_MINIFY_DIR)) {
            w3_writable_error(W3TC_CACHE_FILE_MINIFY_DIR);
        }

        if (!@is_dir(W3TC_LOG_DIR) && !@mkdir(W3TC_LOG_DIR)) {
            w3_writable_error(W3TC_LOG_DIR);
        }

        if (!@is_dir(W3TC_TMP_DIR) && !@mkdir(W3TC_TMP_DIR)) {
            w3_writable_error(W3TC_TMP_DIR);
        }

        if (w3_is_network() && file_exists(W3TC_CONFIG_MASTER_PATH)) {
            /**
             * For multisite load master config
             */
            $this->_config->load_master();

            if (!$this->_config->save(false)) {
                w3_writable_error(W3TC_CONFIG_PATH);
            }
        } elseif (!file_exists(W3TC_CONFIG_PATH)) {
            /**
             * Set default settings
             */
            $this->_config->set_defaults();

            /**
             * If config doesn't exist enable preview mode
             */
            if (!$this->_config->save(true)) {
                w3_writable_error(W3TC_CONFIG_PREVIEW_PATH);
            }
        }

        /**
         * Save blognames into file
         */
        if (w3_is_network() && !w3_is_subdomain_install()) {
            if (!w3_save_blognames()) {
                w3_writable_error(W3TC_BLOGNAMES_PATH);
            }
        }

        delete_option('w3tc_request_data');
        add_option('w3tc_request_data', '', null, 'no');
    }

    /**
     * Deactivate plugin action
     *
     * @return void
     */
    function deactivate() {
        delete_option('w3tc_request_data');

        // keep for other blogs
        if (!$this->locked()) {
            @unlink(W3TC_BLOGNAMES_PATH);
        }

        @unlink(W3TC_CONFIG_PREVIEW_PATH);

        w3_rmdir(W3TC_TMP_DIR);
        w3_rmdir(W3TC_LOG_DIR);
        w3_rmdir(W3TC_CACHE_FILE_MINIFY_DIR);
        w3_rmdir(W3TC_CACHE_FILE_PGCACHE_DIR);
        w3_rmdir(W3TC_CACHE_FILE_DBCACHE_DIR);
        w3_rmdir(W3TC_CACHE_FILE_OBJECTCACHE_DIR);
        w3_rmdir(W3TC_CONTENT_DIR);
    }

    /**
     * Init action
     *
     * @return void
     */
    function init() {
        /**
         * Check request and handle w3tc_request_data requests
         */
        $pos = strpos($_SERVER['REQUEST_URI'], '/w3tc_request_data/');

        if ($pos !== false) {
            $hash = substr($_SERVER['REQUEST_URI'], $pos + 19, 32);

            if (strlen($hash) == 32) {
                $request_data = (array) get_option('w3tc_request_data');

                if (isset($request_data[$hash])) {
                    echo '<pre>';
                    foreach ($request_data[$hash] as $key => $value) {
                        printf("%s: %s\n", $key, $value);
                    }
                    echo '</pre>';

                    unset($request_data[$hash]);
                    update_option('w3tc_request_data', $request_data);
                } else {
                    echo 'Requested hash expired or invalid';
                }

                exit();
            }
        }

        /**
         * Check for rewrite test request
         */
        require_once W3TC_LIB_W3_DIR . '/Request.php';

        $rewrite_test = W3_Request::get_boolean('w3tc_rewrite_test');

        if ($rewrite_test) {
            echo 'OK';
            exit();
        }
    }

    /**
     * Admin bar menu
     *
     * @return void
     */
    function admin_bar_menu() {
        global $wp_admin_bar;

        if (current_user_can('manage_options')) {
            $menu_items = array(
                array(
                    'id' => 'w3tc',
                    'title' => 'Performance',
                    'href' => admin_url('admin.php?page=w3tc_general')
                ),
                array(
                    'id' => 'w3tc-empty-caches',
                    'parent' => 'w3tc',
                    'title' => 'Empty All Caches',
                    'href' => wp_nonce_url(admin_url('admin.php?page=w3tc_general&amp;w3tc_flush_all'), 'w3tc')
                ),
                array(
                    'id' => 'w3tc-faq',
                    'parent' => 'w3tc',
                    'title' => 'FAQ',
                    'href' => admin_url('admin.php?page=w3tc_faq')
                ),
                array(
                    'id' => 'w3tc-support',
                    'parent' => 'w3tc',
                    'title' => '<span style="color: red; background: none;">Support</span>',
                    'href' => admin_url('admin.php?page=w3tc_support')
                )
            );

            if ($this->_config->get_boolean('cloudflare.enabled')) {
                $menu_items = array_merge($menu_items, array(
                    array(
                        'id' => 'cloudflare',
                        'title' => 'CloudFlare',
                        'href' => 'https://www.cloudflare.com'
                    ),
                    array(
                        'id' => 'cloudflare-my-websites',
                        'parent' => 'cloudflare',
                        'title' => 'My Websites',
                        'href' => 'https://www.cloudflare.com/my-websites.html'
                    ),
                    array(
                        'id' => 'cloudflare-analytics',
                        'parent' => 'cloudflare',
                        'title' => 'Analytics',
                        'href' => 'https://www.cloudflare.com/analytics.html'
                    ),
                    array(
                        'id' => 'cloudflare-account',
                        'parent' => 'cloudflare',
                        'title' => 'Account',
                        'href' => 'https://www.cloudflare.com/my-account.html'
                    )
                ));
            }

            foreach ($menu_items as $menu_item) {
                $wp_admin_bar->add_menu($menu_item);
            }
        }
    }

    /**
     * Template filter
     *
     * @param $template
     * @return string
     */
    function template($template) {
        require_once W3TC_LIB_W3_DIR . '/Mobile.php';
        $w3_mobile = & W3_Mobile::instance();

        $mobile_template = $w3_mobile->get_template();

        if ($mobile_template) {
            return $mobile_template;
        } else {
            require_once W3TC_LIB_W3_DIR . '/Referrer.php';
            $w3_referrer = & W3_Referrer::instance();

            $referrer_template = $w3_referrer->get_template();

            if ($referrer_template) {
                return $referrer_template;
            }
        }

        return $template;
    }

    /**
     * Stylesheet filter
     *
     * @param $stylesheet
     * @return string
     */
    function stylesheet($stylesheet) {
        require_once W3TC_LIB_W3_DIR . '/Mobile.php';
        $w3_mobile = & W3_Mobile::instance();

        $mobile_stylesheet = $w3_mobile->get_stylesheet();

        if ($mobile_stylesheet) {
            return $mobile_stylesheet;
        } else {
            require_once W3TC_LIB_W3_DIR . '/Referrer.php';
            $w3_referrer = & W3_Referrer::instance();

            $referrer_stylesheet = $w3_referrer->get_stylesheet();

            if ($referrer_stylesheet) {
                return $referrer_stylesheet;
            }
        }

        return $stylesheet;
    }

    /**
     * Template filter
     *
     * @param $template
     * @return string
     */
    function template_preview($template) {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        $theme_name = W3_Request::get_string('w3tc_theme');

        $theme = get_theme($theme_name);

        if ($theme) {
            return $theme['Template'];
        }

        return $template;
    }

    /**
     * Stylesheet filter
     *
     * @param $stylesheet
     * @return string
     */
    function stylesheet_preview($stylesheet) {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        $theme_name = W3_Request::get_string('w3tc_theme');

        $theme = get_theme($theme_name);

        if ($theme) {
            return $theme['Stylesheet'];
        }

        return $stylesheet;
    }

    /**
     * Footer plugin action
     *
     * @return void
     */
    function footer() {
        echo '<div style="text-align: center;">Performance Optimization <a href="http://www.w3-edge.com/wordpress-plugins/" rel="external">WordPress Plugins</a> by W3 EDGE</div>';
    }

    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback(&$buffer) {
        global $wpdb;

        if ($buffer != '' && w3_is_xml($buffer)) {
            if (w3_is_database_error($buffer)) {
                @header('HTTP/1.1 503 Service Unavailable');
            } else {
                /**
                 * Replace links for preview mode
                 */
                if (w3_is_preview_mode() && isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != W3TC_POWERED_BY) {
                    $domain_url_regexp = w3_get_domain_url_regexp();

                    $buffer = preg_replace_callback('~(href|src|action)=([\'"])(' . $domain_url_regexp . ')?(/[^\'"]*)~', array(
                        &$this,
                        'link_replace_callback'
                    ), $buffer);
                }

                /**
                 * Add footer comment
                 */
                $date = date_i18n('Y-m-d H:i:s');
                $host = (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

                if ($this->_config->get_string('common.support') != '' || $this->_config->get_boolean('common.tweeted')) {
                    $buffer .= sprintf("\r\n<!-- Served from: %s @ %s by W3 Total Cache -->", w3_escape_comment($host), $date);
                } else {
                    $strings = array();

                    if ($this->_config->get_boolean('minify.enabled') && !$this->_config->get_boolean('minify.debug')) {
                        require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
                        $w3_plugin_minify = & W3_Plugin_Minify::instance();

                        $strings[] = sprintf("Minified using %s%s", w3_get_engine_name($this->_config->get_string('minify.engine')), ($w3_plugin_minify->minify_reject_reason != '' ? sprintf(' (%s)', $w3_plugin_minify->minify_reject_reason) : ''));
                    }

                    if ($this->_config->get_boolean('pgcache.enabled') && !$this->_config->get_boolean('pgcache.debug')) {
                        require_once W3TC_LIB_W3_DIR . '/PgCache.php';
                        $w3_pgcache = & W3_PgCache::instance();

                        $strings[] = sprintf("Page Caching using %s%s", w3_get_engine_name($this->_config->get_string('pgcache.engine')), ($w3_pgcache->cache_reject_reason != '' ? sprintf(' (%s)', $w3_pgcache->cache_reject_reason) : ''));
                    }

                    if ($this->_config->get_boolean('dbcache.enabled') && !$this->_config->get_boolean('dbcache.debug') && is_a($wpdb, 'W3_Db')) {
                        $append = (is_user_logged_in() ? ' (user is logged in)' : '');

                        if ($wpdb->query_hits) {
                            $strings[] = sprintf("Database Caching %d/%d queries in %.3f seconds using %s%s", $wpdb->query_hits, $wpdb->query_total, $wpdb->time_total, w3_get_engine_name($this->_config->get_string('dbcache.engine')), $append);
                        } else {
                            $strings[] = sprintf("Database Caching using %s%s", w3_get_engine_name($this->_config->get_string('dbcache.engine')), $append);
                        }
                    }

                    if ($this->_config->get_boolean('objectcache.enabled') && !$this->_config->get_boolean('objectcache.debug')) {
                        require_once W3TC_LIB_W3_DIR . '/ObjectCache.php';
                        $w3_objectcache = & W3_ObjectCache::instance();

                        $strings[] = sprintf("Object Caching %d/%d objects using %s", $w3_objectcache->cache_hits, $w3_objectcache->cache_total, w3_get_engine_name($this->_config->get_string('objectcache.engine')));
                    }

                    if ($this->_config->get_boolean('cdn.enabled') && !$this->_config->get_boolean('cdn.debug')) {
                        require_once W3TC_LIB_W3_DIR . '/Plugin/Cdn.php';

                        $w3_plugin_cdn = & W3_Plugin_Cdn::instance();
                        $cdn = & $w3_plugin_cdn->get_cdn();
                        $via = $cdn->get_via();

                        $strings[] = sprintf("Content Delivery Network via %s%s", ($via ? $via : 'N/A'), ($w3_plugin_cdn->cdn_reject_reason != '' ? sprintf(' (%s)', $w3_plugin_cdn->cdn_reject_reason) : ''));
                    }

                    $buffer .= "\r\n<!-- Performance optimized by W3 Total Cache. Learn more: http://www.w3-edge.com/wordpress-plugins/\r\n";

                    if (count($strings)) {
                        $buffer .= "\r\n" . implode("\r\n", $strings) . "\r\n";
                    }

                    $buffer .= sprintf("\r\nServed from: %s @ %s -->", w3_escape_comment($host), $date);
                }
            }
        }

        return $buffer;
    }

    /**
     * Check if we can do modify contents
     *
     * @return boolean
     */
    function can_ob() {
        $enabled = w3_is_preview_mode();
        $enabled = $enabled || $this->_config->get_boolean('pgcache.enabled');
        $enabled = $enabled || $this->_config->get_boolean('dbcache.enabled');
        $enabled = $enabled || $this->_config->get_boolean('objectcache.enabled');
        $enabled = $enabled || $this->_config->get_boolean('browsercache.enabled');
        $enabled = $enabled || $this->_config->get_boolean('minify.enabled');
        $enabled = $enabled || $this->_config->get_boolean('cdn.enabled');

        /**
         * Check if plugin enabled
         */
        if (!$enabled) {
            return false;
        }

        /**
         * Skip if admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }

        /**
         * Skip if doing AJAX
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
         * Check for WPMU's and WP's 3.0 short init
         */
        if (defined('SHORTINIT') && SHORTINIT) {
            return false;
        }

        /**
         * Check User Agent
         */
        if (isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], W3TC_POWERED_BY) !== false) {
            return false;
        }

        return true;
    }

    /**
     * Preview link replace callback
     *
     * @param array $matches
     * @return string
     */
    function link_replace_callback($matches) {
        list (, $attr, $quote, $domain_url, , , $path) = $matches;

        $path .= (strstr($path, '?') !== false ? '&amp;' : '?') . 'w3tc_preview=1';

        return sprintf('%s=%s%s%s', $attr, $quote, $domain_url, $path);
    }

    /**
     * Now actually allow CF to see when a comment is approved/not-approved.
     *
     * @param int $id
     * @param string $status
     * @return void
     */
    function cloudflare_set_comment_status($id, $status) {
        if ($status == 'spam') {
            $email = $this->_config->get_string('cloudflare.email');
            $key = $this->_config->get_string('cloudflare.key');

            if ($email && $key) {
                require_once W3TC_LIB_W3_DIR . '/CloudFlare.php';
                $w3_cloudflare =& new W3_CloudFlare(array(
                    'email' => $email,
                    'key' => $key
                ));

                $comment = get_comment($id);

                $value = array(
                    'a' => $comment->comment_author,
                    'am' => $comment->comment_author_email,
                    'ip' => $comment->comment_author_IP,
                    'con' => substr($comment->comment_content, 0, 100)
                );

                $w3_cloudflare->external_event('WP_SPAM', json_encode($value));
            }
        }
    }
}
