<?php

namespace Drupal\youtubeapi\YoutubeAPI;

use Drupal\Component\Utility\UrlHelper;
use Drupal\youtubeapi\YoutubeAPIService;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Class API
 * @package Drupal\youtubeapi\YoutubeAPI
 */
abstract class API implements APIInterface {

  // The APIKey.
  private static $apikey;

  // Query Parameters.
  private $parameters = [];

  // Result
  protected $result = NULL;

  // Api URL.
  const url = "https://www.googleapis.com/youtube/v3";

  // Init.
  public function __construct() {
    if (!self::$apikey) {
      $apikey = \Drupal::config(YoutubeAPIService::getConfigName())
        ->get('apikey');
      if ($apikey) {
        self::$apikey = $apikey;
      }
      else {
        throw new Exception("Api Key is Empty");
      }
    }
  }

  /**
   * Add multiple parameters.
   * @param Array $parameters
   */
  public function addQuerys($parameters) {
    foreach ($parameters as $key => $value) {
      $this->addQuery($key, $value);
    }
  }

  /**
   * Add a parameter.
   * @param $key
   * @param $value
   */
  public function addQuery($key, $value) {
    $this->parameters[$key] = $value;
  }

  /**
   * Execute and Get result.
   * @return Array;
   * @throws \Masterminds\HTML5\Exception
   */
  public function execute() {

    // Build URL.
    $url = $this->getUrl();

    // Read Data.
    $http = \Drupal::httpClient();
    try {
      $options = [];
      $result = $http->request('get', $url, $options);
      $this->result = \GuzzleHttp\json_decode($result->getBody(), TRUE);
    }
    catch (Exception $e) {
      $this->result = NULL;
    }

    return $this->result;
  }

  /**
   * @inheritdoc
   */
  public static function getMethod() {

    //$class = get_class($this);
    return constant(static::class . '::method');
  }


  /**
   * @inheritdoc
   */
  public function getUrl() {
    $url = self::url . "/" . $this->getMethod();
    $qurey = [];
    $param_all = $this->getParameters();
    $param_mand = $this->getMandatoryParameters();

    //Cleanup
    foreach ($this->parameters as $key => $value) {
      if (isset($param_all[$key])) {
        $qurey[$key] = $value;
      }
    }

    //Check Mandatory
    foreach ($param_mand as $key => $value) {
      if (!isset($qurey[$key])) {
        //Search default value
        if (!empty($param_mand[$key]['default'])) {
          $defaults = $param_mand[$key]['default'];
          foreach ($defaults as $def_val) {
            if (isset($qurey[$key])) {
              $qurey[$key] .= "," . $def_val;
            }
            else {
              $qurey[$key] = $def_val;
            }
          }
        }
        else {
          throw new Exception("Required field '$key' not set.");
        }
      }
    }
    $qurey['key'] = self::$apikey;

    //Build query
    $qurey_str = UrlHelper::buildQuery($qurey);
    $url .= "?" . $qurey_str;

    return $url;
  }

  /**
   * Protected/Private functions.
   * @param String $file .
   * @return Array .
   * @throws \Masterminds\HTML5\Exception
   */
  protected function getConfig() {

    $path = drupal_get_path('module', 'youtubeapi');
    $file_path = $path . "/config/api/" . $this->getMethod() . ".yml";
    $file_data = file_get_contents($file_path);
    if ($file_data) {
      $data = Yaml::parse($file_data);
      return $data;
    }

    throw new Exception("Configuration file not found ($file_path).");
  }


  /**
   * @inheritdoc
   */
  public function getParameters() {
    $config = $this->getConfig();
    //TODO : Checke this parameters->request
    if (isset($config['request'])) {
      return $config['request'];
    }
    //if (isset($config['parameters'])) {
    //  return $config['parameters'];
    //}


    throw new Exception("Bad Configuration, parameters not found.");
  }

  /**
   * @inheritdoc
   */
  public function getMandatoryParameters() {
    $parameters = $this->getParameters();
    $mand = [];
    foreach ($parameters as $key => $param) {
      if (!empty($param['required'])) {
        $mand[$key] = $param;
      }
    }
    return $mand;
  }
}
