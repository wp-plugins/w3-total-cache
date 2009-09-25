<?php

define('W3TC_VERSION', '0.8');
define('W3TC_POWERED_BY', 'W3 Total Cache/' . W3TC_VERSION);
define('W3TC_LINK_URL', 'http://www.w3-edge.com/wordpress-plugins/');
define('W3TC_LINK_NAME', 'WordPress Plugins');

if (! defined('W3TC_DIR')) {
    define('W3TC_DIR', realpath(dirname(__FILE__) . '/..'));
}

define('W3TC_FILE', 'w3-total-cache/w3-total-cache.php');
define('W3TC_LIB_W3_DIR', W3TC_DIR . '/lib/W3');
define('W3TC_LIB_MINIFY_DIR', W3TC_DIR . '/lib/Minify/');
define('W3TC_INSTALL_DIR', W3TC_DIR . '/wp-content');
define('W3TC_INSTALL_MINIFY_DIR', W3TC_INSTALL_DIR . '/w3tc');

if (! defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', realpath(W3TC_DIR . '/../..'));
}

define('WP_CONTENT_DIR_NAME', basename(WP_CONTENT_DIR));

define('W3TC_CONTENT_DIR_NAME', WP_CONTENT_DIR_NAME . '/w3tc');
define('W3TC_CONTENT_DIR', ABSPATH . W3TC_CONTENT_DIR_NAME);
define('W3TC_CACHE_FILE_DIR', W3TC_CONTENT_DIR . '/cache');
define('W3TC_CACHE_MINIFY_DIR', W3TC_CONTENT_DIR . '/minify');
define('W3TC_LOG_DIR', W3TC_CONTENT_DIR . '/log');

define('W3TC_CONFIG_NAME', 'w3-total-cache-config');
define('W3TC_CONFIG_PATH', WP_CONTENT_DIR . '/' . W3TC_CONFIG_NAME . (($w3_blog_id = w3_get_blog_id()) != '' ? '-' . $w3_blog_id : '') . '.php');
define('W3TC_CONFIG_DEFAULT_PATH', W3TC_DIR . '/w3-total-cache-config-default.php');

define('W3TC_CDN_COMMAND_UPLOAD', 1);
define('W3TC_CDN_COMMAND_DELETE', 2);
define('W3TC_CDN_TABLE_QUEUE', 'w3tc_cdn_queue');

/**
 * W3 writable error
 *
 * @param string $path
 * @param boolean $die
 * @return string
 */
function w3_writable_error($path, $die = true)
{
    $error = sprintf('<strong>%s</strong> could not be created, please run following command:<br /><strong style="color: #f00;">chmod 777 %s</strong><br />then re-activate plugin.', $path, dirname($path));
    
    if ($die) {
        die($error);
    }
    
    return $error;
}

/**
 * Returns current microtime
 *
 * @return float
 */
