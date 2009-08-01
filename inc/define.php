<?php

if (! defined('W3_PLUGIN_POWERED_BY')) {
    define('W3_PLUGIN_POWERED_BY', 'W3 Total Cache/0.5');
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
    define('W3_CONFIG_PATH', WP_CONTENT_DIR . '/uploads/w3-total-cache-config.php');
}

if (! defined('W3_CONFIG_DEFAULT_PATH')) {
    define('W3_CONFIG_DEFAULT_PATH', W3_PLUGIN_DIR . '/w3-total-cache-config-default.php');
}
