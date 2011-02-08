<?php

/**
 * W3 CDN Netdna Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

/**
 * Class W3_Cdn_Netdna
 */
class W3_Cdn_Netdna extends W3_Cdn_Base {
    /**
     * Uploads files stub
     *
     * @param array $files
     * @param array $results
     * @param boolean $force_rewrite
     * @return void
     */
    function upload($files, &$results, $force_rewrite = false) {
        $results = $this->get_results($files, W3TC_CDN_RESULT_OK, 'OK');
    }

    /**
     * Deletes files stub
     *
     * @param array $files
     * @param array $results
     * @return void
     */
    function delete($files, &$results) {
        $results = $this->get_results($files, W3TC_CDN_RESULT_OK, 'OK');
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
            $results = $this->get_results($files, W3TC_CDN_RESULT_HALT, 'Empty API ID.');
            return;
        }

        if (empty($this->_config['apikey'])) {
            $results = $this->get_results($files, W3TC_CDN_RESULT_HALT, 'Empty API key.');
            return;
        }

        if ($this->sha256('test') === false) {
            $results = $this->get_results($files, W3TC_CDN_RESULT_HALT, "hash() or mhash() function doesn't exists.");
            return;
        }

        if (!class_exists('IXR_Client')) {
            require_once (ABSPATH . WPINC . '/class-IXR.php');
        }

        $date = date('c');
        $method = 'purge';
        $auth_string = sprintf('%s:%s:%s', $date, $this->_config['apikey'], $method);
        $auth_key = $this->sha256($auth_string);

        $client = new IXR_Client('http://api.netdna.com/xmlrpc/cache');
        $client->timeout = 30;

        $results = array();

        foreach ($files as $local_path => $remote_path) {
            $url = $this->format_url($remote_path);

            $client->query('cache.' . $method, $this->_config['apiid'], $auth_key, $date, $url);

            if (!$client->isError()) {
                $val = $client->getResponse();

                if ($val) {
                    $results[] = $this->get_result($local_path, $remote_path, W3TC_CDN_RESULT_OK, 'OK');
                } else {
                    $results[] = $this->get_result($local_path, $remote_path, W3TC_CDN_RESULT_ERROR, 'Unexpected Error');
                }

            } else {
                $results[] = $this->get_result($local_path, $remote_path, W3TC_CDN_RESULT_ERROR, $client->getErrorMessage());
            }
        }
    }

    /**
     * Returns array of CDN domains
     *
     * @return array
     */
    function get_domains() {
        if (!empty($this->_config['domain'])) {
            return (array) $this->_config['domain'];
        }

        return array();
    }

    /**
     * Returns SHA 256 hash of string
     *
     * @param string $string
     * @return string
     */
    function sha256($string) {
        if (function_exists('hash')) {
            return hash('sha256', $string);
        } elseif (function_exists('mhash')) {
            return bin2hex(mhash(MHASH_SHA256, $string));
        }

        return false;
    }
}
