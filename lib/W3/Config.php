<?php

/**
 * W3 Config object
 */

/**
 * Class W3_Config
 */
class W3_Config
{
    /**
     * Tabs count
     *
     * @var integer
     */
    var $_tabs = 0;
    
    /**
     * Array of config values
     *
     * @var array
     */
    var $_config = array();
    
    /**
     * Config keys
     */
    var $_keys = array(
        'dbcache.enabled' => 'boolean', 
        'dbcache.debug' => 'boolean', 
        'dbcache.engine' => 'string', 
        'dbcache.file.gc' => 'integer', 
        'dbcache.memcached.engine' => 'string', 
        'dbcache.memcached.servers' => 'array', 
        'dbcache.reject.logged' => 'boolean', 
        'dbcache.reject.uri' => 'array', 
        'dbcache.reject.cookie' => 'array', 
        'dbcache.reject.sql' => 'array', 
        'dbcache.lifetime' => 'integer', 
        
        'pgcache.enabled' => 'boolean', 
        'pgcache.debug' => 'boolean', 
        'pgcache.engine' => 'string', 
        'pgcache.file.gc' => 'integer', 
        'pgcache.memcached.engine' => 'string', 
        'pgcache.memcached.servers' => 'array', 
        'pgcache.lifetime' => 'integer', 
        'pgcache.compression' => 'string', 
        'pgcache.cache.query' => 'boolean', 
        'pgcache.cache.home' => 'boolean', 
        'pgcache.cache.feed' => 'boolean', 
        'pgcache.cache.404' => 'boolean', 
        'pgcache.cache.flush' => 'boolean', 
        'pgcache.cache.headers' => 'array', 
        'pgcache.accept.files' => 'array', 
        'pgcache.reject.logged' => 'boolean', 
        'pgcache.reject.uri' => 'array', 
        'pgcache.reject.ua' => 'array', 
        'pgcache.reject.cookie' => 'array', 
        'pgcache.mobile.redirect' => 'string', 
        'pgcache.mobile.agents' => 'array', 
        
        'minify.enabled' => 'boolean', 
        'minify.debug' => 'boolean', 
        'minify.engine' => 'string', 
        'minify.file.locking' => 'boolean', 
        'minify.file.gc' => 'integer', 
        'minify.memcached.engine' => 'string', 
        'minify.memcached.servers' => 'array', 
        'minify.rewrite' => 'boolean', 
        'minify.fixtime' => 'integer', 
        'minify.compress' => 'boolean', 
        'minify.compress.ie6' => 'boolean', 
        'minify.options' => 'array', 
        'minify.symlinks' => 'array', 
        'minify.maxage' => 'integer', 
        'minify.lifetime' => 'integer', 
        'minify.upload' => 'boolean', 
        'minify.html.enable' => 'boolean', 
        'minify.html.reject.admin' => 'boolean', 
        'minify.html.inline.css' => 'boolean', 
        'minify.html.inline.js' => 'boolean', 
        'minify.html.strip.crlf' => 'boolean', 
        'minify.css.enable' => 'boolean', 
        'minify.css.strip.comments' => 'boolean', 
        'minify.css.strip.crlf' => 'boolean', 
        'minify.css.groups' => 'array', 
        'minify.js.enable' => 'boolean', 
        'minify.js.combine.header' => 'boolean', 
        'minify.js.combine.footer' => 'boolean', 
        'minify.js.strip.comments' => 'boolean', 
        'minify.js.strip.crlf' => 'boolean', 
        'minify.js.groups' => 'array', 
        'minify.reject.ua' => 'array', 
        'minify.reject.uri' => 'array', 
        
        'cdn.enabled' => 'boolean', 
        'cdn.debug' => 'boolean', 
        'cdn.engine' => 'string', 
        'cdn.includes.enable' => 'boolean', 
        'cdn.includes.files' => 'string', 
        'cdn.theme.enable' => 'boolean', 
        'cdn.theme.files' => 'string', 
        'cdn.minify.enable' => 'boolean', 
        'cdn.custom.enable' => 'boolean', 
        'cdn.custom.files' => 'array', 
        'cdn.import.external' => 'boolean', 
        'cdn.import.files' => 'string', 
        'cdn.limit.queue' => 'integer', 
        'cdn.mirror.domain' => 'string', 
        'cdn.ftp.host' => 'string', 
        'cdn.ftp.user' => 'string', 
        'cdn.ftp.pass' => 'string', 
        'cdn.ftp.path' => 'string', 
        'cdn.ftp.pasv' => 'boolean', 
        'cdn.ftp.domain' => 'string', 
        'cdn.s3.key' => 'string', 
        'cdn.s3.secret' => 'string', 
        'cdn.s3.bucket' => 'string', 
        'cdn.cf.key' => 'string', 
        'cdn.cf.secret' => 'string', 
        'cdn.cf.bucket' => 'string', 
        'cdn.cf.id' => 'string', 
        'cdn.cf.cname' => 'string', 
        'cdn.reject.ua' => 'array', 
        'cdn.reject.uri' => 'array', 
        
        'common.support' => 'string', 
        'common.install' => 'integer', 
        'common.tweeted' => 'integer', 
        'common.widget.latest' => 'boolean', 
        
        'notes.defaults' => 'boolean', 
        'notes.wp_content_perms' => 'boolean', 
        'notes.cdn_first_time' => 'boolean', 
        'notes.no_memcached_nor_apc' => 'boolean', 
        'notes.php_is_old' => 'boolean', 
        'notes.theme_changed' => 'boolean', 
        'notes.wp_upgraded' => 'boolean', 
        'notes.plugins_updated' => 'boolean', 
        'notes.cdn_upload' => 'boolean', 
        'notes.need_empty_pgcache' => 'boolean', 
        'notes.need_empty_minify' => 'boolean', 
        'notes.pgcache_rules_core' => 'boolean', 
        'notes.pgcache_rules_cache' => 'boolean', 
        'notes.minify_rules' => 'boolean', 
        'notes.support_us' => 'boolean', 
        'notes.no_curl' => 'boolean'
    );
    
