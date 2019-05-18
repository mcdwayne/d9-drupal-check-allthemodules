<?php

namespace Drupal\json_scanner_block\BaseClass;

/**
 * This is a definition only so that in different case getJSON function can
 * be usable, like if url needs to proceed with curl or file_get_contents().
 * 
 * @author  Swapnil Srivastava <swapnilsrivastava66@gmail.com>
 * @version 1.0.0 
 */
interface JsonScannerInterface {
    
    /**
     * Define function getJSON which can be usable in different condition.
     * 
     * @param type $url
     */
    function getJSON($url);
}
