<?php

/**
 * W3 Total Cache CDN Plugin
 */
if (!defined('W3TC')) {
    die();
}

require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_Cdn
 */
class W3_Plugin_Cdn extends W3_Plugin {
    /**
     * Run plugin
     */
    function run() {
        register_activation_hook(W3TC_FILE, array(
            &$this,
            'activate'
        ));

        register_deactivation_hook(W3TC_FILE, array(
            &$this,
            'deactivate'
        ));

        if ($this->_config->get_boolean('cdn.enabled')) {
            $this->get_worker()->run();
        }
    }

    /**
     * Instantiates worker on demand
     *
     * @return W3_Plugin_CdnEnabled
     */
    function &get_worker() {
        return w3_instance('/Plugin/CdnEnabled.php');
    }

    /**
     * Activation action
     */
    function activate() {
        $this->get_worker()->activate();
    }

    /**
     * Deactivation action
     */
    function deactivate() {
        $this->get_worker()->deactivate();
    }
}
