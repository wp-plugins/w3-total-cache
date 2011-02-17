<?php

/**
 * W3 CDN LimeLight Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

/**
 * Class W3_Cdn_Mirror_LimeLight
 */
class W3_Cdn_Mirror_LimeLight extends W3_Cdn_Mirror {
    /**
     * PHP5 Constructor
     *
     * @param array $config
     */
    function __construct($config = array()) {
        $config = array_merge(array(
        ), $config);

        parent::__construct($config);
    }

    /**
     * PHP4 Constructor
     *
     * @param array $config
     */
    function W3_Cdn_Mirror_LimeLight($config = array()) {
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

        $results = array();

        foreach ($files as $local_path => $remote_path) {
            $url = $this->format_url($remote_path);

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
}
