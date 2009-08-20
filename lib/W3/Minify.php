<?php

/**
 * W3 Minify object
 */

if (! defined('MINIFY_DIR')) {
    define('MINIFY_DIR', dirname(__FILE__) . '/../Minify/min');
}

if (! defined('MINIFY_LIB_DIR')) {
    define('MINIFY_LIB_DIR', MINIFY_DIR . '/lib');
}

/**
 * Class W3_Minify
 */
class W3_Minify
{
    /**
     * Config
     * 
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * PHP5 constructor
     */
    function __construct()
    {
        require_once dirname(__FILE__) . '/Config.php';
        $this->_config = W3_Config::instance();
        
        set_include_path(get_include_path() . PATH_SEPARATOR . MINIFY_LIB_DIR);
    }
    
    /**
     * PHP4 constructor
     * @return W3_Minify
     */
    function W3_Minify()
    {
        $this->__construct();
    }
    
    /**
     * Runs minify
     */
    function process()
    {
        set_include_path(MINIFY_LIB_DIR . PATH_SEPARATOR . get_include_path());
        
        require_once MINIFY_LIB_DIR . '/Minify.php';
        require_once MINIFY_LIB_DIR . '/HTTP/Encoder.php';
        
        HTTP_Encoder::$encodeToIe6 = $this->_config->get_boolean('minify.comprss.ie6', true);
        
        Minify::$uploaderHoursBehind = $this->_config->get_integer('minify.fixtime');
        Minify::setCache($this->_get_cache());
        
        $serve_options = $this->_config->get_array('minify.options');
        $serve_options['encodeOutput'] = $this->_config->get_boolean('minify.compress', true);
        $serve_options['postprocessor'] = array(
            &$this, 
            'postprocessor'
        );
        
        if (($docroot = $this->_config->get_string('minify.docroot'))) {
            $_SERVER['DOCUMENT_ROOT'] = $docroot;
        } elseif (0 === stripos(PHP_OS, 'win')) {
            Minify::setDocRoot(); // IIS may need help
        }
        
        // normalize paths in symlinks
        foreach ($this->_config->get_array('minify.symlinks') as $link => $target) {
            $link = str_replace('//', realpath($_SERVER['DOCUMENT_ROOT']), $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            $serve_options['minifierOptions']['text/css']['symlinks'][$link] = realpath($target);
        }
        
        if ($this->_config->get_boolean('minify.debug') && isset($_REQUEST['debug'])) {
            $serve_options['debug'] = true;
        }
        
        if (($logger = $this->_config->get('minify.logger'))) {
            require_once MINIFY_LIB_DIR . '/Minify/Logger.php';
            if (true === $logger) {
                require_once MINIFY_LIB_DIR . '/FirePHP.php';
                Minify_Logger::setLogger(FirePHP::getInstance(true));
            } else {
                Minify_Logger::setLogger($logger);
            }
        }
        
        // check for URI versioning
        if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
            $serve_options['maxAge'] = 31536000;
        }
        
        if (isset($_GET['g']) && isset($_GET['t'])) {
            // well need groups config
            $serve_options['minApp']['groups'] = $this->_get_groups($_GET['t']);
        }
        
        if (isset($_GET['f']) || isset($_GET['g'])) {
            // serve!   
            @header('X-Powered-By: ' . W3_PLUGIN_POWERED_BY);
            Minify::serve('MinApp', $serve_options);
        } elseif ($this->_config->get_boolean('minify.builder')) {
            header('Location: /wp-content/plugins/w3-total-cache/lib/Minify/min/builder/');
            exit();
        } else {
            header("Location: /");
            exit();
        }
    }
    
    /**
     * Minify postprocessor
     *
     * @param string $content
     * @param string $type
     * @return string
     */
    function postprocessor($content, $type)
    {
        switch ($type) {
            case 'text/css':
                if ($this->_config->get_boolean('minify.css.strip.comments', true)) {
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.css.strip.crlf', true)) {
                    $content = preg_replace("~[\r\n]+~", ' ', $content);
                }
                break;
            
            case 'application/x-javascript':
                if ($this->_config->get_boolean('minify.js.strip.comments', true)) {
                    $content = preg_replace('~^//.*$~m', '', $content);
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.js.strip.crlf', true)) {
                    $content = preg_replace("~[\r\n]+~", '', $content);
                }
                break;
        }
        
        return $content;
    }
    
