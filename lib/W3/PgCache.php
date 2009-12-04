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
     * Caching flag
     *
     * @var boolean
     */
    var $_caching = false;
    
    /**
     * Compression availability flag
     *
     * @var boolean
     */
    var $_compression = false;
    
    /**
     * Page key
     *
     * @var string
     */
    var $_page_key = '';
    
    /**
     * Time start
     *
     * @var double
     */
    var $_time_start = 0;
    
    /**
     * Lifetime
     * @var integer
     */
    var $_lifetime = 0;
    
    /**
     * Cache reject reason
     *
     * @var string
     */
    var $cache_reject_reason = '';
    
    /**
     * PHP5 Constructor
     */
    function __construct()
    {
        require_once W3TC_LIB_W3_DIR . '/Config.php';
        $this->_config = & W3_Config::instance();
        $this->_lifetime = $this->_config->get_integer('pgcache.lifetime');
        $this->_compression = ($this->_config->get_boolean('pgcache.compress') ? $this->_get_compression() : false);
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
        /**
         * Skip caching for some pages
         */
        switch (true) {
            case defined('DOING_AJAX'):
            case defined('DOING_CRON'):
            case defined('APP_REQUEST'):
            case defined('XMLRPC_REQUEST'):
            case defined('WP_ADMIN'):
                return;
        }
        
        /**
         * Handle mobile redirects
         */
        $mobile_redirect = $this->_config->get_string('pgcache.mobile.redirect');
        
        if ($mobile_redirect != '' && $this->_is_mobile()) {
            header('Location: ' . $mobile_redirect);
            exit();
        }
        
        /**
         * Do page cache logic
         */
        if ($this->_config->get_boolean('pgcache.debug')) {
            $this->_time_start = w3_microtime();
        }
        
        $this->_caching = $this->_can_cache();
        $this->_page_key = $this->_get_page_key($_SERVER['REQUEST_URI'], $this->_compression);
        
        if ($this->_caching) {
            /**
             * Check if page is cached
             */
            $cache = & $this->_get_cache();
            
            if (($data = $cache->get($this->_page_key))) {
                if ($this->_config->get_string('pgcache.engine') == 'file_pgcache') {
                    @$this->_conditional_get($cache->mtime($this->_page_key), md5($data));
                    
                    @header('Content-Encoding: ' . $this->_compression);
                    @header('Vary: Accept-Encoding, Cookie');
                    
                    $content = $data;
                } else {
                    /**
                     * Handle 404 error
                     */
                    if ($data['404']) {
                        @header('HTTP/1.1 404 Not Found');
                    } else {
                        @$this->_conditional_get($data['time'], md5($data['content']));
                    }
                    
                    /**
                     * Send cached headers
                     */
                    foreach ((array) $data['headers'] as $header_name => $header_value) {
                        @header($header_name . ': ' . $header_value);
                    }
                    
                    $content = $data['content'];
                }
                
                @header('Pragma: public');
                
                /**
                 * Append debug info to content if debug mode is enabled
                 */
                if ($this->_config->get_boolean('pgcache.debug')) {
                    $time_total = w3_microtime() - $this->_time_start;
                    $debug_info = $this->_get_debug_info(true, '', true, $time_total);
                    $content = $this->_append_content($content, "\r\n\r\n" . $debug_info, $this->_compression);
                }
                
                echo $content;
                exit();
            }
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
        if (! w3_is_xml($buffer)) {
            return $buffer;
        }
        
        if (! $this->_can_cache2()) {
            $this->_send_compression_headers($this->_compression);
            
            if ($this->_config->get_boolean('pgcache.debug')) {
                $time_total = w3_microtime() - $this->_time_start;
                $debug_info = $this->_get_debug_info(false, $this->cache_reject_reason, false, $time_total);
                $buffer .= "\r\n\r\n" . $debug_info;
            }
            
            switch ($this->_compression) {
                case 'gzip':
                    return gzencode($buffer);
                
                case 'deflate':
                    return gzdeflate($buffer);
            }
            
            return $buffer;
        }
        
        /**
         * Create data object
         */
        $data_array = array();
        $page_keys = array();
        
        $cache = & $this->_get_cache();
        $time = time();
        $is_404 = is_404();
        
        $compressions = array(
            false, 
            'gzip', 
            'deflate'
        );
        
        foreach ($compressions as $compression) {
            $data = array(
                'time' => $time, 
                '404' => $is_404
            );
            
            /**
             * Set content to cache
             */
            if ($compression == 'gzip') {
                $data['content'] = gzencode($buffer);
            } elseif ($compression == 'deflate') {
                $data['content'] = gzdeflate($buffer);
            } else {
                $data['content'] = $buffer;
            }
            
            $data_array[$compression] = $data;
        }
        
        /**
         * We must send compression headers first before ::_get_data_headers() call
         */
        @$this->_conditional_get($time, md5($data_array[$this->_compression]['content']));
        $this->_send_compression_headers($this->_compression);
        @header('Pragma: public');
        
        foreach ($data_array as $compression => $data) {
            /**
             * Set headers to cache
             */
            $data['headers'] = $this->_get_data_headers($compression);
            $data_array[$compression] = $data;
            
            /**
             * Cache data
             */
            $page_key = $this->_get_page_key($_SERVER['REQUEST_URI'], $compression);
            
            if ($this->_config->get_string('pgcache.engine') == 'file_pgcache') {
                $cache->set($page_key, $data['content']);
            } else {
                $cache->set($page_key, $data, $this->_lifetime);
            }
            
            $page_keys[] = $page_key;
        }
        
        /**
         * Append debug info if debug mode is enabled
         */
        if ($this->_config->get_boolean('pgcache.debug')) {
            $time_total = w3_microtime() - $this->_time_start;
            $debug_info = $this->_get_debug_info(true, '', false, $time_total);
            $data_array[$this->_compression]['content'] = $this->_append_content($data_array[$this->_compression]['content'], "\r\n\r\n" . $debug_info, $this->_compression);
        }
        
        return $data_array[$this->_compression]['content'];
    }
    
    /**
     * Flushes all caches
     *
     * @return boolean
     */
    function flush()
    {
        $cache = & $this->_get_cache();
        
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
            $home = get_option('home');
            
            $page_keys = array(
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), false), 
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), 'gzip'), 
                $this->_get_page_key(str_replace($home, '', post_permalink($post_id)), 'deflate'), 
                $this->_get_page_key('/', false), 
                $this->_get_page_key('/', 'gzip'), 
                $this->_get_page_key('/', 'deflate')
            );
            
            $cache = & $this->_get_cache();
            
            foreach ($page_keys as $page_key) {
                $cache->delete($page_key);
            }
        }
        
        return false;
    }
    
    /**
     * Returns object instance
     *
     * @return W3_PgCache
     */
    function &instance()
    {
        static $instances = array();
        
        if (! isset($instances[0])) {
            $class = __CLASS__;
            $instances[0] = & new $class();
        }
        
        return $instances[0];
    }
    
    /**
     * Checks if can we do cache logic
     *
     * @return boolean
     */
    function _can_cache()
    {
        /**
         * Skip if disabled
         */
        if (! $this->_config->get_boolean('pgcache.enabled')) {
            $this->cache_reject_reason = 'page caching is disabled';
            return false;
        }
        
        /**
         * Skip if posting
         */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->cache_reject_reason = 'request method is POST';
            return false;
        }
        
        /**
         * Skip if session defined
         */
        if (defined('SID') && SID != '') {
            $this->cache_reject_reason = 'session is started';
            return false;
        }
        
        /**
         * Skip if there is query in the request uri
         */
        if (! $this->_config->get_boolean('pgcache.cache.query') && strstr($_SERVER['REQUEST_URI'], '?') !== false) {
            $this->cache_reject_reason = 'request URI contains query';
            return false;
        }
        
        /**
         * Check request URI
         */
        if (! in_array($_SERVER['PHP_SELF'], $this->_config->get_array('pgcache.accept.files')) && ! $this->_check_request_uri()) {
            $this->cache_reject_reason = 'request URI is rejected';
            return false;
        }
        
        /**
         * Check User Agent
         */
        if (! $this->_check_ua()) {
            $this->cache_reject_reason = 'user agent is rejected';
            return false;
        }
        
        /**
         * Check WordPress cookies
         */
        if (! $this->_check_cookies()) {
            $this->cache_reject_reason = 'cookie is rejected';
            return false;
        }
        
        /**
         * Skip if user is logged in
         */
        if ($this->_config->get_boolean('pgcache.reject.logged') && ! $this->_check_logged_in()) {
            $this->cache_reject_reason = 'user is logged in';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks if can we do cache logic
     *
     * @param string $buffer
     * @return boolean
     */
    function _can_cache2()
    {
        /**
         * Skip if caching is disabled
         */
        if (! $this->_caching) {
            return false;
        }
        
        /**
         * Don't cache 404 pages
         */
        if (! $this->_config->get_boolean('pgcache.cache.404') && is_404()) {
            $this->cache_reject_reason = 'Page is 404';
            
            return false;
        }
        
        /**
         * Don't cache homepage
         */
        if (! $this->_config->get_boolean('pgcache.cache.home') && is_home()) {
            $this->cache_reject_reason = 'Page is home';
            
            return false;
        }
        
        /**
         * Don't cache feed
         */
        if (! $this->_config->get_boolean('pgcache.cache.feed') && is_feed()) {
            $this->cache_reject_reason = 'Page is feed';
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns cache object
     *
     * @return W3_Cache_Base
     */
    function &_get_cache()
    {
        static $cache = array();
        
        if (! isset($cache[0])) {
            $engine = $this->_config->get_string('pgcache.engine');
            
            switch ($engine) {
                case 'memcached':
                    $engineConfig = array(
                        'engine' => $this->_config->get_string('pgcache.memcached.engine'), 
                        'servers' => $this->_config->get_array('pgcache.memcached.servers'), 
                        'persistant' => true
                    );
                    break;
                
                case 'file':
                    $engineConfig = array(
                        'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR
                    );
                    break;
                
                case 'file_pgcache':
                    $engineConfig = array(
                        'cache_dir' => W3TC_CACHE_FILE_PGCACHE_DIR, 
                        'expire' => $this->_lifetime
                    );
                    break;
                
                default:
                    $engineConfig = array();
            }
            
            require_once W3TC_LIB_W3_DIR . '/Cache.php';
            $cache[0] = & W3_Cache::instance($engine, $engineConfig);
        }
        
        return $cache[0];
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function _check_request_uri()
    {
        $auto_reject_uri = array(
            'wp-login', 
            'wp-register'
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
            if (preg_match('/^(wp-postpass|comment_author)/', $cookie_name)) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('pgcache.reject.cookie') as $reject_cookie) {
            foreach (array_keys($_COOKIE) as $cookie_name) {
                if (strstr($cookie_name, $reject_cookie) !== false) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if user is logged in
     *
     * @return boolean
     */
    function _check_logged_in()
    {
        foreach (array_keys($_COOKIE) as $cookie_name) {
            if ($cookie_name == 'wordpress_test_cookie') {
                continue;
            }
            if (strpos($cookie_name, 'wordpress') === 0) {
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
        
        if (function_exists('headers_list')) {
            foreach (headers_list() as $header) {
                list ($header_name, $header_value) = explode(': ', $header, 2);
                $headers[$header_name] = $header_value;
            }
        } elseif (function_exists('apache_response_headers')) {
            flush();
            $headers = apache_response_headers();
        }
        
        ksort($headers);
        reset($headers);
        
        return $headers;
    }
    
    /**
     * Returns array of data headers
     *
     * @param string $compression
     * @return array
     */
    function _get_data_headers($compression)
    {
        $data_headers = array();
        $cache_headers = $this->_config->get_array('pgcache.cache.headers');
        
        foreach ($this->_get_response_headers() as $header_name => $header_value) {
            foreach ($cache_headers as $cache_header_name) {
                if (strcasecmp($header_name, $cache_header_name) == 0) {
                    $data_headers[$header_name] = $header_value;
                }
            }
        }
        
        if ($compression !== false) {
            $data_headers['Content-Encoding'] = $compression;
            $data_headers['Vary'] = 'Accept-Encoding, Cookie';
        } else {
            $data_headers['Vary'] = 'Cookie';
        }
        
        if (isset($data_headers['Last-Modified'])) {
            $data_headers['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
        }
        
        return $data_headers;
    }
    
    /**
     * Returns page key
     *
     * @param string $request_uri
     * @param string $compression
     * @return string
     */
    function _get_page_key($request_uri, $compression)
    {
        if ($this->_config->get_string('pgcache.engine') == 'file_pgcache') {
            $request_uri = preg_replace('~\?.*$~', '', $request_uri);
            $request_uri = str_replace('/index.php', '/', $request_uri);
            $request_uri = preg_replace('~[/\\\]+~', '/', $request_uri);
            $request_uri = w3_realpath($request_uri);
            
            if (empty($request_uri)) {
                $request_uri = '/';
            }
            
            if (substr($request_uri, - 1) == '/') {
                $request_uri .= 'index.html';
            }
            
            $request_uri = ltrim($request_uri, '/');
            
            $key = sprintf('%s/%s', $_SERVER['HTTP_HOST'], $request_uri);
            
            if (! empty($compression)) {
                $key .= '.' . $compression;
            }
        } else {
            $blog_id = w3_get_blog_id();
            
            if (empty($blog_id)) {
                $blog_id = $_SERVER['HTTP_HOST'];
            }
            
            $key = sprintf('w3tc_%s_page_%s', md5($blog_id), md5($request_uri));
            
            if (! empty($compression)) {
                $key .= '_' . $compression;
            }
        }
        
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
    
    /**
     * Returns debug info
     *
     * @param boolean $cache
     * @param string $reason
     * @param boolean $status
     * @param double $time
     * @return string
     */
    function _get_debug_info($cache, $reason, $status, $time)
    {
        $debug_info = "<!-- W3 Total Cache: Page cache debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), w3_get_engine_name($this->_config->get_string('pgcache.engine')));
        $debug_info .= sprintf("%s%s\r\n", str_pad('Key: ', 20), $this->_page_key);
        $debug_info .= sprintf("%s%s\r\n", str_pad('Caching: ', 20), ($cache ? 'enabled' : 'disabled'));
        if (! $cache) {
            $debug_info .= sprintf("%s%s\r\n", str_pad('Reject reason: ', 20), $reason);
        }
        $debug_info .= sprintf("%s%s\r\n", str_pad('Status: ', 20), ($status ? 'cached' : 'not cached'));
        $debug_info .= sprintf("%s%.3fs\r\n", str_pad('Creation Time: ', 20), $time);
        
        $headers = $this->_get_response_headers();
        
        if (count($headers)) {
            $debug_info .= "Header info:\r\n";
            
            foreach ($headers as $header_name => $header_value) {
                $debug_info .= sprintf("%s%s\r\n", str_pad($header_name . ': ', 20), $header_value);
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Appends content to data content
     *
     * @param array $data
     * @param string $content
     * @param string $compression
     * @return string
     */
    function _append_content($data, $content, $compression)
    {
        switch ($this->_compression) {
            case false:
                $data .= $content;
                break;
            
            case 'gzip':
                $data = (function_exists('gzdecode') ? gzdecode($data) : w3_gzdecode($data));
                $data .= $content;
                $data = gzencode($data);
                break;
            
            case 'deflate':
                $data = gzinflate($data);
                $data .= $content;
                $data = gzdeflate($data);
                break;
        }
        
        return $data;
    }
    
    /**
     * Sends compression headers
     *
     * @param string $compression
     */
    function _send_compression_headers($compression)
    {
        if ($compression !== false) {
            @header('Content-Encoding: ' . $compression);
            @header('Vary: Accept-Encoding, Cookie');
        }
    }
    
    /**
     * Conditional get
     * @param $time
     * @param $etag
     * @return void
     */
    function _conditional_get($time, $etag)
    {
        if (W3TC_PHP5) {
            require_once W3TC_LIB_MINIFY_DIR . '/HTTP/ConditionalGet.php';
            
            $cg_options = array(
                'setExpires' => ($time + $this->_lifetime), 
                'contentHash' => $etag
            );
            
            if ($this->_compression) {
                $cg_options['encoding'] = $this->_compression;
            }
            
            HTTP_ConditionalGet::check($time, true, $cg_options);
        }
    }
    
    /**
     * Checks if User Agent is mobile
     * @return boolean
     */
    function _is_mobile()
    {
        $mobile_agents = $this->_config->get_array('pgcache.mobile.agents');
        
        foreach ($mobile_agents as $mobile_agent) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $mobile_agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
