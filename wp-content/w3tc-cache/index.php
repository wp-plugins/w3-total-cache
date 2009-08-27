<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

/**
 * W3 Total Cache Minify module
 */
if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

if (! defined('W3TC_DIR')) {
    define('W3TC_DIR', dirname(__FILE__) . '/../plugins/w3-total-cache');
}

if (! is_dir(W3TC_DIR) || ! file_exists(W3TC_DIR . '/inc/define.php')) {
    die(sprintf('<strong>W3 Total Cache Error:</strong> plugin seems to be broken. Please re-install plugin or remove <strong>%s</strong>.', dirname(__FILE__)));
}

require_once ABSPATH . 'wp-config.php';
require_once W3TC_DIR . '/inc/define.php';
require_once W3TC_DIR . '/lib/W3/Minify.php';

$w3_minify = W3_Minify::instance();
$w3_minify->process();
