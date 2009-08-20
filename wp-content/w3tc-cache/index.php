<?php

error_reporting(E_ALL ^ E_NOTICE);

/**
 * W3 Total Cache Minify module
 */
if (! defined('W3_PLUGIN_DIR')) {
    define('W3_PLUGIN_DIR', dirname(__FILE__) . '/../plugins/w3-total-cache');
}

require_once W3_PLUGIN_DIR . '/inc/define.php';
require_once W3_PLUGIN_DIR . '/lib/W3/Minify.php';

$w3_minify = W3_Minify::instance();
$w3_minify->process();
