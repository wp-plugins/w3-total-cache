<?php

/**
 * W3 Minify object
 */

/**
 * Class W3_Minify
 */
class W3_Minify {
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
    function __construct() {
        require_once W3TC_LIB_W3_DIR . '/Config.php';

        $this->_config = & W3_Config::instance();
    }

    /**
     * PHP4 constructor
     * @return W3_Minify
     */
    function W3_Minify() {
        $this->__construct();
    }

    /**
     * Runs minify
     */
    function process() {
        /**
         * Request variables:
         *
         * f - files
         * tt - theme name in format template:stylesheet
         * gg - template
         * g - location (include, include-footer, etc...)
         * t - type (js or css)
         */
        require_once W3TC_LIB_W3_DIR . '/Request.php';

        $files = W3_Request::get_array('f');
        $theme = W3_Request::get_string('tt');
        $template = W3_Request::get_string('gg');
        $location = W3_Request::get_string('g');
        $type = W3_Request::get_string('t');

        if (!$files && !$theme && !$template && !$location && !$type) {
            die('Params (f, tt, gg, g, t) are missed.');
        } elseif (!$files) {
            if (!$theme) {
                die('Theme param (tt) is missed.');
            }

            if (!$template) {
                die('Template param (gg) is missed.');
            }

            if (!$location) {
                die('Location param (g) is missed.');
            }

            if (!$type) {
                die('Type param (t) is missed.');
            }
        } else {
            $type = 'css';
        }

        require_once W3TC_LIB_MINIFY_DIR . '/Minify.php';
        require_once W3TC_LIB_MINIFY_DIR . '/HTTP/Encoder.php';

        /**
         * Fix DOCUMENT_ROOT for minify
         */
        $_SERVER['DOCUMENT_ROOT'] = w3_get_document_root();

        Minify::setCache($this->_get_cache());

        if ($this->_config->get_boolean('minify.debug')) {
            require_once W3TC_LIB_MINIFY_DIR . '/Minify/Logger.php';
            Minify_Logger::setLogger($this);
        }

        $browsercache = $this->_config->get_boolean('browsercache.enabled');

        $serve_options = array_merge($this->_config->get_array('minify.options'), array(
            'debug' => $this->_config->get_boolean('minify.debug'),
            'maxAge' => $this->_config->get_integer('browsercache.cssjs.lifetime'),
            'encodeOutput' => ($browsercache && $this->_config->get_boolean('browsercache.cssjs.compression')),
            'bubbleCssImports' => ($this->_config->get_string('minify.css.imports') == 'bubble'),
            'processCssImports' => ($this->_config->get_string('minify.css.imports') == 'process'),
            'cacheHeaders' => array(
                'use_etag' => ($browsercache && $this->_config->get_boolean('browsercache.cssjs.etag')),
                'expires_enabled' => ($browsercache && $this->_config->get_boolean('browsercache.cssjs.expires')),
                'cacheheaders_enabled' => ($browsercache && $this->_config->get_boolean('browsercache.cssjs.cache.control')),
                'cacheheaders' => $this->_config->get_string('browsercache.cssjs.cache.policy')
            )
        ));

        if (!$files) {
            $serve_options['minApp']['groups'] = $this->get_groups($theme, $template, $type);
        }

        require_once W3TC_LIB_W3_DIR . '/Minifier.php';
        $w3_minifier =& W3_Minifier::instance();

        if ($type == 'js') {
            $minifier_type = 'application/x-javascript';

            switch (true) {
                case (($files || $location == 'include' || $location == 'include-nb') && $this->_config->get_boolean('minify.js.combine.header')):
                case (($location == 'include-body' || $location == 'include-body-nb') && $this->_config->get_boolean('minify.js.combine.body')):
                case (($location == 'include-footer' || $location == 'include-footer-nb') && $this->_config->get_boolean('minify.js.combine.footer')):
                    $engine = 'combinejs';
                    break;

                default:
                    $engine = $this->_config->get_string('minify.js.engine');

                    if (!$w3_minifier->exists($engine) || !$w3_minifier->available($engine)) {
                        $engine = 'js';
                    }
                    break;
            }

        } elseif ($type == 'css') {
            $minifier_type = 'text/css';

            if (($files || $location == 'include') && $this->_config->get_boolean('minify.css.combine')) {
                $engine = 'combinecss';
            } else {
                $engine = $this->_config->get_string('minify.css.engine');

                if (!$w3_minifier->exists($engine) || !$w3_minifier->available($engine)) {
                    $engine = 'css';
                }

            }
        }

        $w3_minifier->init($engine);

        $serve_options['minifiers'][$minifier_type] = $w3_minifier->get_minifier($engine);
        $serve_options['minifierOptions'][$minifier_type] = $w3_minifier->get_options($engine);

        /**
         * Setup user-friendly cache ID for disk cache
         */
        if (!$files && $this->_config->get_string('minify.engine') == 'file') {
            $id = $this->get_id($theme, $template, $location, $type);
            $cacheId = sprintf('%s/%s.%s.%s.%s', $theme, $template, $location, $id, $type);

            Minify::setCacheId($cacheId);
        }

        if ($browsercache && $this->_config->get_boolean('browsercache.cssjs.w3tc')) {
            @header('X-Powered-By: ' . W3TC_POWERED_BY);
        }

        try {
            Minify::serve('MinApp', $serve_options);
        } catch (Exception $exception) {
            printf('<strong>W3 Total Cache Error:</strong> Minify error: %s', $exception->getMessage());
        }
    }

