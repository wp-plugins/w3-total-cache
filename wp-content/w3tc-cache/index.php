<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

/**
 * W3 Total Cache Minify module
 */
if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

if (! defined('W3_PLUGIN_DIR')) {
    define('W3_PLUGIN_DIR', dirname(__FILE__) . '/../plugins/w3-total-cache');
}

require_once ABSPATH . 'wp-config.php';
require_once W3_PLUGIN_DIR . '/inc/define.php';
require_once W3_PLUGIN_DIR . '/lib/W3/Minify.php';

$w3_minify = W3_Minify::instance();
$w3_minify->process();
