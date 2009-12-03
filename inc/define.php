<?php

define('W3TC_VERSION', '0.8.5');
define('W3TC_POWERED_BY', 'W3 Total Cache/' . W3TC_VERSION);
define('W3TC_LINK_URL', 'http://www.w3-edge.com/wordpress-plugins/');
define('W3TC_LINK_NAME', 'WordPress Plugins');

if (! defined('W3TC_DIR')) {
    define('W3TC_DIR', realpath(dirname(__FILE__) . '/..'));
}

define('W3TC_FILE', 'w3-total-cache/w3-total-cache.php');
define('W3TC_LIB_DIR', W3TC_DIR . '/lib');
define('W3TC_LIB_W3_DIR', W3TC_LIB_DIR . '/W3');
define('W3TC_LIB_MINIFY_DIR', W3TC_LIB_DIR . '/Minify');
define('W3TC_PLUGINS_DIR', W3TC_DIR . '/plugins');
define('W3TC_INSTALL_DIR', W3TC_DIR . '/wp-content');
define('W3TC_INSTALL_MINIFY_DIR', W3TC_INSTALL_DIR . '/w3tc/min');

if (! defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', realpath(W3TC_DIR . '/../..'));
}

define('WP_CONTENT_DIR_NAME', basename(WP_CONTENT_DIR));

define('W3TC_CONTENT_DIR_NAME', WP_CONTENT_DIR_NAME . '/w3tc');
define('W3TC_CONTENT_DIR', ABSPATH . W3TC_CONTENT_DIR_NAME);
define('W3TC_CONTENT_MINIFY_DIR_NAME', WP_CONTENT_DIR_NAME . '/w3tc/min');
define('W3TC_CONTENT_MINIFY_DIR', ABSPATH . W3TC_CONTENT_DIR_NAME . '/min');
define('W3TC_CACHE_FILE_DBCACHE_DIR', W3TC_CONTENT_DIR . '/dbcache');
define('W3TC_CACHE_FILE_PGCACHE_DIR', W3TC_CONTENT_DIR . '/pgcache');
define('W3TC_CACHE_FILE_MINIFY_DIR', W3TC_CONTENT_DIR . '/min');
define('W3TC_LOG_DIR', W3TC_CONTENT_DIR . '/log');
define('W3TC_TMP_DIR', W3TC_CONTENT_DIR . '/tmp');

define('W3TC_CONFIG_PATH', WP_CONTENT_DIR . '/w3-total-cache-config' . (($w3_blog_id = w3_get_blog_id()) != '' ? '-' . $w3_blog_id : '') . '.php');
define('W3TC_CONFIG_EXAMPLE_PATH', W3TC_DIR . '/w3-total-cache-config-example.php');

define('W3TC_CDN_COMMAND_UPLOAD', 1);
define('W3TC_CDN_COMMAND_DELETE', 2);
define('W3TC_CDN_TABLE_QUEUE', 'w3tc_cdn_queue');

define('W3TC_PHP5', PHP_VERSION >= 5);
define('W3TC_WIN', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));

define('W3TC_EMAIL', 'w3tc@w3-edge.com');
define('W3TC_FEED_URL', 'http://feeds.feedburner.com/W3TOTALCACHE');
define('W3TC_FEED_ITEMS', 3);
define('W3TC_README_URL', 'http://plugins.trac.wordpress.org/browser/w3-total-cache/trunk/readme.txt?format=txt');

define('W3TC_TWITTER_STATUS', 'I just optimized my #wordpress blog\'s performance using the W3 Total Cache #plugin by @w3edge. Check it out! http://j.mp/A69xX');
define('W3TC_SUPPORT_US_TIMEOUT', 2592000);

/**
 * W3 writable error
 *
 * @param string $path
 * @param boolean $die
 * @return string
 */
