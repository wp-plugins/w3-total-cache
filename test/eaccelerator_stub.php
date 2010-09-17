<?php

if (! function_exists('eaccelerator_put')) {
    function eaccelerator_put($key, $var, $expire = 0)
    {
        return true;
    }
}

if (! function_exists('eaccelerator_get')) {
    function eaccelerator_get($key)
    {
        return null;
    }
}

if (! function_exists('eaccelerator_rm')) {
    function eaccelerator_rm($key)
    {
        return true;
    }
}

if (! function_exists('eaccelerator_clean')) {
    function eaccelerator_clean()
    {
    }
}

if (! function_exists('eaccelerator_clear')) {
    function eaccelerator_clear()
    {
    }
}
