<?php

/**
 * W3 Minify plugin
 */
if (!defined('W3TC')) {
    die();
}

require_once W3TC_LIB_W3_DIR . '/Plugin.php';

/**
 * Class W3_Plugin_Minify
 */
class W3_Plugin_Minify extends W3_Plugin {
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

        if ($this->_config->get_boolean('minify.enabled')) {
            $this->get_worker()->run();
        }
    }

    /**
     * Instantiates worker on demand
     *
     * @return W3_Plugin_MinifyEnabled
     */
    function &get_worker() {
        return w3_instance('/Plugin/MinifyEnabled.php');
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
