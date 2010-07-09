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
        'dbcache.file.locking' => 'boolean', 
        'dbcache.memcached.servers' => 'array', 
        'dbcache.memcached.persistant' => 'boolean', 
        'dbcache.reject.logged' => 'boolean', 
        'dbcache.reject.uri' => 'array', 
        'dbcache.reject.cookie' => 'array', 
        'dbcache.reject.sql' => 'array', 
        'dbcache.lifetime' => 'integer', 
        
        'objectcache.enabled' => 'boolean', 
        'objectcache.debug' => 'boolean', 
        'objectcache.engine' => 'string', 
        'objectcache.file.gc' => 'integer', 
        'objectcache.file.locking' => 'boolean', 
        'objectcache.memcached.servers' => 'array', 
        'objectcache.memcached.persistant' => 'boolean', 
        'objectcache.reject.admin' => 'boolean', 
        'objectcache.reject.uri' => 'array', 
        'objectcache.groups.global' => 'array', 
        'objectcache.groups.nonpersistent' => 'array', 
        'objectcache.lifetime' => 'integer', 
        
        'pgcache.enabled' => 'boolean', 
        'pgcache.debug' => 'boolean', 
        'pgcache.engine' => 'string', 
        'pgcache.file.gc' => 'integer', 
        'pgcache.file.locking' => 'boolean', 
        'pgcache.memcached.servers' => 'array', 
        'pgcache.memcached.persistant' => 'boolean', 
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
        'pgcache.varnish.enabled' => 'boolean', 
        'pgcache.varnish.servers' => 'array', 
        'pgcache.prime.enabled' => 'boolean', 
        'pgcache.prime.interval' => 'integer', 
        'pgcache.prime.limit' => 'integer', 
        'pgcache.prime.sitemap' => 'string', 
        
        'minify.enabled' => 'boolean', 
        'minify.debug' => 'boolean', 
        'minify.engine' => 'string', 
        'minify.file.gc' => 'integer', 
        'minify.file.locking' => 'boolean', 
        'minify.memcached.servers' => 'array', 
        'minify.memcached.persistant' => 'boolean', 
        'minify.rewrite' => 'boolean', 
        'minify.fixtime' => 'integer', 
        'minify.options' => 'array', 
        'minify.symlinks' => 'array', 
        'minify.lifetime' => 'integer', 
        'minify.upload' => 'boolean', 
        'minify.html.enable' => 'boolean', 
        'minify.html.reject.feed' => 'boolean', 
        'minify.html.inline.css' => 'boolean', 
        'minify.html.inline.js' => 'boolean', 
        'minify.html.strip.crlf' => 'boolean', 
        'minify.html.comments.ignore' => 'array', 
        'minify.css.enable' => 'boolean', 
        'minify.css.combine' => 'boolean', 
        'minify.css.strip.comments' => 'boolean', 
        'minify.css.strip.crlf' => 'boolean', 
        'minify.css.groups' => 'array', 
        'minify.js.enable' => 'boolean', 
        'minify.js.combine.header' => 'boolean', 
        'minify.js.combine.body' => 'boolean', 
        'minify.js.combine.footer' => 'boolean', 
        'minify.js.strip.comments' => 'boolean', 
        'minify.js.strip.crlf' => 'boolean', 
        'minify.js.groups' => 'array', 
        'minify.reject.ua' => 'array', 
        'minify.reject.uri' => 'array', 
        'minify.error.notification' => 'string', 
        'minify.error.notification.last' => 'integer', 
        
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
        'cdn.queue.limit' => 'integer', 
        'cdn.force.rewrite' => 'boolean', 
        'cdn.autoupload.enabled' => 'boolean', 
        'cdn.autoupload.interval' => 'integer', 
        'cdn.mirror.domain' => 'array', 
        'cdn.netdna.apiid' => 'string', 
        'cdn.netdna.apikey' => 'string', 
        'cdn.netdna.domain' => 'array', 
        'cdn.ftp.host' => 'string', 
        'cdn.ftp.user' => 'string', 
        'cdn.ftp.pass' => 'string', 
        'cdn.ftp.path' => 'string', 
        'cdn.ftp.pasv' => 'boolean', 
        'cdn.ftp.domain' => 'array', 
        'cdn.s3.key' => 'string', 
        'cdn.s3.secret' => 'string', 
        'cdn.s3.bucket' => 'string', 
        'cdn.s3.cname' => 'array', 
        'cdn.cf.key' => 'string', 
        'cdn.cf.secret' => 'string', 
        'cdn.cf.bucket' => 'string', 
        'cdn.cf.id' => 'string', 
        'cdn.cf.cname' => 'array', 
        'cdn.rscf.user' => 'string', 
        'cdn.rscf.key' => 'string', 
        'cdn.rscf.container' => 'string', 
        'cdn.rscf.id' => 'string', 
        'cdn.rscf.cname' => 'array', 
        'cdn.reject.ua' => 'array', 
        'cdn.reject.uri' => 'array', 
        'cdn.reject.files' => 'array', 
        
        'browsercache.enabled' => 'boolean', 
        'browsercache.no404wp' => 'boolean', 
        'browsercache.cssjs.compression' => 'boolean', 
        'browsercache.cssjs.expires' => 'boolean', 
        'browsercache.cssjs.lifetime' => 'integer', 
        'browsercache.cssjs.cache.control' => 'boolean', 
        'browsercache.cssjs.cache.policy' => 'string', 
        'browsercache.cssjs.etag' => 'boolean', 
        'browsercache.cssjs.w3tc' => 'boolean', 
        'browsercache.html.compression' => 'boolean', 
        'browsercache.html.expires' => 'boolean', 
        'browsercache.html.lifetime' => 'integer', 
        'browsercache.html.cache.control' => 'boolean', 
        'browsercache.html.cache.policy' => 'string', 
        'browsercache.html.etag' => 'boolean', 
        'browsercache.html.w3tc' => 'boolean', 
        'browsercache.other.compression' => 'boolean', 
        'browsercache.other.expires' => 'boolean', 
        'browsercache.other.lifetime' => 'integer', 
        'browsercache.other.cache.control' => 'boolean', 
        'browsercache.other.cache.policy' => 'string', 
        'browsercache.other.etag' => 'boolean', 
        'browsercache.other.w3tc' => 'boolean', 
        
        'mobile.enabled' => 'boolean', 
        'mobile.groups' => 'array', 
        
        'common.support' => 'string', 
        'common.install' => 'integer', 
        'common.tweeted' => 'integer', 
        
        'widget.latest.enabled' => 'boolean', 
        'widget.latest.items' => 'integer', 
        
        'notes.wp_content_perms' => 'boolean', 
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
        'notes.no_curl' => 'boolean', 
        'notes.no_zlib' => 'boolean', 
        'notes.zlib_output_compression' => 'boolean', 
        'notes.no_permalink_rules' => 'boolean', 
        'notes.browsercache_rules_cache' => 'boolean', 
        'notes.browsercache_rules_no404wp' => 'boolean', 
        'notes.minify_error' => 'boolean'
    );
    
    var $_defaults = array(
        'dbcache.enabled' => false, 
        'dbcache.debug' => false, 
        'dbcache.engine' => 'file', 
        'dbcache.file.gc' => 3600, 
        'dbcache.file.locking' => false, 
        'dbcache.memcached.servers' => array(
            '127.0.0.1:11211'
        ), 
        'dbcache.memcached.persistant' => true, 
        'dbcache.reject.logged' => true, 
        'dbcache.reject.uri' => array(), 
        'dbcache.reject.cookie' => array(), 
        'dbcache.reject.sql' => array(
            'gdsr_', 
            'wp_rg_'
        ), 
        'dbcache.lifetime' => 180, 
        
        'objectcache.enabled' => false, 
        'objectcache.debug' => false, 
        'objectcache.engine' => 'file', 
        'objectcache.file.gc' => 3600, 
        'objectcache.file.locking' => false, 
        'objectcache.memcached.servers' => array(
            '127.0.0.1:11211'
        ), 
        'objectcache.memcached.persistant' => true, 
        'objectcache.reject.admin' => true, 
        'objectcache.reject.uri' => array(), 
        'objectcache.groups.global' => array(
            'users', 
            'userlogins', 
            'usermeta', 
            'site-options', 
            'site-lookup', 
            'blog-lookup', 
            'blog-details', 
            'rss'
        ), 
        'objectcache.groups.nonpersistent' => array(
            'comment', 
            'counts'
        ), 
        'objectcache.lifetime' => 180, 
        
        'pgcache.enabled' => false, 
        'pgcache.debug' => false, 
        'pgcache.engine' => 'file_pgcache', 
        'pgcache.file.gc' => 3600, 
        'pgcache.file.locking' => false, 
        'pgcache.memcached.servers' => array(
            '127.0.0.1:11211'
        ), 
        'pgcache.memcached.persistant' => true, 
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
        'pgcache.reject.ua' => array(), 
        'pgcache.reject.cookie' => array(), 
        'pgcache.varnish.enabled' => false, 
        'pgcache.varnish.servers' => array(), 
        'pgcache.prime.enabled' => false, 
        'pgcache.prime.interval' => 86400, 
        'pgcache.prime.limit' => 100, 
        'pgcache.prime.sitemap' => '', 
        
        'minify.enabled' => false, 
        'minify.debug' => false, 
        'minify.engine' => 'file', 
        'minify.file.gc' => 86400, 
        'minify.file.locking' => false, 
        'minify.memcached.servers' => array(
            '127.0.0.1:11211'
        ), 
        'minify.memcached.persistant' => true, 
        'minify.rewrite' => true, 
        'minify.fixtime' => 0, 
        'minify.options' => array(
            'bubbleCssImports' => true, 
            'minApp' => array(
                'groupsOnly' => false, 
                'maxFiles' => 20
            )
        ), 
        'minify.symlinks' => array(), 
        'minify.lifetime' => 86400, 
        'minify.upload' => true, 
        'minify.html.enable' => false, 
        'minify.html.reject.feed' => false, 
        'minify.html.inline.css' => false, 
        'minify.html.inline.js' => false, 
        'minify.html.strip.crlf' => false, 
        'minify.html.comments.ignore' => array(
            'google_ad_section_', 
            'RSPEAK_'
        ), 
        'minify.css.enable' => true, 
        'minify.css.combine' => false, 
        'minify.css.strip.comments' => false, 
        'minify.css.strip.crlf' => false, 
        'minify.css.groups' => array(), 
        'minify.js.enable' => true, 
        'minify.js.combine.header' => false, 
        'minify.js.combine.body' => false, 
        'minify.js.combine.footer' => false, 
        'minify.js.strip.comments' => false, 
        'minify.js.strip.crlf' => false, 
        'minify.js.groups' => array(), 
        'minify.reject.ua' => array(), 
        'minify.reject.uri' => array(), 
        'minify.error.notification' => '', 
        'minify.error.notification.last' => 0, 
        
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
            'favicon.ico', 
            'wp-content/gallery/*'
        ), 
        'cdn.import.external' => false, 
        'cdn.import.files' => '*.jpg;*.png;*.gif;*.avi;*.wmv;*.mpg;*.wav;*.mp3;*.txt;*.rtf;*.doc;*.xls;*.rar;*.zip;*.tar;*.gz;*.exe', 
        'cdn.queue.limit' => 25, 
        'cdn.force.rewrite' => false, 
        'cdn.autoupload.enabled' => false, 
        'cdn.autoupload.interval' => 3600, 
        'cdn.mirror.domain' => array(), 
        'cdn.netdna.apiid' => '', 
        'cdn.netdna.apikey' => '', 
        'cdn.netdna.domain' => array(), 
        'cdn.ftp.host' => '', 
        'cdn.ftp.user' => '', 
        'cdn.ftp.pass' => '', 
        'cdn.ftp.path' => '', 
        'cdn.ftp.pasv' => false, 
        'cdn.ftp.domain' => array(), 
        'cdn.s3.key' => '', 
        'cdn.s3.secret' => '', 
        'cdn.s3.bucket' => '', 
        'cdn.s3.cname' => array(), 
        'cdn.cf.key' => '', 
        'cdn.cf.secret' => '', 
        'cdn.cf.bucket' => '', 
        'cdn.cf.id' => '', 
        'cdn.cf.cname' => array(), 
        'cdn.rscf.user' => '', 
        'cdn.rscf.key' => '', 
        'cdn.rscf.container' => '', 
        'cdn.rscf.id' => '', 
        'cdn.rscf.cname' => array(), 
        'cdn.reject.ua' => array(), 
        'cdn.reject.uri' => array(), 
        'cdn.reject.files' => array(
            'wp-content/uploads/wpcf7_captcha/*'
        ), 
        
        'browsercache.enabled' => false, 
        'browsercache.no404wp' => true, 
        'browsercache.cssjs.compression' => true, 
        'browsercache.cssjs.expires' => true, 
        'browsercache.cssjs.lifetime' => 3600, 
        'browsercache.cssjs.cache.control' => true, 
        'browsercache.cssjs.cache.policy' => 'cache_validation', 
        'browsercache.cssjs.etag' => true, 
        'browsercache.cssjs.w3tc' => true, 
        'browsercache.html.compression' => true, 
        'browsercache.html.expires' => true, 
        'browsercache.html.lifetime' => 3600, 
        'browsercache.html.cache.control' => true, 
        'browsercache.html.cache.policy' => 'cache_validation', 
        'browsercache.html.etag' => true, 
        'browsercache.html.w3tc' => true, 
        'browsercache.other.compression' => true, 
        'browsercache.other.expires' => true, 
        'browsercache.other.lifetime' => 31536000, 
        'browsercache.other.cache.control' => true, 
        'browsercache.other.cache.policy' => 'cache_validation', 
        'browsercache.other.etag' => true, 
        'browsercache.other.w3tc' => true, 
        
        'mobile.enabled' => true, 
        'mobile.groups' => array(
            'apple' => array(
                'theme' => '', 
                'enabled' => true, 
                'redirect' => '', 
                'agents' => array(
                    'iPhone', 
                    'iPad', 
                    'iPod'
                )
            ), 
            'mobile' => array(
                'theme' => '', 
                'enabled' => true, 
                'redirect' => '', 
                'agents' => array(
                    '2.0 MMP', 
                    '240x320', 
                    'ASUS', 
                    'AU-MIC', 
                    'Alcatel', 
                    'Amoi', 
                    'Android', 
                    'Audiovox', 
                    'AvantGo', 
                    'BenQ', 
                    'Bird', 
                    'BlackBerry', 
                    'Blazer', 
                    'CDM', 
                    'Cellphone', 
                    'DDIPOCKET', 
                    'Danger', 
                    'DoCoMo', 
                    'dopod', 
                    'Elaine/3.0', 
                    'Ericsson', 
                    'EudoraWeb', 
                    'Fly', 
                    'HP.iPAQ', 
                    'Haier', 
                    'hiptop', 
                    'htc', 
                    'Huawei', 
                    'i-mobile', 
                    'IEMobile', 
                    'J-PHONE', 
                    'KDDI', 
                    'KONKA', 
                    'KWC', 
                    'KYOCERA/WX310K', 
                    'LG', 
                    'LG/U990', 
                    'Lenovo', 
                    'MIDP-2.0', 
                    'MMEF20', 
                    'MOT-V', 
                    'MobilePhone', 
                    'Motorola', 
                    'NEWGEN', 
                    'NetFront', 
                    'Newt', 
                    'Nintendo Wii', 
                    'Nitro', 
                    'Nokia', 
                    'Novarra', 
                    'O2', 
                    'Opera Mini', 
                    'Opera.Mobi', 
                    'PANTECH', 
                    'PDXGW', 
                    'PG', 
                    'PPC', 
                    'PT', 
                    'Palm', 
                    'Panasonic', 
                    'Philips', 
                    'Playstation Portable', 
                    'portalmmm', 
                    'ProxiNet', 
                    'Proxinet', 
                    'Qtek', 
                    'SCH', 
                    'SEC', 
                    'SGH', 
                    'SHARP-TQ-GX10', 
                    'SIE', 
                    'SPH', 
                    'Sagem', 
                    'Samsung', 
                    'Sanyo', 
                    'Sendo', 
                    'Sharp', 
                    'Small', 
                    'Smartphone', 
                    'SoftBank', 
                    'SonyEricsson', 
                    'Symbian', 
                    'Symbian OS', 
                    'SymbianOS', 
                    'TS21i-10', 
                    'Toshiba', 
                    'Treo', 
                    'UP.Browser', 
                    'UP.Link', 
                    'UTS', 
                    'Vertu', 
                    'WILLCOME', 
                    'WinWAP', 
                    'Windows CE', 
                    'Windows.CE', 
                    'Xda', 
                    'ZTE', 
                    'vodafone'
                )
            )
        ), 
        
        'common.support' => '', 
        'common.install' => 0, 
        'common.tweeted' => 0, 
        
        'widget.latest.enabled' => true, 
        'widget.latest.items' => 3, 
        
        'notes.wp_content_perms' => true, 
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
        'notes.no_curl' => true, 
        'notes.no_zlib' => true, 
        'notes.zlib_output_compression' => true, 
        'notes.no_permalink_rules' => true, 
        'notes.browsercache_rules_cache' => true, 
        'notes.browsercache_rules_no404wp' => true, 
        'notes.minify_error' => false
    );
    
    /**
     * PHP5 Constructor
     * @param boolean $preview
     */
    function __construct($preview = null)
    {
        $this->load_defaults();
        $this->load($preview);
        
        if (!$this->get_integer('common.install')) {
            $this->set('common.install', time());
        }
    }
    
    /**
     * PHP4 Constructor
     * @param boolean $preview
     */
    function W3_Config($preview = null)
    {
        $this->__construct($preview);
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
             * Check cache engines
             */
            case 'pgcache.engine':
            case 'dbcache.engine':
            case 'minify.engine':
                switch (true) {
                    case ($value == 'apc' && !function_exists('apc_store')):
                    case ($value == 'eaccelerator' && !function_exists('eaccelerator_put')):
                    case ($value == 'xcache' && !function_exists('xcache_set')):
                    case ($value == 'memcached' && !class_exists('Memcache')):
                        return 'file';
                }
                break;
            
            /**
             * Disabled some page cache options when enhanced mode enabled
             */
            case 'pgcache.cache.query':
                if ($this->get_boolean('pgcache.enabled') && $this->get_string('pgcache.engine') == 'file_pgcache') {
                    return false;
                }
                break;
            
            /**
             * Set default value to sitemap file
             */
            case 'pgcache.prime.sitemap':
                if (!$value) {
                    $value = w3_get_site_url() . '/sitemap.xml';
                }
                break;
            
            /**
             * Don't support additional headers in some cases
             */
            case 'pgcache.cache.headers':
                if (!W3TC_PHP5 || ($this->get_boolean('pgcache.enabled') && $this->get_string('pgcache.engine') == 'file_pgcache')) {
                    return array();
                }
                break;
            
            /**
             * Disabled minify when PHP5 is not installed
             */
            case 'minify.enabled':
                if (!W3TC_PHP5) {
                    return false;
                }
                break;
            
            /**
             * Compatibility with older versions
             */
            case 'minify.js.groups':
            case 'minify.css.groups':
                if (is_array($value) && count($value)) {
                    $group = current($value);
                    
                    if (is_array($group) && count($group)) {
                        $location = current($group);
                        
                        if (isset($location['files'])) {
                            if (function_exists('get_theme')) {
                                $theme = get_theme(get_current_theme());
                                $theme_key = sprintf('%s/%s', $theme['Template'], $theme['Stylesheet']);
                            } else {
                                $theme_key = 'default/default';
                            }
                            
                            $value = array(
                                $theme_key => $value
                            );
                        }
                    }
                }
                break;
            
            /**
             * Disable CDN minify when PHP5 is not installed or minify is disabled
             */
            case 'cdn.minify.enable':
                if (!W3TC_PHP5 || !$this->get_boolean('minify.enabled') || !$this->get_boolean('minify.rewrite')) {
                    return false;
                }
                break;
            
            /**
             * Check CDN engines
             */
            case 'cdn.engine':
                if (($value == 's3' || $value == 'cf' || $value == 'rscf') && (!W3TC_PHP5 || !function_exists('curl_init'))) {
                    return 'mirror';
                }
                if ($value == 'ftp' && !function_exists('ftp_connect')) {
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
            
            if (!is_array($config)) {
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
        
        $request = W3_Request::get_request();
        
        foreach ($this->_keys as $key => $type) {
            $request_key = str_replace('.', '_', $key);
            
            if (!isset($request[$request_key])) {
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
        $fp = @fopen($file, 'w');
        
        if ($fp) {
            @fputs($fp, "<?php\r\n\r\nreturn array(\r\n");
            
            $this->_tabs = 1;
            
            foreach ($this->_config as $key => $value) {
                $this->_write($fp, $key, $value);
            }
            
            @fputs($fp, ");");
            @fclose($fp);
            
            return true;
        }
        
        return false;
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
        
        if (is_numeric($key)) {
            @fputs($fp, sprintf("%d => ", $key));
        } else {
            @fputs($fp, sprintf("'%s' => ", addslashes($key)));
        }
        
        switch (gettype($value)) {
            case 'object':
            case 'array':
                @fputs($fp, "array(\r\n");
                ++$this->_tabs;
                foreach ((array) $value as $k => $v) {
                    $this->_write($fp, $k, $v);
                }
                --$this->_tabs;
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
     * @param boolean $preview
     * @return boolean
     */
    function load($preview = null)
    {
        if ($preview === null) {
            $preview = w3_is_preview_mode();
        }
        
        if ($preview) {
            return $this->read(W3TC_CONFIG_PREVIEW_PATH);
        }
        
        return $this->read(W3TC_CONFIG_PATH);
    }
    
    /**
     * Loads master config (for WPMU)
     */
    function load_master()
    {
        return $this->read(W3TC_CONFIG_MASTER_PATH);
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
     * Set default option on plugin activate
     */
    function set_defaults()
    {
        $this->set('pgcache.enabled', true);
        $this->set('minify.enabled', true);
        $this->set('browsercache.enabled', true);
    }
    
    /**
     * Saves config
     *
     * @param boolean preview
     * @return boolean
     */
    function save($preview = null)
    {
        if ($preview === null) {
            $preview = w3_is_preview_mode();
        }
        
        if ($preview) {
            return $this->write(W3TC_CONFIG_PREVIEW_PATH);
        }
        
        return $this->write(W3TC_CONFIG_PATH);
    }
    
    /**
     * Returns config instance
     *
     * @param boolean $preview
     * @return W3_Config
     */
    function &instance($preview = null)
    {
        static $instances = array();
        
        if (!isset($instances[0])) {
            $class = __CLASS__;
            $instances[0] = & new $class($preview);
        }
        
        return $instances[0];
    }
}
