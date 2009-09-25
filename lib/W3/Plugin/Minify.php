<?php

/**
 * W3 Minify plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_Minify
 */
class W3_Plugin_Minify extends W3_Plugin
{
    /**
     * Runs plugin
     */
    function run()
    {
        register_activation_hook(W3TC_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3TC_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        if ($this->can_minify()) {
            ob_start(array(
                &$this, 
                'ob_callback'
            ));
            
            add_action('wp_footer', array(
                &$this, 
                'footer'
            ));
        }
    }
    
    /**
     * Returns instance
     *
     * @return W3_Plugin_Minify
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
     * Activate plugin action
     */
    function activate()
    {
        if (! $this->locked()) {
            if (! is_dir(W3TC_CONTENT_DIR)) {
                if (@mkdir(W3TC_CONTENT_DIR, 0755)) {
                    @chmod(W3TC_CONTENT_DIR, 0755);
                } else {
                    w3_writable_error(W3TC_CONTENT_DIR);
                }
            }
            
            $file_htaccess = W3TC_CONTENT_DIR . '/.htaccess';
            
            if (@copy(W3TC_INSTALL_MINIFY_DIR . '/_htaccess', $file_htaccess)) {
                @chmod($file_htaccess, 0644);
            } else {
                w3_writable_error($file_htaccess);
            }
            
            $file_index = W3TC_CONTENT_DIR . '/index.html';
            
            if (@copy(W3TC_INSTALL_MINIFY_DIR . '/index.html', $file_index)) {
                @chmod($file_index, 0644);
            } else {
                w3_writable_error($file_index);
            }
            
            $file_minify = W3TC_CONTENT_DIR . '/minify.php';
            
            if (@copy(W3TC_INSTALL_MINIFY_DIR . '/minify.php', $file_minify)) {
                @chmod($file_minify, 0644);
            } else {
                w3_writable_error($file_minify);
            }
        }
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        if (! $this->locked()) {
            @unlink(W3TC_CONTENT_DIR . '/.htaccess');
            @unlink(W3TC_CONTENT_DIR . '/index.php');
        }
    }
    
    /**
     * OB callback
     * 
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        if (! w3_is_xml($buffer)) {
            return $buffer;
        }
        
        $head_prepend = '';
        
        if ($this->_config->get_boolean('minify.css.enable', true)) {
            $head_prepend .= $this->get_styles('include');
        }
        
        if ($this->_config->get_boolean('minify.js.enable', true)) {
            $head_prepend .= $this->get_scripts('include') . $this->get_scripts('include-nb');
        }
        
        if (! empty($head_prepend)) {
            $buffer = preg_replace('~<head[^>]*>~Ui', '\\0' . $head_prepend, $buffer);
        }
        
        $buffer = $this->clean($buffer);
        
        if ($this->_config->get_boolean('minify.debug')) {
            $buffer .= "\r\n\r\n" . $this->get_debug_info();
        }
        
        return $buffer;
    }
    
    /**
     * Footer action
     */
    function footer()
    {
        if ($this->_config->get_boolean('minify.js.enable', true)) {
            echo $this->get_scripts('include-footer') . $this->get_scripts('include-footer-nb');
        }
    }
    
    /**
     * Cleans content
     * 
     * @param string $content
     * @return string
     */
    function clean($content)
    {
        if (! is_feed()) {
            if ($this->_config->get_boolean('minify.css.enable', true)) {
                $content = $this->clean_styles($content);
            }
            
            if ($this->_config->get_boolean('minify.js.enable', true)) {
                $content = $this->clean_scripts($content);
            }
        }
        
        if ($this->_config->get_boolean('minify.html.enable', true) && ! ($this->_config->get_boolean('minify.html.reject.admin', true) && current_user_can('manage_options'))) {
            $content = $this->minify_html($content);
        }
        
        $content = preg_replace('~<style[^<>]*>\s*</style>~', '', $content);
        
        return $content;
    }
    
    /**
     * Cleans styles
     * 
     * @param string $content
     * @return string
     */
    function clean_styles($content)
    {
        $regexps = array();
        
        $groups = $this->_config->get_array('minify.css.groups');
        $siteurl = w3_get_domain_url();
        
        foreach ($groups as $group => $locations) {
            foreach ((array) $locations as $location => $config) {
                if (! empty($config['files'])) {
                    foreach ((array) $config['files'] as $file) {
                        if (w3_is_url($file) && strstr($file, $siteurl) === false) {
                            $regexps[] = preg_quote($file);
                        } else {
                            $file = ltrim(str_replace($siteurl, '', $file), '/');
                            $regexps[] = '(' . preg_quote($siteurl) . ')?/?' . preg_quote($file);
                        }
                    }
                }
            }
        }
        
        foreach ($regexps as $regexp) {
            $content = preg_replace('~<link\s+[^<>]*href=["\']?' . $regexp . '["\']?[^<>]*/?>~is', '', $content);
            $content = preg_replace('~@import\s+(url\s*)?\(?["\']?\s*' . $regexp . '\s*["\']?\)?[^;]*;?~is', '', $content);
        }
        
        return $content;
    }
    
    /**
     * Cleans scripts
     *
     * @param string $content
     * @return string
     */
    function clean_scripts($content)
    {
        $regexps = array();
        
        $groups = $this->_config->get_array('minify.js.groups');
        $siteurl = w3_get_domain_url();
        
        foreach ($groups as $group => $locations) {
            foreach ((array) $locations as $location => $config) {
                if (! empty($config['files'])) {
                    foreach ((array) $config['files'] as $file) {
                        if (w3_is_url($file) && strstr($file, $siteurl) === false) {
                            $regexps[] = preg_quote($file);
                        } else {
                            $file = ltrim(str_replace($siteurl, '', $file), '/');
                            $regexps[] = '(' . preg_quote($siteurl) . ')?/?' . preg_quote($file);
                        }
                    }
                }
            }
        }
        
        foreach ($regexps as $regexp) {
            $content = preg_replace('~<script\s+[^<>]*src=["\']?' . $regexp . '["\']?[^<>]*>\s*</script>~is', '', $content);
        }
        
        return $content;
    }
    
    /**
     * Minifies HTML
     *
     * @param string $content
     * @return string
     */
    function minify_html($content)
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . W3TC_LIB_MINIFY_DIR);
        
