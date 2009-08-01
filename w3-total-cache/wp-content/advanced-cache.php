<?php

/**
 * W3 Total Cache advanced cache module
 */
if (! defined('W3_PLUGIN_DIR')) {
    define('W3_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/w3-total-cache');
}

require_once W3_PLUGIN_DIR . '/inc/define.php';
require_once W3_PLUGIN_DIR . '/lib/W3/PgCache.php';

$w3_pgcache = W3_PgCache::instance();

if ($w3_pgcache->can_cache()) {
    $w3_pgcache->process();
} else {
    $w3_pgcache->log();
}
