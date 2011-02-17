<?php

/**
 * W3 CDN Netdna Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

/**
 * Class W3_Cdn_Mirror_Netdna
 */
class W3_Cdn_Mirror_Netdna extends W3_Cdn_Mirror {
    /**
     * PHP5 Constructor
     *
     * @param array $config
     */
    function __construct($config = array()) {
        $config = array_merge(array(
            'apiid' => '',
            'apikey' => ''
        ), $config);

        parent::__construct($config);
    }

    /**
     * PHP4 Constructor
     *
     * @param array $config
     */
    function W3_Cdn_Mirror_Netdna($config = array()) {
        $this->__construct($config);
    }

    /**
     * Purges remote files
     *
     * @param array $files
     * @param array $results
     * @return void
     */
    function purge($files, &$results) {
        if (empty($this->_config['apiid'])) {
            $results = $this->_get_results($files, W3TC_CDN_RESULT_HALT, 'Empty API ID.');

            return;
        }

        if (empty($this->_config['apikey'])) {
            $results = $this->_get_results($files, W3TC_CDN_RESULT_HALT, 'Empty API key.');

            return;
        }

        if ($this->_sha256('test') === false) {
            $results = $this->_get_results($files, W3TC_CDN_RESULT_HALT, "hash() or mhash() function doesn't exists.");

            return;
        }

        if (!class_exists('IXR_Client')) {
            require_once (ABSPATH . WPINC . '/class-IXR.php');
        }

        $date = date('c');
        $method = 'purge';
        $auth_string = sprintf('%s:%s:%s', $date, $this->_config['apikey'], $method);
        $auth_key = $this->_sha256($auth_string);

        $client = new IXR_Client('http://api.netdna.com/xmlrpc/cache');
        $client->timeout = 30;

        $results = array();

        foreach ($files as $local_path => $remote_path) {
            $url = $this->format_url($remote_path);

            $client->query('cache.' . $method, $this->_config['apiid'], $auth_key, $date, $url);

            if (!$client->isError()) {
                $val = $client->getResponse();

                if ($val) {
                    $results[] = $this->_get_result($local_path, $remote_path, W3TC_CDN_RESULT_OK, 'OK');
                } else {
                    $results[] = $this->_get_result($local_path, $remote_path, W3TC_CDN_RESULT_ERROR, 'Unexpected Error.');
                }
            } else {
                $results[] = $this->_get_result($local_path, $remote_path, W3TC_CDN_RESULT_ERROR, $client->getErrorMessage());
            }
        }
    }

    /**
     * Returns SHA 256 hash of string
     *
     * @param string $string
     * @return string
     */
    function _sha256($string) {
        if (function_exists('hash')) {
            return hash('sha256', $string);
        } elseif (function_exists('mhash')) {
            return bin2hex(mhash(MHASH_SHA256, $string));
        }

        return false;
    }
}
