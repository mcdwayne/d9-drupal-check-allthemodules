<?php

namespace Drupal\json_scanner_block\BaseClass;

use Drupal\json_scanner_block\BaseClass\JsonScannerInterface;

/**
 * JsonScannerCurlGet allows simple data retrieval via cURL.
 *
 * JsonScannerCurlGet provides a method of retrieving data. It
 * implements the JsonScannerInterface interface and currently has one method,
 * just to get json data from the api url  
 *
 * @author  Swapnil Srivastava <swapnilsrivastava66@gmail.com>
 * @version 1.0.0 
 */
class JsonScannerCurlGet implements JsonScannerInterface {

    public function getJSON($url) {
        $result = false;
        $ch = curl_init();
        if ($ch) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl = curl_exec($ch);
            if ($curl) {
                $result = $curl;
            }
            curl_close($ch);
        }
        return $result;
    }

}
