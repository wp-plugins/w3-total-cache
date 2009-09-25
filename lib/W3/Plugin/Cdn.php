<?php

/**
 * W3 Total Cache CDN Plugin
 */
require_once W3TC_LIB_W3_DIR . '/Plugin.php';

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
        register_activation_hook(W3TC_FILE, array(
            &$this, 
            'activate'
        ));
        
        register_deactivation_hook(W3TC_FILE, array(
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
            
            if ($this->can_cdn()) {
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
        
        if ($instance === null) {
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
        
        $upload_info = w3_upload_info();
        
        if (! $upload_info) {
            $upload_path = get_option('upload_path');
            $upload_path = trim($upload_path);
            if (empty($upload_path)) {
                $upload_path = WP_CONTENT_DIR . '/uploads';
            }
            
            w3_writable_error($upload_path);
        }
        
        $sql = sprintf('DROP TABLE IF EXISTS `%s%s`', $wpdb->prefix, W3TC_CDN_TABLE_QUEUE);
        
        $wpdb->query($sql);
        
        $sql = sprintf("CREATE TABLE `%s%s` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `local_path` varchar(255) NOT NULL DEFAULT '',
            `remote_path` varchar(255) NOT NULL DEFAULT '',
            `command` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 - Upload, 2 - Delete',
            `last_error` varchar(255) NOT NULL DEFAULT '',
            `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            UNIQUE KEY `path` (`local_path`, `remote_path`),
            KEY `date` (`date`)
        ) DEFAULT CHARSET=latin1", $wpdb->prefix, W3TC_CDN_TABLE_QUEUE);
        
        $wpdb->query($sql);
        
        if (! $wpdb->result) {
            die(sprintf('Unable to create table <strong>%s%s</strong>: %s', $wpdb->prefix, W3TC_CDN_TABLE_QUEUE, $wpdb->last_error));
        }
        
        wp_schedule_event(time(), 'every_15_min', 'w3_cdn_cron_queue_process');
    }
    
    /**
     * Deactivation action
     */
    function deactivate()
    {
        global $wpdb;
        
        wp_clear_scheduled_hook('w3_cdn_cron_queue_process');
        
        $sql = sprintf('DROP TABLE IF EXISTS `%s%s`', $wpdb->prefix, W3TC_CDN_TABLE_QUEUE);
        $wpdb->query($sql);
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
        $siteurl = w3_get_site_url();
        $upload_url = $this->get_upload_url();
        
        if ($upload_url) {
            $regexp = '~(["\'])((' . preg_quote($siteurl) . ')?/?(' . preg_quote($upload_url) . '[^"\'>]+))~';
            
            $content = preg_replace_callback($regexp, array(
                &$this, 
                'link_replace_callback'
            ), $content);
        }
        
        return $content;
    }
    
    /**
     * Cron schedules filter
     *
     * @paran array $schedules
     * @return array
     */
    function cron_schedules($schedules)
    {
        return array_merge($schedules, array(
            'every_15_min' => array(
                'interval' => 900, 
                'display' => 'Every 15 minutes'
            )
        ));
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
                $this->normalize_attachment_file($file)
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
        
        $siteurl = w3_get_site_url();
        $regexps = array();
        
        if ($this->_config->get_boolean('cdn.includes.enable', true)) {
            $mask = $this->_config->get_string('cdn.includes.files');
            if (! empty($mask)) {
                $regexps[] = '~(["\'])((' . preg_quote($siteurl) . ')?/?(' . preg_quote(WPINC) . '/(' . $this->get_regexp_by_mask($mask) . ')))~';
            }
        }
        
        if ($this->_config->get_boolean('cdn.theme.enable', true)) {
            $theme_dir = ltrim(str_replace($siteurl, '', get_stylesheet_directory_uri()), '/');
            $mask = $this->_config->get_string('cdn.theme.files');
            if (! empty($mask)) {
                $regexps[] = '~(["\'])((' . preg_quote($siteurl) . ')?/?(' . preg_quote($theme_dir) . '/(' . $this->get_regexp_by_mask($mask) . ')))~';
            }
        }
        
        if ($this->_config->get_boolean('cdn.minify.enable', true)) {
            $regexps[] = '~(["\'])((' . preg_quote($siteurl) . ')?/?(' . preg_quote(W3TC_CONTENT_DIR_NAME) . '/[a-z0-9-_]+\.include(-footer)?(-nb)?\.(css|js)))~';
        }
        
        if ($this->_config->get_boolean('cdn.custom.enable', true)) {
            $files = $this->_config->get_array('cdn.custom.files');
            if (! empty($files)) {
                $files_quoted = array();
                foreach ($files as $file) {
                    $file = ltrim(str_replace($siteurl, '', $file), '/');
                    $files_quoted[] = preg_quote($file);
                }
                $regexps[] = '~(["\'])((' . preg_quote($siteurl) . ')?/?(' . implode('|', $files_quoted) . '))~';
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
        
        $site_path = w3_get_site_path();
        $upload_dir = $this->get_upload_dir();
        $upload_url = $this->get_upload_url();
        
        if ($upload_dir && $upload_url) {
            if (isset($metadata['file'])) {
                $file = $this->normalize_attachment_file($metadata['file']);
                $local_file = $site_path . $upload_dir . '/' . $file;
                $remote_file = $site_path . $upload_url . '/' . $file;
                $files[$local_file] = $remote_file;
                if (isset($metadata['sizes'])) {
                    $file_dir = dirname($file);
                    foreach ((array) $metadata['sizes'] as $size) {
                        if (isset($size['file'])) {
                            $local_file = $site_path . $upload_dir . '/' . $file_dir . '/' . $size['file'];
                            $remote_file = $site_path . $upload_url . '/' . $file_dir . '/' . $size['file'];
                            $files[$local_file] = $remote_file;
                        }
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Adds file to queue
     *
     * @param string $local_path
     * @param string $remote_path
     * @param integer $command
     * @param string $last_error
     * @return ingteer
     */
    function queue_add($local_path, $remote_path, $command, $last_error)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . W3TC_CDN_TABLE_QUEUE;
        $sql = sprintf('SELECT id FROM %s WHERE local_path = "%s" AND remote_path = "%s" AND command != %d', $table, $wpdb->escape($local_path), $wpdb->escape($remote_path), $command);
        
        if (($row = $wpdb->get_row($sql))) {
            $sql = sprintf('DELETE FROM %s WHERE id = %d', $table, $row->id);
        } else {
            $sql = sprintf('REPLACE INTO %s (local_path, remote_path, command, last_error, date) VALUES ("%s", "%s", %d, "%s", NOW())', $table, $wpdb->escape($local_path), $wpdb->escape($remote_path), $command, $wpdb->escape($last_error));
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
        
        $sql = sprintf('UPDATE %s SET last_error = "%s", date = NOW() WHERE id = %d', $wpdb->prefix . W3TC_CDN_TABLE_QUEUE, $wpdb->escape($last_error), $queue_id);
        
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
        
        $sql = sprintf('DELETE FROM %s WHERE id = %d', $wpdb->prefix . W3TC_CDN_TABLE_QUEUE, $queue_id);
        
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
        
        $sql = sprintf('DELETE FROM %s WHERE command = %d', $wpdb->prefix . W3TC_CDN_TABLE_QUEUE, $command);
        
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
        
        $sql = sprintf('SELECT * FROM %s%s ORDER BY date', $wpdb->prefix, W3TC_CDN_TABLE_QUEUE);
        
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
                    $files[$result->local_path] = $result->remote_path;
                    $map[$result->local_path] = $result->id;
                }
                
                switch ($command) {
                    case W3TC_CDN_COMMAND_UPLOAD:
                        $cdn->upload($files, $results);
                        break;
                    
                    case W3TC_CDN_COMMAND_DELETE:
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
        
        foreach ($files as $local_file => $remote_file) {
            $local_path = $this->format_local_path($local_file);
            $remote_path = $this->format_remote_path($remote_file);
            $upload[$local_path] = $remote_path;
        }
        
        $cdn = $this->get_cdn();
        if (! $cdn->upload($upload, $results)) {
            if ($queue_failed) {
                foreach ($results as $result) {
                    if ($result['result'] != W3_CDN_RESULT_OK) {
                        $this->queue_add($result['local_path'], $result['remote_path'], W3TC_CDN_COMMAND_UPLOAD, $result['error']);
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
        
        foreach ($files as $local_file => $remote_file) {
            $local_path = $this->format_local_path($local_file);
            $remote_path = $this->format_remote_path($remote_file);
            $delete[$local_path] = $remote_path;
        }
        
        $cdn = $this->get_cdn();
        if (! $cdn->delete($delete, $results)) {
            if ($queue_failed) {
                foreach ($results as $result) {
                    if ($result['result'] != W3_CDN_RESULT_OK) {
                        $this->queue_add($result['local_path'], $result['remote_path'], W3TC_CDN_COMMAND_DELETE, $result['error']);
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
        
        $site_path = w3_get_site_path();
        $upload_dir = $this->get_upload_dir();
        $upload_url = $this->get_upload_url();
        
        if ($upload_dir && $upload_url) {
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
                p.post_type = "attachment"
            GROUP BY
            	p.ID', $wpdb->prefix, $wpdb->prefix, $wpdb->prefix);
            
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
                    } else {
                        $metadata = array();
                    }
                    if (isset($metadata['file'])) {
                        $files = array_merge($files, $this->get_metadata_files($metadata));
                    } elseif (! empty($post->file)) {
                        $file = $this->normalize_attachment_file($post->file);
                        $local_file = $site_path . $upload_dir . '/' . $file;
                        $remote_file = $site_path . $upload_url . '/' . $file;
                        $files[$local_file] = $remote_file;
                    }
                }
                
                return $this->upload($files, false, $results);
            }
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
        
        $siteurl = w3_get_site_url();
        $upload_dir = $this->get_upload_dir();
        $upload_url = $this->get_upload_url();
        
        if ($upload_dir && $upload_url) {
            $sql = sprintf('SELECT
        		ID,
        		post_content,
        		post_date
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
                $regexp = $this->get_regexp_by_mask($this->_config->get_string('cdn.import.files'));
                $import_external = $this->_config->get_boolean('cdn.import.external');
                
                foreach ($posts as $post) {
                    $matches = null;
                    $post_content = $post->post_content;
                    
                    if (preg_match_all('~(href|src)=[\'"]?([^\'"<>\s]+)[\'"]?~', $post_content, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $src = $match[2];
                            
                            if (preg_match('~(' . $regexp . ')$~', $src)) {
                                $file = ltrim(str_replace($siteurl, '', $src), '\\/');
                                $file_dir = date('Y/m', strtotime($post->post_date));
                                $file_base = basename($file);
                                $dst = sprintf('%s/%s/%s', $upload_dir, $file_dir, $file_base);
                                $dst_dir = dirname($dst);
                                $dst_path = ABSPATH . $dst;
                                $dst_url = sprintf('%s/%s/%s/%s', $siteurl, $upload_url, $file_dir, $file_base);
                                $result = false;
                                $error = '';
                                $download_result = null;
                                
                                w3_mkdir($dst_dir, 0755, ABSPATH);
                                
                                if (! file_exists($dst_path)) {
                                    if (w3_is_url($file)) {
                                        if ($import_external) {
                                            $download_result = $this->download($file, $dst_path);
                                        } else {
                                            $error = 'External file import is disabled';
                                        }
                                    } elseif (strstr($src, $upload_url) === false) {
                                        $file_path = ABSPATH . $file;
                                        $download_result = @copy($file_path, $dst_path);
                                    } else {
                                        $error = 'Source file already exists';
                                    }
                                    
                                    if ($download_result !== null) {
                                        if ($download_result) {
                                            $title = $file_base;
                                            $guid = $upload_url . '/' . $title;
                                            $mime_type = $this->get_mime_type($file_base);
                                            
                                            $GLOBALS['wp_rewrite'] = & new WP_Rewrite();
                                            
                                            $id = wp_insert_attachment(array(
                                                'post_mime_type' => $mime_type, 
                                                'guid' => $guid, 
                                                'post_title' => $title, 
                                                'post_content' => ''
                                            ), $dst_path);
                                            
                                            if (! is_wp_error($id)) {
                                                require_once ABSPATH . 'wp-admin/includes/image.php';
                                                wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $dst_path));
                                                
                                                $post_content = str_replace($src, $dst_url, $post_content);
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
                                
                                $results[] = array(
                                    'src' => $src, 
                                    'dst' => $dst, 
                                    'result' => $result, 
                                    'error' => $error
                                );
                            }
                        }
                    }
                    
                    if ($post_content != $post->post_content) {
                        wp_update_post(array(
                            'ID' => $post->ID, 
                            'post_content' => $post_content
                        ));
                    }
                }
            }
        }
    }
    
    /**
     * Rename domain
     * @param $names
     * @param $limit
     * @param $offset
     * @param $count
     * @param $total
     * @param $results
     * @return void
     */
    function rename_domain($names, $limit = null, $offset = null, &$count = null, &$total = null, &$results = array())
    {
        global $wpdb;
        
        $count = 0;
        $total = 0;
        $results = array();
        
        $siteurl = w3_get_site_url();
        $upload_url = $this->get_upload_url();
        
        foreach ($names as $index => $name) {
            $names[$index] = str_ireplace('www.', '', $name);
        }
        
        if ($upload_url) {
            $sql = sprintf('SELECT
        		ID,
        		post_content,
        		post_date
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
                $total = $this->get_rename_posts_count();
                $names_quoted = array_map('preg_quote', $names);
                
                foreach ($posts as $post) {
                    $matches = null;
                    $post_content = $post->post_content;
                    $regexp = '~(href|src)=[\'"]?(https?://(www\.)?(' . implode('|', $names_quoted) . ')/' . preg_quote($upload_url) . '([^\'"<>\s]+))[\'"]~';
                    
                    if (preg_match_all($regexp, $post_content, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $old_url = $match[2];
                            $new_url = sprintf('%s/%s%s', $siteurl, $upload_url, $match[5]);
                            $post_content = str_replace($old_url, $new_url, $post_content);
                            
                            $results[] = array(
                                'old' => $old_url, 
                                'new' => $new_url, 
                                'result' => true, 
                                'error' => 'OK'
                            );
                        }
                    }
                    
                    if ($post_content != $post->post_content) {
                        wp_update_post(array(
                            'ID' => $post->ID, 
                            'post_content' => $post_content
                        ));
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
     * Returns rename posts count
     *
     * @return integer
     */
    function get_rename_posts_count()
    {
        return $this->get_import_posts_count();
    }
    
    /**
     * Exports includes to CDN
     */
    function get_files_includes()
    {
        $files = $this->search_files(ABSPATH . WPINC, w3_get_site_path() . WPINC, $this->_config->get_string('cdn.includes.files'));
        
        return $files;
    }
    
    /**
     * Exports theme to CDN
     */
    function get_files_theme()
    {
        $theme_dir = ltrim(str_replace(ABSPATH, '', get_stylesheet_directory()), '/');
        $files = $this->search_files(get_stylesheet_directory(), w3_get_site_path() . $theme_dir, $this->_config->get_string('cdn.theme.files'));
        
        return $files;
    }
    
    /**
     * Exports min files to CDN
     */
    function get_files_minify()
    {
        require_once W3TC_LIB_W3_DIR . '/Plugin/Minify.php';
        $minify = W3_Plugin_Minify::instance();
        $urls = $minify->get_urls();
        
        $files = array();
        $site_path = w3_get_site_path();
        
        foreach ($urls as $url) {
            $file = basename($url);
            if ($this->download($url, W3TC_CONTENT_DIR . '/' . $file)) {
                $files[] = $site_path . W3TC_CONTENT_DIR_NAME . '/' . $file;
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
        if (($data = w3_url_get($url)) && ($fp = @fopen($file, 'w'))) {
            @fputs($fp, $data);
            @fclose($fp);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Formats local file path
     *
     * @param string $file
     * @return string
     */
    function format_local_path($file)
    {
        $site_path = w3_get_site_path();
        $abspath = ($site_path ? substr(ABSPATH, 0, - strlen($site_path)) : ABSPATH);
        return $abspath . $file;
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
        static $queue = null, $domain = null;
        
        if (in_array($matches[2], $this->replaced_urls)) {
            return $matches[0];
        }
        
        if ($queue === null) {
            $sql = sprintf('SELECT remote_path FROM %s', $wpdb->prefix . W3TC_CDN_TABLE_QUEUE);
            $queue = $wpdb->get_col($sql);
        }
        
        if (in_array(ltrim($matches[4], '/'), $queue)) {
            return $matches[0];
        }
        
        if ($domain === null) {
            $domain = $this->_config->get_string('cdn.domain');
        }
        
        if (empty($domain)) {
            return $matches[0];
        }
        
        $this->replaced_urls[] = $matches[2];
        
        $replacement = sprintf('%shttp://%s/%s%s', $matches[1], $domain, w3_get_site_path(), $matches[4]);
        
        return $replacement;
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
        static $stack = array();
        
        $files = array();
        $dir = @opendir($search_dir);
        
        if ($dir) {
            while (($entry = @readdir($dir))) {
                if ($entry != '.' && $entry != '..') {
                    $path = $search_dir . '/' . $entry;
                    if (is_dir($path) && $recursive) {
                        array_push($stack, $entry);
                        $files = array_merge($files, $this->search_files($path, $base_dir, $mask, $recursive));
                        array_pop($stack);
                    } else {
                        $regexp = '~^' . $this->get_regexp_by_mask($mask) . '$~i';
                        if (preg_match($regexp, $entry)) {
                            $files[] = $base_dir . '/' . (($p = implode('/', $stack)) != '' ? $p . '/' : '') . $entry;
                        }
                    }
                }
            }
            @closedir($dir);
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
            '[^\s"\'>]*', 
            '[^\s"\'>]', 
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
     * Returns upload dir
     * 
     * @return string
     */
    function get_upload_dir()
    {
        static $upload_dir = null;
        
        if ($upload_dir === null) {
            $upload_info = w3_upload_info();
            if ($upload_info) {
                $upload_dir = ltrim(str_replace(ABSPATH, '', $upload_info['basedir']), '\\/');
            } else {
                $upload_dir = false;
            }
        }
        
        return $upload_dir;
    }
    
    /**
     * Returns upload dir
     * 
     * @return string
     */
    function get_upload_url()
    {
        static $upload_dir = null;
        
        if ($upload_dir === null) {
            $upload_info = w3_upload_info();
            if ($upload_info) {
                $siteurl = w3_get_site_url();
                $upload_dir = ltrim(str_replace($siteurl, '', $upload_info['baseurl']), '\\/');
            } else {
                $upload_dir = false;
            }
        }
        
        return $upload_dir;
    }
    
    /**
     * Normalizes attachment file
     *
     * @param string $file
     * @return string
     */
    function normalize_attachment_file($file)
    {
        $upload_info = w3_upload_info();
        if ($upload_info) {
            $file = ltrim(str_replace($upload_info['basedir'], '', $file), '\\/');
        }
        
        return $file;
    }
    
    /**
     * Returns CDN object
     *
     * @return W3_Cdn_Base
     */
    function &get_cdn()
    {
        static $cdn = null;
        
        if ($cdn === null) {
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
            
            require_once W3TC_LIB_W3_DIR . '/Cdn.php';
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
    
    /**
     * Check if we can do CDN logic
     * @return boolean
     */
    function can_cdn()
    {
        /**
         * Skip if CDN is disabled
         */
        if (! $this->_config->get_boolean('cdn.enabled')) {
            return false;
        }
        
        /**
         * Skip if admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }
        
        /**
         * Skip if doint AJAX
         */
        if (defined('DOING_AJAX')) {
            return false;
        }
        
        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            return false;
        }
        
        /**
         * Skip if APP request
         */
        if (defined('APP_REQUEST')) {
            return false;
        }
        
        /**
         * Skip if XMLRPC request
         */
        if (defined('XMLRPC_REQUEST')) {
            return false;
        }
        
        /**
         * Check User agent
         */
        if (! $this->check_ua()) {
            return false;
        }
        
        /**
         * Check request URI
         */
        if (! $this->check_request_uri()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks User Agent
     *
     * @return boolean
     */
    function check_ua()
    {
        foreach ($this->_config->get_array('cdn.reject.ua') as $ua) {
            if (stristr($_SERVER['HTTP_USER_AGENT'], $ua) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks request URI
     *
     * @return boolean
     */
    function check_request_uri()
    {
        $auto_reject_uri = array(
            'wp-login', 
            'wp-register'
        );
        
        foreach ($auto_reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        foreach ($this->_config->get_array('cdn.reject.uri') as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
}