    var $_defaults = array(
        'dbcache.enabled' => true, 
        'dbcache.debug' => false, 
        'dbcache.engine' => 'file', 
        'dbcache.file.gc' => 3600, 
        'dbcache.memcached.engine' => 'auto', 
        'dbcache.memcached.servers' => array(
            'localhost:11211'
        ), 
        'dbcache.reject.logged' => true, 
        'dbcache.reject.uri' => array(), 
        'dbcache.reject.cookie' => array(), 
        'dbcache.reject.sql' => array(), 
        'dbcache.lifetime' => 180, 
        
        'pgcache.enabled' => true, 
        'pgcache.debug' => false, 
        'pgcache.engine' => 'file', 
        'pgcache.file.gc' => 3600, 
        'pgcache.memcached.engine' => 'auto', 
        'pgcache.memcached.servers' => array(
            'localhost:11211'
        ), 
        'pgcache.lifetime' => 3600, 
        'pgcache.compression' => 'gzip', 
        'pgcache.cache.query' => true, 
        'pgcache.cache.home' => true, 
        'pgcache.cache.feed' => true, 
        'pgcache.cache.404' => false, 
        'pgcache.cache.flush' => false, 
        'pgcache.cache.headers' => array(
            'Last-Modified', 
            'Content-Type', 
            'X-Pingback'
        ), 
        'pgcache.accept.files' => array(
            'wp-comments-popup.php', 
            'wp-links-opml.php', 
            'wp-locations.php'
        ), 
        'pgcache.reject.logged' => true, 
        'pgcache.reject.uri' => array(
            'wp-.*\.php', 
            'index\.php'
        ), 
        'pgcache.reject.ua' => array(
            'bot', 
            'ia_archive', 
            'slurp', 
            'crawl', 
            'spider'
        ), 
        'pgcache.reject.cookie' => array(), 
        'pgcache.mobile.redirect' => '', 
        'pgcache.mobile.agents' => array(
            'Android', 
            '2.0 MMP', 
            '240x320', 
            'AvantGo', 
            'BlackBerry', 
            'Blazer', 
            'Cellphone', 
            'Danger', 
            'DoCoMo', 
            'Elaine/3.0', 
            'EudoraWeb', 
            'hiptop', 
            'IEMobile', 
            'iPhone', 
            'iPod', 
            'KYOCERA/WX310K', 
            'LG/U990', 
            'MIDP-2.0', 
            'MMEF20', 
            'MOT-V', 
            'NetFront', 
            'Newt', 
            'Nintendo Wii', 
            'Nitro', 
            'Nokia', 
            'Opera Mini', 
            'Palm', 
            'Playstation Portable', 
            'portalmmm', 
            'Proxinet', 
            'ProxiNet', 
            'SHARP-TQ-GX10', 
            'Small', 
            'SonyEricsson', 
            'Symbian OS', 
            'SymbianOS', 
            'TS21i-10', 
            'UP.Browser', 
            'UP.Link', 
            'Windows CE', 
            'WinWAP', 
            'Ericsson', 
            'htc', 
            'Huawei', 
            'MobilePhone', 
            'Motorola', 
            'nokia', 
            'Novarra', 
            'O2', 
            'Samsung', 
            'Sanyo', 
            'Smartphone', 
            'Symbian', 
            'Toshiba', 
            'Treo', 
            'vodafone', 
            'Xda', 
            'Alcatel', 
            'Amoi', 
            'ASUS', 
            'Audiovox', 
            'AU-MIC', 
            'BenQ', 
            'Bird', 
            'CDM', 
            'dopod', 
            'Fly', 
            'Haier', 
            'HP.iPAQ', 
            'i-mobile', 
            'KDDI', 
            'KONKA', 
            'KWC', 
            'Lenovo', 
            'LG', 
            'NEWGEN', 
            'Panasonic', 
            'PANTECH', 
            'PG', 
            'Philips', 
            'PPC', 
            'PT', 
            'Qtek', 
            'Sagem', 
            'SCH', 
            'SEC', 
            'Sendo', 
            'SGH', 
            'Sharp', 
            'SIE', 
            'SoftBank', 
            'SPH', 
            'UTS', 
            'Vertu', 
            'Opera.Mobi', 
            'Windows.CE', 
            'ZTE'
        ), 
        
        'minify.enabled' => true, 
        'minify.debug' => false, 
        'minify.engine' => 'file', 
        'minify.file.locking' => true, 
        'minify.file.gc' => 86400, 
        'minify.memcached.engine' => 'auto', 
        'minify.memcached.servers' => array(
            'localhost:11211'
        ), 
        'minify.rewrite' => true, 
        'minify.fixtime' => 0, 
        'minify.compress' => true, 
        'minify.compress.ie6' => true, 
        'minify.options' => array(
            'bubbleCssImports' => false, 
            'minApp' => array(
                'groupsOnly' => false, 
                'maxFiles' => 20
            )
        ), 
        'minify.symlinks' => array(), 
        'minify.maxage' => 86400, 
        'minify.lifetime' => 86400, 
        'minify.upload' => true, 
        'minify.html.enable' => true, 
        'minify.html.reject.admin' => true, 
        'minify.html.inline.css' => false, 
        'minify.html.inline.js' => false, 
        'minify.html.strip.crlf' => false, 
        'minify.css.enable' => true, 
        'minify.css.strip.comments' => false, 
        'minify.css.strip.crlf' => false, 
        'minify.css.groups' => array(), 
        'minify.js.enable' => true, 
        'minify.js.combine.header' => false, 
        'minify.js.combine.footer' => false, 
        'minify.js.strip.comments' => false, 
        'minify.js.strip.crlf' => false, 
        'minify.js.groups' => array(), 
        'minify.reject.ua' => array(), 
        'minify.reject.uri' => array(), 
        
        'cdn.enabled' => false, 
        'cdn.debug' => false, 
        'cdn.engine' => 'ftp', 
        'cdn.includes.enable' => true, 
        'cdn.includes.files' => '*.css;*.js;*.gif;*.png;*.jpg', 
        'cdn.theme.enable' => true, 
        'cdn.theme.files' => '*.css;*.js;*.gif;*.png;*.jpg;*.ico', 
        'cdn.minify.enable' => true, 
        'cdn.custom.enable' => true, 
        'cdn.custom.files' => array(
            'favicon.ico'
        ), 
        'cdn.import.external' => false, 
        'cdn.import.files' => '*.jpg;*.png;*.gif;*.avi;*.wmv;*.mpg;*.wav;*.mp3;*.txt;*.rtf;*.doc;*.xls;*.rar;*.zip;*.tar;*.gz;*.exe', 
        'cdn.limit.queue' => 25, 
        'cdn.mirror.domain' => '', 
        'cdn.ftp.host' => '', 
        'cdn.ftp.user' => '', 
        'cdn.ftp.pass' => '', 
        'cdn.ftp.path' => '', 
        'cdn.ftp.pasv' => false, 
        'cdn.ftp.domain' => '', 
        'cdn.s3.key' => '', 
        'cdn.s3.secret' => '', 
        'cdn.s3.bucket' => '', 
        'cdn.cf.key' => '', 
        'cdn.cf.secret' => '', 
        'cdn.cf.bucket' => '', 
        'cdn.cf.id' => '', 
        'cdn.cf.cname' => '', 
        'cdn.reject.ua' => array(), 
        'cdn.reject.uri' => array(), 
        
        'common.support' => '', 
        'common.install' => 0, 
        'common.tweeted' => 0, 
        'common.widget.latest' => true, 
        
        'notes.defaults' => true, 
        'notes.wp_content_perms' => true, 
        'notes.cdn_first_time' => true, 
        'notes.no_memcached_nor_apc' => true, 
        'notes.php_is_old' => true, 
        'notes.theme_changed' => false, 
        'notes.wp_upgraded' => false, 
        'notes.plugins_updated' => false, 
        'notes.cdn_upload' => false, 
        'notes.need_empty_pgcache' => false, 
        'notes.need_empty_minify' => false, 
        'notes.pgcache_rules_core' => true, 
        'notes.pgcache_rules_cache' => true, 
        'notes.minify_rules' => true, 
        'notes.support_us' => true, 
        'notes.no_curl' => true
    );
    
