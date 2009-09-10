<?php

/**
 * W3 Minify object
 */

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
     * Memcached object
     *
     * @var W3_Cache_Memcached
     */
    var $_memcached = null;
    
    /**
     * PHP5 constructor
     */
    function __construct()
    {
        require_once W3TC_LIB_W3_DIR . '/Config.php';
        $this->_config = W3_Config::instance();
        set_include_path(get_include_path() . PATH_SEPARATOR . W3TC_LIB_MINIFY_DIR);
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
        require_once W3TC_LIB_MINIFY_DIR . '/Minify.php';
        require_once W3TC_LIB_MINIFY_DIR . '/HTTP/Encoder.php';
        
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
        
        if ($this->_config->get('minify.logging', true)) {
            require_once W3TC_LIB_MINIFY_DIR . '/Minify/Logger.php';
            Minify_Logger::setLogger($this);
        }
        
        if (isset($_GET['f']) || (isset($_GET['g']) && isset($_GET['t']))) {
            if (isset($_GET['g']) && isset($_GET['t'])) {
                $serve_options['minApp']['groups'] = $this->_get_groups($_GET['t']);
                
                if ($_GET['t'] == 'js' && ((in_array($_GET['g'], array(
                    'include', 
                    'include-nb'
                )) && $this->_config->get_boolean('minify.js.combine.header')) || (in_array($_GET['g'], array(
                    'include-footer', 
                    'include-footer-nb'
                )) && $this->_config->get_boolean('minify.js.combine.footer')))) {
                    $serve_options['minifiers']['application/x-javascript'] = array(
                        $this, 
                        'minify_stub'
                    );
                }
            }
            
            @header('X-Powered-By: ' . W3TC_POWERED_BY);
            Minify::serve('MinApp', $serve_options);
        } else {
            die('This file cannot be accessed directly');
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
                if ($this->_config->get_boolean('minify.css.strip.comments')) {
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.css.strip.crlf')) {
                    $content = preg_replace("~[\r\n]+~", ' ', $content);
                } else {
                    $content = preg_replace("~[\r\n]+~", "\n", $content);
                }
                break;
            
            case 'application/x-javascript':
                if ($this->_config->get_boolean('minify.js.strip.comments')) {
                    $content = preg_replace('~^//.*$~m', '', $content);
                    $content = preg_replace('~/\*.*\*/~Us', '', $content);
                }
                
                if ($this->_config->get_boolean('minify.js.strip.crlf')) {
                    $content = preg_replace("~[\r\n]+~", '', $content);
                } else {
                    $content = preg_replace("~[\r\n]+~", "\n", $content);
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
        
        if (is_a($cache, 'Minify_Cache_Memcache') && is_a($this->_memcached, 'W3_Cache_Memcached_Base')) {
            return $this->_memcached->flush();
        } elseif (is_a($cache, 'Minify_Cache_APC') && function_exists('apc_clear_cache')) {
            return apc_clear_cache('user');
        } elseif (is_a($cache, 'Minify_Cache_File')) {
            if (! $cache_path) {
                $cache_path = $this->_config->get_string('minify.cache.path');
                
                if (! $cache_path) {
                    require_once W3TC_LIB_MINIFY_DIR . '/Solar/Dir.php';
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
        
        if ($instance === null) {
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
                        $css_file_path = $css_file_info = ABSPATH . ltrim($css_file, '\\/');
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
                        $js_file_path = $js_file_info = ABSPATH . ltrim($js_file, '\\/');
                    }
                    
                    $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad($js_group, 15, ' ', STR_PAD_BOTH), str_pad(date('Y-m-d H:i:s', filemtime($js_file_path)), 19, ' ', STR_PAD_BOTH), str_pad(filesize($js_file_path), 12, ' ', STR_PAD_LEFT), $js_file_info);
                }
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Minify stub function
     *
     * @param string $source
     */
    function minify_stub($source)
    {
        return $source;
    }
    
    /**
     * Log
     *
     * @param mixed $object
     * @param string $label
     */
    function log($object, $label = null)
    {
        $file = W3TC_MINIFY_DIR . '/error.log';
        $data = sprintf("[%s] [%s] %s\n", date('r'), $_SERVER['REQUEST_URI'], $object);
        
        if (($fp = @fopen($file, 'a'))) {
            @fputs($fp, $data);
            @fclose($fp);
        }
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
        
        if ($cache_path === null) {
            $cache_path = $this->_config->get_string('minify.cache.path');
            
            if (! $cache_path) {
                require_once W3TC_LIB_MINIFY_DIR . '/Solar/Dir.php';
                $cache_path = Solar_Dir::tmp();
            }
        }
        
        $lifetime = $this->_config->get_integer('minify.lifetime', 3600);
        $file_path = sprintf('%s/minify_%s.%s', $cache_path, md5($file), $type);
        $file_exists = file_exists($file_path);
        
        if (file_exists($file_path) && @filemtime($file_path) >= (time() - $lifetime)) {
            return $file_path;
        }
        
        if (is_dir($cache_path)) {
            if (($file_data = @file_get_contents($file))) {
                if (($fp = @fopen($file_path, 'w'))) {
                    @fputs($fp, $file_data);
                    @fclose($fp);
                } else {
                    $this->log(sprintf('Unable to open file %s for writing', $file_path));
                }
            } else {
                $this->log(sprintf('Unable to download URL: %s', $file));
            }
        } else {
            $this->log(sprintf('Cache directory %s is not exists', $cache_path));
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
        
        if ($cache === null) {
            switch ($this->_config->get_string('minify.engine', 'memcached')) {
                case 'memcached':
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Memcache.php';
                    $this->_memcached = & W3_Cache_Memcached::instance($this->_config->get_string('minify.memcached.engine', 'auto'), array(
                        'servers' => $this->_config->get_array('minify.memcached.servers'), 
                        'persistant' => true
                    ));
                    $cache = & new Minify_Cache_Memcache($this->_memcached);
                    break;
                
                case 'apc':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/APC.php';
                    $cache = & new Minify_Cache_APC();
                    break;
                
                default:
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/File.php';
                    $cache_path = $this->_config->get_string('minify.cache.path');
                    if (empty($cache_path)) {
                        $cache_path = W3TC_MINIFY_DIR;
                    }
                    if (! is_dir($cache_path)) {
                        $this->log(sprintf('Cache directory %s is not exists', $cache_path));
                    }
                    $cache = & new Minify_Cache_File($cache_path, $this->_config->get_boolean('minify.cache.locking'));
                    break;
            }
        }
        
        return $cache;
    }
}
