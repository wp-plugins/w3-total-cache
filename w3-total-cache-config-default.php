<?php

/**
 * W3 Total Cache default configuration
 */
return array(
    /**
     * DbCache configuration
     */
    'dbcache.enabled' => true, 
    'dbcache.debug' => false, 
    'dbcache.engine' => 'memcached', 
    'dbcache.memcached.engine' => 'auto', 
    'dbcache.memcached.servers' => array(
        'localhost:11211'
    ), 
    'dbcache.reject.admin' => false, 
    'dbcache.reject.uri' => array(), 
    'dbcache.reject.sql' => array(), 
    'dbcache.lifetime.default' => 180, 
    'dbcache.lifetime.options' => 180, 
    'dbcache.lifetime.links' => 10800, 
    'dbcache.lifetime.terms' => 10800, 
    'dbcache.lifetime.user' => 1800, 
    'dbcache.lifetime.post' => 3600, 
    
    /**
     * PgCache configuration
     */
    'pgcache.enabled' => true, 
    'pgcache.debug' => false, 
    'pgcache.engine' => 'memcached', 
    'pgcache.memcached.engine' => 'auto', 
    'pgcache.memcached.servers' => array(
        'localhost:11211'
    ), 
    'pgcache.lifetime' => 3600, 
    'pgcache.compress' => true, 
    'pgcache.cache.logged' => true, 
    'pgcache.cache.query' => true, 
    'pgcache.cache.home' => true, 
    'pgcache.cache.feed' => true, 
    'pgcache.cache.404' => true, 
    'pgcache.cache.flush' => true, 
    'pgcache.cache.headers' => array(
        'Last-Modified', 
        'Expires', 
        'Content-Type', 
        'X-Pingback', 
        'ETag', 
        'Cache-Control', 
        'Pragma'
    ), 
    'pgcache.accept.files' => array(
        'wp-comments-popup.php', 
        'wp-links-opml.php', 
        'wp-locations.php'
    ), 
    'pgcache.reject.uri' => array(
        'wp-.*\.php', 
        'index\.php'
    ), 
    'pgcache.reject.ua' => array(
        'bot', 
        'ia_archive', 
        'slurp', 
        'crawl', 
        'spider'
    ), 
    'pgcache.mobile.check' => false, 
    'pgcache.mobile.whitelist' => array(), 
    'pgcache.mobile.browsers' => array(), 
    
    /**
     * Minify configuration
     */
    'minify.enabled' => true, 
    'minify.debug' => false, 
    'minify.engine' => 'file', 
    'minify.memcached.engine' => 'auto', 
    'minify.memcached.servers' => array(
        'localhost:11211'
    ), 
    'minify.rewrite' => true, 
    'minify.logger' => false, 
    'minify.builder' => false, 
    'minify.cache.path' => '', 
    'minify.cache.locking' => true, 
    'minify.docroot' => '', 
    'minify.fixtime' => 0, 
    'minify.symlinks' => array(),
    'minify.compress' => true,
    'minify.compress.ie6' => true, 
    'minify.options' => array(
        'bubbleCssImports' => false, 
        'maxAge' => 1800, 
        'minApp' => array(
            'groupsOnly' => false, 
            'maxFiles' => 20
        )
    ), 
    'minify.lifetime' => 3600, 
    'minify.html.enable' => true, 
    'minify.html.reject.admin' => true,
    'minify.html.strip.comments' => true,
    'minify.html.strip.crlf' => true, 
    'minify.css.enable' => true, 
    'minify.css.clean' => true, 
    'minify.css.strip.comments' => true,
    'minify.css.strip.crlf' => true, 
    'minify.css.groups' => array(
        'include' => array(
            'files' => array(
                'wp-content/themes/default/style.css'
            )
        )
    ), 
    'minify.js.enable' => true, 
    'minify.js.clean' => true, 
    'minify.js.strip.comments' => true,
    'minify.js.strip.crlf' => true, 
    'minify.js.groups' => array(), 
    
    /**
     * CDN configuration
     */
    'cdn.enabled' => false, 
    'cdn.engine' => 'ftp',
    'cdn.domain' => '', 
    'cdn.process.includes' => true,
    'cdn.process.theme' => true,
	'cdn.process.minify' => true,	
    'cdn.process.custom' => true,
    'cdn.files.includes' => '*.css;*.js;*.gif;*.png;*.jpg;*.xml',
    'cdn.files.theme' => '*.css;*.js;*.gif;*.png;*.jpg;*.ico',
    'cdn.files.custom' => array(
        'favicon.ico'
    ),    
    'cdn.limit.queue' => 25,
    'cdn.ftp.host' => '', 
    'cdn.ftp.user' => '', 
    'cdn.ftp.pass' => '',
    'cdn.ftp.path' => '', 
    'cdn.ftp.pasv' => false, 
    
    /**
     * Common configuration
     */
    'common.defaults' => true, 
    'common.support' => true,
    'common.support.type' => 'footer'
);
