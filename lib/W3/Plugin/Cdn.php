<?php

/**
 * W3 Total Cache CDN Plugin
 */
if (! defined('W3_PLUGIN_CDN_COMMAND_UPLOAD')) {
    define('W3_PLUGIN_CDN_COMMAND_UPLOAD', 1);
}

if (! defined('W3_PLUGIN_CDN_COMMAND_DELETE')) {
    define('W3_PLUGIN_CDN_COMMAND_DELETE', 2);
}

if (! defined('W3_PLUGIN_CDN_TABLE_QUEUE')) {
    define('W3_PLUGIN_CDN_TABLE_QUEUE', 'w3tc_cdn_queue');
}

if (! defined('W3_PLUGIN_CDN_UPLOADS_DIR')) {
    define('W3_PLUGIN_CDN_UPLOADS_DIR', 'wp-content/uploads/');
}

if (! defined('W3_PLUGIN_CDN_THEMES_DIR')) {
    define('W3_PLUGIN_CDN_THEMES_DIR', ABSPATH . 'wp-content/themes/');
}

if (! defined('W3_PLUGIN_CDN_INCLUDES_DIR')) {
    define('W3_PLUGIN_CDN_INCLUDES_DIR', ABSPATH . 'wp-includes/');
}

require_once dirname(__FILE__) . '/../Plugin.php';

/**
 * Class W3_Plugin_Cdn
 */
class W3_Plugin_Cdn extends W3_Plugin
{
    /**
     * Array of replaced URLs
     *
     * @var array
     */
    var $replaced_urls = array();
    
    /**
     * Run plugin
     */
    function run()
    {
        register_activation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3_PLUGIN_FILE, array(
            &$this, 
            'deactivate'
        ));
        
        add_filter('cron_schedules', array(
            &$this, 
            'cron_schedules'
        ));
        
