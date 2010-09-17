<?php

if (! function_exists('apc_store')) {
    function apc_store($key, $var, $expire = 0)
    {
        return true;
    }
}

if (! function_exists('apc_fetch')) {
    function apc_fetch($key)
    {
        return false;
    }
}

if (! function_exists('apc_delete')) {
    function apc_delete($key)
    {
        return true;
    }
}

if (! function_exists('apc_clear_cache')) {
    function apc_clear_cache($mode = 'user')
    {
        return true;
    }
}
