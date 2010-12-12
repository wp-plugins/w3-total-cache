<?php

/**
 * Amazon CloudFront (S3 origin) CDN engine
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Cf.php';

class W3_Cdn_Cf_S3 extends W3_Cdn_Cf {
    var $type = W3TC_CDN_CF_TYPE_S3;
}