    /**
     * Flushes cache
     */
    function flush() {
        $cache = & $this->_get_cache();

        if (is_a($cache, 'W3_Cache_Memcached') && class_exists('Memcache')) {
            return $this->_memcached->flush();
        } elseif (is_a($cache, 'Minify_Cache_APC') && function_exists('apc_clear_cache')) {
            return apc_clear_cache('user');
        } elseif (is_a($cache, 'Minify_Cache_File')) {
            if (!@is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
                $this->log(sprintf('Cache directory %s does not exists', W3TC_CACHE_FILE_MINIFY_DIR));
            }

            return w3_emptydir(W3TC_CACHE_FILE_MINIFY_DIR, array(
                W3TC_CACHE_FILE_MINIFY_DIR . '/index.php',
                W3TC_CACHE_FILE_MINIFY_DIR . '/.htaccess'
            ));
        }

        return false;
    }

    /**
     * Returns onject instance
     *
     * @return W3_Minify
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
     * Log
     *
     * @param mixed $object
     * @param string $label
     */
    function log($object, $label = null) {
        $data = sprintf("[%s] [%s] %s\n", date('r'), $_SERVER['REQUEST_URI'], $object);

        return @file_put_contents(W3TC_MINIFY_LOG_FILE, $data, FILE_APPEND);
    }