    /**
     * PHP5 Constructor
     * @param boolean $check_config
     */
    function __construct($check_config = true)
    {
        $this->load_defaults();
        
        if (! $this->load() && $check_config) {
            die(sprintf('<strong>W3 Total Cache Error:</strong> Unable to read config file or it is broken. Please create <strong>%s</strong> from <strong>%s</strong>.', W3TC_CONFIG_PATH, W3TC_CONFIG_EXAMPLE_PATH));
        }
    }
    
    /**
     * PHP4 Constructor
     * @param booleab $check_config
     */
    function W3_Config($check_config = true)
    {
        $this->__construct($check_config);
    }
    
    /**
     * Returns config value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_keys) && array_key_exists($key, $this->_config)) {
            $value = $this->_config[$key];
        } else {
            if ($default === null && array_key_exists($key, $this->_defaults)) {
                $value = $this->_defaults[$key];
            } else {
                $value = $default;
            }
        }
        
        switch ($key) {
            /**
             * Disable compression if compression functions don't exist
             */
            case 'pgcache.compression':
                if ((stristr($value, 'gzip') && ! function_exists('gzencode')) || (stristr($value, 'deflate') && ! function_exists('gzdeflate'))) {
                    return '';
                }
                break;
            
            /**
             * Don't support additional headers caching when PHP5 is not installed
             */
            case 'pgcache.cache.headers':
                if (! W3TC_PHP5) {
                    return array();
                }
                break;
            
