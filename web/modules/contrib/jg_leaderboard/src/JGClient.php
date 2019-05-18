<?php

namespace Drupal\jg_leaderboard;

/**
 * Class JGClient
 *
 * @package Drupal\jg_leaderboard
 */
class JGClient {
  protected $apiKey;
  protected $apiVersion;
  protected $envirnoment;

  /**
   * JGClient constructor.
   *
   * @param array $client
   */
  function __construct(array $client) {
    $this->envirnoment = $client['envirnoment'];
    $this->apiKey      = $client['api_key'];
    $this->apiVersion  = $client['api_version'];
  }

  public function getApiKey() {
    return $this->apiKey;
  }

  public function getApiVersion() {
    return $this->apiVersion;
  }

  /**
   * @return mixed
   */
  public function getEnvirnoment() {
    return $this->envirnoment;
  }

  /**
   * @param $env
   *
   * @return mixed
   */
  public function buildUrl($env) {
    $url = $env;
    $url = str_replace("{apiKey}", $this->apiKey, $url);
    $url = str_replace("{apiVersion}", $this->apiVersion, $url);

    return $url;
  }

}
