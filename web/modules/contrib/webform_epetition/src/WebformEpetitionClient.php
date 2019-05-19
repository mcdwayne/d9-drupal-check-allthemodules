<?php

namespace Drupal\webform_epetition;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

/**
 * Class WebformEpetitionClient.
 */
class WebformEpetitionClient implements WebformEpetitionClientInterface {

  protected $api_key;

  protected $api_url;

  protected $url_params;

  protected $httpClient;

  /**
   * WebformEpetitionClient constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger_dblog
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * @param $api_type
   * @param array $url_params
   *
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function sendRequest($api_type, array $url_params) {
    $url = $this->buildRequest($api_type, $url_params);
    $status = '';
    try {
      $request = $this->httpClient->request('GET', $url);
      $status = $request->getStatusCode();
      $transfer_success = $request->getBody()->getContents();
      return $transfer_success;
    }
    catch (RequestException $e) {
      $message = 'Webform E-petition api request failed with' . $e . '::' . $status;
      \Drupal::logger('webform_epetition')->error($message);
    }
    return false;

  }

  /**
   * @param $red_type
   * @param $url_params
   *
   * @return string
   */
  private function buildRequest($red_type, $url_params) {
    $config = \Drupal::config('webform_epetition.webformepetitionconfig');
    $this->api_key = $config->get('api_key');
    $this->api_url = $config->get('api_url');
    $url_params = array('key'=>$this->api_key) + $url_params;
    $url_params = $url_params + array('output' => 'js');
    $url = $this->api_url . '/api/' . $red_type;
    $url .= '?';
    foreach ($url_params as $key => $value) {
      $url .= $key . '=' . $value . '&';
    }
    return $url;
  }

}
