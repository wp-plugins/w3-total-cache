<?php

/**
 * W3 Request object
 */

/**
 * Class W3_Request
 */
class W3_Request
{
    /**
     * Returns request value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get($key, $default = null)
    {
        if (isset($_REQUEST[$key])) {
            $value = $_REQUEST[$key];
            
            if (is_string($value) && get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Returns string value
     *
     * @param string $key
     * @param string $default
     * @param boolean $trim
     * @return string
     */
    function get_string($key, $default = 0, $trim = true)
    {
        $value = (string) W3_Request::get($key, $default);
        
        return ($trim) ? trim($value) : $value;
    }
    
    /**
     * Returns integer value
     *
     * @param string $key
     * @param integer $default
     * @return integer
     */
    function get_integer($key, $default = 0)
    {
        return (integer) W3_Request::get($key, $default);
    }
    
    /**
     * Returns double value
     *
     * @param string $key
     * @param double $default
     * @return double
     */
    function get_double($key, $default = 0.)
    {
        return (double) W3_Request::get($key, $default);
    }
    
    /**
     * Returns boolean value
     *
     * @param string $key
     * @param boolean $default
     * @return boolean
     */
    function get_boolean($key, $default = false)
    {
        $value = W3_Request::get($key);
        
        if (is_string($value)) {
            switch (strtolower($value)) {
                case '+':
                case '1':
                case 'y':
                case 'on':
                case 'yes':
                case 'true':
                case 'enabled':
                    
                    return true;
                case '-':
                case '0':
                case 'n':
                case 'no':
                case 'off':
                case 'false':
                case 'disabled':
                    return false;
            }
        }
        
        return $default;
    }
    
    /**
     * Returns array value
     *
     * @param string $key
     * @param array $default
     * @return array
     */
    function get_array($key, $default = array())
    {
        $value = W3_Request::get($key);
        
        if (is_array($value)) {
            return $value;
        } elseif ($value != '') {
            return preg_split("/[\r\n,;]+/", trim($value));
        }
        
        return $default;
    }
}
