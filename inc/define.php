<?php

if (! defined('W3_PLUGIN_VERSION')) {
    define('W3_PLUGIN_VERSION', '0.6');
}

if (! defined('W3_PLUGIN_POWERED_BY')) {
    define('W3_PLUGIN_POWERED_BY', 'W3 Total Cache/' . W3_PLUGIN_VERSION);
}

if (! defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', dirname(__FILE__) . '/../../../');
}

if (! defined('W3_PLUGIN_DIR')) {
    define('W3_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/w3-total-cache');
}

if (! defined('W3_PLUGIN_CONTENT_DIR')) {
    define('W3_PLUGIN_CONTENT_DIR', W3_PLUGIN_DIR . '/wp-content');
}

if (! defined('W3_PLUGIN_FILE')) {
    define('W3_PLUGIN_FILE', 'w3-total-cache/w3-total-cache.php');
}

if (! defined('W3_CONFIG_PATH')) {
    define('W3_CONFIG_PATH', WP_CONTENT_DIR . '/w3-total-cache-config.php');
}

if (! defined('W3_CONFIG_DEFAULT_PATH')) {
    define('W3_CONFIG_DEFAULT_PATH', W3_PLUGIN_DIR . '/w3-total-cache-config-default.php');
}

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

if (! function_exists('gzdecode')) {
    /**
     * Decodes gzip-encoded string
     *
     * @param string $data
     * @return string
     */
    function gzdecode($data)
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
}

/**
 * Creates thumbnail
 *
 * @param string $file
 * @param integer $max_w
 * @param integer $max_h
 * @param boolean $crop
 * @param string $suffix
 * @param string $dest_path
 * @param integer $jpeg_quality
 * @return string
 */
function w3_create_thumbnail($file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90)
{
    $thumbpath = image_resize($file, $max_w, $max_h, $crop, $suffix, $dest_path, $jpeg_quality);
    
    return apply_filters('wp_create_thumbnail', $thumbpath);
}

/**
 * Recursive creates directory
 *
 * @param string $path
 * @param integer $mask
 * @return boolean
 */
function w3_mkdir($path, $mask = 0777)
{
    $dirs = preg_split('~[\\/]+~', $path);
    $curr_path = '';
    foreach ($dirs as $dir) {
        if (empty($dir)) {
            return false;
        }
        $curr_path .= $dir;
        if (! is_dir($curr_path)) {
            if (@mkdir($curr_path, $mask)) {
                @chmod($curr_path, $mask);
            } else {
                return false;
            }
        }
        $curr_path .= DIRECTORY_SEPARATOR;
    }
    return true;
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
