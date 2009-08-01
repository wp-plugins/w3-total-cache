<?php

/**
 * W3 PgCache
 */

/**
 * Class W3_PgCache
 */
class W3_PgCache
{
    /**
     * Advanced cache config
     *
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * Compression availability flag
     *
     * @var boolean
     */
    var $_compression = false;
    
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
     */
    function W3_PgCache()
    {
        $this->__construct();
    }
    
    /**
     * Do cache logic
     */
    function process()
    {
        $this->_compression = ($this->_config->get_boolean('pgcache.compress', true) ? $this->_get_compression() : false);
        $page_key = $this->_get_page_key($this->_compression);
        
        /**
         * Check if page is cached
         */
        $cache = $this->_get_cache();
        
        if (is_array(($data = $cache->get($page_key)))) {
            /**
             * Handle 404 error
             */
            if ($data['404']) {
                header('HTTP/1.1 404 Not Found');
            } elseif (! empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']));
                if ($if_modified_since >= $data['time']) {
                    header("HTTP/1.1 304 Not Modified");
                }
            }
            
            /**
             * Send cached headers
             */
            foreach ((array) $data['headers'] as $header_name => $header_value) {
                header($header_name . ': ' . $header_value);
            }
            
            $this->log(true, true);
            
            /**
             * Send content
             */
            echo $data['content'];
            exit();
        }
        
        /**
         * Start ob
         */
        ob_start(array(
            &$this, 
            'ob_callback'
        ));
    }
    
    /**
     * Output buffering callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        /**
         * Don't cache 404 pages
         */
        if (! $this->_config->get_boolean('pgcache.cache.404', true) && is_404()) {
            return $buffer;
        }
        
        /**
         * Don't cache homepage
         */
        if (! $this->_config->get_boolean('pgcache.cache.home', true) && is_home()) {
            return $buffer;
        }
        
        /**
         * Don't cache feed
         */
        if (! $this->_config->get_boolean('pgcache.cache.feed', true) && is_feed()) {
            return $buffer;
        }
        
        /**
         * Skip if user is logged in
         */
        if (! $this->_config->get_boolean('pgcache.cache.logged', true) && is_user_logged_in()) {
            return $buffer;
        }
        
        /**
         * Skip if buffer is empty
         */
        if ($buffer == '') {
            return '';
        }
        
        /**
         * Create data object
         */
        $compressions = array(
            false, 
            'gzip', 
            'deflate'
        );
        
        $data_array = array();
        $page_keys = array();
        
        if ($this->_compression !== false) {
            header('Content-Encoding: ' . $this->_compression);
            header('Vary: Accept-Encoding, Cookie');
        }
        
        $cache = $this->_get_cache();
        
        foreach ($compressions as $compression) {
            $data = array(
                'time' => time(), 
                '404' => is_404()
            );
            
            /**
             * Set headers to cache
             */
            $headers = $this->_config->get_array('pgcache.cache.headers');
            
            foreach ($this->_get_response_headers() as $header_name => $header_value) {
                foreach ($headers as $known_header_name) {
                    if (strcasecmp($header_name, $known_header_name) == 0) {
                        $data['headers'][$header_name] = $header_value;
                    }
                }
            }
            
            if ($compression !== false) {
                /**
                 * If compression is supported, cache compressed page
                 */
                if ($compression == 'gzip') {
                    $data['content'] = gzencode($buffer);
                } else {
                    $data['content'] = gzdeflate($buffer);
                }
                
                $data['headers']['Content-Encoding'] = $compression;
                $data['headers']['Vary'] = 'Accept-Encoding, Cookie';
            } else {
                /**
                 * Otherwise cache plain
                 */
                $data['content'] = $buffer;
                $data['headers']['Vary'] = 'Cookie';
            }
            
            /**
             * Set last modified header
             */
            if (isset($data['headers']['Last-Modified'])) {
                $data['headers']['Last-Modified'] = gmdate('D, d M Y H:i:s', $data['time']) . ' GMT';
            }
            
            /**
             * Store data
             */
            $page_key = $this->_get_page_key($compression);
            
            $cache->set($page_key, $data, $this->_config->get_integer('dbcache.lifetime', 3600));
            
            $data_array[$compression] = $data;
            $page_keys[] = $page_key;
        }
        