    /**
     * Flushes cache
     */
    function flush()
    {
        static $cache_path = null;
        
        $cache = $this->_get_cache();
        
        if (is_a($cache, 'Minify_Cache_Memcached') || is_a($cache, 'Minify_Cache_APC')) {
            return $cache->flush();
        } elseif (is_a($cache, 'Minify_Cache_File')) {
            if (! $cache_path) {
                $cache_path = $this->_config->get_string('minify.cache.path');
                
                if (! $cache_path) {
                    require_once MINIFY_LIB_DIR . '/Solar/Dir.php';
                    $cache_path = Solar_Dir::tmp();
                }
            }
            
            $dir = dir($cache_path);
            
            while (($entry = $dir->read()) !== false) {
                if (strpos($entry, 'minify') === 0) {
                    @unlink($dir->path . '/' . $entry);
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns onject instance
     *
     * @return W3_Minify
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
     * Returns debug info
     */
    function get_debug_info()
    {
        $debug_info = "<!-- W3 Total Cache: Minify debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), $this->_config->get_string('minify.engine'));
        
        $css_groups = $this->_get_groups('css');
        
        if (count($css_groups)) {
            $debug_info .= "Stylesheet info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Group', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
            foreach ($css_groups as $css_group => $css_files) {
                foreach ($css_files as $css_file => $css_file_path) {
                    if (w3_is_url($css_file)) {
                        $css_file_info = sprintf('%s (%s)', $css_file, $css_file_path);
                    } else {
                        $css_file_path = $css_file_info = ABSPATH . ltrim($css_file, '/');
                    }
                    
                    $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad($css_group, 15, ' ', STR_PAD_BOTH), str_pad(date('Y-m-d H:i:s', filemtime($css_file_path)), 19, ' ', STR_PAD_BOTH), str_pad(filesize($css_file_path), 12, ' ', STR_PAD_LEFT), $css_file_info);
                }
            }
        }
        
        $js_groups = $this->_get_groups('js');
        
        if (count($js_groups)) {
            $debug_info .= "JavaScript info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Group', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
            foreach ($js_groups as $js_group => $js_files) {
                foreach ($js_files as $js_file => $js_file_path) {
                    if (w3_is_url($js_file)) {
                        $js_file_info = sprintf('%s (%s)', $js_file, $js_file_path);
                    } else {
                        $js_file_path = $js_file_info = ABSPATH . ltrim($js_file, '/');
                    }
                    
                    $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad($js_group, 15, ' ', STR_PAD_BOTH), str_pad(date('Y-m-d H:i:s', filemtime($js_file_path)), 19, ' ', STR_PAD_BOTH), str_pad(filesize($js_file_path), 12, ' ', STR_PAD_LEFT), $js_file_info);
                }
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Returns minify groups
     *
     * @param string $type
     * @return array
     */
    function _get_groups($type)
    {
        switch ($type) {
            case 'css':
                $groups = $this->_config->get_array('minify.css.groups');
                break;
            
            case 'js':
                $groups = $this->_config->get_array('minify.js.groups');
                break;
            
            default:
                return array();
        }
        
        $result = array();
        
        foreach ($groups as $group => $config) {
            if (isset($config['files'])) {
                foreach ((array) $config['files'] as $file) {
                    if (w3_is_url($file)) {
                        if (($precached_file = $this->_precache_file($file, $type))) {
                            $result[$group][$file] = $precached_file;
                        }
                    } else {
                        $result[$group][$file] = '//' . $file;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Precaches external file
     *
     * @param string $file
     * @param string $type
     * @return string
     */
    function _precache_file($file, $type)
    {
        static $cache_path = null;
        
        if (! $cache_path) {
            $cache_path = $this->_config->get_string('minify.cache.path');
            
            if (! $cache_path) {
                require_once MINIFY_LIB_DIR . '/Solar/Dir.php';
                $cache_path = Solar_Dir::tmp();
            }
        }
        
        $file_path = sprintf('%s/minify_%s.%s', $cache_path, md5($file), $type);
        $file_exists = file_exists($file_path);
        
        if ($file_exists && filemtime($file_path) >= (time() - $this->_config->get_integer('minify.lifetime', 3600))) {
            return $file_path;
        }
        
        if (($file_data = file_get_contents($file)) && ($fp = fopen($file_path, 'w'))) {
            fputs($fp, $file_data);
            fclose($fp);
            
            return $file_path;
        }
        
        return ($file_exists ? $file_path : false);
    }
    
    /**
     * Returns minify cache object
     *
     * @return object
     */
    function &_get_cache()
    {
        static $cache = null;
        
        if (! $cache) {
            switch ($this->_config->get_string('minify.engine', 'memcached')) {
                case 'memcached':
                    require_once dirname(__FILE__) . '/Cache/Memcached.php';
                    require_once MINIFY_LIB_DIR . '/Minify/Cache/Memcache.php';
                    
                    $memcached = & W3_Cache_Memcached::instance($this->_config->get_string('minify.memcached.engine', 'auto'), array(
                        'servers' => $this->_config->get_array('minify.memcached.servers')
                    ));
                    $cache = & new Minify_Cache_Memcache($memcached);
                    break;
                
                case 'apc':
                    require_once MINIFY_LIB_DIR . '/Minify/Cache/APC.php';
                    
                    $cache = & new Minify_Cache_APC();
                    break;
                
                default:
                    require_once MINIFY_LIB_DIR . '/Minify/Cache/File.php';
                    
                    $cache = & new Minify_Cache_File($this->_config->get_string('minify.cache.path'), $this->_config->get_boolean('minify.cache.locking'));
                    break;
            }
        }
        
        return $cache;
    }
}