function w3_microtime()
{
    list ($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/**
 * Check if URL is valid
 *
 * @param string $url
 * @return boolean
 */
function w3_is_url($url)
{
    return preg_match('~^https?://~', $url);
}

/**
 * Decodes gzip-encoded string
 *
 * @param string $data
 * @return string
 */
function w3_gzdecode($data)
{
    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;
    
    if ($flags & 4) {
        $extralen = unpack('v', substr($data, 10, 2));
        $extralen = $extralen[1];
        $headerlen += 2 + $extralen;
    }
    
    if ($flags & 8) {
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    }
    
    if ($flags & 16) {
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    }
    
    if ($flags & 2) {
        $headerlen += 2;
    }
    
    $unpacked = gzinflate(substr($data, $headerlen));
    
    if ($unpacked === FALSE) {
        $unpacked = $data;
    }
    
    return $unpacked;
}

/**
 * Recursive creates directory
 *
 * @param string $path
 * @param integer $mask
 * @param string
 * @return boolean
 */
function w3_mkdir($path, $mask = 0755, $curr_path = '')
{
    $path = preg_replace('~[\\\/]+~', '/', $path);
    $path = trim($path, '/');
    $dirs = explode('/', $path);
    foreach ($dirs as $dir) {
        if (empty($dir)) {
            return false;
        }
        $curr_path .= ($curr_path == '' ? '' : '/') . $dir;
        if (! is_dir($curr_path)) {
            if (@mkdir($curr_path, $mask)) {
                @chmod($curr_path, $mask);
            } else {
                return false;
            }
        }
    }
    return true;
}

/**
 * Recursive remove dir
 * 
 * @param $path
 * @return boolean
 */
function w3_rmdir($path, $empty = false)
{
    $dir = @opendir($path);
    if ($dir) {
        while (($entry = readdir($dir))) {
            if ($entry != '.' && $entry != '..') {
                $full_path = $path . '/' . $entry;
                if (is_dir($full_path)) {
                    $result = @w3_rmdir($full_path);
                } else {
                    $result = @unlink($full_path);
                }
                if (! $result) {
                    @closedir($dir);
                    return false;
                }
            }
        }
        @closedir($dir);
        return ($empty ? true : @rmdir($path));
    }
    return false;
}

/**
 * Recursive empty dir
 * 
 * @param $path
 * @return boolean
 */
function w3_emptydir($path)
{
    return w3_rmdir($path, true);
}

/**
 * Check if content is HTML or XML
 *
 * @param string $content
 * @return boolean
 */
function w3_is_xml($content)
{
    return (stristr($content, '<?xml') !== false || stristr($content, '<html') !== false);
}

/**
 * Returns blog ID
 *
 * @return string
 */
function w3_get_blog_id()
{
    static $id = null;
    
    if ($id === null) {
        $wpmu = false;
        
        if (defined('VHOST')) {
            $wpmu = true;
        } else {
            $wpmu = file_exists(ABSPATH . 'wpmu-settings.php');
        }
        
        if ($wpmu) {
            if (defined('VHOST') && VHOST === 'yes') {
                $id = w3_get_domain($_SERVER['HTTP_HOST']);
            } else {
                if (defined('PATH_CURRENT_SITE')) {
                    $base = PATH_CURRENT_SITE;
                } elseif (isset($GLOBALS['base'])) {
                    $base = $GLOBALS['base'];
                } else {
                    $base = '/';
                }
                
                if (empty($base)) {
                    $base = '/';
                }
                
                $id = strtolower($_SERVER['REQUEST_URI']);
                
                if (strpos($id, $base) === 0) {
                    $id = substr_replace($id, '', 0, strlen($base));
                }
                
                if (($pos = strpos($id, '/'))) {
                    $id = substr($id, 0, $pos);
                }
                
                if (($pos = strpos($id, '?'))) {
                    $id = substr($id, 0, $pos);
                }
                
                if ($id != '') {
                    $id = trim($id, '/');
                    
                    if (in_array($id, array(
                        'page', 
                        'comments', 
                        'blog', 
                        'wp-admin', 
                        'wp-includes', 
                        'wp-content', 
                        'files', 
                        'feed'
                    )) || is_file($id)) {
                        $id = '';
                    } else {
                        $id = $id . '.' . w3_get_domain($_SERVER['HTTP_HOST']);
                    }
                }
            }
        }
    }
    
    return $id;
}

/**
 * Returns domain from host
 *
 * @param string $host
 * @return string
 */
function w3_get_domain($host)
{
    $host = strtolower($host);
    
    if (strpos($host, 'www.') === 0) {
        $host = str_replace('www.', '', $host);
    }
    
    if (($pos = strpos($host, ':'))) {
        $host = substr($host, 0, $pos);
    }
    
    return $host;
}

/**
 * Returns site url [fast]
 *
 * @return string
 */
function w3_get_site_url()
{
    static $site_url = null;
    
    if ($site_url === null) {
        $site_url = get_option('siteurl');
    }
    
    return $site_url;
}

/**
 * Get domain URL
 * @return string
 */
function w3_get_domain_url()
{
    $siteurl = w3_get_site_url();
    $parse_url = @parse_url($siteurl);
    
    if ($parse_url && isset($parse_url['scheme'])) {
        $scheme = $parse_url['scheme'];
        if (isset($parse_url['host'])) {
            $host = $parse_url['host'];
            $port = (isset($parse_url['port']) ? ':' . $parse_url['port'] : '');
            return sprintf('%s://%s%s', $scheme, $host, $port);
        }
    }
    
    return false;
}

/**
 * Returns blog path
 * @return string
 */
function w3_get_site_path()
{
    static $site_path = null;
    
    if ($site_path === null) {
        
        $site_url = w3_get_site_url();
        $domain_url = w3_get_domain_url();
        
        $site_path = str_replace($domain_url, '', $site_url);
        $site_path = trim($site_path, '/');
        
        if ($site_path != '') {
            $site_path .= '/';
        }
    }
    
    return $site_path;
}

/**
 * Returns upload info
 *
 * @return array
 */
function w3_upload_info()
{
    static $upload_info = null;
    
    if ($upload_info === null) {
        $upload_info = @wp_upload_dir();
        
        if (! empty($upload_info['error'])) {
            $upload_info = false;
        }
    }
    
    return $upload_info;
}

/**
 * Redirects to URL
 * 
 * @param string $url
 * @param string $params
 */
function w3_redirect($url = '', $params = '')
{
    $url = (! empty($url) ? $url : (! empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI']));
    
    if (($parse_url = @parse_url($url))) {
        $url = $parse_url['scheme'] . '://' . (! empty($parse_url['user']) ? $parse_url['user'] . (! empty($parse_url['pass']) ? ':' . $parse_url['pass'] : '') . '@' : '') . $parse_url['host'] . (! empty($parse_url['port']) ? ':' . $parse_url['port'] : '') . $parse_url['path'];
    } else {
        $parse_url = array();
    }
    
    $old_params = array();
    if (! empty($parse_url['query'])) {
        parse_str($parse_url['query'], $old_params);
    }
    
    $new_params = array();
    if (! empty($params)) {
        parse_str($params, $new_params);
    }
    
    $merged_params = array_merge($old_params, $new_params);
    
    if (! empty($merged_params)) {
        $count = count($merged_params);
        $query_string = '';
        
        foreach ($merged_params as $param => $value) {
            $count--;
            $query_string .= urlencode($param) . (! empty($value) ? '=' . urlencode($value) : '') . ($count ? '&' : '');
        }
        
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query_string;
    }
    
    $url .= (! empty($parse_url['fragment']) ? '#' . $parse_url['fragment'] : '');
    
    @header('Location: ' . $url);
    exit();
}

/**
 * Returns caching engine name
 * @param $engine
 * @return string
 */
function w3_get_engine_name($engine)
{
    switch ($engine) {
        case 'memcached':
            $engine_name = 'memcached';
            break;
        
        case 'apc':
            $engine_name = 'apc';
            break;
        
        case 'file':
            $engine_name = 'disk';
            break;
        
        default:
            $engine_name = 'N/A';
            break;
    }
    
    return $engine_name;
}

/**
 * Converts value to boolean
 * @param mixed $value
 * @return boolean
 */
function w3_to_boolean($value)
{
    if (is_string($value)) {
        switch (strtolower($value)) {
            case '+':
            case '1':
            case 'y':
            case 'on':
            case 'yes':
            case 'true':
            case 'enabled':
                return true;
            
            case '-':
            case '0':
            case 'n':
            case 'no':
            case 'off':
            case 'false':
            case 'disabled':
                return false;
        }
    }
    
    return (boolean) $value;
}

/**
 * Download url via GET
 * @param $url
 * @return string
 */
function w3_url_get($url)
{
    if (w3_to_boolean(ini_get('allow_url_fopen'))) {
        ini_set('user_agent', W3TC_POWERED_BY);
        return @file_get_contents($url);
    } elseif (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, W3TC_POWERED_BY);
        $contents = curl_exec($ch);
        curl_close($ch);
        return $contents;
    } else {
        $parse_url = @parse_url($url);
        if (isset($parse_url['host'])) {
            $host = $parse_url['host'];
            $port = (isset($parse_url['port']) ? (int) $parse_url['path'] : 80);
            $path = (isset($parse_url['path']) ? trim($parse_url['path']) : '/');
            $query = (isset($parse_url['query']) ? trim($parse_url['query']) : '');
            $request_uri = $path . ($query != '' ? '?' . $query : '');
            $request = sprintf("GET %s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\nUser-Agent: %s\r\n\r\n", $request_uri, $host, W3TC_POWERED_BY);
            if (($fp = @fsockopen($host, $port))) {
                $response = '';
                @fputs($fp, $request);
                while (! @feof($fp)) {
                    $response .= @fgets($fp, 4096);
                }
                @fclose($fp);
                list (, $contents) = explode("\r\n\r\n", $response, 2);
                return $contents;
            }
        }
    }
}

/**
 * Send powered by header
 */
@header('X-Powered-By: ' . W3TC_POWERED_BY);