        /**
         * Try to detect post id
         */
        $post_id = $this->_detect_post_id();
        
        /**
         * If there is post id, store link to cached page
         */
        if ($post_id) {
            $map = $this->get_map($post_id);
            $map += $page_keys;
            $this->set_map($post_id, $map);
        }
        
        /**
         * Store links to home page
         */
        if ($_SERVER['REQUEST_URI'] == '/') {
            $map = $this->get_map('home');
            $map += $page_keys;
            $this->set_map('home', $map);
        }
        
        $this->log(true, false);
        
        return $data_array[$this->_compression]['content'];
    }
    
    /**
     * Checks if can we do cache logic
     *
     * @return boolean
     */
    function can_cache()
    {
        /**
         * Skip if disabled
         */
        if (! $this->_config->get_boolean('pgcache.enabled', true)) {
            return false;
        }
        
        /**
         * Skip if posting
         */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return false;
        }
        
        /**
         * Skip if session defined
         */
        if (defined('SID') && SID != '') {
            return false;
        }
        
        /**
         * Skip if there is query in the request uri
         */
        if (! $this->_config->get_boolean('pgcache.cache.query', true) && strstr($_SERVER['REQUEST_URI'], '?') !== false) {
            return false;
        }
        
        /**
         * Check request URI
         */
        if (! in_array($_SERVER['PHP_SELF'], $this->_config->get_array('pgcache.accept.files')) && ! $this->_check_request_uri()) {
            return false;
        }
        
        /**
         * Check User Agent
         */
        if (! $this->_check_ua()) {
            return false;
        }
        
        /**
         * Check WordPress cookies
         */
        if (! $this->_check_cookies()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns map associated to namespace
     *
     * @param string $namespace
     * @return array
     */
    function get_map($namespace)
    {
        $map_key = $this->_get_map_key($namespace);
        $cache = $this->_get_cache();
        
        if (is_array(($map = $cache->get($map_key)))) {
            return $map;
        }
        
        return array();
    }
    
    /**
     * Sets map
     *
     * @param string $namespace
     * @param array $map
     * @return boolean
     */
    function set_map($namespace, $map)
    {
        $map_key = $this->_get_map_key($namespace);
        $cache = $this->_get_cache();
        
        return $cache->set($map_key, $map, $this->_config->get_integer('pgcache.lifetime', 3600));
    }
    
    /**
     * Flushes all cache
     *
     * @return boolean
     */
    function flush()
    {
        $cache = $this->_get_cache();
        
        return $cache->flush();
    }
    
    /**
     * Flushes post cache
     *
     * @param integer $post_id
     * @return boolean
     */
    function flush_post($post_id)
    {
        if (! $post_id) {
            $post_id = $this->_detect_post_id();
        }
        
        if ($post_id) {
            $cache = $this->_get_cache();
            
            /**
             * Flush post page
             */
            foreach ((array) $this->get_map($post_id) as $page_key) {
                $cache->delete($page_key);
            }
            
            /**
             * Flush home page
             */
            foreach ((array) $this->get_map('home') as $page_key) {
                $cache->delete($page_key);
            }
            
            /**
             * Flush map cache
             */
            $this->set_map($post_id, array());
            $this->set_map('home', array());
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Put log data into log file
     *
     * @param boolean $caching
     * @param boolean $cached
     */
    function log($caching = false, $cached = false)
    {
        if ($this->_config->get_boolean('pgcache.debug')) {
            $data = sprintf("[%s] %s \"%s\" [%s]\n", date('r'), $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], ($caching ? ($cached ? 'cached' : 'not cached') : 'not caching'));
            
            if (($fp = @fopen(WP_CONTENT_DIR . '/w3-pagecache.log', 'a'))) {
                @fputs($fp, $data);
                @fclose($fp);
            }
        }
    }
    
    /**
     * Returns onject instance
     *
     * @return W3_PgCache
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
     * Returns cache object
     *
     * @return W3_Cache_Base
     */
    function &_get_cache()
    {
        static $cache = null;
        
        if (! $cache) {
            $engine = $this->_config->get_string('pgcache.engine', 'memcached');
            if ($engine == 'memcached') {
                $engineConfig = array(
                    'engine' => $this->_config->get_string('pgcache.memcached.engine', 'auto'), 
                    'servers' => $this->_config->get_array('pgcache.memcached.servers')
                );
            } else {
                $engineConfig = array();
            }
            
            require_once dirname(__FILE__) . '/Cache.php';
            $cache = & W3_Cache::instance($engine, $engineConfig);
        }
        
        return $cache;
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function _check_request_uri()
    {
        $auto_reject_uri = array(
            'wp-admin', 
            'wp-includes', 
            'wp-content', 
            'xmlrpc.php', 
            'wp-app.php', 
            'robots.txt'
        );
        
        foreach ($auto_reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('pgcache.reject.uri') as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks User Agent
     *
     * @return boolean
     */
    function _check_ua()
    {
        foreach ($this->_config->get_array('pgcache.reject.ua') as $ua) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $ua) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks WordPress cookies
     *
     * @return boolean
     */
    function _check_cookies()
    {
        foreach (array_keys($_COOKIE) as $cookie_name) {
            if ($cookie_name == 'wordpress_test_cookie') {
                continue;
            }
            if (preg_match('/^wp-postpass|^wordpress|^comment_author/', $cookie_name)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks gzip availability
     *
     * @return boolean
     */
    function _get_compression()
    {
        if (ini_get('zlib.output_compression')) {
            return false;
        }
        
        if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            return 'gzip';
        } elseif (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false) {
            return 'deflate';
        }
        
        return false;
    }
    
    /**
     * Returns array of response headers
     *
     * @return array
     */
    function _get_response_headers()
    {
        $headers = array();
        
        if (function_exists('apache_response_headers')) {
            flush();
            $headers = apache_response_headers();
        } elseif (function_exists('headers_list')) {
            foreach (headers_list() as $header) {
                list ($header_name, $header_value) = explode(': ', $header, 2);
                $headers[$header_name] = $header_value;
            }
        } else {
            foreach (array_keys($_SERVER) as $skey) {
                if (substr($skey, 0, 5) == 'HTTP_') {
                    $header_name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($skey, 5)))));
                    $headers[$header_name] = $_SERVER[$skey];
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Returns mobile type
     *
     * @return string
     */
    function _get_mobile_type()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            foreach ($this->_config->get_array('pgcache.mobile.whitelist') as $browser) {
                if (strstr($_SERVER['HTTP_USER_AGENT'], trim($browser)) !== false) {
                    return '';
                }
            }
            
            foreach ($this->_config->get_array('pgcache.mobile.browsers') as $browser) {
                if (strstr($_SERVER["HTTP_USER_AGENT"], trim($browser)) !== false) {
                    return strtolower($browser);
                }
            }
        }
        
        return '';
    }
    
    /**
     * Returns page key
     *
     * @param string $compression
     * @return string
     */
    function _get_page_key($compression = false)
    {
        $key = 'page_' . md5($_SERVER['REQUEST_URI']);
        
        if ($this->_config->get_boolean('pgcache.mobile.check') && ($mobile_type = $this->_get_mobile_type()) != '') {
            $key .= '_' . $mobile_type;
        }
        
        if (! empty($compression)) {
            $key .= '_' . $compression;
        }
        
        return $key;
    }
    
    /**
     * Returns map key
     *
     * @param string $namespace
     * @return string
     */
    function _get_map_key($namespace)
    {
        $key = 'map_' . $namespace;
        
        return $key;
    }
    
    /**
     * Detects post ID
     *
     * @return integer
     */
    function _detect_post_id()
    {
        global $posts, $comment_post_ID, $post_ID;
        
        if ($post_ID) {
            return $post_ID;
        } elseif ($comment_post_ID) {
            return $comment_post_ID;
        } elseif (is_single() || is_page() && count($posts)) {
            return $posts[0]->ID;
        } elseif (isset($_REQUEST['p'])) {
            return (integer) $_REQUEST['p'];
        }
        
        return 0;
    }
}
