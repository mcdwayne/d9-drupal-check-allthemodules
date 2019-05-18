<?php

namespace Drupal\be_ixf_drupal\Factory;

/* Can comment out if you have modified merge-plugin in the top level composer.json */
include __DIR__ . '../../../vendor/autoload.php';

use GuzzleHttp\ClientInterface;
use BrightEdge\BEIXFClient;
use BrightEdge\BEIXFClientInterface;

class BrightEdgeIXFPHPClient {
  // subscriber and block instaniate different instances from factory so we use static array here
  protected static $sdk_client_array = array();
  protected $sdk_config;

  public function __construct($sdk_config) {
    $this->sdk_config = $sdk_config;
  } 

  public function getClient() {
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if (array_key_exists($url, self::$sdk_client_array)) {
      $be_ixf_client = self::$sdk_client_array[$url];
//      echo "SYU self=" . spl_object_hash($this) . ", pid=" . getmypid() . ", date=" . date(DATE_RFC2822) . ", url=$url, returning same object=" . spl_object_hash($be_ixf_client) . "<BR>\n";
    } else {
      // keep only 1 for now
//      echo "SYU url=$url, old key size=" . count($this->sdk_client_array) . "<BR>\n";
      self::$sdk_client_array = array();
      $be_ixf_client = new BEIXFClient($this->sdk_config);
      self::$sdk_client_array[$url] = $be_ixf_client;
//      echo "SYU self=" . spl_object_hash($this) . ", pid=" . getmypid() . ", date=" . date(DATE_RFC2822) . ", url=$url, returning new object=" . spl_object_hash($be_ixf_client) . "<BR>\n";
    }
//    echo "SYU capsule_URL " . $be_ixf_client->getCapsuleAPIURL() . ", pid=" . getmypid() . ", date=" . date(DATE_RFC2822) . "<BR>\n";
    return $be_ixf_client;
  }
}

class BrightEdgeFactory {

  public static function createRequest($config, ClientInterface $client) {
    $be_config = $config->get('be_ixf_drupal.settings');

    $be_ixf_config = array(
        BEIXFClient::$CAPSULE_MODE_CONFIG => $be_config->get('capsule_mode'),
        BEIXFClient::$ACCOUNT_ID_CONFIG => $be_config->get('account_id'),
        BEIXFClient::$API_ENDPOINT_CONFIG => $be_config->get('api_endpoint')
    );

    $be_ixf_config['defer.redirect'] = "true";
    return new BrightEdgeIXFPHPClient($be_ixf_config);
  }

}