        require_once 'Minify/HTML.php';
        require_once 'Minify/CSS.php';
        require_once 'JSMin.php';
        
        $options = array(
            'xhtml' => true
        );
        
        if ($this->_config->get_boolean('minify.html.inline.css', false)) {
            $options['cssMinifier'] = array(
                'Minify_CSS', 
                'minify'
            );
        }
        
        if ($this->_config->get_boolean('minify.html.inline.js', false)) {
            $options['jsMinifier'] = array(
                'JSMin', 
                'minify'
            );
        }
        
        try {
            $content = Minify_HTML::minify($content, $options);
        } catch (Exception $exception) {
            return sprintf('<strong>W3 Total Cache Error:</strong> Minify error: %s', $exception->getMessage());
        }
        
        if ($this->_config->get_boolean('minify.html.strip.crlf')) {
            $content = preg_replace("~[\r\n]+~", ' ', $content);
        } else {
            $content = preg_replace("~[\r\n]+~", "\n", $content);
        }
        
        return $content;
    }
    
    /**
     * Returns current group
     * @return string
     */
    function get_group()
    {
        static $group = null;
        
        if ($group === null) {
            switch (true) {
                case is_date():
                    $group = 'date';
                    break;
                
                case is_category():
                    $group = 'category';
                    break;
                
                case is_tag():
                    $group = 'tag';
                    break;
                
                case is_author():
                    $group = 'author';
                    break;
                
                case is_home():
                    $group = 'home';
                    break;
                
                case is_page():
                    $group = 'page';
                    break;
                
                case is_search():
                    $group = 'search';
                    break;
                
                case is_404():
                    $group = '404';
                    break;
                
                case is_attachment():
                    $group = 'attachment';
                    break;
                
                case is_archive():
                    $group = 'archive';
                    break;
                
                case is_single():
                    $group = 'single';
                    break;
                
                default:
                    $group = 'default';
                    break;
            }
        }
        
        return $group;
    }
    
    /**
     * Returns style link
     * 
     * @param string $url
     * @param string $import
     */
    function get_style($url, $import = false)
    {
        if ($import) {
            return "<style type=\"text/css\" media=\"all\">@import url(\"" . $url . "\");</style>\r\n";
        } else {
            return "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . str_replace('&', '&amp;', $url) . "\" media=\"all\" />\r\n";
        }
    }
    
    /**
     * Prints script link
     *
     * @param string $url
     * @param boolean $non_blocking
     */
    function get_script($url, $blocking = true)
    {
        static $non_blocking_function = false;
        
        if ($blocking) {
            return '<script type="text/javascript" src="' . str_replace('&', '&amp;', $url) . '"></script>';
        } else {
            $script = '';
            
            if (! $non_blocking_function) {
                $non_blocking_function = true;
                $script = "<script type=\"text/javascript\">/*<![CDATA[*/function w3tc_load_js(u){var d=document;var p=d.getElementsByTagName('HEAD')[0];var c=d.createElement('script');c.type='text/javascript';c.src=u;p.appendChild(c);}/*]]>*/</script>";
            }
            
            $script .= "<script type=\"text/javascript\">/*<![CDATA[*/w3tc_load_js('" . $url . "');/*]]>*/</script>";
            
            return $script;
        }
    }
    
    /**
     * returns style link for styles group
     * @param string $location
     * @param string $group
     */
    function get_styles($location, $group = null)
    {
        $styles = '';
        $groups = $this->_config->get_array('minify.css.groups');
        
        if (empty($group)) {
            $group = $this->get_group();
        }
        
        if ($group != 'default' && empty($groups[$group][$location]['files'])) {
            $group = 'default';
        }
        
        if (! empty($groups[$group][$location]['files'])) {
            $styles .= $this->get_style($this->format_url($group, $location, 'css'), isset($groups[$group][$location]['import']) ? (boolean) $groups[$group][$location]['import'] : false);
        }
        
        return $styles;
    }
    
    /**
     * Returns script linkg for scripts group
     * @param string $location
     * @param string $group
     */
    function get_scripts($location, $group = null)
    {
        $scripts = '';
        $groups = $this->_config->get_array('minify.js.groups');
        
        if (empty($group)) {
            $group = $this->get_group();
        }
        
        if ($group != 'default' && empty($groups[$group][$location]['files'])) {
            $group = 'default';
        }
        
        if (! empty($groups[$group][$location]['files'])) {
            $scripts .= $this->get_script($this->format_url($group, $location, 'js'), isset($groups[$group][$location]['blocking']) ? (boolean) $groups[$group][$location]['blocking'] : true);
        }
        
        return $scripts;
    }
    
    /**
     * Returns link for custom script files
     *
     * @param string|array $files
     * @param boolean $blocking
     */
    function get_custom_script($files, $blocking = true)
    {
        return $this->get_script($this->format_custom_url($files), $blocking);
    }
    
    /**
     * Returns link for custom style files
     *
     * @param string|array $files
     * @param boolean $import
     */
    function get_custom_style($files, $import = false)
    {
        return $this->get_style($this->format_custom_url($files), $import);
    }
    
    /**
     * Formats URL
     *
     * @param string $group
     * @param string $location
     * @param string $type
     * @return string
     */
    function format_url($group, $location, $type)
    {
        $siteurl = w3_get_site_url();
        
        if ($this->_config->get_boolean('minify.rewrite', true)) {
            return sprintf('%s/%s/%s.%s.%s', $siteurl, W3TC_CONTENT_DIR_NAME, $group, $location, $type);
        }
        
        return sprintf('%s/%s/minify.php?gg=%s&g=%s&t=%s', $siteurl, W3TC_CONTENT_DIR_NAME, $group, $location, $type);
    }
    
    /**
     * Formats custom URL
     *
     * @param string|array $files
     * @return string
     */
    function format_custom_url($files)
    {
        if (! is_array($files)) {
            $files = array(
                (string) $files
            );
        }
        
        $base = false;
        foreach ($files as &$file) {
            $current_base = dirname($file);
            if ($base && $base != $current_base) {
                $base = false;
                break;
            } else {
                $file = basename($file);
                $base = $current_base;
            }
        }
        
        $siteurl = w3_get_site_url();
        $url = sprintf('%s/%s/minify.php?f=%s', $siteurl, W3TC_CONTENT_DIR_NAME, implode(',', $files));
        
        if ($base) {
            $url .= sprintf('&b=%s', $base);
        }
        
        return $url;
    }
    
    /**
     * Returns array of minify URLs
     * @return array
     */
    function get_urls()
    {
        $files = array();
        
        $js_groups = $this->_config->get_array('minify.js.groups');
        $css_groups = $this->_config->get_array('minify.css.groups');
        
        foreach ($js_groups as $js_group => $js_locations) {
            foreach ((array) $js_locations as $js_location => $js_config) {
                if (! empty($js_config['files'])) {
                    $files[] = $this->format_url($js_group, $js_location, 'js');
                }
            }
        }
        
        foreach ($css_groups as $css_group => $css_locations) {
            foreach ((array) $css_locations as $css_location => $css_config) {
                if (! empty($css_config['files'])) {
                    $files[] = $this->format_url($css_group, $css_location, 'css');
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Returns debug info
     */
    function get_debug_info()
    {
        $group = $this->get_group();
        
        $debug_info = "<!-- W3 Total Cache: Minify debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), w3_get_engine_name($this->_config->get_string('minify.engine')));
        $debug_info .= sprintf("%s%s\r\n", str_pad('Group: ', 20), $group);
        
        require_once W3TC_LIB_W3_DIR . '/Minify.php';
        $w3_minify = W3_Minify::instance();
        
        $css_groups = $w3_minify->get_groups($group, 'css');
        
        if (count($css_groups)) {
            $debug_info .= "Stylesheet info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Location', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
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
        
        $js_groups = $w3_minify->get_groups($group, 'js');
        
        if (count($js_groups)) {
            $debug_info .= "JavaScript info:\r\n";
            $debug_info .= sprintf("%s | %s | % s | %s\r\n", str_pad('Location', 15, ' ', STR_PAD_BOTH), str_pad('Last modified', 19, ' ', STR_PAD_BOTH), str_pad('Size', 12, ' ', STR_PAD_LEFT), 'Path');
            
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
     * Check if we can do minify logic
     * @return boolean
     */
    function can_minify()
    {
        /**
         * Skip if Minify is disabled
         */
        if (! $this->_config->get_boolean('minify.enabled', true)) {
            return false;
        }
        
        /**
         * Skip if Admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }
        
        /**
         * Skip if doint AJAX
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
         * Check User agent
         */
        if (! $this->check_ua()) {
            return false;
        }
        
        /**
         * Check request URI
         */
        if (! $this->check_request_uri()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks User Agent
     *
     * @return boolean
     */
    function check_ua()
    {
        foreach ($this->_config->get_array('minify.reject.ua') as $ua) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $ua) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function check_request_uri()
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
        
        foreach ($this->_config->get_array('minify.reject.uri') as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
}

/**
 * Prints script link for scripts group
 * @param string $location
 * @param string $group
 */
function w3tc_scripts($location, $group = null)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_scripts($location, $group);
}

/**
 * Prints style link for styles group
 * @param string $location
 * @param string $group
 */
function w3tc_styles($location, $group = null)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_styles($location, $group);
}

/**
 * Prints link for custom scripts
 *
 * @param string|array $files
 * @param boolean $blocking
 */
function w3tc_custom_script($files, $blocking = true)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_custom_script($files, $blocking);
}

/**
 * Prints link for custom styles
 *
 * @param string|array $files
 * @param boolean $import
 */
function w3tc_custom_style($files, $import = false)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_custom_style($files, $import);
}
