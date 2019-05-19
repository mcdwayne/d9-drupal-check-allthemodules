<?php

namespace Drupal\szube_api\SzuBeAPI;

use Drupal\szube_api\SzuBeAPIHelper;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Class API
 * @package szube
 */
abstract class API {

  // The APIKey.
  protected static $apikey;
  protected static $apiid;

  // Result
  protected $result = NULL;

  // Api URL.
  const url = NULL;

  // Init.
  public function __construct() {
    if (!self::$apikey) {
      $config = SzuBeAPIHelper::getConfig();
      self::$apikey = $config->get('apikey');
      self::$apiid = $config->get('apiid');


      if (!self::$apikey || !self::$apiid) {
        throw new Exception("Api Key is Empty, Please set the Api key and ID");
      }
    }
  }


  /**
   * Execute and Get result.
   * @return Array;
   * @throws \Masterminds\HTML5\Exception
   */
  public function execute($url) {

    // Read Data.
    $http = \Drupal::httpClient();
    try {
      $options = [];
      $result = $http->request('get', $url, $options);
      $this->result = \GuzzleHttp\json_decode($result->getBody(), TRUE);
    }
    catch (Exception $e) {
      \Drupal::logger("szube_api")->error($e->getMessage());
      $this->result = NULL;
    }

    return $this->result;
  }


  /**
   * @inheritdoc
   */
  public function getUrl() {
    $url = static::url . "?id=" . static::$apiid . "&key=" . static::$apikey . "";
    return $url;
  }

}