        if ($this->_config->get_boolean('cdn.enabled')) {
            add_action('delete_attachment', array(
                &$this, 
                'delete_attachment'
            ));
            
            add_filter('wp_generate_attachment_metadata', array(
                &$this, 
                'generate_attachment_metadata'
            ));
            
            add_filter('the_content', array(
                &$this, 
                'the_content'
            ));
            
            add_action('w3_cdn_cron_queue_process', array(
                &$this, 
                'cron_queue_process'
            ));
            
            if (($this->_config->get_boolean('cdn.includes.enable', true) || $this->_config->get_boolean('cdn.theme.enable', true) || $this->_config->get_boolean('cdn.minify.enable', true) || $this->_config->get_boolean('cdn.custom.enable', true) || $this->_config->get_boolean('cdn.debug')) && ! is_admin()) {
                ob_start(array(
                    &$this, 
                    'ob_callback'
                ));
            }
        }
    }
    
    /**
     * Returns plugin instance
     *
     * @return W3_Plugin_Cdn
     */
    function &instance()
    {
        static $instance = null;
        
        if (! $instance) {
            $class = __CLASS__;
            $instance = & new $class();
        }
        
        return $instance;
    }
    
    /**
     * Activation action
     */
    function activate()
    {
        global $wpdb;
        
        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `file` varchar(255) NOT NULL DEFAULT '0',
            `command` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 - upload, 2 - delete',
            `last_error` varchar(255) NOT NULL DEFAULT '',
            `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            UNIQUE KEY `file` (`file`),
            KEY `date` (`date`)
        )", $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE);
        $wpdb->query($sql);
        
        wp_schedule_event(time(), 'every_15_min', 'w3_cdn_cron_queue_process');
    }
    
    /**
     * Deactivation action
     */
    function deactivate()
    {
        global $wpdb;
        
        $sql = sprintf('DROP TABLE IF EXISTS %s', $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE);
        $wpdb->query($sql);
        
        wp_clear_scheduled_hook('w3_cdn_cron_queue_process');
    }
    
    /**
     * Cron queue process event
     */
    function cron_queue_process()
    {
        $limit_queue = $this->_config->get_integer('cdn.limit.queue', 25);
        $this->queue_process($limit_queue);
    }
    
    /**
     * On attachment delete action
     *
     * @param integer $attachment_id
     */
    function delete_attachment($attachment_id)
    {
        $files = $this->get_attachment_files($attachment_id);
        $this->delete($files, true);
    }
    
    /**
     * Generate attachment metadata filter
     *
     * @param array $metadata
     * @return array
     */
    function generate_attachment_metadata($metadata)
    {
        $files = $this->get_metadata_files($metadata);
        
        $this->upload($files, true);
        
        return $metadata;
    }
    
    /**
     * Content filter
     *
     * @param string $content
     * @return string
     */
    function the_content($content)
    {
        static $siteurl = null;
        
        if (! $siteurl) {
            $siteurl = get_option('siteurl');
        }
        
        $content = preg_replace_callback('~' . preg_quote($siteurl) . '(/wp-content/uploads/([^"\'>]+))~', array(
            &$this, 
            'link_replace_callback'
        ), $content);
        
        return $content;
    }
    
    /**
     * Cron schedules filter
     *
     * @return array
     */
    function cron_schedules()
    {
        return array(
            'every_15_min' => array(
                'interval' => 900, 
                'display' => 'Every 15 minutes'
            ), 
            'every_30_min' => array(
                'interval' => 1800, 
                'display' => 'Every 30 munites'
            )
        );
    }
    
    /**
     * Returns attachment files by attachment ID
     *
     * @param integer $attachment_id
     * @return array
     */
    function get_attachment_files($attachment_id)
    {
        $metadata = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        
        if (isset($metadata['file'])) {
            $files = $this->get_metadata_files($metadata);
        } else {
            $file = get_post_meta($attachment_id, '_wp_attached_file', true);
            $files = array(
                W3_PLUGIN_CDN_UPLOADS_DIR . $file
            );
        }
        
        return $files;
    }
    
    /**
     * OB Callback
     *
     * @param string $buffer
     * @return string
     */
    function ob_callback($buffer)
    {
        if (! w3_is_xml($buffer)) {
            return $buffer;
        }
        
        $siteurl = get_option('siteurl');
        $regexps = array();
        
        if ($this->_config->get_boolean('cdn.includes.enable', true)) {
            $mask = $this->_config->get_string('cdn.includes.files');
            if (! empty($mask)) {
                $regexps[] = '~' . preg_quote($siteurl) . '(/wp-includes/(' . $this->get_regexp_by_mask($mask) . '))~U';
            }
        }
        
        if ($this->_config->get_boolean('cdn.theme.enable', true)) {
            $stylesheet = get_stylesheet();
            $mask = $this->_config->get_string('cdn.theme.files');
            if (! empty($mask)) {
                $regexps[] = '~' . preg_quote($siteurl) . '(/wp-content/themes/' . preg_quote($stylesheet) . '/(' . $this->get_regexp_by_mask($mask) . '))~U';
            }
        }
        
        if ($this->_config->get_boolean('cdn.minify.enable', true)) {
            $regexps[] = '~' . preg_quote($siteurl) . '(/' . preg_quote(W3_PLUGIN_MINIFY_MIN_DIRNAME) . '/include(-footer)?(-nb)?\.(css|js))~';
        }
        
        if ($this->_config->get_boolean('cdn.custom.enable', true)) {
            $files = $this->_config->get_array('cdn.custom.files');
            if (! empty($files)) {
                $files_quoted = array();
                foreach ($files as $file) {
                    $files_quoted[] = preg_quote($file);
                }
                $regexps[] = '~' . preg_quote($siteurl) . '(/(' . implode('|', $files_quoted) . '))~';
            }
        }
        
        foreach ($regexps as $regexp) {
            $buffer = preg_replace_callback($regexp, array(
                &$this, 
                'link_replace_callback'
            ), $buffer);
        }
        
        if ($this->_config->get_boolean('cdn.debug')) {
            $buffer .= "\r\n\r\n" . $this->get_debug_info();
        }
        
        return $buffer;
    }
    
    /**
     * Returns attachment files by metadata
     *
     * @param array $metadata
     * @return array
     */
    function get_metadata_files($metadata)
    {
        $files = array();
        
        if (isset($metadata['file'])) {
            $files[] = W3_PLUGIN_CDN_UPLOADS_DIR . $metadata['file'];
            if (isset($metadata['sizes'])) {
                $file_dir = W3_PLUGIN_CDN_UPLOADS_DIR . dirname($metadata['file']);
                foreach ((array) $metadata['sizes'] as $size) {
                    if (isset($size['file'])) {
                        $files[] = $file_dir . '/' . $size['file'];
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Adds file to queue
     *
     * @param string $file
     * @param integer $command
     * @param string $last_error
     * @return ingteer
     */
    function queue_add($file, $command, $last_error)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE;
        $sql = sprintf('SELECT id FROM %s WHERE file = "%s" AND command != %d', $table, $wpdb->escape($file), $command);
        
        if (($row = $wpdb->get_row($sql))) {
            $sql = sprintf('DELETE FROM %s WHERE id = %d', $table, $row->id);
        } else {
            $sql = sprintf('REPLACE INTO %s (file, command, last_error, date) VALUES ("%s", %d, "%s", NOW())', $table, $wpdb->escape($file), $command, $wpdb->escape($last_error));
        }
        
        return $wpdb->query($sql);
    }
    
    /**
     * Updates file date in the queue
     *
     * @param integer $queue_id
     * @param string $last_error
     * @return integer
     */
    function queue_update($queue_id, $last_error)
    {
        global $wpdb;
        
        $sql = sprintf('UPDATE %s SET last_error = "%s", date = NOW() WHERE id = %d', $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE, $wpdb->escape($last_error), $queue_id);
        
        return $wpdb->query($sql);
    }
    
    /**
     * Removes from queue
     *
     * @param integer $queue_id
     * @return integer
     */
    function queue_delete($queue_id)
    {
        global $wpdb;
        
        $sql = sprintf('DELETE FROM %s WHERE id = %d', $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE, $queue_id);
        
        return $wpdb->query($sql);
    }
    
    /**
     * Empties queue
     *
     * @param integer $command
     * @return integer
     */
    function queue_empty($command)
    {
        global $wpdb;
        
        $sql = sprintf('DELETE FROM %s WHERE command = %d', $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE, $command);
        
        return $wpdb->query($sql);
    }
    
    /**
     * Returns queue
     *
     * @param integer $limit
     * @return array
     */
    function queue_get($limit = null)
    {
        global $wpdb;
        
        $sql = sprintf('SELECT * FROM %s%s ORDER BY date', $wpdb->prefix, W3_PLUGIN_CDN_TABLE_QUEUE);
        
        if ($limit) {
            $sql .= sprintf(' LIMIT %d', $limit);
        }
        
        $results = $wpdb->get_results($sql);
        $queue = array();
        
        if ($results) {
            foreach ((array) $results as $result) {
                $queue[$result->command][] = $result;
            }
        }
        
        return $queue;
    }
    
    /**
     * Process queue
     *
     * @param integer $limit
     */
    function queue_process($limit)
    {
        $commands = $this->queue_get($limit);
        
        if (count($commands)) {
            $cdn = $this->get_cdn();
            foreach ($commands as $command => $queue) {
                $files = array();
                $results = array();
                $map = array();
                
                foreach ($queue as $result) {
                    $local_path = $this->format_local_path($result->file);
                    $remote_path = $this->format_remote_path($result->file);
                    $files[$local_path] = $remote_path;
                    $map[$local_path] = $result->id;
                }
                
                switch ($command) {
                    case W3_PLUGIN_CDN_COMMAND_UPLOAD:
                        $cdn->upload($files, $results);
                        break;
                    
                    case W3_PLUGIN_CDN_COMMAND_DELETE:
                        $cdn->delete($files, $results);
                        break;
                }
                
                foreach ($results as $result) {
                    if ($result['result'] == W3_CDN_RESULT_OK) {
                        $this->queue_delete($map[$result['local_path']]);
                    } else {
                        $this->queue_update($map[$result['local_path']], $result['error']);
                    }
                }
            }
        }
    }
    
    /**
     * Uploads files to CDN
     *
     * @param array $files
     * @param boolean $queue_failed
     * @param array $results
     * @return boolean|array
     */
    function upload($files, $queue_failed = false, &$results = array())
    {
        $upload = array();
        $map = array();
        
        foreach ($files as $file) {
            $local_path = $this->format_local_path($file);
            $remote_path = $this->format_remote_path($file);
            $upload[$local_path] = $remote_path;
            $map[$local_path] = $file;
        }
        
        $cdn = $this->get_cdn();
        if (! $cdn->upload($upload, $results)) {
            if ($queue_failed) {
                foreach ($results as $result) {
                    if ($result['result'] != W3_CDN_RESULT_OK) {
                        $this->queue_add($map[$result['local_path']], W3_PLUGIN_CDN_COMMAND_UPLOAD, $result['error']);
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Deletes files frrom CDN
     *
     * @param array $files
     * @param boolean $queue_failed
     * @param array $results
     * @return boolean|array
     */
    function delete($files, $queue_failed = false, &$results = array())
    {
        $delete = array();
        $map = array();
        
        foreach ($files as $file) {
            $local_path = $this->format_local_path($file);
            $remote_path = $this->format_remote_path($file);
            $delete[$local_path] = $remote_path;
            $map[$local_path] = $file;
        }
        
        $cdn = $this->get_cdn();
        if (! $cdn->delete($delete, $results)) {
            if ($queue_failed) {
                foreach ($results as $result) {
                    if ($result['result'] != W3_CDN_RESULT_OK) {
                        $this->queue_add($map[$result['local_path']], W3_PLUGIN_CDN_COMMAND_DELETE, $result['error']);
                    }
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Export library to CDN
     * 
     * @param integer $limit
     * @param integer $offset
     * @param integer $count
     * @param integer $total
     * @param array $results
     * @return boolean
     */
    function export_library($limit = null, $offset = null, &$count = null, &$total = null, &$results = array())
    {
        global $wpdb;
        
        $count = 0;
        $total = 0;
        $results = array();
        
        $sql = sprintf('SELECT
        		pm.meta_value AS file,
                pm2.meta_value AS metadata
            FROM
                %sposts AS p
            LEFT JOIN
                %spostmeta AS pm ON p.ID = pm.post_ID AND pm.meta_key = "_wp_attached_file" 
            LEFT JOIN
            	%spostmeta AS pm2 ON p.ID = pm2.post_ID AND pm2.meta_key = "_wp_attachment_metadata"    
            WHERE
                p.post_type = "attachment"', $wpdb->prefix, $wpdb->prefix, $wpdb->prefix);
        
        if ($limit) {
            $sql .= sprintf(' LIMIT %d', $limit);
            
            if ($offset) {
                $sql .= sprintf(' OFFSET %d', $offset);
            }
        }
        
        $posts = $wpdb->get_results($sql);
        
        if ($posts) {
            $count = count($posts);
            $total = $this->get_attachments_count();
            $files = array();
            
            foreach ($posts as $post) {
                if (! empty($post->metadata)) {
                    $metadata = @unserialize($post->metadata);
                    if (isset($metadata['file'])) {
                        $files = array_merge($files, $this->get_metadata_files($metadata));
                    } elseif (! empty($post->file)) {
                        $files[] = $post->file;
                    }
                }
            }
            
            return $this->upload($files, false, $results);
        }
        
        return false;
    }
    
    /**
     * Imports library
     *
     * @param integer $limit
     * @param integer $offset
     * @param integer $count
     * @param integer $total
     * @param array $results
     * @return boolean
     */
    function import_library($limit = null, $offset = null, &$count = null, &$total = null, &$results = array())
    {
        global $wpdb;
        
        $count = 0;
        $total = 0;
        $results = array();
        
        $sql = sprintf('SELECT
        		post_content
            FROM
                %sposts
            WHERE
                post_status = "publish"
                AND post_type = "post"
                AND (post_content LIKE "%%src=%%"
                	OR post_content LIKE "%%href=%%")
       ', $wpdb->prefix);
        
        if ($limit) {
            $sql .= sprintf(' LIMIT %d', $limit);
            
            if ($offset) {
                $sql .= sprintf(' OFFSET %d', $offset);
            }
        }
        
        $posts = $wpdb->get_results($sql);
        
        if ($posts) {
            $count = count($posts);
            $total = $this->get_import_posts_count();
            
            $siteurl = get_option('siteurl');
            $upload_info = wp_upload_dir();
            $upload_dir = ltrim(str_replace($siteurl, '', $upload_info['baseurl']), '/');
            $regexp = $this->get_regexp_by_mask($this->_config->get_string('cdn.import.files'));
            $import_external = $this->_config->get_boolean('cdn.import.external');
            
            foreach ($posts as $post) {
                $matches = null;
                
                if (preg_match_all('~(href|src)=[\'"]?([^\'"<>\s]+)[\'"]?~', $post->post_content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $src = ltrim(str_replace($siteurl, '', $match[2]), '/');
                        $file = sprintf('%s/%s', $upload_info['path'], basename($src));
                        $dst = str_replace(ABSPATH, '', $file);
                        $result = false;
                        $error = '';
                        $download_result = null;
                        
                        if (preg_match('~(' . $regexp . ')$~', $src)) {
                            if (! file_exists($file)) {
                                if (w3_is_url($src)) {
                                    if ($import_external) {
                                        $download_result = $this->download($src, $file);
                                    } else {
                                        $error = 'External file import is disabled';
                                    }
                                } elseif (strstr($src, $upload_dir) === false) {
                                    $download_result = @copy(ABSPATH . $src, $file);
                                } else {
                                    $error = 'Source file already exists';
                                }
                                
                                if ($download_result !== null) {
                                    if ($download_result) {
                                        $title = basename($file);
                                        $guid = $upload_info['url'] . '/' . $title;
                                        $mime_type = $this->get_mime_type($file);
                                        
                                        $GLOBALS['wp_rewrite'] = & new WP_Rewrite();
                                        
                                        $id = wp_insert_attachment(array(
                                            'post_mime_type' => $mime_type, 
                                            'guid' => $guid, 
                                            'post_title' => $title, 
                                            'post_content' => ''
                                        ), $file);
                                        
                                        if (! is_wp_error($id)) {
                                            require_once ABSPATH . 'wp-admin/includes/image.php';
                                            wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $file));
                                            
                                            $result = true;
                                            $error = 'OK';
                                        } else {
                                            $error = 'Unable to insert attachment';
                                        }
                                    
                                    } else {
                                        $error = 'Unable to download file';
                                    }
                                }
                            } else {
                                $error = 'Destination file already exists';
                            }
                        } else {
                            $error = 'File type is not supported';
                        }
                        
                        $results[] = array(
                            'src' => $src, 
                            'dst' => $dst, 
                            'result' => $result, 
                            'error' => $error
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Returns attachments count
     *
     * @return integer
     */
    function get_attachments_count()
    {
        global $wpdb;
        
        $sql = sprintf('SELECT
        		COUNT(DISTINCT p.ID)
            FROM
                %sposts AS p
            JOIN
                %spostmeta AS pm ON p.ID = pm.post_ID AND (pm.meta_key = "_wp_attached_file" OR pm.meta_key = "_wp_attachment_metadata")
            WHERE
                p.post_type = "attachment"', $wpdb->prefix, $wpdb->prefix);
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Returns import posts count
     *
     * @return integer
     */
    function get_import_posts_count()
    {
        global $wpdb;
        
        $sql = sprintf('SELECT
        		COUNT(*)
            FROM
                %sposts
            WHERE
                post_status = "publish"
                AND post_type = "post"
                AND (post_content LIKE "%%src=%%"
                	OR post_content LIKE "%%href=%%")
                ', $wpdb->prefix);
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Exports includes to CDN
     */
    function get_files_includes()
    {
        $files = $this->search_files(W3_PLUGIN_CDN_INCLUDES_DIR, 'wp-includes', $this->_config->get_string('cdn.includes.files'));
        
        return $files;
    }
    
    /**
     * Exports theme to CDN
     */
    function get_files_theme()
    {
        $stylesheet = get_stylesheet();
        $files = $this->search_files(W3_PLUGIN_CDN_THEMES_DIR . $stylesheet, 'wp-content/themes/' . $stylesheet, $this->_config->get_string('cdn.theme.files'));
        
        return $files;
    }
    
    /**
     * Exports min files to CDN
     */
    function get_files_minify()
    {
        require_once dirname(__FILE__) . '/Minify.php';
        $minify = W3_Plugin_Minify::instance();
        
        $css_header_url = $minify->format_group_url('css', 'include');
        $js_header_url = $minify->format_group_url('js', 'include');
        $js_header_nb_url = $minify->format_group_url('js', 'include-nb');
        $js_footer_url = $minify->format_group_url('js', 'include-footer');
        $js_footer_nb_url = $minify->format_group_url('js', 'include-footer-nb');
        
        $css_header_file = basename($css_header_url);
        $js_header_file = basename($js_header_url);
        $js_header_nb_file = basename($js_header_nb_url);
        $js_footer_file = basename($js_footer_url);
        $js_footer_nb_file = basename($js_footer_nb_url);
        
        $downloads = array(
            array(
                'url' => $css_header_url, 
                'file' => $css_header_file
            ), 
            array(
                'url' => $js_header_url, 
                'file' => $js_header_file
            ), 
            array(
                'url' => $js_header_nb_url, 
                'file' => $js_header_nb_file
            ), 
            array(
                'url' => $js_footer_url, 
                'file' => $js_footer_file
            ), 
            array(
                'url' => $js_footer_nb_url, 
                'file' => $js_footer_nb_file
            )
        );
        
        $files = array();
        
        foreach ($downloads as $download) {
            if ($this->download($download['url'], W3_PLUGIN_MINIFY_MIN_DIR . '/' . $download['file'])) {
                $files[] = W3_PLUGIN_MINIFY_MIN_DIRNAME . '/' . $download['file'];
            }
        }
        
        return $files;
    }
    
    /**
     * Exports custom files to CDN
     */
    function get_files_custom()
    {
        $files = $this->_config->get_array('cdn.custom.files');
        
        return $files;
    }
    
    /**
     * Downloads URL
     *
     * @param string $url
     * @param string $file
     * @return boolean
     */
    function download($url, $file)
    {
        if (! ($data = @file_get_contents($url)) || ! ($fp = @fopen($file, 'w'))) {
            return false;
        }
        
        @fputs($fp, $data);
        @fclose($fp);
        
        return true;
    }
    
    /**
     * Formats local file path
     *
     * @param string $file
     * @return string
     */
    function format_local_path($file)
    {
        return ABSPATH . $file;
    }
    
    /**
     * Formats remote file path
     *
     * @param string $file
     * @return string
     */
    function format_remote_path($file)
    {
        return $file;
    }
    
    /**
     * Link replace callback
     *
     * @param array $matches
     * @return string
     */
    function link_replace_callback($matches)
    {
        global $wpdb;
        static $queue = null, $domain = null, $debug = null;
        
        if ($queue === null) {
            $sql = sprintf('SELECT file FROM %s', $wpdb->prefix . W3_PLUGIN_CDN_TABLE_QUEUE);
            $queue = $wpdb->get_col($sql);
        }
        
        if (in_array($matches[2], $queue)) {
            return $matches[0];
        }
        
        if ($domain === null) {
            $domain = $this->_config->get_string('cdn.domain');
        }
        
        if ($debug === null) {
            $debug = $this->_config->get_boolean('cdn.debug');
        }
        
        if (empty($domain)) {
            return $matches[0];
        }
        
        $url = sprintf('http://%s%s', $domain, $matches[1]);
        
        if ($debug) {
            $this->replaced_urls[] = $url;
        }
        
        return $url;
    }
    
    /**
     * Search files
     *
     * @param string $search_dir
     * @param string $mask
     * @param boolean $recursive
     * @return array
     */
    function search_files($search_dir, $base_dir, $mask = '*.*', $recursive = true)
    {
        static $path = array();
        
        $files = array();
        
        if (! ($dir = @dir($search_dir))) {
            return $files;
        }
        
        while (($entry = @$dir->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            
            if (is_dir($dir->path . DIRECTORY_SEPARATOR . $entry) && $recursive) {
                array_push($path, $entry);
                $files = array_merge($files, $this->search_files($dir->path . DIRECTORY_SEPARATOR . $entry, $base_dir, $mask, $recursive));
                array_pop($path);
            } else {
                $regexp = '~^' . $this->get_regexp_by_mask($mask) . '$~i';
                if (preg_match($regexp, $entry)) {
                    $files[] = $base_dir . '/' . (($p = implode('/', $path)) != '' ? $p . '/' : '') . $entry;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Returns regexp by mask
     *
     * @param string $mask
     * @return string
     */
    function get_regexp_by_mask($mask)
    {
        $regexp = str_replace(array(
            '\*', 
            '\?', 
            '\[', 
            '\]', 
            ';'
        ), array(
            '[^\s]*', 
            '[^\s]', 
            '[', 
            ']', 
            '|'
        ), preg_quote($mask));
        
        return $regexp;
    }
    
    /**
     * Returns file mime type
     *
     * @param string $file
     * @return string
     */
    function get_mime_type($file)
    {
        $mime_types = array(
            'jpg|jpeg|jpe' => 'image/jpeg', 
            'gif' => 'image/gif', 
            'png' => 'image/png', 
            'bmp' => 'image/bmp', 
            'tif|tiff' => 'image/tiff', 
            'ico' => 'image/x-icon', 
            'asf|asx|wax|wmv|wmx' => 'video/asf', 
            'avi' => 'video/avi', 
            'divx' => 'video/divx', 
            'mov|qt' => 'video/quicktime', 
            'mpeg|mpg|mpe' => 'video/mpeg', 
            'txt|c|cc|h' => 'text/plain', 
            'rtx' => 'text/richtext', 
            'css' => 'text/css', 
            'htm|html' => 'text/html', 
            'mp3|m4a' => 'audio/mpeg', 
            'mp4|m4v' => 'video/mp4', 
            'ra|ram' => 'audio/x-realaudio', 
            'wav' => 'audio/wav', 
            'ogg' => 'audio/ogg', 
            'mid|midi' => 'audio/midi', 
            'wma' => 'audio/wma', 
            'rtf' => 'application/rtf', 
            'js' => 'application/javascript', 
            'pdf' => 'application/pdf', 
            'doc|docx' => 'application/msword', 
            'pot|pps|ppt|pptx' => 'application/vnd.ms-powerpoint', 
            'wri' => 'application/vnd.ms-write', 
            'xla|xls|xlsx|xlt|xlw' => 'application/vnd.ms-excel', 
            'mdb' => 'application/vnd.ms-access', 
            'mpp' => 'application/vnd.ms-project', 
            'swf' => 'application/x-shockwave-flash', 
            'class' => 'application/java', 
            'tar' => 'application/x-tar', 
            'zip' => 'application/zip', 
            'gz|gzip' => 'application/x-gzip', 
            'exe' => 'application/x-msdownload', 
            // openoffice formats
            'odt' => 'application/vnd.oasis.opendocument.text', 
            'odp' => 'application/vnd.oasis.opendocument.presentation', 
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet', 
            'odg' => 'application/vnd.oasis.opendocument.graphics', 
            'odc' => 'application/vnd.oasis.opendocument.chart', 
            'odb' => 'application/vnd.oasis.opendocument.database', 
            'odf' => 'application/vnd.oasis.opendocument.formula'
        );
        
        $file_ext = strrchr($file, '.');
        
        if ($file_ext) {
            $file_ext = ltrim($file_ext, '.');
            foreach ($mime_types as $extension => $mime_type) {
                $exts = explode('|', $extension);
                foreach ($exts as $ext) {
                    if ($file_ext == $ext) {
                        return $mime_type;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Returns file from thumb file
     *
     * @param string $thumb
     * @return string
     */
    function get_file_from_thumb($thumb)
    {
        return preg_replace('~\-\d{2,4}x\d{2,4}\.(jpg|png|gif)$~', '.\\1', $thumb);
    }
    
    /**
     * Returns CDN object
     *
     * @return W3_Cdn_Base
     */
    function &get_cdn()
    {
        static $cdn = null;
        
        if (! $cdn) {
            require_once dirname(__FILE__) . '/../Cdn.php';
            
            $engine = $this->_config->get_string('cdn.engine');
            $engine_config = array();
            
            switch ($engine) {
                case 'ftp':
                    $engine_config = array(
                        'host' => $this->_config->get_string('cdn.ftp.host'), 
                        'user' => $this->_config->get_string('cdn.ftp.user'), 
                        'pass' => $this->_config->get_string('cdn.ftp.pass'), 
                        'path' => $this->_config->get_string('cdn.ftp.path'), 
                        'pasv' => $this->_config->get_boolean('cdb.ftp.pasv')
                    );
                    break;
            }
            
            $cdn = W3_Cdn::instance($engine, $engine_config);
        }
        
        return $cdn;
    }
    
    /**
     * Returns debug info
     * 
     * @return string
     */
    function get_debug_info()
    {
        $debug_info = "<!-- W3 Total Cache: CDN debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), $this->_config->get_string('cdn.engine'));
        
        if (count($this->replaced_urls)) {
            $debug_info .= "Replaced URLs:\r\n";
            
            foreach ($this->replaced_urls as $replaced_url) {
                $debug_info .= sprintf("%s\r\n", $replaced_url);
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
}