    /**
     * Returns minify groups
     *
     * @param string $theme
     * @param string $template
     * @param string $type
     * @return array
     */
    function get_groups($theme, $template, $type) {
        $result = array();

        switch ($type) {
            case 'css':
                $groups = $this->_config->get_array('minify.css.groups');
                break;

            case 'js':
                $groups = $this->_config->get_array('minify.js.groups');
                break;

            default:
                return $result;
        }

        if (isset($groups[$theme]['default'])) {
            $locations = (array) $groups[$theme]['default'];
        } else {
            $locations = array();
        }

        if ($template != 'default' && isset($groups[$theme][$template])) {
            $locations = array_merge_recursive($locations, (array) $groups[$theme][$template]);
        }

        foreach ($locations as $location => $config) {
            if (!empty($config['files'])) {
                foreach ((array) $config['files'] as $file) {
                    $file = w3_normalize_file_minify2($file);

                    if (w3_is_url($file)) {
                        $precached_file = $this->_precache_file($file, $type);

                        if ($precached_file) {
                            $result[$location][$file] = $precached_file;
                        } else {
                            $this->_handle_error($file);
                        }
                    } else {
                        $file = urldecode($file);
                        $path = w3_get_document_root() . '/' . $file;

                        if (file_exists($path)) {
                            $result[$location][$file] = '//' . $file;
                        } else {
                            $this->_handle_error($file);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns id of file
     *
     * @param string $theme
     * @param string $template
     * @param string $location
     * @param string $type
     * @return string
     */
    function get_id($theme, $template, $location, $type) {
        $id = $this->_load_id($theme, $template, $location, $type);

        if (!$id) {
            $id = $this->_generate_id($theme, $template, $location, $type);
            $this->_save_id($id, $theme, $template, $location, $type);
        }

        return $id;
    }

    /**
     * Precaches external file
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    function _precache_file($url, $type) {
        $lifetime = $this->_config->get_integer('minify.lifetime');
        $file_path = sprintf('%s/minify_%s.%s', W3TC_CACHE_FILE_MINIFY_DIR, md5($url), $type);
        $file_exists = file_exists($file_path);

        if ($file_exists && @filemtime($file_path) >= (time() - $lifetime)) {
            return $this->_get_minify_source($file_path, $url);
        }

        if (@is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
            if (w3_download($url, $file_path) !== false) {
                return $this->_get_minify_source($file_path, $url);
            } else {
                $this->log(sprintf('Unable to download URL: %s', $url));
            }
        } else {
            $this->log(sprintf('The cache directory %s does not exist', W3TC_CACHE_FILE_MINIFY_DIR));
        }

        return ($file_exists ? $this->_get_minify_source($file_path, $url) : false);
    }

    /**
     * Returns minify source
     * @param $file_path
     * @param $url
     * @return Minify_Source
     */
    function _get_minify_source($file_path, $url) {
        require_once W3TC_LIB_MINIFY_DIR . '/Minify/Source.php';

        return new Minify_Source(array(
            'filepath' => $file_path,
            'minifyOptions' => array(
                'prependRelativePath' => $url
            )
        ));
    }

    /**
     * Returns minify cache object
     *
     * @return object
     */
    function &_get_cache() {
        static $cache = array();

        if (!isset($cache[0])) {
            switch ($this->_config->get_string('minify.engine')) {
                case 'memcached':
                    require_once W3TC_LIB_W3_DIR . '/Cache/Memcached.php';
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Memcache.php';
                    $this->_memcached = & new W3_Cache_Memcached(array(
                        'servers' => $this->_config->get_array('minify.memcached.servers'),
                        'persistant' => $this->_config->get_boolean('minify.memcached.persistant')
                    ));
                    $cache[0] = & new Minify_Cache_Memcache($this->_memcached);
                    break;

                case 'apc':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/APC.php';
                    $cache[0] = & new Minify_Cache_APC();
                    break;

                case 'eaccelerator':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Eaccelerator.php';
                    $cache[0] = & new Minify_Cache_Eaccelerator();
                    break;

                case 'xcache':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/XCache.php';
                    $cache[0] = & new Minify_Cache_XCache();
                    break;

                case 'wincache':
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/Wincache.php';
                    $cache[0] = & new Minify_Cache_Wincache();
                    break;

                case 'file':
                default:
                    require_once W3TC_LIB_MINIFY_DIR . '/Minify/Cache/File.php';
                    if (!@is_dir(W3TC_CACHE_FILE_MINIFY_DIR)) {
                        $this->log(sprintf('Cache directory %s does not exist', W3TC_CACHE_FILE_MINIFY_DIR));
                    }
                    $cache[0] = & new Minify_Cache_File(W3TC_CACHE_FILE_MINIFY_DIR, $this->_config->get_boolean('minify.file.locking'));
                    break;
            }
        }

        return $cache[0];
    }

    /**
     * Handle minify error
     *
     * @param string file
     * @return void
     */
    function _handle_error($file) {
        $notification = $this->_config->get_string('minify.error.notification');

        if ($notification) {
            if (stristr($notification, 'admin') !== false) {
                $this->_config->set('notes.minify_error', true);
            }

            if (stristr($notification, 'email') !== false) {
                $last = $this->_config->get_integer('minify.error.notification.last');

                /**
                 * Prevent email flood: send email every 5 min
                 */
                if ((time() - $last) > 300) {
                    $this->_config->set('minify.error.notification.last', time());
                    $this->_send_notification();
                }
            }

            $this->_config->save();
        }
    }

    /**
     * Send E-mail notification when error occured
     *
     * @return boolean
     */
    function _send_notification() {
        $from_email = 'wordpress@' . w3_get_domain($_SERVER['SERVER_NAME']);
        $from_name = get_option('blogname');
        $to_name = $to_email = get_option('admin_email');
        $body = @file_get_contents(W3TC_DIR . '/inc/email/minify_error_notification.html');

        $headers = array(
            sprintf('From: "%s" <%s>', addslashes($from_name), $from_email),
            sprintf('Reply-To: "%s" <%s>', addslashes($to_name), $to_email),
            'Content-Type: text/html; charset=UTF-8'
        );

        @set_time_limit(120);

        $result = @wp_mail($to_email, 'W3 Total Cache Error Notification', $body, implode("\n", $headers));

        return $result;
    }

    /**
     * Generates file ID
     *
     * @param string $theme
     * @param string $template
     * @param string $location
     * @param string $type
     * @return string
     */
    function _generate_id($theme, $template, $location, $type) {
        $hash = '';
        $files = array();
        $groups = $this->get_groups($theme, $template, $type);

        if (isset($groups[$location])) {
            $files = (array) $groups[$location];
        }

        $document_root = w3_get_document_root();

        foreach ($files as $file) {
            if (is_a($file, 'Minify_Source')) {
                $path = $file->filepath;
            } else {
                $path = $document_root . '/' . $file;
            }

            $hash .= $path . @file_get_contents($path);
        }

        $id = abs(crc32($hash));

        return $id;
    }

    /**
     * Returns key for ID data
     *
     * @param string $theme
     * @param string $template
     * @param string $location
     * @param string $type
     * @return string
     */
    function _get_id_key($theme, $template, $location, $type) {
        $key = sprintf('%s/%s.%s.%s.id', $theme, $template, $location, $type);

        return $key;
    }

    /**
     * Returns cached ID
     *
     * @param string $theme
     * @param string $template
     * @param string $location
     * @param string $type
     * @return string
     */
    function _load_id($theme, $template, $location, $type) {
        $key = $this->_get_id_key($theme, $template, $location, $type);
        $cache = & $this->_get_cache();

        $id = @$cache->fetch($key);

        return $id;
    }

    /**
     * Cache ID
     *
     * @param string $id
     * @param string $theme
     * @param string $template
     * @param string $location
     * @param string $type
     * @return boolean
     */
    function _save_id($id, $theme, $template, $location, $type) {
        $key = $this->_get_id_key($theme, $template, $location, $type);
        $cache = & $this->_get_cache();

        return $cache->store($key, $id);
    }
}
