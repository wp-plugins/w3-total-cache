<?php

/**
 * W3 Minify plugin
 */
require_once dirname(__FILE__) . '/../Plugin.php';

if (! defined('W3_PLUGIN_MINIFY_MIN_DIRNAME')) {
    define('W3_PLUGIN_MINIFY_MIN_DIRNAME', 'wp-content/uploads/w3tc-cache');
}

if (! defined('W3_PLUGIN_MINIFY_MIN_DIR')) {
    define('W3_PLUGIN_MINIFY_MIN_DIR', ABSPATH . W3_PLUGIN_MINIFY_MIN_DIRNAME);
}

if (! defined('W3_PLUGIN_MINIFY_MIN_CONTENT_DIR')) {
    define('W3_PLUGIN_MINIFY_MIN_CONTENT_DIR', W3_PLUGIN_DIR . '/' . W3_PLUGIN_MINIFY_MIN_DIRNAME);
}

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
        register_activation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        if ($this->_config->get_boolean('minify.enabled') && ! is_admin()) {
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
        static $instance;
        
        if (! $instance) {
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
        @mkdir(W3_PLUGIN_MINIFY_MIN_DIR, 0777);
        @copy(W3_PLUGIN_MINIFY_MIN_CONTENT_DIR . '/.htaccess', W3_PLUGIN_MINIFY_MIN_DIR . '/.htaccess');
        @copy(W3_PLUGIN_MINIFY_MIN_CONTENT_DIR . '/index.php', W3_PLUGIN_MINIFY_MIN_DIR . '/index.php');
        @chmod(W3_PLUGIN_MINIFY_MIN_DIR . '/.htaccess', 0666);
        @chmod(W3_PLUGIN_MINIFY_MIN_DIR . '/index.php', 0666);
    }
    
    /**
     * Deactivate plugin action
     */
    function deactivate()
    {
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/.htaccess');
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/index.php');
        
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/include.css');
        
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/include.js');
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/include-nb.js');
        
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/include-footer.js');
        @unlink(W3_PLUGIN_MINIFY_MIN_DIR . '/include-footer-nb.js');
        
        @rmdir(W3_PLUGIN_MINIFY_MIN_DIR);
    }
    
    /**
     * OB callback
     * 
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        $head_prepend = '';
        
        if ($this->_config->get_boolean('minify.css.enable', true)) {
            $head_prepend .= $this->get_styles('include');
        }
        
        if ($this->_config->get_boolean('minify.js.enable', true)) {
            $head_prepend .= $this->get_scripts('include') . $this->get_scripts('include-nb');
        }
        
        if (! empty($head_prepend)) {
            $buffer = preg_replace('~<head[^>]+>~Ui', '\\0' . $head_prepend, $buffer);
        }
        
        $buffer = $this->clean($buffer);
        
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
        if ($this->_config->get_boolean('minify.css.enabled', true) && $this->_config->get_boolean('minify.css.clean', true)) {
            $content = $this->clean_styles($content);
        }
        
        if ($this->_config->get_boolean('minify.js.enabled', true) && $this->_config->get_boolean('minify.js.clean', true)) {
            $content = $this->clean_scripts($content);
        }
        
        if ($this->_config->get_boolean('minify.html.enable', true) && ! ($this->_config->get_boolean('minify.html.reject.admin', true) && current_user_can('manage_options'))) {
            $content = $this->minify_html($content);
        }
        
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
        $urls = array();
        
        $groups = $this->_config->get_array('minify.css.groups');
        
        foreach ($groups as $config) {
            if (isset($config['files'])) {
                foreach ((array) $config['files'] as $file) {
                    if (preg_match('~^https?://~i', $file)) { 
                        $urls[] = $file;
                    } else {
                        $urls[] = get_option('siteurl') . '/' . $file;
                    }
                }
            }
        }
        
        foreach ($urls as $url) {
            $content = preg_replace('~<link[^<>]*href=["\']?' . preg_quote($url) . '["\']?[^<>]*/?>~is', '', $content);
            $content = preg_replace('~@import\s+url\s*[\(]?["\']?\s*' . preg_quote($url) . '\s*["\']?[\)]?\s*;?~is', '', $content);
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
        $urls = array();
        
        $groups = $this->_config->get_array('minify.js.groups');
        
        foreach ($groups as $config) {
            if (isset($config['files'])) {
                foreach ((array) $config['files'] as $file) {
                    if (preg_match('~^https?://~', $file)) {
                        $urls[] = $file;
                    } else {
                        $urls[] = get_option('siteurl') . '/' . $file;
                    }
                }
            }
        }
        
        foreach ($urls as $url) {
            $content = preg_replace('~<script[^<>]*src=["\']?' . preg_quote($url) . '["\']?[^<>]*></script>~is', '', $content);
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
        $content = preg_replace("~[\r\n]~", "\n", $content);
        $content = preg_replace("~\n+~", "\r\n", $content);
        $content = preg_replace('~"\s+/>~', '"/>', $content);
        $content = preg_replace("~(</?\\w+>)\r\n~", "\\1", $content);
        $content = preg_replace('~^\s+~m', '', $content);
        $content = preg_replace("~[\t ]+~", ' ', $content);
        $content = str_replace('> <', '><', $content);
        
        if ($this->_config->get_boolean('minify.html.strip.comments', true)) {
            $content = preg_replace('~(?<!(/\*|//))<\!--.+-->~sU', '', $content);
        }
        
        if ($this->_config->get_boolean('minify.html.strip.crlf', true)) {
            $content = preg_replace("~[\r\n]+~", '', $content);
        }
        
        return $content;
    }
    
    /**
     * Returns style link
     * 
     * @param string $url
     * @param string $import
     */
    function get_style($url, $import)
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
                $script = "<script type=\"text/javascript\">function w3tc_load_js(u){var d=document;var p=d.getElementsByTagName('HEAD')[0];var c=d.createElement('script');c.type='text/javascript';c.src=u;p.appendChild(c);}</script>";
            }
            
            $script .= "<script type=\"text/javascript\">/*<!--*/w3tc_load_js('" . $url . "');/*-->*/</script>";
            
            return $script;
        }
    }
    
    /**
     * returns style link for styles group
     *
     * @param string $group
     */
    function get_styles($group)
    {
        $styles = '';
        $groups = $this->_config->get_array('minify.css.groups');
        
        if (isset($groups[$group]['files']) && count($groups[$group]['files'])) {
            $styles .= $this->get_style($this->format_group_url('css', $group), isset($groups[$group]['import']) ? (boolean) $groups[$group]['import'] : false);
        }
        
        return $styles;
    }
    
    /**
     * Returns script linkg for scripts group
     *
     * @param string $group
     */
    function get_scripts($group)
    {
        $scripts = '';
        $groups = $this->_config->get_array('minify.js.groups');
        
        if (isset($groups[$group]['files']) && count($groups[$group]['files'])) {
            $scripts .= $this->get_script($this->format_group_url('js', $group), isset($groups[$group]['blocking']) ? (boolean) $groups[$group]['blocking'] : true);
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
        return $this->get_script($this->format_files_url($files), $blocking);
    }
    
    /**
     * Returns link for custom style files
     *
     * @param string|array $files
     * @param boolean $import
     */
    function get_custom_style($files, $import = false)
    {
        return $this->get_style($this->format_files_url($files), $import);
    }
    
    /**
     * Formats URL for group
     *
     * @param string $type
     * @param string $group
     * @return string
     */
    function format_group_url($type, $group)
    {
        if ($this->_config->get_boolean('minify.rewrite', false)) {
            return sprintf('%s/%s/%s.%s', get_option('siteurl'), W3_PLUGIN_MINIFY_MIN_DIRNAME, $group, $type);
        }
        
        return sprintf('%s/%s/?t=%s&g=%s', get_option('siteurl'), W3_PLUGIN_MINIFY_MIN_DIRNAME, $type, $group);
    }
    
    /**
     * Formats URL for files
     *
     * @param string|array $files
     * @return string
     */
    function format_files_url($files)
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
        
        $url = sprintf('%s/%s/?f=%s', get_option('siteurl'), W3_PLUGIN_MINIFY_MIN_DIRNAME, implode(',', $files));
        
        if ($base) {
            $url .= sprintf('&b=%s', $base);
        }
        
        return $url;
    }
}

/**
 * Prints script link for scripts group
 *
 * @param string $group
 */
function w3tc_scripts($group)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_scripts($group);
}

/**
 * Prints style link for styles group
 *
 * @param string $group
 */
function w3tc_styles($group)
{
    $w3_plugin_minify = W3_Plugin_Minify::instance();
    echo $w3_plugin_minify->get_styles($group);
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
