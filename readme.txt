=== Plugin Name ===
Contributors: fredericktownes
Tags: user experience, cache, caching, page cache, css cache, js cache, db cache, disk cache, disk caching, database cache, http compression, gzip, deflate, minify, CDN, content delivery network, media library, wp cache, wp super cache, w3 total cache, performance, speed, multiple hosts, CSS, merge, combine, unobtrusive javascript, compress, optimize, optimizer, JavaScript, JS, cascading style sheet, plugin
Requires at least: 2.5
Tested up to: 2.8.4
Stable tag: 0.8

Dramatically improve the user experience of your blog. Add page caching, database caching, minify and content delivery network functionality and more to WordPress.

== Description ==

Trusted by many popular blogs like: mashable.com, pearsonified.com, webdesignerdepot.com, freelanceswitch.com, briansolis.com, css3.info, tutsplus.com, yoast.com, noupe.com and others &mdash; W3 Total Cache improves the user experience of your blog by improving your server performance, caching every aspect of your site, reducing the download time of your theme and providing transparent content delivery network (CDN) integration.

Benefits:

* At least 10x improvement in site performance (Grade A in YSlow, when fully configured)
* "Instant" second page views (browser caching after first page view)
* Reduced page load time: increased visitor time on site (visitors view more pages)
* Optimized progressive render (pages appear to load instantly)
* Improved web server performance (easily sustain high traffic spikes)
* Up to 80% Bandwidth savings via Minify and HTTP compression of HTML, CSS, JavaScript and RSS feeds

Features:

* Compatible with shared hosting, virtual private servers and dedicated servers / clusters
* Transparent content delivery network (CDN) integration with Media Library, theme files and WordPress itself
* Caching of (minified and compressed) pages and posts in memory or on disk
* Caching of (minified and compressed) CSS and JavaScript in memory, on disk or on CDN
* Caching of RSS (comments, page and site) feeds in memory or on disk
* Caching of search results pages (i.e. URIs with query string variables) in memory or on disk
* Caching of database objects in memory
* Minification of posts and pages and RSS feeds
* Minification (combine and remove comments / white space) of inline, embedded or 3rd party JavaScript (with automated updates)
* Minification (combine and remove comments / white space) of inline, embedded or 3rd party CSS (with automated updates)
* Browser caching of CSS, JavaScript and HTML using future expire headers and entity tags (ETag)
* JavaScript grouping by template (home page, post page etc) with embed location management
* Non-blocking JavaScript embedding
* Import post attachments directly into the Media Library (and CDN)

Easily improve the user experience for your readers without having to change WordPress, your theme, your plugins or how you produce your content.

== Frequently Asked Questions ==

= Why does speed matter? =

Speed is among the most significant success factors web sites face. In fact, your blog's speed directly affects your income (revenue) &mdash; it's a fact. Some high traffic sites conducted some research and uncovered the following:

* Google.com: +500 ms (speed decrease) -> -20% traffic loss [[1](http://home.blarg.net/~glinden/StanfordDataMining.2006-11-29.ppt)]
* Yahoo.com: +400 ms (speed decrease) -> -5-9% full-page traffic loss (visitor left before the page finished loading) [[2](http://www.slideshare.net/stoyan/yslow-20-presentation)]
* Amazon.com: +100 ms (speed decrease) -> -1% sales loss [[1](http://home.blarg.net/~glinden/StanfordDataMining.2006-11-29.ppt)]

A millionth of a second is not a long time, yet the impact is quite significant. Even if you're not a large company (or just hope to become one), a loss is still a loss. However, there is a solution to this problem, take advantage.

= Why is W3 Total Cache better than other cache plugins? =

Most of the popular cache plugins available do a great job and serve their purpose very well. Our plugin remedies numerous performance reducing aspects of any web site going far beyond merely reducing CPU usage (load) and bandwidth consumption for HTML pages alone. Equally important, the plugin requires no theme modifications, modifications to your .htaccess (mod_rewrite rules) or programming compromises to reap the benefits. Setup is easy.

= I've never heard of any of this stuff; my blog is fine, no one complains about the speed. Why should I install this? =

Rarely do readers take the time to complain. They typically just stop browsing earlier than you'd prefer and may not return altogether. It's in every web site owner's best interest is to make sure that the performance of your blog is not hindering its success.

= And how many years of university do I need to use this thing? =

-4 - That's right; a youngster in junior high school can get started with this plugin. Seriously, if you did your own WordPress install or have ever installed a plugin before you're in good shape. If you need help, let us know or perhaps we'll make some videos or the like.

= But even Matt Mullenweg doesn't agree that additional caching is so important, why bother? =

You're right, [Matt did say that](http://ma.tt/2008/03/wordpress-is-open-source/#comment-439787). However, this plugin provides more than just "caching". Because he is correct, the web is dynamic and must remain so. But as we explain throughout this FAQ, our goal is to improve the performance of any blog and we deliver. Furthermore, the techniques we use, are well documented from past [WordCamp presentations](http://www.slideshare.net/bazza/high-performance-wordpress), we simply have combined them in a way that we have found stands up to the highest traffic situations.

= Which WordPress versions are supported? =

To use all features in the suite, a minimum of version 2.5 is required. Earlier versions will benefit from our Media Library Importer to get them back on the upgrade path and into a CDN of their choosing.

= Will the plugin interfere with other plugins or widgets? =

No, on the contrary if you use the minify settings you will improve their performance by several times.

= Does this plugin work with WordPress MU? =

Indeed it does.

= Does this plugin work with BuddyPress (bbPress)? =

Not sure, we'll get to that soon.

= What about comments? Does the plugin slow down the rate at which comments appear? =

On the contrary, as with any other action a user can perform on a site, faster performance will encourage more of it. The cache is so quickly rebuilt in memory that it's no trouble to show visitors the most current version of a post that's experiencing Digg, Slashdot, Drudge Report, Yahoo Buzz or Twitter effect.

= Who do you recommend as a CDN (Content Delivery Network) provider? =

That depends on how you use your blog and where most of your readers read your blog (regionally). Here's a short list:</p>

* [NetDNA](http://www.netdna.com/)
* [VPS NET](https://vps.net/cdn-signup)
* [SimpleCDN](http://www.simplecdn.com/)
* [Amazon S3](http://aws.amazon.com/s3/)
* [Amazon Cloudfront](http://aws.amazon.com/cloudfront/)
* [EdgeCast](http://www.edgecast.com/)
* [Voxel](http://www.voxel.net/products-services/voxcast-cdn)
* [Limelight Networks](http://www.limelightnetworks.com/)
* [Akamai](http://www.akamai.com/)

= Why would I want to cache my feeds? =

We feel that caching objects after the first request and checking for updates before responding subsequent requests (which is kind of how web browsers work too) creates more opportunities for interesting applications and mashups where the blogosphere doesn't require institutional investment to be able to handle developers making hundreds of requests every day the same way we use Google, Twitter and Facebook (for example) APIs today. Think about it, even when major search engines crawl your site, they have to be "gentle" so they don't bring it down, let's turn the paradigm around so that every blog can deliver content in real-time in various ways.

= I don't understand what a CDN has to do with caching, that's completely different, no? =

Technically no, a CDN is a high performance cache that stores static assets (your theme files, media library etc) in various locations throughout the world in order to provide low latency access to them by readers in those regions. So indeed a CDN is a high performance cache, many of which actually store your frequently requested assets in memory for fastest possible response.

= Will this plugin speed up WP Admin? =

Yes, indirectly - if you have a lot of bloggers working with you, you will find that it feels like you have a server dedicated only to WP Admin once this plugin is enabled; the result, increased productivity.


= Which web servers do you support? =

We are aware of no incompatibilities with [apache](http://httpd.apache.org/) 1.3+, [IIS](http://www.iis.net/) 5+ or [litespeed](http://litespeedtech.com/products/webserver/overview/) 4.0.2+. If there's a web server you feel we should be actively testing (e.g. [lighttpd](http://www.lighttpd.net/)), we're [interested in hearing](mailto:wordpressexperts@w3-edge.com).

= Is this plugin server cluster and load balancer friendly? =

Yes, built from the ground up with scale and current hosting paradigms in mind.

= Aren't there any troubleshooting tips? =

No, however we do have an extensive FAQ in the plugin.

In general, due to the manner in which this plugin works the only issues with you may encounter existed before you installed this plugin. In other words, the order or location of JavaScript or CSS files etc will need to be paid attention to as you configure your settings.

Install the plugin to read the full FAQ.

== Installation ==

1. Disable and remove any other caching plugin you may be using &mdash; most plugins have uninstall procedures you can follow. Make sure wp-content/ has 777 permissions before proceeding, e.g.: `# chmod 777 /var/www/vhosts/domain.com/httpdocs/wp-content/`
1. Unzip and upload the plugin to your plugins directory (wp-content/plugins/), when done wp-content/plugins/w3-total-cache/ should exist.
1. Ensure that wp-config.php contains the statement below; if you previously used a caching plugin, this statement is likely to exist already: `define('WP_CACHE', true);`
1. Locate and activate the plugin on the plugins page, then click the Settings link to proceed to the General Settings tab. Set the permissions of wp-content back to 755, e.g.: `# chmod 755 /var/www/vhosts/domain.com/httpdocs/wp-content/`
1. Select your caching preferences for page, database and minify. If memcached is used this will require you to confirm or modify the default settings and add any additional memcached servers you wish to use. To utilize APC and memcached + memcache installation guides have been provided under the Installation tab. For those in shared hosting environments, contact your provider to see if either of these is supported.
1. If you already have a content delivery network provider, proceed to the CDN Settings tab and populate the fields and set your preferences. If you're not running a version of WordPress with the Media Library feature, use the Media Library Import Tool to migrate your post images etc to appropriate locations. If you do not have a CDN provider, you can still improve your site's performance using this feature. Create and use a subdomain on your own server; e.g. static.domain.com and configure options on the CDN tab accordingly.
1. On the Minify Settings tab all of the recommended settings are preset. Specify any CSS and JS files in the respective sections, view your site's HTML source and search for .css and .js files. In the case of JS files you can specify the type and location of the embedding using the drop down menu.
1. You're done! Get back to blogging!

== Changelog ==

= 0.8 =
* Added disk as method for page caching
* Added support for mirror (origin pull) content delivery networks
* Added options to specify minify group policies per template
* Added options for toggling inline CSS and JS minification to improve minify reliability
* Added option to update Media Library attachment hostnames (when migrating domains etc)
* Added "Empty Cache" buttons to respective tabs
* Added additional file download fallback methods for minify
* Improved cookie handling
* Improved header handling
* Improved reliability of Media Library import
* "Don't cache pages for logged in users" is now the default page cache setting
* Fixed minify bug with RSS feeds
* Fixed minify bug with rewriting of url() URI in CSS
* Addressed more page cache invalidity cases
* Addressed rare occurrence of PHP fatal errors when saving post or comments
* Addressed CSS bug on wp-login.php
* Addressed rare MySQL error when uploading attachments to Media Library
* Modified plugin file/directory structure
* Confirmed compatibility with varnish and squid

= 0.7.5.2 =
* Added warning dialog to minify tab about removal of query strings locally hosted object URIs
* Added empty (memcached) cache button to each tab
* Improved reliability of memcache flush
* Minified files now (optionally) upload automatically according to update interval (expiry time)
* Changed directory of minify working files to wp-content/w3tc-cache/
* Fixed bug with parsing memcached server strings
* Fixed bug with minify sometimes not creating files as it should
* Addressed WordPress MU site-wide activation/deactivation issues
* Provided memcache.ini directives updated to improve network throughput

= 0.7.5.1 =
* Added memcached test button for convenience
* Added option to concatenate any script to header or footer with non-blocking options for scripts that cannot be minified (e.g. obfuscated scripts)
* Added options to concatenate JS files only in header or footer (for use with obfuscated scripts)
* Improved notification handling
* Improved compatibility with suPHP
* Improved reliability of Media Library Export
* Fixed bug with database cache that caused comment counts to become out of date
* Fixed minor issue with URI with CDN functionality enabled
* Removed unnecessary minify options
* Minification error dialogs now disabled when JS or CSS minify settings disabled
* Normalized line endings with /n as per minify author's direction
* Resolved a bug in the minify library preventing proper permission notification messages

= 0.7.5 =
* Added handling for magic_quotes set to on
* Fixed issue with auto-download/upgrade and additional error checking
* Fixed a bug preventing minify working properly if either CSS or JS minification was disabled
* Improved handling of inline comments and JavaScript in HTML documents
* Improved handing of @import CSS embedding
* Addressed privilege control issue
* Resolved warnings thrown in various versions of WordPress
* Memcached engine logic modified to better support clustering and multiple memcached instances
* Eliminated false negatives in a number of gzip/deflate compression analysis tools
* Total plugin file size reduced

= 0.7 =
* Added minify support for URIs starting with /
* WordPress MU support bug fixes
* Minor CDN uploader fixes
* Minor error message improvements

= 0.6 =
* Added "Debug Mode" listing all settings and queries with statistics
* Improved error message notifications
* Improved cache stability for large objects
* FAQ and installation instructions corrections/adjustments
* Support for multiple wordpress installations added
* Resolved bug in minification of feeds

= 0.5 =
* Initial release