<?php

/**
 * W3 CDN CloudFlare Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

define('W3TC_CDN_CFL_API_URL', 'https://www.cloudflare.com/api_json.html');
define('W3TC_CDN_CFL_HOST_GW_URL', 'https://api.cloudflare.com/host-gw.html');
define('W3TC_CDN_CFL_EXTERNAL_EVENT_URL', 'https://www.cloudflare.com/ajax/external-event.html');

/**
 * Class W3_Cdn_Mirror
 */
class W3_Cdn_Cfl extends W3_Cdn_Base {
    /**
     * Uploads files stub
     *
     * @param array $files
     * @param array $results
     * @param boolean $force_rewrite
     * @return boolean
     */
    function upload($files, &$results, $force_rewrite = false) {
        $results = $this->get_results($files, W3TC_CDN_RESULT_OK, 'OK');

        return count($files);
    }

    /**
     * Deletes files stub
     *
     * @param array $files
     * @param array $results
     * @return boolean
     */
    function delete($files, &$results) {
        $results = $this->get_results($files, W3TC_CDN_RESULT_OK, 'OK');

        return count($files);
    }

    /**
     * Returns array of CDN domains
     *
     * @return array
     */
    function get_domains() {
        return array(w3_get_host());
    }

    /**
     * Makes API request
     *
     * @param string $action
     * @param string $value
     * @return array
     */
    function api_request($action, $value) {
        $url = sprintf('%s?email=%s&tkn=%s&z=%s&a=%s&v=%s', W3TC_CDN_CFL_API_URL, urlencode($this->_config['email']), urlencode($this->_config['key']), urlencode(w3_get_host()), urlencode($action), urlencode($value));
        $response = w3_http_get($url, '', false);

        if ($response) {
            return json_decode($response);
        }

        return false;
    }

    /**
     * User create API request
     *
     * @param string $email
     * @param string $password
     * @param string $username
     * @return array
     */
    function user_create($email, $password, $username) {
        $host_key = $this->_get_host_key();

        $url = sprintf('%s?act=user_create&host_key=%s&cloudflare_email=%s&cloudflare_pass=%s&cloudflare_username', W3TC_CDN_CFL_HOST_GW_URL, urlencode($host_key), urlencode($email), urlencode($password), urlencode($username));
        $response = w3_http_get($url, '', false);

        if ($response) {
            return json_decode($response);
        }

        return false;
    }

    /**
     * Zone set API request
     *
     * @param string $user_key
     * @param string $zone_name
     * @param string $resolve_to
     * @param string $subdomains
     * @return array
     */
    function zone_set($user_key, $zone_name, $resolve_to, $subdomains = '') {
        $host_key = $this->_get_host_key();

        $url = sprintf('%s?act=zone_set&host_key=%s&user_key=%s&zone_name=%s&resolve_to=%s&subdomains=%s', W3TC_CDN_CFL_HOST_GW_URL, urlencode($host_key), urlencode($user_key), urlencode($zone_name), urlencode($resolve_to), urlencode($subdomains));
        $response = w3_http_get($url, '', false);

        if ($response) {
            return json_decode($response);
        }

        return false;
    }

    /**
     * Returns host key
     *
     * @return string
     */
    function _get_host_key() {
        return '8afbe6dea02407989af4dd4c97bb6e25';
    }

    /**
     * Makes external event request
     *
     * @param string $type
     * @param string $value
     * @return array
     */
    function external_event($type, $value) {
        $url = sprintf('%s?u=%s&tkn=%s&evnt_t=%s&evnt_v=%s', W3TC_CDN_CFL_EXTERNAL_EVENT_URL, urlencode($this->_config['email']), urlencode($this->_config['key']), urlencode($type), urlencode($value));
        $response = w3_http_get($url, '', false);

        if ($response) {
            return json_decode($response);
        }

        return false;
    }
}
