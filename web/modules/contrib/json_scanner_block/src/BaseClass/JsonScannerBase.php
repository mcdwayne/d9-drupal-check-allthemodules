<?php

namespace Drupal\json_scanner_block\BaseClass;

use Drupal\json_scanner_block\BaseClass\JsonScannerFileGet;
use Drupal\json_scanner_block\BaseClass\JsonScannerCurlGet;


/**
 * This is the all-purpose Base Class for accessing JSON API data.
 * 
 * @author  Swapnil Srivastava <swapnilsrivastava66@gmail.com>
 * @version 1.0.0 
 */
class JsonScannerBase {
    
    private $conn = false;
    private $url;
    private $connFGC = false;
    private $connCURL = false;
    private $connType;

    /**
     * Simple constructor to test for retrieval method (curl and file_get_contents)
     */
    public function __construct() {
        
        $this->connFGC = ini_get('allow_url_fopen');
        $this->connCURL = extension_loaded('curl');
        if (!$this->connFGC && !$this->connCURL) {
            echo "<h3>dwAPI: NO RETRIEVAL METHOD AVAILABLE:</h3>
    	         <p>file_get_contents() is disabled and cURL is not loaded.</p>";
            exit;
        } else {
            $this->connType = ($this->connCURL) ? 'curl' : 'file';
        }
    }
   
    /**
     * Route all requests through this to get JSON data
     *
     * @return str Returns the JSON string.
     */
    private function returnData() {
        if (!$this->conn) {
            $this->setConn();
        }
        //echo $this->url;
        return $this->conn->getJSON($this->url);
    }

    /**
     * Method to manually set the data retrieval method
     *
     * @param str 	$connectionType 	Set to file|curl 
     */
    public function setConnectionType($connectionType) {
        if (strtolower($connectionType) == 'file' && $this->connFGC){
            $this->connType = 'file';
        }
        if (strtolower($connectionType) == 'curl' && $this->connCURL){
            $this->connType = 'curl';
        }
        $this->setConn();
    }

    /**
     * Method to instatiate the retrieval method
     * Just sets the $conn
     */
    private function setConn() {
        $this->conn = ($this->connType == 'curl') ? new JsonScannerCurlGet : new JsonScannerFileGet;
    }

    /**
     * Method to query the API for json data,
     *
     * @param str $url Required.
     *
     * @return str Returns the JSON string via returnData method.
     */
    public function getApiData($url) {
        $this->url = $url;
        return $this->returnData();
    }

    /**
     * Shortcut method to query the API with a direct url,
     *
     * @param str $url URL string.
     *
     * @return str Returns the JSON string via returnData method.
     */
    public function getList($url) {
        $this->url = $url;
        return $this->returnData();
    }

    /**
     * Returns data as a PHP associative array.
     *
     * @param str $str JSON string.
     *
     * @return mixed[] Returns the associative array.
     */
    public function json2Array($str) {
        return json_decode($str, true);
    }
    
    /**
     * Returns data as a PHP string in key=>value format.
     *
     * @param str $json JSON string.
     *
     * @return str Returns the string.
     */
    public function jsonParser($json) {
        $jsonIterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($this->json2Array($json)), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($jsonIterator as $key => $val) {
            if (is_array($val)) {
                echo "$key:\n";
            } else {
                echo "$key => $val\n";
            }
        }
    }
}
