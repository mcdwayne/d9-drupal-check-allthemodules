<?php

namespace Drupal\json_scanner_block\BaseClass;

use Drupal\json_scanner_block\BaseClass\JsonScannerInterface;

/**
 * JsonScannerFileGet allows simple data retrieval via file_get_contents.
 *
 * JsonScannerFileGet provides a method of retrieving data. It
 * implements the JsonScannerInterface interface and currently has one method,
 * just to get json data from the api url  

 * @author  Swapnil Srivastava <swapnilsrivastava66@gmail.com>
 * @version 1.0.0 
 */
class JsonScannerFileGet implements JsonScannerInterface {

    public function getJSON($url) {
        $result = false;
        if (file_get_contents($url)) {
            $result = file_get_contents($url);
        }
        return $result;
    }

}