function w3_writable_error($path, $die = true)
{
    if (w3_check_open_basedir($path)) {
        $error = sprintf('<strong>%s</strong> could not be created, please run following command:<br /><strong style="color: #f00;">chmod 777 %s</strong><br />then re-activate plugin.', $path, dirname($path));
    } else {
        $error = sprintf('<strong>%s</strong> could not be created, <strong>open_basedir</strong> restriction in effect, please check your php.ini settings:<br /><strong style="color: #f00;">open_basedir = "%s"</strong></br />then re-activate plugin.', $path, ini_get('open_basedir'));
    }
    
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
 * @param string $path
 * @param array $exclude
 * @return void
 */
function w3_rmdir($path, $exclude = array(), $remove = true)
{
    $dir = @opendir($path);
    
    if ($dir) {
        while (($entry = @readdir($dir))) {
            $full_path = $path . '/' . $entry;
            
            if ($entry != '.' && $entry != '..' && ! in_array($full_path, $exclude)) {
                if (is_dir($full_path)) {
                    w3_rmdir($full_path, $exclude);
                } else {
                    @unlink($full_path);
                }
            }
        }
        
        @closedir($dir);
        
        if ($remove) {
            @rmdir($path);
        }
    }
}

/**
 * Recursive empty dir
 * 
 * @param string $path
 * @param array $exclude
 * @return void
 */
function w3_emptydir($path, $exclude = array())
{
    w3_rmdir($path, $exclude, false);
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
        $site_url = rtrim($site_url, '/') . '/';
    }
    
    return $site_url;
}

/**
 * Returns SSL site url
 *
 * @return string
 */
function w3_get_site_url_ssl()
{
    $site_url = w3_get_site_url();
    
    if (w3_is_https()) {
        $site_url = str_replace('http:', 'https:', $site_url);
    }
    
    return $site_url;
}

/**
 * Returns site url regexp
 *
 * @return string
 */
function w3_get_site_url_regexp()
{
    $site_url = w3_get_site_url();
    $domain = preg_replace('~https?://~i', '', $site_url);
    $regexp = 'https?://' . w3_preg_quote($domain);
    return $regexp;
}

/**
 * Get domain URL
 *
 * @return string
 */
function w3_get_domain_url()
{
    $site_url = w3_get_site_url();
    $parse_url = @parse_url($site_url);
    
    if ($parse_url && isset($parse_url['scheme'])) {
        $scheme = $parse_url['scheme'];
        if (isset($parse_url['host'])) {
            $host = $parse_url['host'];
            $port = (isset($parse_url['port']) && $parse_url['port'] != 80 ? ':' . $parse_url['port'] : '');
            $domain_url = sprintf('%s://%s%s/', $scheme, $host, $port);
            return $domain_url;
        }
    }
    
    return false;
}

/**
 * Returns domain url regexp
 *
 * @return string
 */
function w3_get_domain_url_regexp()
{
    $domain_url = w3_get_domain_url();
    $domain = preg_replace('~https?://~i', '', $domain_url);
    $regexp = 'https?://' . w3_preg_quote($domain);
    return $regexp;
}

/**
 * Returns blog path
 *
 * @return string
 */
