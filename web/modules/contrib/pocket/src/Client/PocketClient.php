<?php

namespace Drupal\pocket\Client;

use Drupal\Component\Serialization\Json;
use Drupal\pocket\Exception\PocketHttpException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;

class PocketClient {

  /**
   * The main service URL.
   */
  const URL = 'https://getpocket.com/';

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $http;

  /**
   * @var string
   */
  private $key;

  /**
   * PocketClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http
   * @param string                      $key
   */
  public function __construct(ClientInterface $http, string $key) {
    $this->http = $http;
    $this->key = $key;
  }

  /**
   * @param string $endpoint
   * @param array  $request
   *
   * @return array
   *
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function sendRequest(string $endpoint, array $request): array {
    $request['consumer_key'] = $this->key;
    return $this->sendJson(static::URL . $endpoint, $request);
  }

  /**
   * @param string $url
   * @param mixed  $body
   *
   * @return array
   *
   * @throws \Drupal\pocket\Exception\PocketHttpException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function sendJson(string $url, $body): array {
    try {
      $response = $this->http->request('POST', $url, [
        'json' => $body,
        'headers' => ['X-Accept' => 'application/json'],
      ]);
      try {
        $body = $response->getBody()->getContents();
      }
      catch (\RuntimeException $e) {
        watchdog_exception('pocket', $e);
        $body = '';
      }
      return Json::decode($body);
    }
    catch (BadResponseException $e) {
      throw new PocketHttpException($e);
    }
  }

}
