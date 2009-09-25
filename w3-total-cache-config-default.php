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
    'dbcache.reject.cookie' => array(), 
    'dbcache.reject.sql' => array(), 
    'dbcache.lifetime' => 180, 
    
    /**
     * PgCache configuration
     */
    'pgcache.enabled' => true, 
    'pgcache.debug' => false, 
    'pgcache.engine' => 'file', 
    'pgcache.memcached.engine' => 'auto', 
    'pgcache.memcached.servers' => array(
        'localhost:11211'
    ), 
    'pgcache.lifetime' => 3600, 
    'pgcache.compress' => true, 
    'pgcache.cache.query' => true, 
    'pgcache.cache.home' => true, 
    'pgcache.cache.feed' => true, 
    'pgcache.cache.404' => true, 
    'pgcache.cache.flush' => false, 
    'pgcache.cache.headers' => array(
        'Last-Modified', 
        'Content-Type', 
        'X-Pingback'
    ), 
    'pgcache.accept.files' => array(
        'wp-comments-popup.php', 
        'wp-links-opml.php', 
        'wp-locations.php'
    ), 
    'pgcache.reject.logged' => true, 
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
    'pgcache.reject.cookie' => array(), 
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
    'minify.locking' => true, 
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
    'minify.upload' => true, 
    'minify.html.enable' => true, 
    'minify.html.reject.admin' => true, 
    'minify.html.inline.css' => false, 
    'minify.html.inline.js' => false, 
    'minify.html.strip.crlf' => false, 
    'minify.css.enable' => true, 
    'minify.css.strip.comments' => false, 
    'minify.css.strip.crlf' => false, 
    'minify.css.groups' => array(), 
    'minify.js.enable' => true, 
    'minify.js.combine.header' => false, 
    'minify.js.combine.footer' => false, 
    'minify.js.strip.comments' => false, 
    'minify.js.strip.crlf' => false, 
    'minify.js.groups' => array(), 
    'minify.reject.ua' => array(), 
    'minify.reject.uri' => array(), 
    
    /**
     * CDN configuration
     */
    'cdn.enabled' => false, 
    'cdn.debug' => false, 
    'cdn.engine' => 'ftp', 
    'cdn.domain' => '', 
    'cdn.includes.enable' => true, 
    'cdn.includes.files' => '*.css;*.js;*.gif;*.png;*.jpg;*.xml', 
    'cdn.theme.enable' => true, 
    'cdn.theme.files' => '*.css;*.js;*.gif;*.png;*.jpg;*.ico', 
    'cdn.minify.enable' => true, 
    'cdn.custom.enable' => true, 
    'cdn.custom.files' => array(
        'favicon.ico'
    ), 
    'cdn.import.external' => false, 
    'cdn.import.files' => '*.jpg;*.png;*.gif;*.avi;*.wmv;*.mpg;*.wav;*.mp3;*.txt;*.rtf;*.doc;*.xls;*.rar;*.zip;*.tar;*.gz;*.exe', 
    'cdn.limit.queue' => 25, 
    'cdn.ftp.host' => '', 
    'cdn.ftp.user' => '', 
    'cdn.ftp.pass' => '', 
    'cdn.ftp.path' => '', 
    'cdn.ftp.pasv' => false, 
    'cdn.reject.ua' => array(
        'bot', 
        'ia_archive', 
        'slurp', 
        'crawl', 
        'spider'
    ), 
    'cdn.reject.uri' => array(), 
    
    /**
     * Common configuration
     */
    'common.support.enabled' => true, 
    'common.support.type' => 'footer', 
    
    /**
     * Notes configuration
     */
    'notes.defaults' => true, 
    'notes.wp_content_perms' => true, 
    'notes.cdn_first_time' => true, 
    'notes.no_memcached_nor_apc' => true
);
