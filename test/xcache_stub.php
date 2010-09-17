<?php

if (! function_exists('xcache_set')) {
    function xcache_set($key, $var, $expire = 0)
    {
        return true;
    }
}

if (! function_exists('xcache_get')) {
    function xcache_get($key)
    {
        return false;
    }
}

if (! function_exists('xcache_unset')) {
    function xcache_unset($key)
    {
        return true;
    }
}

if (! function_exists('xcache_clear_cache')) {
    function xcache_clear_cache($type = 0, $id = 0)
    {
    }
}