function w3_get_site_path()
{
    static $site_path = null;
    
    if ($site_path === null) {
        $site_url = w3_get_site_url();
        $domain_url = w3_get_domain_url();
        
        $site_path = str_replace($domain_url, '', $site_url);
        $site_path = rtrim($site_path, '/');
        
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
        
        if (empty($upload_info['error'])) {
            $site_url = w3_get_site_url();
            $upload_info['upload_url'] = ltrim(str_replace($site_url, '', $upload_info['baseurl']), '/');
            $upload_info['upload_dir'] = ltrim(str_replace(ABSPATH, '', $upload_info['basedir']), '/\\');
        } else {
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
 *
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
        
        case 'file_pgcache':
            $engine_name = 'disk (enchanced)';
            break;
        
        default:
            $engine_name = 'N/A';
            break;
    }
    
    return $engine_name;
}

/**
 * Converts value to boolean
 *
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
 * Request URL
 *
 * @param string $method
 * @param string $url
 * @param string $data
 * @param string $auth
 * @return string
 */
function w3_url_request($method, $url, $data = '', $auth = '')
{
    $method = strtoupper($method);
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, W3TC_POWERED_BY);
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        if (! empty($auth)) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
        }
        
        $contents = curl_exec($ch);
        
        curl_close($ch);
        
        return $contents;
    } else {
        $parse_url = @parse_url($url);
        
        if (isset($parse_url['host'])) {
            $host = $parse_url['host'];
            $port = (isset($parse_url['port']) ? (int) $parse_url['path'] : 80);
            $path = (! empty($parse_url['path']) ? trim($parse_url['path']) : '/');
            $query = (isset($parse_url['query']) ? trim($parse_url['query']) : '');
            $request_uri = $path . ($query != '' ? '?' . $query : '');
            
            $headers = array(
                sprintf('%s %s HTTP/1.1', $method, $request_uri), 
                sprintf('Host: %s', $host), 
                sprintf('User-Agent: %s', W3TC_POWERED_BY), 
                'Connection: close'
            );
            
            if (! empty($data)) {
                $headers[] = sprintf('Content-Length: %d', strlen($data));
            }
            
            if (! empty($auth)) {
                $headers[] = sprintf('Authorization: Basic %s', base64_encode($auth));
            }
            
            $request = implode("\r\n", $headers) . "\r\n\r\n" . $data;
            
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
 * Download url via GET
 *
 * @param $url
 * @return string
 */
function w3_url_get($url)
{
    return w3_url_request('GET', $url);
}

/**
 * Send POST request to URL
 *
 * @param string $url
 * @param string $data
 * @param string $auth
 * @return string
 */
function w3_url_post($url, $data = '', $auth = '')
{
    return w3_url_request('POST', $url, $data, $auth);
}

/**
 * Loads plugins
 *
 * @return void
 */
function w3_load_plugins()
{
    $dir = @opendir(W3TC_PLUGINS_DIR);
    
    if ($dir) {
        while (($entry = @readdir($dir))) {
            if (strrchr($entry, '.') === '.php') {
                require_once W3TC_PLUGINS_DIR . '/' . $entry;
            }
        }
        @closedir($dir);
    }
}

/**
 * Returns true if current connection is secure
 *
 * @return boolean
 */
function w3_is_https()
{
    switch (true) {
        case (isset($_SERVER['HTTPS']) && w3_to_boolean($_SERVER['HTTPS'])):
        case (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] == 443):
            return true;
    }
    
    return false;
}

/**
 * Returns realpath of given path
 *
 * @param string $path
 */
function w3_realpath($path)
{
    $path = preg_replace('~[/\\\]+~', '/', $path);
    $parts = explode('/', $path);
    $absolutes = array();
    
    foreach ($parts as $part) {
        if ('.' == $part) {
            continue;
        }
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    
    return implode('/', $absolutes);
}

/**
 * Returns open basedirs
 *
 * @return array
 */
function w3_get_open_basedirs()
{
    $open_basedir_ini = ini_get('open_basedir');
    $open_basedirs = (W3TC_WIN ? preg_split('~[;,]~', $open_basedir_ini) : explode(':', $open_basedir_ini));
    $result = array();
    
    foreach ($open_basedirs as $open_basedir) {
        $open_basedir = trim($open_basedir);
        if ($open_basedir != '') {
            $result[] = w3_realpath($open_basedir);
        }
    }
    
    return $result;
}

/**
 * Checks if path is restricted by open_basedir
 *
 * @param string $path
 * @return boolean
 */
function w3_check_open_basedir($path)
{
    $path = w3_realpath($path);
    $open_basedirs = w3_get_open_basedirs();
    
    if (! count($open_basedirs)) {
        return true;
    }
    
    foreach ($open_basedirs as $open_basedir) {
        if (strstr($path, $open_basedir) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Returns PHP info
 *
 * @return array
 */
function w3_phpinfo()
{
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();
    
    $phpinfo = preg_replace(array(
        '#^.*<body>(.*)</body>.*$#ms', 
        '#<h2>PHP License</h2>.*$#ms', 
        '#<h1>Configuration</h1>#', 
        "#\r?\n#", 
        "#</(h1|h2|h3|tr)>#", 
        '# +<#', 
        "#[ \t]+#", 
        '#&nbsp;#', 
        '#  +#', 
        '# class=".*?"#', 
        '%&#039;%', 
        '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>' . '<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#', 
        '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#', 
        '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#', 
        "# +#", 
        '#<tr>#', 
        '#</tr>#'
    ), array(
        '$1', 
        '', 
        '', 
        '', 
        '</$1>' . "\n", 
        '<', 
        ' ', 
        ' ', 
        ' ', 
        '', 
        ' ', 
        '<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' . "\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>', 
        '<tr><td>PHP Credits Egg</td><td>$1</td></tr>', 
        '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" . '<tr><td>Zend Egg</td><td>$1</td></tr>', 
        ' ', 
        '%S%', 
        '%E%'
    ), $phpinfo);
    
    $sections = explode('<h2>', strip_tags($phpinfo, '<h2><th><td>'));
    
    $pi = array();
    
    foreach ($sections as $section) {
        $n = substr($section, 0, strpos($section, '</h2>'));
        $askapache = null;
        if (preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $askapache, PREG_SET_ORDER)) {
            foreach ($askapache as $m) {
                $pi[$n][$m[1]] = (! isset($m[3]) || $m[2] == $m[3]) ? $m[2] : array_slice($m, 2);
            }
        }
    }
    
    return $pi;
}

/**
 * Returns file mime type
 *
 * @param string $file
 * @return string
 */
function w3_get_mime_type($file)
{
    $mime_types = array(
        'jpg|jpeg|jpe' => 'image/jpeg', 
        'gif' => 'image/gif', 
        'png' => 'image/png', 
        'bmp' => 'image/bmp', 
        'tif|tiff' => 'image/tiff', 
        'ico' => 'image/x-icon', 
        'asf|asx|wax|wmv|wmx' => 'video/asf', 
        'avi' => 'video/avi', 
        'divx' => 'video/divx', 
        'mov|qt' => 'video/quicktime', 
        'mpeg|mpg|mpe' => 'video/mpeg', 
        'txt|c|cc|h' => 'text/plain', 
        'svg' => 'image/svg+xml', 
        'rtx' => 'text/richtext', 
        'css' => 'text/css', 
        'xsl|xsd|xml' => 'text/xml', 
        'htm|html' => 'text/html', 
        'mp3|m4a' => 'audio/mpeg', 
        'mp4|m4v' => 'video/mp4', 
        'ra|ram' => 'audio/x-realaudio', 
        'wav' => 'audio/wav', 
        'ogg' => 'audio/ogg', 
        'mid|midi' => 'audio/midi', 
        'wma' => 'audio/wma', 
        'rtf' => 'application/rtf', 
        'js' => 'application/x-javascript', 
        'pdf' => 'application/pdf', 
        'doc|docx' => 'application/msword', 
        'pot|pps|ppt|pptx' => 'application/vnd.ms-powerpoint', 
        'wri' => 'application/vnd.ms-write', 
        'xla|xls|xlsx|xlt|xlw' => 'application/vnd.ms-excel', 
        'mdb' => 'application/vnd.ms-access', 
        'mpp' => 'application/vnd.ms-project', 
        'swf' => 'application/x-shockwave-flash', 
        'class' => 'application/java', 
        'tar' => 'application/x-tar', 
        'zip' => 'application/zip', 
        'gz|gzip' => 'application/x-gzip', 
        'exe' => 'application/x-msdownload', 
        'odt' => 'application/vnd.oasis.opendocument.text', 
        'odp' => 'application/vnd.oasis.opendocument.presentation', 
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet', 
        'odg' => 'application/vnd.oasis.opendocument.graphics', 
        'odc' => 'application/vnd.oasis.opendocument.chart', 
        'odb' => 'application/vnd.oasis.opendocument.database', 
        'odf' => 'application/vnd.oasis.opendocument.formula'
    );
    
    $file_ext = strrchr($file, '.');
    
    if ($file_ext) {
        $file_ext = ltrim($file_ext, '.');
        foreach ($mime_types as $extension => $mime_type) {
            $exts = explode('|', $extension);
            foreach ($exts as $ext) {
                if ($file_ext == $ext) {
                    return $mime_type;
                }
            }
        }
    }
    
    return false;
}

/**
 * Send twitter update status request
 *
 * @param string $username
 * @param string $password
 * @param string $status
 * @param string $error
 * @return string
 */
function w3_twitter_status_update($username, $password, $status, &$error)
{
    $data = sprintf('status=%s', urlencode($status));
    $auth = sprintf('%s:%s', $username, $password);
    
    $xml = w3_url_post('http://twitter.com/statuses/update.xml', $data, $auth);
    
    if ($xml) {
        $matches = null;
        
        if (preg_match('~<id>(\d+)</id>~', $xml, $matches)) {
            return $matches[1];
        } elseif (preg_match('~<error>([^<]+)</error>~', $xml, $matches)) {
            $error = $matches[1];
        } else {
            $error = 'Unknown error.';
        }
    } else {
        $error = 'Unable to send request.';
    }
    
    return false;
}

/**
 * Quotes regular expression string
 * 
 * @param string $regexp
 * @return string
 */
function w3_preg_quote($string, $delimiter = null)
{
    $string = preg_quote($string, $delimiter);
    $string = strtr($string, array(
        ' ' => '\ '
    ));
    
    return $string;
}

/**
 * Send powered by header
 */
function w3_send_x_powered_by()
{
    switch (true) {
        case defined('DOING_AJAX'):
        case defined('DOING_CRON'):
        case defined('APP_REQUEST'):
        case defined('XMLRPC_REQUEST'):
        case defined('WP_ADMIN'):
            return;
    }
    
    @header('X-Powered-By: ' . W3TC_POWERED_BY);
}

w3_send_x_powered_by();
