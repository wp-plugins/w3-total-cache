<?php

/**
 * W3 Database object
 */

/**
 * @global w3db $wpdb
 */
$wpdb = false;

/**
 * Require default WordPress database object
 */
require_once ABSPATH . 'wp-includes/wp-db.php';

/**
 * Class W3_Db
 */
class W3_Db extends wpdb
{
    /**
     * Array of queries
     *
     * @var array
     */
    var $query_stats = array();
    
    /**
     * Queries total
     *
     * @var integer
     */
    var $query_total = 0;
    
    /**
     * Query cache hits
     *
     * @var integer
     */
    var $query_hits = 0;
    
    /**
     * Query cache misses
     *
     * @var integer
     */
    var $query_misses = 0;
    
    /**
     * Time total
     *
     * @var integer
     */
    var $time_total = 0;
    
    /**
     * Config
     *
     * @var W3_Config
     */
    var $_config = null;
    
    /**
     * PHP5 constructor
     *
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbname
     * @param string $dbhost
     */
    function __construct($dbuser, $dbpassword, $dbname, $dbhost)
    {
        require_once dirname(__FILE__) . '/Config.php';
        $this->_config = W3_Config::instance();
        
        parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);
    }
    
    /**
     * PHP4 constructor
     *
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbname
     * @param string $dbhost
     */
    function W3_Db($dbuser, $dbpassword, $dbname, $dbhost)
    {
        $this->__construct($dbuser, $dbpassword, $dbname, $dbhost);
    }
    
    /**
     * Executes query
     *
     * @param string $query
     * @return integer
     */
    function query($query)
    {
        if (! $this->ready) {
            return false;
        }
        
        ++ $this->query_total;
        
        // filter the query, if filters are available
        // NOTE: some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
        if (function_exists('apply_filters')) {
            $query = apply_filters('query', $query);
        }
        
        // initialise return
        $return_val = 0;
        $this->flush();
        
        // Log how the function was called
        $this->func_call = "\$db->query(\"$query\")";
        
        // Keep track of the last query for debug..
        $this->last_query = $query;
        
        $caching = $this->can_cache($query);
        $cached = false;
        $data = false;
        $time_total = 0;
        
        if ($caching) {
            $cache_key = $this->_get_cache_key($query);
            
            $this->timer_start();
            $cache = $this->_get_cache();
            $data = $cache->get($cache_key);
            $time_total = $this->timer_stop();
        }
        
        /**
         * Check if query was cached
         */
        if (is_array($data)) {
            ++ $this->query_hits;
            $cached = true;
            
            /**
             * Set result from the cache
             */
            $this->last_error = $data['last_error'];
            $this->last_query = $data['last_query'];
            $this->last_result = $data['last_result'];
            $this->col_info = $data['col_info'];
            $this->num_rows = $data['num_rows'];
        } else {
            ++ $this->num_queries;
            ++ $this->query_misses;
            
            // Perform the query via std mysql_query function..
            $this->timer_start();
            $this->result = @mysql_query($query, $this->dbh);
            $time_total = $this->timer_stop();
            
            if (defined('SAVEQUERIES') && SAVEQUERIES) {
                $this->queries[] = array(
                    $query, 
                    $time_total, 
                    $this->get_caller()
                );
            }
            
            // If there is an error then take note of it..
            if (($this->last_error = mysql_error($this->dbh))) {
                $this->print_error();
                return false;
            }
            
            if (preg_match("/^\\s*(insert|delete|update|replace|alter) /i", $query)) {
                $this->rows_affected = mysql_affected_rows($this->dbh);
                // Take note of the insert_id
                if (preg_match("/^\\s*(insert|replace) /i", $query)) {
                    $this->insert_id = mysql_insert_id($this->dbh);
                }
                // Return number of rows affected
                $return_val = $this->rows_affected;
            } else {
                $i = 0;
                while ($i < @mysql_num_fields($this->result)) {
                    $this->col_info[$i] = @mysql_fetch_field($this->result);
                    $i ++;
                }
                
                $num_rows = 0;
                while (($row = @mysql_fetch_object($this->result))) {
                    $this->last_result[$num_rows] = $row;
                    $num_rows ++;
                }
                
                @mysql_free_result($this->result);
                
                // Log number of rows the query returned
                $this->num_rows = $num_rows;
                
                // Return number of rows selected
                $return_val = $this->num_rows;
                
                if ($caching) {
                    /**
                     * Store result to the cache
                     */
                    $data = array(
                        'last_error' => $this->last_error, 
                        'last_query' => $this->last_query, 
                        'last_result' => $this->last_result, 
                        'col_info' => $this->col_info, 
                        'num_rows' => $this->num_rows
                    );
                    
                    $cache = $this->_get_cache();
                    $cache->set($cache_key, $data, $this->_get_lifetime($query));
                }
            }
        }
        
        if ($this->_config->get_boolean('dbcache.debug')) {
            $this->query_stats[] = array(
                'query' => $query, 
                'caching' => $caching, 
                'cached' => $cached, 
                'time_total' => $time_total
            );
        }
        
        $this->time_total += $time_total;
        
        return $return_val;
    }
    
    /**
     * Check if can cache sql
     *
     * @param string $sql
     * @return boolean
     */
    function can_cache($sql)
    {
        /**
         * Skip if disabled
         */
        if (! $this->_config->get_boolean('dbcache.enabled')) {
            return false;
        }
        
        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            return false;
        }
        
        /**
         * Skip if request URI is rejected
         */
        if (! $this->_check_request_uri()) {
            return false;
        }
        
        /**
         * Skip if SQL is rejected
         */
        if (! $this->_check_sql($sql)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Flushes cache
     *
     * @return boolean
     */
    function flush_cache()
    {
        $cache = $this->_get_cache();
        
        return $cache->flush();
    }
    
    /**
     * Returns onject instance
     *
     * @return W3_Db
     */
    function &instance()
    {
        static $instance = null;
        
        if (! $instance) {
            $class = __CLASS__;
            $instance = & new $class(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        }
        
        return $instance;
    }
    
    /**
     * Returns debug info
     * 
     * @return string
     */
    function get_debug_info()
    {
        $debug_info = "<!-- W3 Total Cache: Db cache debug info:\r\n";
        $debug_info .= sprintf("%s%s\r\n", str_pad('Engine: ', 20), $this->_config->get_string('dbcache.engine'));
        $debug_info .= sprintf("%s%d\r\n", str_pad('Total queries: ', 20), $this->query_total);
        $debug_info .= sprintf("%s%d\r\n", str_pad('Cached queries: ', 20), $this->query_hits);
        $debug_info .= sprintf("%s%.3f\r\n", str_pad('Total query time: ', 20), $this->time_total);
        
        if (count($this->query_stats)) {
            $debug_info .= "SQL info:\r\n";
            $debug_info .= sprintf("%s | %s | %s | % s | %s\r\n", str_pad('#', 5, ' ', STR_PAD_LEFT), str_pad('Time (s)', 8, ' ', STR_PAD_LEFT), str_pad('Caching', 10, ' ', STR_PAD_BOTH), str_pad('Status', 10, ' ', STR_PAD_BOTH), 'Query');
            foreach ($this->query_stats as $index => $query) {
                $debug_info .= sprintf("%s | %s | %s | %s | %s\r\n", str_pad($index + 1, 5, ' ', STR_PAD_LEFT), str_pad(round($query['time_total'], 3), 8, ' ', STR_PAD_LEFT), str_pad(($query['caching'] ? 'enabled' : 'disabled'), 10, ' ', STR_PAD_BOTH), str_pad(($query['cached'] ? 'Cached' : 'Not cached'), 10, ' ', STR_PAD_BOTH), str_replace('-->', '-- >', trim($query['query'])));
            }
        }
        
        $debug_info .= '-->';
        
        return $debug_info;
    }
    
    /**
     * Returns cache object
     *
     * @return W3_Cache_Base
     */
    function &_get_cache()
    {
        static $cache = null;
        
        if (! $cache) {
            $engine = $this->_config->get_string('dbcache.engine', 'memcached');
            if ($engine == 'memcached') {
                $engineConfig = array(
                    'engine' => $this->_config->get_string('dbcache.memcached.engine', 'auto'), 
                    'servers' => $this->_config->get_array('dbcache.memcached.servers')
                );
            } else {
                $engineConfig = array();
            }
            
            require_once dirname(__FILE__) . '/Cache.php';
            $cache = & W3_Cache::instance($engine, $engineConfig);
        }
        
        return $cache;
    }
    
    /**
     * Check SQL
     *
     * @param string $sql
     * @return boolean
     */
    function _check_sql($sql)
    {
        $auto_reject_strings = array(
            'insert', 
            'delete', 
            'update', 
            'replace', 
            'alter', 
            'set names', 
            'found_rows', 
            $this->prefix . 'posts', 
            $this->prefix . 'postsmeta'
        );
        
        if (preg_match('@(' . implode('|', $auto_reject_strings) . ')@i', $sql)) {
            return false;
        }
        
        $reject_sql = $this->_config->get_array('dbcache.reject.sql');
        
        foreach ($reject_sql as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $sql)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check request URI
     *
     * @return boolean
     */
    function _check_request_uri()
    {
        $auto_reject_uri = array(
            'wp-admin', 
            'wp-login', 
            'wp-register', 
            'wp-signup'
        );
        
        foreach ($auto_reject_uri as $uri) {
            if (strstr($_SERVER['REQUEST_URI'], $uri) !== false) {
                return false;
            }
        }
        
        $reject_uri = $this->_config->get_array('dbcache.reject.uri');
        
        foreach ($reject_uri as $expr) {
            $expr = trim($expr);
            if ($expr != '' && preg_match('@' . $expr . '@i', $_SERVER['REQUEST_URI'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Returns cache key
     *
     * @param string $sql
     * @return string
     */
    function _get_cache_key($sql)
    {
        return sprintf('w3tc_%s_sql_%s', md5($_SERVER['HTTP_HOST']), md5($sql));
    }
    
    /**
     * Returns lifetime for SQL
     *
     * @param string $sql
     * @return integer
     */
    function _get_lifetime($sql)
    {
        $config = array(
            '__default' => $this->_config->get_integer('dbcache.lifetime.default', 180), 
            '_options' => $this->_config->get_integer('dbcache.lifetime.options', 180), 
            '_links' => $this->_config->get_integer('dbcache.lifetime.links', 10800), 
            '_terms' => $this->_config->get_integer('dbcache.lifetime.terms', 10800), 
            '_user' => $this->_config->get_integer('dbcache.lifetime.user', 1800), 
            '_post' => $this->_config->get_integer('dbcache.lifetime.post', 3600)
        );
        
        $lifetime = $config['__default'];
        
        foreach ($config as $string => $ttl) {
            if (strstr($sql, $string) !== false) {
                $lifetime = $ttl;
                break;
            }
        }
        
        return $lifetime;
    }
}