            /**
             * Disabled some page cache options when enchanced mode enabled
             */
            case 'pgcache.cache.query':
                if ($this->get_boolean('pgcache.enabled') && $this->get_string('pgcache.engine') == 'file_pgcache') {
                    return false;
                }
                break;
            
            case 'pgcache.cache.headers':
                if ($this->get_boolean('pgcache.enabled') && $this->get_string('pgcache.engine') == 'file_pgcache') {
                    return array();
                }
                break;
            
            /**
             * Disabled minify when PHP5 is not installed
             */
            case 'minify.enabled':
            case 'cdn.minify.enable':
                if (! W3TC_PHP5) {
                    return false;
                }
                break;
            
            case 'cdn.engine':
                if (($value == 's3' || $value == 'cf') && (! W3TC_PHP5 || ! function_exists('curl_init'))) {
                    return 'mirror';
                }
                break;
        }
        
        return $value;
    }
    
    /**
     * Returns string value
     *
     * @param string $key
     * @param string $default
     * @param boolean $trim
     * @return string
     */
    function get_string($key, $default = '', $trim = true)
    {
        $value = (string) $this->get($key, $default);
        
        return ($trim ? trim($value) : $value);
    }
    
    /**
     * Returns integer value
     *
     * @param string $key
     * @param integer $default
     * @return integer
     */
    function get_integer($key, $default = 0)
    {
        return (integer) $this->get($key, $default);
    }
    
    /**
     * Returns boolean value
     *
     * @param string $key
     * @param boolean $default
     * @return boolean
     */
    function get_boolean($key, $default = false)
    {
        return (boolean) $this->get($key, $default);
    }
    
    /**
     * Returns array value
     *
     * @param string $key
     * @param array $default
     * @return array
     */
    function get_array($key, $default = array())
    {
        return (array) $this->get($key, $default);
    }
    
    /**
     * Sets config value
     *
     * @param string $key
     * @param string $value
     */
    function set($key, $value)
    {
        if (array_key_exists($key, $this->_keys)) {
            $type = $this->_keys[$key];
            settype($value, $type);
            $this->_config[$key] = $value;
        }
        
        return false;
    }
    
    /**
     * Flush config
     */
    function flush()
    {
        $this->_config = array();
    }
    
    /**
     * Reads config from file
     *
     * @param string $file
     * @return array
     */
    function read($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $config = @include $file;
            
            if (! is_array($config)) {
                return false;
            }
            
            foreach ($config as $key => $value) {
                $this->set($key, $value);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Reads config from request
     */
    function read_request()
    {
        require_once W3TC_LIB_W3_DIR . '/Request.php';
        
        foreach ($this->_keys as $key => $type) {
            $request_key = str_replace('.', '_', $key);
            
            if (! isset($_REQUEST[$request_key])) {
                continue;
            }
            
            switch ($type) {
                case 'string':
                    $this->set($key, W3_Request::get_string($request_key));
                    break;
                
                case 'int':
                case 'integer':
                    $this->set($key, W3_Request::get_integer($request_key));
                    break;
                
                case 'float':
                case 'double':
                    $this->set($key, W3_Request::get_double($request_key));
                    break;
                
                case 'bool':
                case 'boolean':
                    $this->set($key, W3_Request::get_boolean($request_key));
                    break;
                
                case 'array':
                    $this->set($key, W3_Request::get_array($request_key));
                    break;
            }
        }
    }
    
    /**
     * Writes config
     *
     * @param string $file
     * @return boolean
     */
    function write($file)
    {
        @$fp = fopen($file, 'w');
        
        if (! $fp) {
            return false;
        }
        
        @fputs($fp, "<?php\r\n\r\nreturn array(\r\n");
        
        $this->_tabs = 1;
        
        foreach ($this->_config as $key => $value) {
            $this->_write($fp, $key, $value);
        }
        
        @fputs($fp, ");");
        @fclose($fp);
        
        return true;
    }
    
    /**
     * Writes config pair
     *
     * @param resource $fp
     * @param string $key
     * @param mixed $value
     */
    function _write($fp, $key, $value)
    {
        @fputs($fp, str_repeat("\t", $this->_tabs));
        
        if (is_string($key)) {
            @fputs($fp, sprintf("'%s' => ", addslashes($key)));
        }
        
        switch (gettype($value)) {
            case 'object':
            case 'array':
                @fputs($fp, "array(\r\n");
                ++ $this->_tabs;
                foreach ((array) $value as $k => $v) {
                    $this->_write($fp, $k, $v);
                }
                -- $this->_tabs;
                @fputs($fp, sprintf("%s),\r\n", str_repeat("\t", $this->_tabs)));
                return;
            
            case 'integer':
                $data = (string) $value;
                break;
            
            case 'double':
                $data = (string) $value;
                break;
            
            case 'boolean':
                $data = ($value ? 'true' : 'false');
                break;
            
            case 'NULL':
                $data = 'null';
                break;
            
            default:
            case 'string':
                $data = "'" . addslashes((string) $value) . "'";
                break;
        }
        
        @fputs($fp, $data . ",\r\n");
    }
    
    /**
     * Loads config
     *
     * @return boolean
     */
    function load()
    {
        return $this->read(W3TC_CONFIG_PATH);
    }
    
    /**
     * Loads config dfefaults
     */
    function load_defaults()
    {
        foreach ($this->_defaults as $key => $value) {
            $this->set($key, $value);
        }
    }
    
    /**
     * Saves config
     *
     * @return boolean
     */
    function save()
    {
        return $this->write(W3TC_CONFIG_PATH);
    }
    
    /**
     * Returns config instance
     *
     * @param boolean $check_config
     * @return W3_Config
     */
    function &instance($check_config = true)
    {
        static $instances = array();
        
        if (! isset($instances[0])) {
            $class = __CLASS__;
            $instances[0] = & new $class($check_config);
        }
        
        return $instances[0];
    }
}
