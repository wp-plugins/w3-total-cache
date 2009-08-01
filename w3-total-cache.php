<?php

/*
Plugin Name: W3 Total Cache
Description: Dramatically improve the user experience of your blog. Add page caching, database caching, minify and content delivery network functionality and more to WordPress.
Version: 0.5
Plugin URI: http://www.w3-edge.com/wordpress-plugins/w3-total-cache/
Author: Frederick Townes
Author URI: http://www.linkedin.com/in/w3edge
*/

/**
 * Require plugin configuration
 */
require_once dirname(__FILE__) . '/inc/define.php';

/**
 * Run plugin
 */
require_once W3_PLUGIN_DIR . '/lib/W3/Plugin/TotalCache.php';
$w3_plugin_totalcache = W3_Plugin_TotalCache::instance();
$w3_plugin_totalcache->run();
