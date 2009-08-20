<?php

/**
 * W3 Total Cache Database module
 */
if (! defined('W3_PLUGIN_DIR')) {
    define('W3_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/w3-total-cache');
}

require_once W3_PLUGIN_DIR . '/inc/define.php';
require_once W3_PLUGIN_DIR . '/lib/W3/Db.php';

$wpdb = W3_Db::instance();
